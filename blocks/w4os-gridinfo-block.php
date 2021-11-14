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
function w4os_gridinfo_block_block_init() {
	// Skip block registration if Gutenberg is not enabled/merged.
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}
	$dir = dirname( __FILE__ );

	$index_js = 'w4os-gridinfo-block/index.js';
	wp_register_script(
		'w4os-gridinfo-block-block-editor',
		plugins_url( $index_js, __FILE__ ),
		[
			'wp-blocks',
			'wp-i18n',
			'wp-element',
		],
		filemtime( "{$dir}/{$index_js}" )
	);

	$editor_css = 'w4os-gridinfo-block/editor.css';
	wp_register_style(
		'w4os-gridinfo-block-block-editor',
		plugins_url( $editor_css, __FILE__ ),
		[],
		filemtime( "{$dir}/{$editor_css}" )
	);

	$style_css = 'w4os-gridinfo-block/style.css';
	wp_register_style(
		'w4os-gridinfo-block-block',
		plugins_url( $style_css, __FILE__ ),
		[],
		filemtime( "{$dir}/{$style_css}" )
	);

	register_block_type( 'w4os/w4os-gridinfo-block', [
    // 'render_callback' => 'w4os_gridinfo_block_render',
		'editor_script' => 'w4os-gridinfo-block-block-editor',
		'editor_style'  => 'w4os-gridinfo-block-block-editor',
		'style'         => 'w4os-gridinfo-block-block',
	] );
}

add_action( 'init', 'w4os_gridinfo_block_block_init' );

function w4os_gridinfo_block_render($args=[], $dumb="", $block_object=[]) {
	return sprintf(
		'<div>%s</div>',
		w4os_gridinfo_html($atts, $block_object )
	);
}
