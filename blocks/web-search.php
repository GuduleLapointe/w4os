<?php
/**
 * Functions to register client-side assets (scripts and stylesheets) for the Gutenberg block.
 *
 * @package GuduleLapointe/w4os
 */

/**
 * Registers all block assets so that they can be enqueued through Gutenberg in the corresponding context.
 *
 * @see https://wordpress.org/gutenberg/handbook/designers-developers/developers/tutorials/block-tutorial/applying-styles-with-stylesheets/
 */
function web_search_block_init() {
	add_shortcode( 'web-search', 'w4os_web_search_shortcode' );

	// Skip block registration if Gutenberg is not enabled/merged.
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}
	$dir = dirname( __FILE__ );

	$index_js = 'web-search/web-search.js';
	wp_register_script(
		'web-search-block-editor',
		plugins_url( $index_js, __FILE__ ),
		array(
			'wp-blocks',
			'wp-i18n',
			'wp-element',
			'wp-components',
			'wp-server-side-render',
		),
		filemtime( "{$dir}/{$index_js}" )
	);

	$style_css = 'web-search/web-search.css';
	wp_register_style(
		'web-search-block',
		plugins_url( $style_css, __FILE__ ),
		array(),
		filemtime( "{$dir}/{$style_css}" )
	);

	register_block_type(
		'w4os/web-search',
		array(
			'editor_script'   => 'web-search-block-editor',
			'editor_style'    => 'web-search-block-editor',
			'style'           => 'web-search-block',
			'attributes'      => array(
				'title'             => array(
					'type' => 'string',
				),
				'level'             => array(
					'type' => 'string',
				),
				'max'               => array(
					'type' => 'number',
				),
				'include_hypergrid' => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'include_landsales' => array(
					'type'    => 'boolean',
					'default' => false,
				),
			),
			'render_callback' => 'w4os_web_search_block_render',
		)
	);
}
add_action( 'init', 'web_search_block_init' );

function w4os_web_search_block_render( $attributes, $void, $block = true ) {
	$atts = wp_parse_args(
		$attributes,
		array(
			'title'             => null,
			'level'             => null,
			'max'               => null,
			'include_hypergrid' => false,
			'include_landsales' => false,
		)
	);

	$content = w4os_web_search_html( $atts );
	if ( empty( $content ) ) {
		return '';
	}

	$class = preg_replace( ':/:', '-', $block->name );

	return W4OS::sprintf_safe(
		'<div class="w4os-block wp-block wp-block-spacing %s">%s</div>',
		$class,
		$content
	);
}

function w4os_web_search_shortcode( $atts = array(), $content = null ) {
	empty( $content ) ? $content = '' : $content = "<div>$content</div>";
	$atts                        = wp_parse_args(
		$atts,
		array(
			'title'             => __( 'Web Search', 'w4os' ),
			'include_hypergrid' => in_array( 'include-hypergrid', $atts ) ? true : ( isset( $atts['include-hypergrid'] ) ? $atts['include-hypergrid'] : false ),
			'include_landsales' => in_array( 'include-landsales', $atts ) ? true : ( isset( $atts['include-landsales'] ) ? $atts['include-landsales'] : false ),
		)
	);

	$args   = array();
	$result = w4os_web_search_html( $atts, $args );
	if ( empty( $result ) ) {
		return '';
	}

	$content .= $result;
	return "<div class='w4os-shortcode w4os-web-search'>$result</div>";
}

function w4os_web_search( $atts = array() ) {
	if ( ! function_exists( 'xmlrpc_encode_request' ) ) {
		return array();
	}
	$searchURL = get_option( 'w4os_search_url' );
	if ( empty( $searchURL ) ) {
		return array();
	}

	$req['query_start'] = 0;
	$req['text']        = empty( $atts['text'] ) ? '' : $atts['text'];
	$req['flags']       = pow( 2, 12 );  // has_picture

	if ( isset( $atts['rating'] ) ) {
		if ( $atts['rating'] == 'pg' ) {
			$req['flags'] += pow( 2, 24 ); // PG Only
		} elseif ( $atts['rating'] != 'adult' ) {
			$req['flags'] += pow( 2, 24 ) + pow( 2, 25 );
		}
		// 24 PG; 25 Mature; 26 Adult; default PG & Mature
	}

	$req['gatekeeper_url'] = W4OS_GRID_LOGIN_URI;
	$req['sim_name']       = '';
	// $req = array_merge($atts, $req);
	$req['include_hypergrid'] = ! empty( $atts['include_hypergrid'] ) ? empty( $atts['include_hypergrid'] ) : 'false';
	$req['include_landsales'] = ! empty( $atts['include_landsales'] ) ? 'true' : 'false';
	$req['websearch']         = true;

	$request = xmlrpc_encode_request( 'dir_places_query', $req );

	$post_data = array( 'xml' => $request );
	$context   = stream_context_create(
		array(
			'http' => array(
				'method'  => 'POST',
				'header'  => 'Content-Type: text/xml' . "\r\n",
				'content' => $request,
			),
		)
	);
	$response  = xmlrpc_decode( file_get_contents( $searchURL, false, $context ) );
	if ( is_array( $response ) && ! xmlrpc_is_fault( $response ) && ! empty( $response ) && isset( $response['data'] ) ) {
		return $response['data'];
	} else {
		return array();
	}
}

