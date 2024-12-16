<?php if ( ! defined( 'W4OS_ADMIN' ) ) {
	die;}

function w4os_register_settings() {
	$grid_info = w4os_update_grid_info();
	// $check_login_uri = 'http://' . (!empty(get_option('w4os_login_uri'))) ? esc_attr(get_option('w4os_login_uri')) : 'http://localhost:8002';
	$default_loginuri        = ( isset( $grid_info['login'] ) ) ? $grid_info['login'] : '';
	$default_gridname        = ( isset( $grid_info['gridname'] ) ) ? $grid_info['gridname'] : '';
	$default_search_url      = ( get_option( 'w4os_provide_search' ) ? str_replace( 'https:', 'http:', get_home_url() ) : 'http://2do.directory' ) . '/helpers/query.php';
	$default_search_register = ( get_option( 'w4os_provide_search' ) ? str_replace( 'https:', 'http:', get_home_url() ) : 'http://2do.directory' ) . '/helpers/register.php';
	$login_uri               = get_option( 'w4os_login_uri', 'yourgrid.org:8002' );
	$gatekeeperURL           = preg_match( '#https?://#', $login_uri ) ? $login_uri : 'http://' . $login_uri;

	// if ( get_option( 'w4os_provide_search' ) || empty( get_option( 'w4os_search_url' ) ) ) {
	// update_option( 'w4os_search_url', $default_search_url );
	// }
	// if ( get_option( 'w4os_provide_search' ) || empty( get_option( 'w4os_search_register' ) ) ) {
	// update_option( 'w4os_search_register', $default_search_register );
	// }

	if ( ! w4os_option_exists( 'w4os_configuration_instructions' ) ) {
		update_option( 'w4os_configuration_instructions', true );
	}
	if ( ! w4os_option_exists( 'w4os_hypevents_url' ) ) {
		update_option( 'w4os_hypevents_url', 'http://2do.pm/events/' );
	}

	if ( get_option( 'w4os_search_use_default_db' ) ) {
		update_option( 'w4os_search_db_host', get_option( 'w4os_db_host' ) );
		update_option( 'w4os_search_db_database', get_option( 'w4os_db_database' ) );
		update_option( 'w4os_search_db_user', get_option( 'w4os_db_user' ) );
		update_option( 'w4os_search_db_pass', get_option( 'w4os_db_pass' ) );
	}

	$settings_pages = array(
		'w4os_status' => array(
			'sections' => array(
				'default' => array(
					'fields' => array(
						'w4os_sync_users' => array(
							'type'  => 'hidden',
							'value' => 1,
							'name'  => 'Synchronize users now',
						),
						// 'w4os_check_urls_now' => array(
						// 'type' => 'hidden',
						// 'value' => 1,
						// 'name' => 'Check urls now',
						// ),
					),
				),
			),
		),
	);

	foreach ( $settings_pages as $page_slug => $page ) {
		add_settings_section( $page_slug, '', '', $page_slug );

		foreach ( $page['sections'] as $section_slug => $section ) {
			add_settings_section( $section_slug, ( isset( $section['name'] ) ) ? $section['name'] : $section_slug, ( isset( $section['section_callback'] ) ) ? $section['section_callback'] : '', $page_slug );
			foreach ( $section['fields'] as $field_slug => $field ) {
				$field['section'] = $section_slug;
				w4os_register_setting( $page_slug, $field_slug, $field );
			}
		}
	}
	// die();
	return;
}
add_action( 'admin_init', 'w4os_register_settings' );

function w4os_register_setting( $option_page, $option_slug, $args = array() ) {
	if ( empty( $args['type'] ) ) {
		$args['type'] = 'string';
	}
	switch ( $args['type'] ) {
		case 'checkbox':
			foreach ( $args['values'] as $option_key => $option_value ) {
				register_setting( $option_page, $option_slug . '_' . $option_key, $args );
			}
			break;

		case 'radio':
			$register_args = array(
				'name' => $args['name'],
				'type' => 'string',
			);
			register_setting( $option_page, $option_slug, $register_args );
			break;

		default:
			// w4os_admin_notice("register_setting( $option_page, $option_slug, <pre>" . print_r($args, true) . "</pre> );");
			// if(!in_array($args['type'], [ 'string', 'boolean', 'integer', 'number', 'array', 'object'] ) ) $args['type'] = 'string';
			register_setting( $option_page, $option_slug, $args );
	}
	// if(!isset($args['name'])) $args['name'] = $option_slug;
	if ( empty( $args['sanitize_callback'] ) ) {
		$args['sanitize_callback'] = 'w4os_settings_field';
	}
	// add_settings_field( $option_slug, $args['name'], $args['sanitize_callback'], 'w4os_settings', $option_page, $args);
	$args['option_slug'] = $option_slug;
	add_settings_field(
		$option_slug,                   // Field ID
		( isset( $args['name'] ) ) ? $args['name'] : $option_slug,  // Title
		$args['sanitize_callback'],            // Callback to display the field
		$option_page,                // Page
		$args['section'],
		$args,                      // Section
	);
}

