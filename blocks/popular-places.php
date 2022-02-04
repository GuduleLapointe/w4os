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
function popular_places_block_init() {
	// Skip block registration if Gutenberg is not enabled/merged.
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}
	$dir = dirname( __FILE__ );

	$index_js = 'popular-places/index.js';
	wp_register_script(
		'popular-places-block-editor',
		plugins_url( $index_js, __FILE__ ),
		[
			'wp-blocks',
			'wp-i18n',
			'wp-element',
			// 'wp-server-side-render',
		],
		filemtime( "{$dir}/{$index_js}" )
	);

	$editor_css = 'popular-places/editor.css';
	wp_register_style(
		'popular-places-block-editor',
		plugins_url( $editor_css, __FILE__ ),
		[],
		filemtime( "{$dir}/{$editor_css}" )
	);

	$style_css = 'popular-places/style.css';
	wp_register_style(
		'popular-places-block',
		plugins_url( $style_css, __FILE__ ),
		[],
		filemtime( "{$dir}/{$style_css}" )
	);

	register_block_type( 'w4os/popular-places', [
		'render_callback' => 'w4os_popular_places_block_render',
		'editor_script' => 'popular-places-block-editor',
		'editor_style'  => 'popular-places-block-editor',
		'style'         => 'popular-places-block',
	] );

	add_shortcode('popular-places', 'w4os_popular_places_shortcode');
}
add_action( 'init', 'popular_places_block_init' );

function w4os_popular_places_block_render($args=[], $dumb="", $block_object=[]) {
	// if(! W4OS_DB_CONNECTED) return; // not sure it's mandatory here
	$block = (array) $block_object;
	$block['before_title'] = '<h4>';
	$block['after_title'] = '</h4>';
	$atts = $block_object->block_type->attributes;

	$class = preg_replace(":/:", "-", $block_object->name);
	return sprintf(
		'<div class="w4os-block %s">%s</div>',
		$class,
		w4os_popular_places_html($atts, $block ),
	);
}

function w4os_popular_places_shortcode($atts = [], $content = null)
{
	// if(! W4OS_DB_CONNECTED) return; // not sure it's mandatory here
	empty($content) ? $content='' : $content="<div>$content</div>";
	$args=array(
		'before_title' => '<h4>',
		'after_title' => '</h4>',
	);
	$content .= w4os_popular_places_html($atts, $args);
	if(!empty($content)) return "<div class='w4os-shortcode w4os-popular-places'>$content</div>";
}

function w4os_popular_places_html($atts = [], $args = []) {
	$searchURL = get_option('w4os_search_url');
	if(empty($searchURL)) return "Search URL not set";

	extract( $args );
	isset($atts['title']) ? $title=$atts['title'] : $title=__("Popular places", 'w4os');
	$content = $before_title . $title . $after_title;
	$debug = '';
	// dir_popular_query
	//
	//
	$req['query_start'] = 0;
	$req['text'] = '';
	$req['flags'] = pow(2,12) + pow(2,11);
	// if ($flags & pow(2,12)) $terms[] = "has_picture = 1";
	// if ($flags & pow(2,11)) $terms[] = "mature = 0";     //PgSimsOnly (1 << 11)
	$req['gatekeeper_url'] = W4OS_GRID_LOGIN_URI;
	$req['sim_name'] = '';
	$request = xmlrpc_encode_request('dir_popular_query', $req );
	$debug .= "request " . '<pre>' . print_r($request, true) . '</pre>';
	// $response = do_call($host, $port, $request);

	$post_data = array('xml' => $request);
	$context = stream_context_create(array('http' => array(
		'method'  => 'POST',
		'header'  => 'Content-Type: text/xml' . "\r\n",
		'content' =>  $request
	)));
	$response = xmlrpc_decode(file_get_contents($searchURL, false, $context));
	if (is_array($response) &! xmlrpc_is_fault($response)) {
		$places = $response['data'];
		$max = get_option('w4os_popular_places_max', 5);
		$i=0;
		foreach($places as $place) {
			if($i++ >= $max) break;
			$content .= sprintf('<p><a href="secondlife://%s/%s">%s</a>',
				$place['regionname'],
				$place['landingpoint'],
				$place['name'],
			);
		}
		return $content;
	}
	return;
}
