<?php if ( ! defined( 'W4OS_PLUGIN' ) ) {
	die;}

define( 'W4OS_PROFILE_PATTERN', '^' . esc_attr( get_option( 'w4os_profile_slug', 'profile' ) ) . '/([a-zA-Z][a-zA-Z9]*)[ \.+-]([a-zA-Z][a-zA-Z9]*)(/.*)?$' );
define( 'W4OS_PROFILE_SELF_PATTERN', '^' . esc_attr( get_option( 'w4os_profile_slug', 'profile' ) ) . '/?$' );

add_action(
	'init',
	function() {
		add_rewrite_rule(
			W4OS_PROFILE_PATTERN,
			'index.php?pagename=' . esc_attr( get_option( 'w4os_profile_slug', 'profile' ) ) . '&post_tyoe=user&profile_firstname=$matches[1]&profile_lastname=$matches[2]&profile_args=$matches[3]',
			'top'
		);
		add_rewrite_rule(
			W4OS_PROFILE_SELF_PATTERN,
			'index.php?pagename=' . esc_attr( get_option( 'w4os_profile_slug', 'profile' ) ) . '&post_tyoe=user&profile_args=$matches[1]',
			'top'
		);
	}
);
update_option( 'w4os_rewrite_rules', true );
flush_rewrite_rules();

add_filter( 'query_vars', 'w4os_profile_query_vars' );
function w4os_profile_query_vars( $query_vars ) {
	$query_vars[] = 'profile_firstname';
	$query_vars[] = 'profile_lastname';
	$query_vars[] = 'profile_args';
	return $query_vars;
}

function w4os_get_avatar_by_name( $firstname = '', $lastname = '' ) {
	$user_query = new WP_User_Query(
		array(
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key'     => 'w4os_firstname',
					'value'   => $firstname,
					'compare' => '=',
				),

				array(
					'key'     => 'w4os_lastname',
					'value'   => $lastname,
					'compare' => '=',
				),
			),
		),
	);
	$users = $user_query->get_results();
	if ( ! empty( $users ) ) {
		return $users[0];
	}
	return false;
}

add_action(
	'login_form_bottom',
	function() {
		$links[] = sprintf(
			'<a href="%1$s" alt="%2$s">%2$s</a>',
			esc_url( wp_lostpassword_url(), 'w4os' ),
			esc_attr__( 'Lost Password', 'textdomain', 'w4os' ),
		);
		if ( get_option( 'users_can_register' ) || get_option( 'avatars_can_register' ) ) {
			$links[] = sprintf(
				'<a href="%1$s" alt="%2$s">%2$s</a>',
				esc_url( wp_registration_url(), 'w4os' ),
				esc_attr__( 'Register', 'textdomain', 'w4os' ),
			);
		}
		if ( is_array( $links ) ) {
			return '<p id=nav>' . join( ' | ', $links ) . '</p>';
		}
	}
);

function w4os_login_form( $args = array() ) {
	if ( ! isset( $args['echo'] ) ) {
		$args['echo'] = false;
	}
	if ( ! isset( $args['form_id'] ) ) {
		$args['form_id'] = 'w4os-loginform';
	}

	$login  = ( isset( $_GET['login'] ) ) ? $_GET['login'] : 0;
	$action = ( isset( $_GET['action'] ) ) ? $_GET['action'] : '';

	switch ( $action ) {
		case 'lostpassword':
			$login_form = 'lost password form';
			$login_form = sprintf(
				'<div id="password-lost-form" class="widecolumn">
        <p>%1$s</p>
        <form id="lostpasswordform" action="%2$s" method="post">
          <p class="form-row">
            <label for="user_login">%3$s
            </label>
            <input type="text" name="user_login" id="user_login">
          </p>
          <p class="lostpassword-submit">
            <input type="submit" name="submit" class="lostpassword-button"
            value="%4$s"/>
          </p>
          <p id=nav>%5$s<p>
        </form>
      </div>',
				__( 'Lost your password? Please enter your username or email address. You will receive a link to create a new password via email.', 'w4os' ),
				wp_lostpassword_url(),
				__( 'Email', 'w4os' ),
				__( 'Reset Password', 'w4os' ),
				sprintf( '<a href="%1$s">%2$s</a>', W4OS_LOGIN_PAGE, __( 'Log in', 'w4Os' ) ),
			);
			break;

		default:
			$login_form  = '<div>' . __( 'Log in to create your avatar, view your profile or set your options.', 'w4os' ) . '</div>';
			$login_form .= wp_login_form( $args );
	}
	return '<div class="login w4os-login ">' . $login_form . '</div>';
}