function w4os_web_search_html( $atts = array(), $args = array() ) {
	$atts         = wp_parse_args(
		array_filter( $atts ),
		array(
			'title'             => null,
			'level'             => 'h3',
			'max'               => null,
			'include_hypergrid' => false,
			'include_landsales' => false,
		)
	);
	$level        = $atts['level'];
	$title        = $atts['title'];
	$max          = empty( $atts['max'] ) ? get_option( 'w4os_web_search_max', 5 ) : $atts['max'];
	$before_title = empty( $level ) ? '' : "<{$level}>";
	$after_title  = empty( $level ) ? '' : "</{$level}>";

	$content = ( empty( $title ) ) ? '' : $before_title . $title . $after_title;

	$search_string       = isset( $_REQUEST['searchString'] ) ? sanitize_text_field( stripslashes( $_REQUEST['searchString'] ) ) : '';
	$search_string_field = '<input type="text" name="searchString" placeholder="Search..." value="' . esc_attr( $search_string ) . '"/>';

	$form_html = '<form action="" method="get">'
	. $search_string_field
	. '<input type="submit" value="Search">'
	. '</form>';

	$content .= $form_html;

	if ( empty( $search_string ) ) {
		$content .= '<p>' . __( 'Type a search key in the field', 'w4os' ) . '</p>';
	} else {
		$atts['text'] = $search_string;

		$places = w4os_web_search( $atts );

		if ( empty( $places ) ) {
			$content .= __( 'No result', 'w4os' );
		} else {
			error_log( 'places ' . print_r( $places, true ) );
			$i        = 0;
			$content .= '<div class="places">';

			foreach ( $places as $place ) {
				$place = array_merge(
					array(
						'imageUUID'     => W4OS_NULL_KEY_IMG,
						'regionname'    => '',
						'landingpoint'  => '',
						'gatekeeperURL' => '',
					),
					$place
				);

				if ( $i++ >= $max ) {
					break;
				}

				$image = W4OS::sprintf_safe(
					'<img class="place-image" src="%1$s" alt="%2$s">',
					w4os_get_asset_url( $place['imageUUID'] ),
					$place['name'],
				);

				$tplink = opensim_format_tp( $place['gatekeeperURL'] . '/' . $place['regionname'] . '/' . $place['landingpoint'], TPLINK_HG );

				$content .= W4OS::sprintf_safe(
					'<div class="place"><a href="%1$s"><div class=place-name>%2$s</div>%3$s</a></div>',
					$tplink,
					$place['name'],
					$image,
				);
			}
		}
		$content .= '</div>';
	}

	return $content;
}

add_action( 'et_builder_ready', 'et_builder_module_w4os_web_search_init' );

function et_builder_module_w4os_web_search_init() {
	// Check if Divi Builder is active
	if ( class_exists( 'ET_Builder_Module' ) ) {
		class ET_Builder_Module_W4OS_web_search extends ET_Builder_Module {
			// ...
			function init() {
				$this->name = __( 'OpenSimulator Web Search', 'w4os' );
				$this->slug = 'et_pb_w4os_web_search';

				$this->whitelisted_fields = array(
					'title',
					'level',
					'max',
					'include_hypergrid',
					'include_landsales',
				);

				$this->fields_defaults = array(
					'title'             => '',
					'max'               => 5,
					'include_hypergrid' => 'off',
					'include_landsales' => 'off',
				);

				$this->main_css_element = '%%order_class%%';
			}

			function get_fields() {
				$fields = parent::get_fields();

				$fields['title'] = array(
					'label'       => __( 'Title', 'w4os' ),
					'type'        => 'text',
					'description' => __( 'Enter the title for the Web Search module.', 'w4os' ),
					'toggle_slug' => 'main_content',
					'default'     => __( 'Web Search', 'w4os' ),
				);

				$fields['level'] = array(
					'label'       => __( 'Title Level', 'w4os' ),
					'type'        => 'select',
					'description' => __( 'Select the HTML heading level for the title.', 'w4os' ),
					'toggle_slug' => 'main_content',
					'options'     => array(
						'h1' => 'H1',
						'h2' => 'H2',
						'h3' => 'H3',
						'h4' => 'H4',
						'h5' => 'H5',
						'h6' => 'H6',
						'p'  => 'P',
					),
					'default'     => 'h3',
				);

				$fields['max'] = array(
					'label'       => __( 'Max Results', 'w4os' ),
					'type'        => 'text',
					'description' => __( 'Enter the maximum number of results to display.', 'w4os' ),
					'toggle_slug' => 'main_content',
				);

				$fields['include_hypergrid'] = array(
					'label'       => __( 'Include Hypergrid', 'w4os' ),
					'type'        => 'yes_no_button',
					'description' => __( 'Enable to restrict results to the grid.', 'w4os' ),
					'options'     => array(
						'off' => __( 'No', 'w4os' ),
						'on'  => __( 'Yes', 'w4os' ),
					),
					'toggle_slug' => 'main_content',
					'default'     => 'off',
				);

				$fields['include_landsales'] = array(
					'label'       => __( 'Include Land for Sale', 'w4os' ),
					'type'        => 'yes_no_button',
					'description' => __( 'Enable to include land for sale in results.', 'w4os' ),
					'options'     => array(
						'off' => __( 'No', 'w4os' ),
						'on'  => __( 'Yes', 'w4os' ),
					),
					'toggle_slug' => 'main_content',
					'default'     => 'off',
				);

				return $fields;
			}

			function shortcode_callback( $atts, $content = null, $function_name = null ) {
				$atts = wp_parse_args(
					$atts,
					array(
						'title'             => '',
						'level'             => '',
						'max'               => 5,
						'include_hypergrid' => false,
						'include_landsales' => false,
					)
				);

				$output = w4os_web_search_html( $atts );

				return W4OS::sprintf_safe(
					'<div class="et_pb_module et_pb_w4os_web_search w4os-web-search">%s</div>',
					$output
				);
			}
		}

		new ET_Builder_Module_W4OS_web_search();
	}
}
