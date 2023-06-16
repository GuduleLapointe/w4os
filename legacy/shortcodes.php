<?php if ( ! defined( 'W4OS_PLUGIN' ) ) {
	die;}
/**
 * Shortcodes
 *
 * @package w4os
 * @author Olivier van Helden <olivier@van-helden.net>
 */

/**
 * Initialize w4os shortcodes
 *
 * @return [type] [description]
 */
function w4os_shortcodes_init() {
	/**
	 * Grid info shortcode
	 *
	 * @param  array  $atts    [description]
	 * @param  string $content html
	 * @return string          html
	 */
	function w4os_gridinfo_shortcode( $atts = array(), $content = null ) {
		if ( ! W4OS_DB_CONNECTED ) {
			return;
		}
		empty( $content ) ? $content = '' : $content = "<div>$content</div>";
		$args                        = array(
			'before_title' => '<h4>',
			'after_title'  => '</h4>',
		);
		$content                    .= w4os_gridinfo_html( $atts, $args );
		if ( ! empty( $content ) ) {
			return "<div class='w4os-shortcode w4os-shortcode-gridinfo'>$content</div>";
		}
	}
	add_shortcode( 'gridinfo', 'w4os_gridinfo_shortcode' );

	/**
	 * Grid status shortcode
	 *
	 * @param  array  $atts    [description]
	 * @param  string $content html
	 * @return string          html
	 */
	function w4os_gridstatus_shortcode( $atts = array(), $content = null ) {
		if ( ! W4OS_DB_CONNECTED ) {
			return;
		}

		global $w4osdb;
		global $wp_locale;

		$args = array(
			'before_title' => '<h4>',
			'after_title'  => '</h4>',
		);

		$content .= w4os_gridstatus_html( $atts, $args );
		if ( ! empty( $content ) ) {
			return "<div class='w4os-shortcode w4os-shortcode-gridstatus'>$content</div>";
		}
	}
	add_shortcode( 'gridstatus', 'w4os_gridstatus_shortcode' );

	function w4os_newusers_shortcode( $atts = array(), $content = null ) {
		if ( ! current_user_can( 'list_users' ) ) {
			return '';
		}

		$content = w4os_newusers_html();
		if ( ! empty( $content ) ) {
			return "<div class='w4os-shortcode w4os-shortcode-newusers'>" . w4os_newusers_html() . '</div>';
		}
	}
	add_shortcode( 'w4os_newusers_shortcode', 'w4os_newusers' );
}
add_action( 'init', 'w4os_shortcodes_init' );

function w4os_newusers_html( $atts = array(), $args = array() ) {
	if ( ! W4OS_DB_CONNECTED ) {
		if ( $args['args']['error-messages'] ) {
			echo w4os_give_settings_url( __( 'Configure W4OS database ', 'w4os' ) );
		}
		return;
	};
	if ( ! current_user_can( 'list_users' ) ) {
		return;
	}
	global $wpdb;
	$recentusers = '<ul class="recent-users">';
	$usernames   = $wpdb->get_results(
		"SELECT user_nicename, user_url, user_email
		FROM $wpdb->users as u LEFT JOIN $wpdb->usermeta as m ON u.ID = m.user_id
		WHERE m.meta_key = 'w4os_uuid' AND m.meta_value != ''
		ORDER BY ID DESC LIMIT 5"
	);
	if ( get_option( 'w4os_profile_page' ) == 'provide' ) {
		$profile_action = '<span class=view><a href="%1$s">%7$s</a></span>';
	}

	foreach ( $usernames as $username ) {
		$user = $wpdb->get_row( $wpdb->prepare( 'select * from ' . $wpdb->prefix . 'users where user_email = %s', $username->user_email ) );
		$uuid = get_the_author_meta( 'w4os_uuid', $user->ID );
		if ( $uuid ) {

			$recentusers .= sprintf(
				'<li>
			<span class="profile-pic"><a href="%1$s">%2$s</a></span>
			<span class=info>
				<span class=avatar-name><a href=%1$s>%3$s</a></span>
				<span class=email><em>(%4$s)</em></span>
				<span class=row-actions>
					<span class=edit><a href="%5$s">%6$s</a></span>
					' . $profile_action . '
				</span>
			</span>
			</li>',
				w4os_web_profile_url( $user ),
				get_avatar( $username->user_email, 32 ),
				get_the_author_meta( 'w4os_avatarname', $user->ID ),
				$username->user_email,
				get_edit_user_link( $user->ID ),
				__( 'Edit user', 'w4os' ),
				__( 'View profile', 'w4os' ),
			);
		} elseif ( ! $username->user_url ) {
			$recentusers .= '<li>' . get_avatar( $username->user_email, 32 ) . '&nbsp;' . $username->user_nicename . '</a></li>';
		} else {
			$recentusers .= '<li>' . get_avatar( $username->user_email, 32 ) . '&nbsp;' . '<a href="' . $username->user_url . '">' . $username->user_nicename . '</a></li>';
		}
	}
	$recentusers .= '</ul>';
	return $recentusers;
}

function w4os_gridinfo_html( $atts = array(), $args = array() ) {
	if ( ( empty( get_option( 'w4os_login_uri' ) ) || empty( get_option( 'w4os_grid_name' ) ) ) && $args['args']['error-messages'] ) {
		echo w4os_give_settings_url( __( 'Configure W4OS: ', 'w4os' ) );
		return;
	}

	extract( $args );

	isset( $atts['title'] ) ? $title = $atts['title'] : $title = __( 'Grid info', 'w4os' );
	$before_title                    = ( isset( $before_title ) ) ? $before_title : '';
	$after_title                     = ( isset( $after_title ) ) ? $after_title : '';
	$content                         = $before_title . $title . $after_title;
	$info                            = array(
		__( 'Grid Name', 'w4os' ) => esc_attr( get_option( 'w4os_grid_name' ) ),
		__( 'Login URI', 'w4os' ) => w4os_hop( get_option( 'w4os_login_uri' ) ),
	);
	if ( ! empty( $info ) ) {
		$content .= w4os_array2table( $info, 'gridinfo' );
	} else {
		$content .= __( 'OpenSimulator not configured', 'w4os' );
	}
	return $content;
}

function w4os_gridstatus_html( $atts = array(), $args = array() ) {
	if ( ! W4OS_DB_CONNECTED ) {
		if ( $args['args']['error-messages'] ) {
			echo w4os_give_settings_url( __( 'Configure W4OS database: ', 'w4os' ) );
		}
		return;
	};

	global $w4osdb;
	global $wp_locale;
	extract( $args );
	$filter = '';

	isset( $atts['title'] ) ? $title = $atts['title'] : $title = __( 'Grid status', 'w4os' );
	$before_title                    = ( isset( $before_title ) ) ? $before_title : '';
	$after_title                     = ( isset( $after_title ) ) ? $after_title : '';
	$content                         = $before_title . $title . $after_title;

	$status = w4os_grid_status_text();
	$result = w4os_array2table( $status, 'gridstatus' );

	if ( empty( $result ) ) {
		$result = __( 'No result', 'w4os' );
	}
	return $content . $result;
}
