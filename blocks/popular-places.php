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
		array(
			'wp-blocks',
			'wp-i18n',
			'wp-element',
			// 'wp-server-side-render',
		),
		filemtime( "{$dir}/{$index_js}" )
	);

	$editor_css = 'popular-places/editor.css';
	wp_register_style(
		'popular-places-block-editor',
		plugins_url( $editor_css, __FILE__ ),
		array(),
		filemtime( "{$dir}/{$editor_css}" )
	);

	$style_css = 'popular-places/style.css';
	wp_register_style(
		'popular-places-block',
		plugins_url( $style_css, __FILE__ ),
		array(),
		filemtime( "{$dir}/{$style_css}" )
	);

	register_block_type(
		'w4os/popular-places',
		array(
			'render_callback' => 'w4os_popular_places_block_render',
			'editor_script'   => 'popular-places-block-editor',
			'editor_style'    => 'popular-places-block-editor',
			'style'           => 'popular-places-block',
		)
	);

	add_shortcode( 'popular-places', 'w4os_popular_places_shortcode' );
}
add_action( 'init', 'popular_places_block_init' );

function w4os_popular_places_block_render( $args = array(), $dumb = '', $block_object = array() ) {
	// if(! W4OS_DB_CONNECTED) return; // not sure it's mandatory here
	$block                 = (array) $block_object;
	$block['before_title'] = '<h4>';
	$block['after_title']  = '</h4>';
	$atts                  = $block_object->block_type->attributes;

	$content = w4os_popular_places_html( $atts, $block );
	if ( empty( $content ) ) {
		return '';
	}
	$class = preg_replace( ':/:', '-', $block_object->name );
	return sprintf(
		'<div class="w4os-block %s">%s</div>',
		$class,
		$content,
	);
}

function w4os_popular_places_shortcode( $atts = array(), $content = null ) {
	// if(! W4OS_DB_CONNECTED) return; // not sure it's mandatory here
	empty( $content ) ? $content = '' : $content = "<div>$content</div>";
	$args                        = array(
		'before_title' => '<h4>',
		'after_title'  => '</h4>',
	);
	$result                      = w4os_popular_places_html( $atts, $args );
	if ( empty( $result ) ) {
		return '';
	}

	$content .= $result;
	return "<div class='w4os-shortcode w4os-popular-places'>$result</div>";
}

function w4os_popular_places( $atts = array() ) {
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

function w4os_popular_places_html( $atts = array(), $args = array() ) {
	extract( $args );
	isset( $atts['title'] ) ? $title = $atts['title'] : $title = __( 'Popular places', 'w4os' );
	$content                         = $before_title . $title . $after_title;

	$places = w4os_popular_places( $atts );
	if ( empty( $places ) ) {
		if ( isset( $_REQUEST['context'] ) && $_REQUEST['context'] == 'edit' ) {
			return $content . __( 'No result', 'w4os' );
		} else {
			return;
		}
	}

	$max      = ( isset( $atts['max'] ) ) ? $atts['max'] : get_option( 'w4os_popular_places_max', 5 );
	$i        = 0;
	$content .= '<div class=places>';
	foreach ( $places as $place ) {
		if ( w4os_empty( $place['imageUUID'] ) ) {
			continue;
		}
		if ( $i++ >= $max ) {
			break;
		}
		// if (!empty($place['imageUUID']) && $place['imageUUID']!=W4OS_NULL_KEY) {
		$image = sprintf(
			'<img src="%1$s" alt="%2$s">',
			w4os_get_asset_url( $place['imageUUID'] ),
			$place['name'],
		);
		// }
		$tplink   = preg_replace( '#.*://#', 'secondlife://', $place['gatekeeperURL'] . ':' . $place['regionname'] . '/' . $place['landingpoint'] . '/' );
		$content .= sprintf(
			'<div class=place><a href="%1$s"><h5>%2$s</h5>%3$s</a></div>',
			$tplink,
			$place['name'],
			$image,
		);
	}
	$content .= '</div>';
	return $content;
}
