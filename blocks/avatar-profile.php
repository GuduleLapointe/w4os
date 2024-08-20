<?php
/**
 * Functions to register client-side assets (scripts and stylesheets) for the Gutenberg block.
 *
 * @package GuduleLapointe/w4os
 */
function w4os_avatar_profile_attributes() {
	return array(
		'title' => array(
			'type' => 'string',
			// 'default' => '',
		),
		'level' => array(
			'type'    => 'string',
			'default' => 'h3',
		),
		'mini'  => array(
			'type'    => 'boolean',
			'default' => false,
		),
	);
}

function w4os_avatar_profile_defaults() {
	$defaults   = array();
	$attributes = w4os_avatar_profile_attributes();
	foreach ( $attributes as $key => $options ) {
		$defaults[ $key ] = isset( $options['default'] ) ? $options['default'] : null;
	}
	return $defaults;
}

/**
 * Registers all block assets so that they can be enqueued through Gutenberg in the corresponding context.
 *
 * @see https://wordpress.org/gutenberg/handbook/designers-developers/developers/tutorials/block-tutorial/applying-styles-with-stylesheets/
 */
function avatar_profile_block_init() {

	add_shortcode( 'avatar-profile', 'w4os_avatar_profile_shortcode' );
	add_shortcode( 'w4os_profile', 'w4os_avatar_profile_shortcode' ); // Backwards compatibility
	add_shortcode( 'gridprofile', 'w4os_avatar_profile_shortcode' ); // Backwards compatibility
	add_shortcode( 'avatar-profile', 'w4os_avatar_profile_shortcode' );

	// Skip block registration if Gutenberg is not enabled/merged.
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}
	$dir = dirname( __FILE__ );

	$index_js = 'avatar-profile/avatar-profile.js';
	wp_register_script(
		'avatar-profile-block-editor',
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

	// $editor_css = 'avatar-profile/editor.css';
	// wp_register_style(
	// 'avatar-profile-block-editor',
	// plugins_url( $editor_css, __FILE__ ),
	// array(),
	// filemtime( "{$dir}/{$editor_css}" )
	// );

	$style_css = 'avatar-profile/avatar-profile.css';
	wp_register_style(
		'avatar-profile-block',
		plugins_url( $style_css, __FILE__ ),
		array(),
		filemtime( "{$dir}/{$style_css}" )
	);

	register_block_type(
		'w4os/avatar-profile',
		array(
			'editor_script'   => 'avatar-profile-block-editor',
			'editor_style'    => 'avatar-profile-block-editor',
			'style'           => 'avatar-profile-block',
			'icon'            => 'users',
			'attributes'      => w4os_avatar_profile_attributes(),
			'render_callback' => 'w4os_avatar_profile_block_render',
		)
	);
}
add_action( 'init', 'avatar_profile_block_init' );

function w4os_avatar_profile_block_render( $attributes, $void, $block = true ) {
	$atts = wp_parse_args(
		$attributes,
		w4os_avatar_profile_defaults(),
	);

	$content = w4os_avatar_profile_html( $atts );
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

function w4os_avatar_profile_shortcode( $atts = array(), $content = null ) {
	// if(! W4OS_DB_CONNECTED) return; // not sure it's mandatory here
	empty( $content ) ? $content = '' : $content = "<div>$content</div>";
	$atts                        = wp_parse_args(
		$atts,
		w4os_avatar_profile_defaults(),
	);
	$args                        = array();

	$result = w4os_avatar_profile_html( $atts, $args );
	if ( empty( $result ) ) {
		return '';
	}

	$content .= $result;
	return "<div class='w4os-shortcode w4os-avatar-profile'>$result</div>";
}

function w4os_avatar_profile( $atts = array() ) {
	if ( ! function_exists( 'xmlrpc_encode_request' ) ) {
		return array();
	}
	$searchURL = get_option( 'w4os_search_url' );
	if ( empty( $searchURL ) ) {
		return array();
	}

	$req['query_start'] = 0;
	$req['text']        = '';
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
	$request               = xmlrpc_encode_request( 'dir_popular_query', $req );

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

	if ( is_array( $response ) & ! xmlrpc_is_fault( $response ) & ! empty( $response ) && isset( $response['data'] ) ) {
		return $response['data'];
	} else {
		return array();
	}
}

function w4os_avatar_profile_html( $atts = array(), $args = array() ) {
	$atts         = wp_parse_args(
		array_filter( $atts ),
		w4os_avatar_profile_defaults(),
	);
	$level        = $atts['level'];
	$title        = $atts['title'];
	$before_title = empty( $level ) ? '' : "<{$level}>";
	$after_title  = empty( $level ) ? '' : "</{$level}>";

	$content = ( empty( $title ) ) ? '' : $before_title . $title . $after_title;

	$args['mini'] = $atts['mini'];
	$content     .= w4os_profile_display( wp_get_current_user(), $args );

	return $content;
}

add_action( 'et_builder_ready', 'et_builder_module_w4os_avatar_profile_init' );

function et_builder_module_w4os_avatar_profile_init() {
	// Check if Divi Builder is active
	if ( class_exists( 'ET_Builder_Module' ) ) {
		class ET_Builder_Module_W4OS_avatar_profile extends ET_Builder_Module {
			// ...
			function init() {
				$this->name = __( 'OpenSimulator Avatar Profile', 'w4os' );
				$this->slug = 'et_pb_w4os_avatar_profile';

				$this->whitelisted_fields = array_keys( w4os_avatar_profile_defaults() );

				$this->fields_defaults = w4os_avatar_profile_defaults();

				$this->main_css_element = '%%order_class%%';
			}

			function get_fields() {
				$fields = parent::get_fields();

				$fields['title'] = array(
					'label'       => __( 'Title', 'w4os' ),
					'type'        => 'text',
					'description' => __( 'Enter the title for the Avatar Profile module.', 'w4os' ),
					'toggle_slug' => 'main_content',
					'default'     => __( 'Avatar Profile', 'w4os' ),
					// 'show_if'         => array(
					// 'mini' => 'off',
					// ),
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
					// 'show_if'         => array(
					// 'mini' => 'off',
					// ),
				);

				$fields['mini'] = array(
					'label'            => __( 'Mini Profile', 'w4os' ),
					'type'             => 'yes_no_button',
					'option_category'  => 'configuration',
					'options'          => array(
						'on'  => __( 'Yes', 'w4os' ),
						'off' => __( 'No', 'w4os' ),
					),
					'toggle_slug'      => 'main_content',
					'description'      => __( 'Enable mini profile display.', 'w4os' ),
					'default_on_front' => 'off',
				);

				return $fields;
			}

			function shortcode_callback( $atts, $content = null, $function_name = null ) {
				$atts = wp_parse_args(
					$atts,
					w4os_avatar_profile_defaults(),
				);

				$output = w4os_avatar_profile_html( $atts );

				return W4OS::sprintf_safe(
					'<div class="et_pb_module et_pb_w4os_avatar_profile w4os-avatar-profile">%s</div>',
					$output
				);
			}
		}

		new ET_Builder_Module_W4OS_avatar_profile();
	}
}
