<?php
/**
 * Functions to register client-side assets (scripts and stylesheets) for the
 * Gutenberg block.
 *
 * @package w4os
 */

/**
 * Registers all block assets so that they can be enqueued through Gutenberg in
 * the corresponding context.
 *
 * @see https://wordpress.org/gutenberg/handbook/designers-developers/developers/tutorials/block-tutorial/applying-styles-with-stylesheets/
 */

function w4os_block_init( $slug, $title ) {
	// Skip block registration if Gutenberg is not enabled/merged.
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}
	$dir = dirname( __FILE__ );

	$index_js = "w4os-$slug-block/index.js";
	wp_register_script(
		"w4os-$slug-block-editor",
		plugins_url( $index_js, __FILE__ ),
		array(
			'wp-blocks',
			'wp-i18n',
			'wp-element',
		),
		filemtime( "{$dir}/{$index_js}" )
	);

	$editor_css = "w4os-$slug-block/editor.css";
	wp_register_style(
		"w4os-$slug-block-editor",
		plugins_url( $editor_css, __FILE__ ),
		array(),
		filemtime( "{$dir}/{$editor_css}" )
	);

	$style_css = "w4os-$slug-block/style.css";
	wp_register_style(
		"w4os-$slug-block",
		plugins_url( $style_css, __FILE__ ),
		array(),
		filemtime( "{$dir}/{$style_css}" )
	);

	register_block_type(
		"w4os/w4os-$slug-block",
		array(
			'render_callback' => "w4os_${slug}_block_render",
			'editor_script'   => "w4os-$slug-block-editor",
			'editor_style'    => "w4os-$slug-block-editor",
			'style'           => "w4os-$slug-block",
		)
	);
}

add_action( 'init', 'w4os_gridinfo_block_init' );
function w4os_gridinfo_block_init() {
	w4os_block_init( 'gridinfo', 'Grid info' );
}
function w4os_gridinfo_block_render( $args = array(), $dumb = '', $block_object = array() ) {
	$args                 = (array) $block_object;
	$args['before_title'] = '<h4>';
	$args['after_title']  = '</h4>';

	return sprintf(
		'<div>%s</div>',
		w4os_gridinfo_html( null, $args )
	);
}

add_action( 'init', 'w4os_gridstatus_block_init' );
function w4os_gridstatus_block_init() {
	w4os_block_init( 'gridstatus', 'Grid status' );
}
function w4os_gridstatus_block_render( $args = array(), $dumb = '', $block_object = array() ) {
	$args                 = (array) $block_object;
	$args['before_title'] = '<h4>';
	$args['after_title']  = '</h4>';

	return sprintf(
		'<div>%s</div>',
		w4os_gridstatus_html( null, $args )
	);
}

require_once __DIR__ . '/popular-places.php';