add_filter(
	'login_errors',
	function( $error ) {
		global $errors;

		if ( $errors ) {
			$err_codes = $errors->get_error_codes();

			// Invalid username.
			// Default: '<strong>ERROR</strong>: Invalid username. <a href="%s">Lost your password</a>?'
			if ( @in_array( 'invalid_username', $err_codes ) ) {
				$error = '<strong>ERROR</strong>: Invalid username.';
				$class = 'fail';
			}
			// Incorrect password.
			// Default: '<strong>ERROR</strong>: The password you entered for the username <strong>%1$s</strong> is incorrect. <a href="%2$s">Lost your password</a>?'
			if ( @in_array( 'incorrect_password', $err_codes ) ) {
				$error = '<strong>ERROR</strong>: The password you entered is incorrect.';
				$class = 'fail';
			}
		}

		if ( $error ) {
			w4os_notice( $error, $class );
		} else {
			w4os_notice( join( ', ', $err_codes ) );
		}

		return $error;
	}
);

add_action(
	'template_include',
	function( $template ) {
		global $wp_query;
		if ( isset( $wp_query->queried_object->post_name ) && $wp_query->queried_object->post_name != get_option( 'w4os_profile_slug' ) ) {
			return $template;
		}
		// echo "post_name " . $wp_query->queried_object->post_name;

		if ( isset( $_REQUEST['w4os_update_avatar'] ) ) {
			$user = get_user_by( 'ID', $_REQUEST['user_id'] );
			$uuid = w4os_update_avatar(
				$user,
				array(
					'action'          => sanitize_text_field( $_REQUEST['action'] ),
					'w4os_firstname'  => sanitize_text_field( $_REQUEST['w4os_firstname'] ),
					'w4os_lastname'   => sanitize_text_field( $_REQUEST['w4os_lastname'] ),
					'w4os_model'      => sanitize_text_field( $_REQUEST['w4os_model'] ),
					'w4os_password_1' => $_REQUEST['w4os_password_1'],
				)
			);
		}

		$query_firstname = get_query_var( 'profile_firstname' );
		$query_lastname  = get_query_var( 'profile_lastname' );

		if ( empty( $query_firstname ) || empty( $query_lastname ) ) {
			if ( is_user_logged_in() ) {
				$uuid = w4os_profile_sync( wp_get_current_user() );
				if ( $uuid ) {
					  $page_title = __( 'My Profile', 'w4os' );
				} else {
					$page_title = __( 'Create My Avatar', 'w4os' );
				}
			} else {
				$page_title = __( 'Log in', 'w4os' );
			}
		} else {

			// if ( $query_firstname != '' && $query_lastname != '' ) {
			$user = w4os_get_avatar_by_name( $query_firstname, $query_lastname );
			if ( $user ) {
				$avatar         = new W4OS_Avatar( $user );
				$avatar_profile = $avatar->profile_page();
			}
			if ( $avatar_profile ) {
				$avatar_name = esc_attr( get_the_author_meta( 'w4os_firstname', $avatar->ID ) . ' ' . get_the_author_meta( 'w4os_lastname', $avatar->ID ) );
				$page_title  = $avatar_name;
				$head_title  = sprintf( __( "%s's profile", 'w4os' ), $avatar_name );
			} else {
				$not_found  = true;
				$page_title = __( 'Avatar not found', 'w4os' );
			}
		}

		if ( isset( $page_title ) ) {
			// Doesn't seem to have any effect here
			// add_action( 'wp_title', function () use($page_title) {
			// return $page_title;
			// }, 20 );

			add_filter(
				'the_title',
				function( $title, $id = null ) use ( $page_title ) {
					if ( is_singular() && in_the_loop() && is_main_query() ) {
						return $page_title;
					}
					return $title;
				},
				20,
				2
			);

			if ( ! isset( $head_title ) ) {
				$head_title = $page_title;
			}

			switch ( get_template() ) {
				case 'Divi':
					add_filter(
						'pre_get_document_title',
						function() use ( $head_title ) {
							return $head_title . ' – ' . get_bloginfo( 'name' );
						},
						20
					);
					break;

				default:
					add_filter(
						'document_title_parts',
						function( $title ) use ( $head_title ) {
							  $title['title'] = $head_title;
							  // $title['site'] = get_option('w4os_grid_name');
							  return $title;
						},
						20
					);
			}
		}

		if ( @$not_found ) {
			// header("Status: 404 Not Found");
			$wp_query->set_404();
			status_header( 404 );
			get_template_part( 404 );
			exit();
		}

		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '';
		if ( strpos( $user_agent, ' - SecondLife/' ) !== false ) {
			return plugin_dir_path( __DIR__ ) . 'templates/page-profile-viewer.php';
			die();
		}
		return $template;
	}
);

