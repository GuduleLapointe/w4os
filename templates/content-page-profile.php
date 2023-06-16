<?php

global $avatar;

$query_firstname = get_query_var( 'profile_firstname' );
$query_lastname  = get_query_var( 'profile_lastname' );

$page_content = '';

if ( empty( $query_firstname ) || empty( $query_lastname ) ) {
	if ( is_user_logged_in() ) {
		// User logged in, get profile if exists, avatar form otherwise
		$user           = wp_get_current_user();
		$avatar         = new W4OS_Avatar( $user );
		$page_title     = __( 'My Avatar', 'w4os' );
		$avatar_profile = $avatar->profile_page();
		if ( empty( $avatar_profile ) ) {
			$avatar_profile = '<div>' . w4os_avatar_creation_form( $user ) . '</div>';
		}
		$page_content .= $avatar_profile;
		if ( get_option( 'w4os_configuration_instructions' ) && get_the_author_meta( 'w4os_lastseen', $user->ID ) == 0 ) {
			include 'content-configuration.php';
		}
	} else {
		// User not logged in, show login form
		$page_title = __( 'Log in', 'w4os' );
		// $page_content .= '<pre>GET ' . print_r($_GET, true) . '</pre>';
		$page_content .= w4os_login_form();
	}
	// echo $page_content; die();

} else {
	// display request for a given user
	$user = w4os_get_avatar_by_name( $query_firstname, $query_lastname );
	// if(! $user || empty($user)) return get_404_template();
	$avatar         = new W4OS_Avatar( $user );
	$avatar_profile = $avatar->profile_page();

	// if(! $avatar_profile) {
	// header("Status: 404 Not Found");
	// $wp_query->set_404();
	// status_header( 404 );
	// add_action( 'wp_title', function () {
	// return $page_title;
	// }, 9999 );
	// get_template_part( 404 );
	// exit();
	// }

	if ( $avatar_profile ) {
		$avatar_name   = esc_attr( get_the_author_meta( 'w4os_firstname', $avatar->ID ) . ' ' . get_the_author_meta( 'w4os_lastname', $avatar->ID ) );
		$page_content .= $avatar_profile;
		$page_title    = $avatar_name;
		$head_title    = sprintf( __( "%s's profile", 'w4os' ), $avatar_name );
	} else {
		$page_content = 'no profile';
	}
}

// if(isset($page_actions)) {
// $page_content .= '<div class=login-actions>' . join(' - ', $page_actions) . '</div>';
// }
$page_content = wp_cache_get( 'w4os_notices' ) . $page_content;
wp_cache_delete( 'w4os_notices' );

echo $page_content;
