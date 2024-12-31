<?php if ( ! defined( 'WPINC' ) ) {
	die;}

function w4os_get_page_slug( $page_slug ) {

	switch ( $page_slug ) {
		case get_option( 'w4os_profile_slug', 'profile' ):
			$page_slug = 'profile';
			break;
	}

	return $page_slug;
}

add_action( 'template_include', 'w4os_template_include' );
function w4os_template_include( $template ) {
	global $wp_query;
	$template_slug  = str_replace( '.php', '', basename( $template ) );
	$localized_post_id = W4OS::get_localized_post_id();
	if ( $template_slug == 'template-canvas' && $wp_query->query['pagename'] == 'profile' ) {
		if ( empty( $localized_post_id ) ) {
			$localized_post_id = get_page_by_path( 'profile' )->ID;
			$custom = W4OS_DIR . '/templates/page-profile-viewer.php';
		} else {
			return $template;
		}
	} else {
		if ( empty( $localized_post_id ) ) {
			// error_log( 'template slug ' . $template_slug );
			$query_page = $wp_query->query['pagename'];
			$page = get_page_by_path( $query_page );
			$localized_post_id = $page->ID;
			// return $template; // Although there's no reason this happens
		}
		$original = get_post( $localized_post_id );
	
		$post_name = w4os_get_page_slug( $original->post_name );
		// error_log( 'post_name ' . $post_name );
		// error_log("original $localized_post_id post_name $post_name");
		$post_type_slug = get_post_type();
		$custom         = W4OS_DIR . "/templates/$template_slug-$post_name.php";
	}

	if ( file_exists( $custom ) ) {
		return $custom;
	}
	return $template;
}

add_filter( 'the_content', 'w4os_the_content' );
function w4os_the_content( $content ) {
	if ( isset( $_GET['et_fb'] ) && $_GET['et_fb'] ) {
		// We don't want to mess up with Divi Builder
		return $content;
	}
	global $wp_query;
	global $template;
	$localized_post_id = W4OS::get_localized_post_id();
	$original          = get_post( $localized_post_id );
	if ( ! $original ) {
		return $content;
	}

	// if(empty($localized_post_id)) return $content; // Although there's no reason this happens

	if ( function_exists( 'wc_print_notices' ) ) {
		wc_print_notices();
	}
	$post_type_slug = $original->post_type;
	$post_name      = w4os_get_page_slug( $original->post_name );
	$template_slug  = str_replace( '.php', '', basename( $template ) );
	$custom_slug    = "content-$post_type_slug-$post_name";

	$custom = W4OS_DIR . "/templates/$custom_slug.php";

	if ( file_exists( $custom ) ) {
		ob_start();
		include $custom;
		$custom_content = ob_get_clean();
		$content        = "<br><br><br><div class='" . W4OS_SLUG . " content $template_slug $post_type_slug'>$custom_content</div>";
	}
	$content = wp_cache_get( 'w4os_notices' ) . $content;
	wp_cache_delete( 'w4os_notices' );
	return $content;
}

// ### Interesting 1
// add_filter('the_title', 'w4os_add_after_title', 10, 2);
// function w4os_add_after_title($title, $post_ID) {
// if ( is_single() && is_main_query() && ! is_admin() && ! is_null( $post_ID ) ) {
// if(w4os_backtrace_match('breadcrumb')) return $title;
//
// $post = get_post( $post_ID );
// $title_after = '';
// if ( $post instanceof WP_Post ) {
// switch($post->post_type) {
// case 'bands':
// $title_after .= (w4os_get_option('layout_page_title:genre')) ? w4os_get_meta([ 'tax_genres' ], $post_ID) : '';
// $title_after .= (w4os_get_option('layout_page_title:band_members')) ? w4os_get_meta([ 'members' ], $post_ID) : '';
// if(w4os_get_option('layout_page_title:official_website')) {
// $url = rwmb_meta( 'official_website', array(), $post_ID );
// if($url) {
// $links[] = W4OS::sprintf_safe("<li class=link><a href='%s'>%s</a></li>", $url, __('Official Website', 'w4os'));
// }
// }
// if(w4os_get_option('layout_page_title:official_store')) {
// $url = rwmb_meta( 'official_store', array(), $post_ID );
// if($url) {
// $links[] = W4OS::sprintf_safe("<li class=link><a href='%s'>%s</a></li>", $url, __('Official Store', 'w4os'));
// }
// }
// if(!empty($links)) $title_after .= "<ul class=links>" . join(' ', $links) . "</ul>";
// break;
//
// case 'records':
// case 'songs':
// if(w4os_get_option('layout_page_title:band')) {
// if($band_ID = rwmb_meta( 'band', array(), $post_ID )) {
// $band = get_post($band_ID);
// echo "<pre>"; print_r($band); die;
// $title_after .= W4OS::sprintf_safe(__('by <a href="%s">%s</a>', 'w4os'), get_permalink($band), $band->post_title);
// }
// }
//
// $title_after .= (w4os_get_option('layout_page_title:release_type')) ? w4os_get_meta([ 'release_type' ], $post_ID) : '';
// $title_after .= (w4os_get_option('layout_page_title:release')) ? w4os_get_meta([ 'release' ], $post_ID, [ 'before' => '&#x2117;' ]) : '';
// $title_after .= (w4os_get_option('layout_page_title:authors')) ? w4os_get_meta([ 'authors' ], $post_ID, [ 'before' => '&#169;' ] ) : '';
// $title_after .= (w4os_get_option('layout_page_title:genre')) ? w4os_get_meta([ 'tax_genres' ], $post_ID) : '';
// break;
// }
// }
// if ( is_single() )
// if (is_singular(array('bands')))
// $title= $title . "</h1>-after<h1>";
// }
// $title_before = (!empty($title_before)) ? "</h1><div class='surtitle'>$title_before</div>" : '';
// $title_after = (!empty($title_after)) ? "</h1><div class='subtitle'>$title_after</div>" : '';
// return $title_before . $title . $title_after;
// }