add_action(
	'admin_init',
	function() {

		add_settings_section( 'w4os_permalinks', 'W4OS', 'w4os_permalinks_output', 'permalink' );
		add_settings_field( 'w4os_profile_slug', __( 'Profile base', 'w4os' ), 'w4os_profile_slug_output', 'permalink', 'w4os_permalinks' );
		if ( isset( $_POST['permalink_structure'] ) ) {
			$newslug = sanitize_title( $_REQUEST['w4os_profile_slug'] );
			if ( esc_attr( get_option( 'w4os_profile_slug' ) ) != $newslug || empty( $newslug ) ) {
				if ( empty( $newslug ) ) {
					$newslug = 'profile';
				}
				update_option( 'w4os_profile_slug', $newslug );
				update_option( 'w4os_rewrite_rules', true );
			}
		}
	}
);

function w4os_permalinks_output() {
	return;
}

function w4os_profile_slug_output() {
	?>
	<input name="w4os_profile_slug" type="text" class="regular-text code" value="<?php echo esc_attr( get_option( 'w4os_profile_slug', 'profile' ) ); ?>" placeholder="<?php echo 'profile'; ?>" />
	<?php
}

// function w4os_redirect_if_profile() {
// $url = getenv('REDIRECT_URL');
// $uuid_pattern='[a-fA-F0-9-]{8}-[a-fA-F0-9-]{4}-[a-fA-F0-9-]{4}-[a-fA-F0-9-]{4}-[a-fA-F0-9-]{12}';
// $ext_pattern='[a-zA-Z0-9]{3}[a-zA-Z0-9]?';
// if(! preg_match(
// '#' . preg_replace(':^/:', '', esc_attr(parse_url(wp_upload_dir()['baseurl'],  PHP_URL_PATH ) ) ) . '/w4os/profile/images/' . $uuid_pattern . '\.' . $ext_pattern . '$' . '#',
// $url,
// )) return false;
//
// $image = explode('.', basename($url));
// if(count($image) != 2) return false;
// $query_profile = $image[0];
// $query_format = $image[1];
// if ( ! preg_match('/^(jpg|png)$/i', $query_format)) return false;
//
// require(dirname(__FILE__) . '/profile-render.php');
// die();
// }
// w4os_redirect_if_profile();
