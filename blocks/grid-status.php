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
 **/

add_action( 'init', 'grid_status_block_init' );
function grid_status_block_init() {

	add_shortcode( 'grid-status', 'w4os_grid_status_shortcode' );
	add_shortcode( 'gridstatus', 'w4os_grid_status_shortcode' ); // Backwards compatibility

	// Skip block registration if Gutenberg is not enabled/merged.
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}
	$dir = dirname( __FILE__ );

	$index_js = 'grid-status/grid-status.js';
	wp_register_script(
		'grid-status-block-editor',
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

	// $editor_css = 'grid-status/editor.css';
	// wp_register_style(
	// 'grid-status-block-editor',
	// plugins_url( $editor_css, __FILE__ ),
	// array(),
	// filemtime( "{$dir}/{$editor_css}" )
	// );

	$style_css = 'grid-status/grid-status.css';
	wp_register_style(
		'grid-status-block',
		plugins_url( $style_css, __FILE__ ),
		array(),
		filemtime( "{$dir}/{$style_css}" )
	);

	register_block_type(
		'w4os/grid-status',
		array(
			'editor_script'   => 'grid-status-block-editor',
			'editor_style'    => 'grid-status-block-editor',
			'style'           => 'grid-status-block',
			'icon'            => 'status',
			'attributes'      => array(
				'title' => array(
					'type' => 'string',
					// 'default' => '',
				),
				'level' => array(
					'type' => 'string',
					// 'default' => '',
				),
			),
			'render_callback' => 'w4os_grid_status_block_render',
		)
	);
}


function w4os_grid_status_block_render( $attributes, $void, $block = true ) {
	$atts = wp_parse_args(
		$attributes,
		array(
			'title' => null,
			'level' => null,
		)
	);

	$content = w4os_grid_status_html( $atts );
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

function w4os_grid_status_shortcode( $atts = array(), $content = null ) {
	// if(! W4OS_DB_CONNECTED) return; // not sure it's mandatory here
	empty( $content ) ? $content = '' : $content = "<div>$content</div>";
	$atts                        = wp_parse_args(
		$atts,
		array(
			'title' => __( 'Grid Status', 'w4os' ),
		)
	);
	$args                        = array();

	$result = w4os_grid_status_html( $atts, $args );
	if ( empty( $result ) ) {
		return '';
	}

	$content .= $result;
	return "<div class='w4os-shortcode w4os-grid-status'>$result</div>";
}

function w4os_grid_status_html( $atts = array(), $args = array() ) {
	$atts         = wp_parse_args(
		array_filter( $atts ),
		array(
			'title' => null,
			'level' => 'h3',
		)
	);
	$level        = $atts['level'];
	$title        = $atts['title'];
	$before_title = empty( $level ) ? '' : "<{$level}>";
	$after_title  = empty( $level ) ? '' : "</{$level}>";

	$content = ( empty( $title ) ) ? '' : $before_title . $title . $after_title;

	$status = w4os_grid_status_text();

	if ( ! empty( $status ) ) {
		$content .= w4os_array2table( $status, 'gridstatus' );
	} else {
		$content .= __( 'No result', 'w4os' );
	}

	return $content;
}

add_action( 'et_builder_ready', 'et_builder_module_w4os_grid_status_init' );
function et_builder_module_w4os_grid_status_init() {
	// Check if Divi Builder is active
	if ( class_exists( 'ET_Builder_Module' ) ) {
		class ET_Builder_Module_W4OS_grid_status extends ET_Builder_Module {
			// ...
			function init() {
				$this->name = __( 'OpenSimulator Grid Status', 'w4os' );
				$this->slug = 'et_pb_w4os_grid_status';

				$this->whitelisted_fields = array(
					'title',
					'level',
				);

				$this->fields_defaults = array(
					'title' => '',
					// 'level' => 'h3',
				);

				$this->main_css_element = '%%order_class%%';
			}

			function get_fields() {
				$fields = parent::get_fields();

				$fields['title'] = array(
					'label'       => __( 'Title', 'w4os' ),
					'type'        => 'text',
					'description' => __( 'Enter the title for the Grid Status module.', 'w4os' ),
					'toggle_slug' => 'main_content',
					'default'     => __( 'Grid Status', 'w4os' ),
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

				return $fields;
			}

			function shortcode_callback( $atts, $content = null, $function_name = null ) {
				$atts = wp_parse_args(
					$atts,
					array(
						'title' => '',
						'level' => '',
					)
				);

				$output = w4os_grid_status_html( $atts );

				return W4OS::sprintf_safe(
					'<div class="et_pb_module et_pb_w4os_grid_status w4os-grid-status">%s</div>',
					$output
				);
			}
		}

		new ET_Builder_Module_W4OS_grid_status();
	}
}