function w4os_settings_field( $args, $user = false ) {
	if ( $args['option_slug'] ) {
		$field_id = $args['option_slug'];
	} elseif ( $args['label_for'] ) {
		$field_id = $args['label_for'];
	} else {
		return;
	}
	// echo "<pre>" . print_r($args, true) . "</pre>";
	// return;

	$parameters = array();
	if ( isset( $args['readonly'] ) && $args['readonly'] ) {
		$parameters[] .= 'readonly';
	}
	if ( isset( $args['disabled'] ) && $args['disabled'] ) {
		$parameters[] .= 'disabled';
	}
	if ( isset( $args['onchange'] ) ) {
		$parameters[] = $args['onchange'];
	}
	if ( isset( $args['placeholder'] ) ) {
		$parameters[] = "placeholder='" . esc_attr( $args['placeholder'] ) . "'";
	}
	if ( isset( $args['autocomplete'] ) ) {
		$parameters[] = "autocomplete='" . $args['autocomplete'] . "'";
	}
	if ( isset( $args['onfocus'] ) ) {
		$parameters[] = "onfocus='" . esc_attr( $args['onfocus'] ) . "'";
	}
	if ( isset( $args['value'] ) ) {
		$value = $args['value'];
	} elseif ( $user ) {
		$value = get_user_meta( $user->ID, $field_id, true );
	} else {
		$value = esc_attr( get_option( $field_id ) );
	}

	switch ( $args['type'] ) {
		case 'url':
			if ( $args['readonly'] ) {
				if ( ! empty( $value ) ) {
					echo W4OS::sprintf_safe( '<a href="%1$s">%1$s</a>', esc_html( $value ) );
				}
				break;
			}

		case 'string':
			echo "<input type='text' class='regular-text input-${args['type']}' id='$field_id' name='$field_id' value='" . esc_attr( $value ) . "' " . join( ' ', $parameters ) . ' />';
			break;

		case 'password':
			echo "<input type='password' class='regular-text' id='$field_id' name='$field_id' value='" . esc_attr( get_option( $field_id ) ) . "' " . join( ' ', $parameters ) . ' />';
			break;

		case 'radio':
			foreach ( $args['values'] as $option_key => $option_name ) {
				$option_id = $field_id . '_' . $option_key;
				$option    = "<input type='radio' id='$option_id' name='$field_id' value='$option_key'";
				// if (get_option($option_key)==$option_key) $parameters[] = "checked";
				$parameters['checked'] = checked( get_option( $field_id ), $option_key, false );
				$option               .= ' ' . join( ' ', $parameters ) . ' ';
				$option               .= '/>';
				$option               .= " <label for='$option_id'>$option_name</label>";
				$options[]             = $option;
			}
			if ( is_array( $options ) ) {
				echo join( '<br>', $options );
			}
			break;

		case 'boolean':
			$args['values'][ $field_id ] = true;
		case 'checkbox':
			foreach ( $args['values'] as $option_key => $option_name ) {
				if ( $args['type'] == 'checkbox' ) {
					$option_id = $field_id . '_' . $option_key;
				} else {
					$option_id   = $field_id;
					$option_name = isset( $args['label'] ) ? $args['label'] : '';
					// unset($args['description']);
				}
				if ( $user ) {
					$value = get_user_meta( $user->ID, $option_id, true );
				} else {
					$value = esc_attr( get_option( $option_id ) );
				}

				$option                = "<input type='checkbox' id='$option_id' name='$option_id' value='1'";
				$parameters['checked'] = checked( $value, true, false );
				$option               .= ' ' . join( ' ', $parameters ) . ' ';
				$option               .= '/>';
				// if($args['type']=='checkbox')
				$option .= " <label for='$option_id'>$option_name</label>";
				// else if($args['type']=='boolean') $option .= " <label for='$option_id'>$option_name</label>";
				$options[] = $option;
			}
			if ( is_array( $options ) ) {
				echo join( '<br>', $options );
			}
			break;

		case 'hidden':
			echo "<input type='hidden' class='regular-text input-${args['type']}' id='$field_id' name='$field_id' value='" . esc_attr( get_option( $field_id ) ) . "' " . join( ' ', $parameters ) . ' />';
			break;

		case 'description':
			break;

		case 'os_asset':
			echo ( ! empty( $value ) ) ? w4os_render_asset( $value ) : $args['placeholder'];
			break;

		default:
			echo "type ${args['type']} not recognized";
	}
	if ( ! empty( $args['description'] ) ) {
		echo "<p class=description>${args['description']}</p>";
	}
	// echo "<input type=text value='$one'/><pre>" . print_r($args, true) . "</pre>";
}

function w4os_settings_callback_gridinfo() {
	echo W4OS::sprintf_safe(
		'<p>%1$s %2$s</p>',
		__( 'Values must match Robust.HG.ini (or Robust.HG.ini) config file.', 'w4os' ),
		__( 'Robust server must be running. Values entered here will be checked against your Robust server and updated if needed.', 'w4os' ),
	);
}

function w4os_settings_callback_models( $arg ) {
	echo '<p>' . __( 'Grid accounts matching first name or last name set below are considered as avatar models. They will appear on the avatar registration form, with their in-world profile picture.', 'w4os' ) . '</p>';
}

// function w4os_settings_callback_webassets($arg) {
// echo "<p class=help>" . __('A web assets server is needed to display in-world assets (from the grid) on the website (e.g. profile pictures). You can use an external web assets server if you already have one installed, or use the one provided by w4os plugin.', 'w4os') . "</p>";
// }

/**
 * Probablyu obsolte
 *
 * @deprecated:
 */
function w4os_add_action_links( $links ) {
	$url = w4os_settings_url();

	array_push(
		$links,
		"<a href='$url'>" . __( 'Settings' ) . '</a>'
	);

	return $links;
}
add_filter( 'plugin_action_links_' . W4OS_PLUGIN, 'w4os_add_action_links' );
