<?php if ( ! defined( 'W4OS_PLUGIN' ) ) {
	die;}

function w4os_array2table( $array, $class = '', $level = 1 ) {
	if ( empty( $array ) ) {
		return;
	}
	if ( $level == 1 ) {
		$result = '';
		foreach ( $array as $key => $value ) {
			if ( is_array( $value ) ) {
				$value = join( ', ', $value );
			}
			$result .= '<tr><td class=gridvar>' . __( $key, 'w4os' ) . "</td><td class=gridvalue>$value</td></tr>";
		}
		if ( ! empty( $result ) ) {
			$result = "<table class='$class'>$result</table>";
		}
		return $result;
	}
	if ( $level == 2 ) {
		$html       = "<table class=$class>";
		$array_head = $array;
		$array_head = array_shift( $array_head );
		if ( is_array( $array_head ) ) {
			$html .= '<tr><th></th>';
			foreach ( $array_head as $column => $value ) {
				$html .= "<th>$column</th>";
			}
			$html .= '</tr>';
		}
		foreach ( $array as $key => $row ) {
			if ( is_array( $row ) ) {
				$html .= "<tr><th>$key</th>";
				foreach ( $row as $column => $value ) {
					$html .= "<td class=col-$column>$value</td>";
				}
				$html .= '</tr>';
			}
		}
		$html .= '</table>';
		return $html;
	}
}

function w4os_notice( $message, $class = '', $id = '', $context = '' ) {
	global $w4Os_notices;
	if ( empty( trim( '$message' ) ) || $message == ' ' ) {
		return;
	}
	if ( ! empty( $id ) ) {
		$w4Os_notices[ $id ] = $notice;
	}
	if ( is_admin() ) {
		w4os_transient_admin_notice( $message, $class );
	} else {
		wp_cache_set(
			'w4os_notices',
			wp_cache_get( 'w4os_notices' ) . sprintf(
				'<div class="notice notice-%2$s"><p>%1$s</p></div>',
				$message,
				$class,
				$id,
			)
		);
	}
}

function w4os_gen_uuid() {
	return sprintf(
		'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
		// 32 bits for "time_low"
		mt_rand( 0, 0xffff ),
		mt_rand( 0, 0xffff ),
		// 16 bits for "time_mid"
		mt_rand( 0, 0xffff ),
		// 16 bits for "time_hi_and_version",
		// four most significant bits holds version number 4
		mt_rand( 0, 0x0fff ) | 0x4000,
		// 16 bits, 8 bits for "clk_seq_hi_res",
		// 8 bits for "clk_seq_low",
		// two most significant bits holds zero and one for variant DCE1.1
		mt_rand( 0, 0x3fff ) | 0x8000,
		// 48 bits for "node"
		mt_rand( 0, 0xffff ),
		mt_rand( 0, 0xffff ),
		mt_rand( 0, 0xffff )
	);}

function w4os_admin_notice( $notice, $class = 'info', $dismissible = true ) {
	if ( empty( $notice ) ) {
		return;
	}
	if ( $dismissible ) {
		$is_dismissible = 'is-dismissible';
	}
	if ( is_admin() ) {
		add_action(
			'admin_notices',
			function () use ( $notice, $class, $is_dismissible ) {
				?>
		<div class="notice notice-<?php echo $class; ?> <?php echo $is_dismissible; ?>">
			<p><strong><?php echo W4OS_PLUGIN_NAME; ?></strong>: <?php _e( $notice, 'w4os' ); ?></p>
	</div>
				<?php
			}
		);
	} else {
		w4os_transient_admin_notice( $notice, $class, $dismissible, __FUNCTION__ );
	}
}

function w4os_transient_admin_notice( $notice, $class = 'info', $dismissible = true, $key = null ) {
	$transient_key = sanitize_title( W4OS_PLUGIN_NAME . '_w4os_transient_admin_notices' );

	$queue = get_transient( $transient_key );
	if ( ! is_array( $queue ) ) {
		$queue = array( $queue );
	}

	$hash           = hash( 'md5', $notice );
	$queue[ $hash ] = array(
		'notice'      => $notice,
		'class'       => $class,
		'dismissible' => $dismissible,
	);
	set_transient( $transient_key, $queue );
}

function w4os_get_transient_admin_notices() {
	if ( ! is_admin() ) {
		return;
	}
	$transient_key = sanitize_title( W4OS_PLUGIN_NAME . '_w4os_transient_admin_notices' );
	$queue         = get_transient( $transient_key );
	if ( ! is_array( $queue ) ) {
		$queue = array( $queue );
	}
	foreach ( $queue as $key => $notice ) {
		if ( ! is_array( $notice ) ) {
			continue;
		}
		w4os_admin_notice( $notice['notice'], $notice['class'], $notice['dismissible'] );
	}
	delete_transient( $transient_key );
}
add_action( 'admin_head', 'w4os_get_transient_admin_notices' );

function w4os_user_notice( $notice, $class = 'info', $dismissible = true, $key = null ) {
	if ( empty( $notice ) ) {
		return;
	}
	if ( is_admin() ) {
		w4os_transient_admin_notice( $notice, $class, $dismissible, __FUNCTION__ );
	} else {
		w4os_transient_user_notice( $notice, $class, $dismissible, __FUNCTION__ );
	}
}

function w4os_transient_user_notice( $notice, $class = 'info', $dismissible = true, $key = null ) {
	if ( empty( $notice ) ) {
		return;
	}
	$transient_key = sanitize_title( W4OS_PLUGIN_NAME . '_user_notices' );

	$queue = get_transient( $transient_key );
	if ( ! is_array( $queue ) ) {
		$queue = array( $queue );
	}

	$hash           = hash( 'md5', $notice );
	$queue[ $hash ] = array(
		'notice'      => $notice,
		'class'       => $class,
		'dismissible' => $dismissible,
	);
	set_transient( $transient_key, $queue );
}

function w4os_get_user_notices( $echo = false ) {
	$transient_key = sanitize_title( W4OS_PLUGIN_NAME . '_user_notices' );
	$queue         = get_transient( $transient_key );
	if ( empty( $queue ) ) {
		return;
	}
	$notices = array();
	foreach ( $queue as $hash => $notice ) {
		if ( ! is_array( $notice ) ) {
			continue;
		}
		$notice = wp_parse_args(
			$notice,
			array(
				'notice'      => '',
				'class'       => 'info',
				'dismissible' => true,
			)
		);
		error_log( 'Notice ' . $notice['class'] . ': ' . $notice['notice'] );
		$notices[ $hash ] = sprintf(
			'<div class="notice notice-%1$s %2$s">%3$s</p>',
			sanitize_key( $notice['class'] ),
			( $notice['dismissible'] ) ? 'is-dismissible' : '',
			'<p>' . esc_attr( $notice['notice'] ) . '</p>',
		);
	}
	$html = empty( $notices ) ? '' : '<div class="notices w4os-user-notices">' . join( '', $notices ) . '</div>';

	delete_transient( $transient_key );
	if ( $echo ) {
		echo $html;
	} else {
		return $html;
	}
}

function w4os_fast_xml( $url ) {
	if ( class_exists( 'W4OS3' ) ) {
		return W4OS3::fast_xml( $url );
	}

	// Exit silently if required php modules are missing
	if ( ! function_exists( 'curl_init' ) ) {
		return null;
	}
	if ( ! function_exists( 'simplexml_load_string' ) ) {
		return null;
	}

	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_URL, $url );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
	$html = curl_exec( $ch );
	curl_close( $ch );
	$xml = simplexml_load_string( $html );
	return $xml;
}

function w4os_get_grid_info( $rechecknow = false ) {
	if ( class_exists( 'W4OS3' ) ) {
		return W4OS3::grid_info( $rechecknow );
	}

	$grid_info = get_option( 'w4os_grid_info' );

	if ( $rechecknow || get_option( 'w4os_check_urls_now' ) ) {
		return w4os_update_grid_info( true );
	}

	if ( ! empty( $grid_info ) ) {
		return json_decode( $grid_info, true );
	}

	return w4os_update_grid_info();
}

function w4os_update_grid_info( $rechecknow = false ) {
	if ( defined( 'W4OS_GRID_INFO_CHECKED' ) & ! $rechecknow ) {
		return get_option( 'w4os_grid_info' );
	}
	define( 'W4OS_GRID_INFO_CHECKED', true );
	$local_uri       = 'http://localhost:8002';
	$check_login_uri = ( get_option( 'w4os_login_uri' ) ) ? 'http://' . get_option( 'w4os_login_uri' ) : $local_uri;
	$check_login_uri = preg_replace( '+http://http+', 'http', $check_login_uri );
	// $xml = simplexml_load_file($check_login_uri . '/get_grid_info');
	$xml = w4os_fast_xml( $check_login_uri . '/get_grid_info' );

	if ( ! $xml ) {
		return false;
	}
	if ( $check_login_uri == $local_uri ) {
		w4os_admin_notice( __( 'A local Robust server has been found. Please check Login URI and Grid Name configuration.', 'w4os' ), 'success' );
	}

	$grid_info = (array) $xml;
	if ( get_option( 'w4os_provide_search', false ) ) {
		$grid_info['SearchURL'] = get_option( 'w4os_search_url' ) . '?gk=http://' . get_option( 'w4os_login_uri' );
	}

	if ( 'provide' === get_option( 'w4os_profile_page' ) && empty( $grid_info['profile'] ) && defined( 'W4OS_PROFILE_URL' ) ) {
		$grid_info['profile'] = W4OS_PROFILE_URL;
	}
	if ( ! empty( $grid_info['login'] ) ) {
		update_option( 'w4os_login_uri', preg_replace( '+/*$+', '', preg_replace( '+https*://+', '', $grid_info['login'] ) ) );
	}
	if ( ! empty( $grid_info['gridname'] ) ) {
		update_option( 'w4os_grid_name', $grid_info['gridname'] );
	}
	// if ( isset( $grid_info['OfflineMessageURL'] ) ) {
	// update_option( 'w4os_offline_helper_uri', $grid_info['OfflineMessageURL'] );
	// }

	if ( isset( $urls ) && is_array( $urls ) ) {
		w4os_get_urls_statuses( $urls, get_option( 'w4os_check_urls_now' ) );
	}

	update_option( 'w4os_grid_info', json_encode( $grid_info ) );
	return $grid_info;
}
function w4os_settings_url( $page = 'w4os_settings' ) {
	// get_admin_url( '', 'admin.php?page=' . $page ),
	$url = esc_url(
		add_query_arg(
			'page',
			'w4os_settings',
			get_admin_url() . 'admin.php'
		)
	);
	return $url;
}

function w4os_settings_link( $page = 'w4os_settings' ) {
	return sprintf(
		"<a href='%s'>%s</a>",
		w4os_settings_url( $page ),
		__( 'settings page', 'w4os' ),
	);
}

function w4os_upload_dir( $subfolder = '' ) {
	$upload     = wp_upload_dir();
	$upload_dir = $upload['basedir'];
	$dirs[]     = $upload_dir;
	$dirs[]     = 'w4os';
	$dirs       = array_merge( $dirs, array_map( 'sanitize_file_name', explode( '/', preg_replace( ':/*$:', '', $subfolder ) ) ) );
	$upload_dir = implode( '/', $dirs );
	if ( ! is_dir( $upload_dir ) ) {
		mkdir( $upload_dir, 0755, true );
	}
	return $upload_dir;
}

function w4os_asset_server_uri() {
	$uri = false;
	if ( get_option( 'w4os_provide_asset_server' ) ) {
		$uri = get_option( 'w4os_internal_asset_server_uri', false );
	} else {
		$uri = get_option( 'w4os_external_asset_server_uri', false );
	}
	if ( $uri ) {
		return untrailingslashit( $uri );
	}
	return $uri;
}
function w4os_get_asset_url( $uuid = W4OS_NULL_KEY, $format = W4OS_ASSETS_DEFAULT_FORMAT ) {
	$asset_server_uri = w4os_asset_server_uri();
	$format           = ( empty( $format ) ? '' : ".$format" );
	return ( $asset_server_uri ) ? "$asset_server_uri/$uuid$format" : false;
}

function w4os_grid_status( $login_uri = null ) {
	$status = w4os_grid_online( $login_uri );
	if ( $status === true ) {
		return __( 'Online', 'w4os' );
	} elseif ( $status === false ) {
		return __( 'Offline', 'w4os' );
	} else {
		return $status;
	}
}

function w4os_grid_online( $login_uri = null ) {
	$cache_key = 'grid_online_status' . ( empty( $login_uri ) ? '' : '_' . $login_uri );
	if ( empty( $login_uri ) ) {
		$login_uri = w4os_grid_login_uri();
	}

	$status = wp_cache_get( $cache_key, 'w4os' );
	if ( false === $status ) {
		$login_uri = $login_uri;
		$parts     = wp_parse_url( $login_uri );
		if ( isset( $parts['host'] ) ) {
			$fp = @fsockopen( $parts['host'], $parts['port'], $errno, $errstr, 1.0 );
			return ( $fp ) ? true : false;
		} else {
			return sprintf(
				__( 'Invalid Login URI', 'w4os' ),
				$login_uri,
			);
		}
	}
	return $status;
}

function w4os_grid_status_text() {
	global $w4osdb;
	// If w4os is not yet configured, calls to $w4osdb would crash
	if ( ! $w4osdb ) {
		return false;
	}

	$status = wp_cache_get( 'gridstatus', 'w4os' );
	if ( false === $status ) {
		// $cached="uncached";
		if ( $w4osdb->check_connection() ) {
			$lastmonth = time() - 30 * 86400;

			// $urlinfo    = explode( ':', get_option( 'w4os_login_uri' ) );
			// $host       = $urlinfo['0'];
			// $port       = $urlinfo['1'];
			// $fp         = @fsockopen( $host, $port, $errno, $errstr, 1.0 );
			$gridonline = w4os_grid_status();

			// if ($fp) {
			// $gridonline = __("Yes", 'w4os' );
			// } else {
			// $gridonline = __("No", 'w4os' );
			// }
			$filter = '';
			if ( get_option( 'w4os_exclude_models' ) ) {
				$filter .= "u.FirstName != '" . get_option( 'w4os_model_firstname' ) . "'
				AND u.LastName != '" . get_option( 'w4os_model_lastname' ) . "'";
			}
			if ( get_option( 'w4os_exclude_nomail' ) ) {
				$filter .= " AND u.Email != ''";
			}
			if ( ! empty( $filter ) ) {
				$filter = "$filter AND ";
			}
		}
		$status                                     = array(
			__( 'Status', 'w4os' )                   => $gridonline,
			__( 'Members', 'w4os' )                  => number_format_i18n(
				$w4osdb->get_var(
					"SELECT COUNT(*)
			FROM UserAccounts as u WHERE $filter active=1"
				)
			),
			__( 'Active members (30 days)', 'w4os' ) => number_format_i18n(
				$w4osdb->get_var(
					"SELECT COUNT(*)
			FROM GridUser as g, UserAccounts as u WHERE $filter PrincipalID = UserID AND g.Login > $lastmonth"
				)
			),
		);
		$status[ __( 'Members in world', 'w4os' ) ] = number_format_i18n(
			$w4osdb->get_var(
				"SELECT COUNT(*)
		FROM Presence AS p, UserAccounts AS u
		WHERE $filter RegionID != '00000000-0000-0000-0000-000000000000'
		AND p.UserID = u.PrincipalID;"
			)
		);
		// 'Active citizens (30 days)' => number_format_i18n($w4osdb->get_var("SELECT COUNT(*)
		// FROM GridUser as g, UserAccounts as u WHERE g.UserID = u.PrincipalID AND Login > $lastmonth" )),
		if ( ! get_option( 'w4os_exclude_hypergrid' ) ) {
			$status[ __( 'Active users (30 days)', 'w4os' ) ] = number_format_i18n(
				$w4osdb->get_var(
					"SELECT COUNT(*)
			FROM GridUser WHERE Login > $lastmonth"
				)
			);
			$status[ __( 'Total users in world', 'w4os' ) ]   = number_format_i18n(
				$w4osdb->get_var(
					"SELECT COUNT(*)
			FROM Presence
			WHERE RegionID != '00000000-0000-0000-0000-000000000000';	"
				)
			);
		}
		$status[ __( 'Regions', 'w4os' ) ]    = number_format_i18n(
			$w4osdb->get_var(
				'SELECT COUNT(*)
		FROM regions'
			)
		);
		$status[ __( 'Total area', 'w4os' ) ] = number_format_i18n(
			$w4osdb->get_var(
				'SELECT round(sum(sizex * sizey / 1000000),2)
		FROM regions'
			),
			2
		) . '&nbsp;km²';
		wp_cache_add( 'gridstatus', $status, 'w4os' );
	}
	return $status;
}

function w4os_empty( $var ) {
	if ( ! $var ) {
		return true;
	}
	if ( empty( $var ) ) {
		return true;
	}
	if ( $var == W4OS_NULL_KEY ) {
		return true;
	}
	return false;
}

function w4os_get_url_status( $url, $output = null, $force = false ) {
	if ( empty( $url ) ) {
		$status_code = '';
	} else {
		$url_transient_key = sanitize_title( 'w4os_url_status_' . $url );
		$status_code       = ( $force ) ? false : get_transient( $url_transient_key );
		if ( ! $status_code ) {
			$headers     = @get_headers( $url, true );
			$status_code = preg_replace( '/.* ([0-9]+) .*/', '$1', $headers['0'] );
			if ( ! $status_code ) {
				$status_code = 0;
			}
			set_transient( $url_transient_key, $status_code, 3600 );
			set_transient( 'w4os_get_url_status_checked', time() );
		}
	}
	switch ( substr( $status_code, 0, 1 ) ) {
		case '':
			$status_icon = 'no';
			break;

		case '2':
		case '3':
			$status_icon = 'yes';
			$success     = true;
			break;

		case '4':
			if ( $status_code == 418 ) {
				$status_icon = 'coffee';
				$success     = false;
				break;
			}

		default:
			$status_icon = 'warning';
			$success     = false;
	}
	if ( $output == 'icon' ) {
		return sprintf( '<span class="w4os-url-status w4os-url-status-%1$s dashicons dashicons-%2$s"></span>', $status_code, $status_icon );
	} elseif ( $output == 'boolean' ) {
		return ( ! empty( $success ) ) ? $success : null;
	} else {
		return $status_code;
	}
}

function w4os_get_urls_statuses( $urls = array(), $force = false ) {
	set_transient( 'w4os_get_url_status_checked', time() );
	// Avoid concurrent checks
	if ( get_transient( 'w4os_get_urls_statuses_lock' ) ) {
		// w4os_get_urls_statuses_lock is already processing, skipping
		return;
	}
	if ( $force ) {
		set_transient( 'w4os_get_urls_statuses_lock', true, 3600 );
		set_transient( 'w4os_get_url_status_checked', time() );
	}

	if ( is_array( $urls ) ) {
		foreach ( $urls as $key => $url ) {
			if ( esc_url_raw( $url ) == $url ) {
				w4os_get_url_status( $url, null, $force );
			} else {
				error_log( __METHOD__ . ' Invalid URL: ' . $url );
			}
		}
	} else {
		error_log( __METHOD__ . ' Empty URLs array' );
	}

	if ( $force ) {
		delete_transient( 'w4os_get_urls_statuses_lock' );
	}

	if ( ! empty( $errors ) ) {
		$messages[] = '<p class=sync-errors><ul><li>' . join( '</li><li>', $errors ) . '</p>';
	}
	// $messages[] = w4os_array2table($accounts, 'accounts', 2);
	if ( ! empty( $messages ) ) {
		return '<div class=messages><p>' . join( '</p><p>', $messages ) . '</div>';
	}

	w4os_clean_previous_scheduled_actions( __FUNCTION__ );
}

function w4os_clean_previous_scheduled_actions( $hook ) {
	// Use the same logic as ActionScheduler_WPCLI_Clean_Command.php
	// DO NOT USE as_unschedule_action, it has already been tested and it does not clean the actions Scheduled Actions admin page

	$batch_size = 20; // Number of actions to delete per batch
	$status     = ActionScheduler_Store::STATUS_COMPLETE;

	// $before = '31 days ago'; // Delete actions older than this date
	// try {
	// $lifespan = as_get_datetime_object( $before );
	// } catch ( Exception $e ) {
	// $lifespan = null;
	// }

	// Instance of the action store
	$store_args = array(
		'hooks' => array( $hook ),
	);
	$store      = ActionScheduler::store();

	// Retrieve actions associated with the specified hook
	$args = array(
		'hook'   => $hook,
		'status' => $status,
		// 'date'   => $lifespan ? $lifespan->format( 'Y-m-d H:i:s' ) : null,
	);
	$actions = as_get_scheduled_actions( $args );
	if ( empty( $actions ) ) {
		// No actions to check for $hook, stopping
		return;
	}

	foreach ( $actions as $action_id => $action ) {
		try {
			$store->delete_action( $action_id );
		} catch ( Exception $e ) {
			error_log( __METHOD__ . " Error while deleting $hook action $action_id : " . $e->getMessage() . ' (File: ' . $e->getFile() . ', Line: ' . $e->getLine() . ')' );
		}
	}
}

function register_w4os_get_urls_statuses_async_cron() {
	// Schedule a new instance of the action only if it is not already running or scheduled
	if ( false === as_has_scheduled_action( 'w4os_get_urls_statuses' ) && ! get_transient( 'w4os_get_urls_statuses_lock' ) ) {
		as_schedule_cron_action( time(), '*/5 * * * *', 'w4os_get_urls_statuses' );
	}
}
add_action( 'init', 'register_w4os_get_urls_statuses_async_cron' );
add_action( 'w4os_get_urls_statuses', 'w4os_get_urls_statuses' );

function w4os_sanitize_login_uri( $login_uri ) {
	if ( W4OS_ENABLE_V3 ) {
		return W4OS3::sanitize_uri( $login_uri );
	}

	if ( empty( $login_uri ) ) {
		return;
	}

	$login_uri = ( preg_match( '/^https?:\/\//', $login_uri ) ) ? $login_uri : 'http://' . $login_uri;

	$parts = wp_parse_args(
		wp_parse_url( $login_uri ),
		array(
			'scheme' => 'http',
			'port'   => preg_match('/osgrid\.org/', $login_uri) ? 80 : 8002,
		),
	);

	$login_uri = $parts['scheme'] . '://' . $parts['host'] . ':' . $parts['port'];

	return $login_uri;
}

function w4os_grid_login_uri() {
	// if ( defined( 'W4OS_GRID_LOGIN_URI' ) ) {
	// return W4OS_GRID_LOGIN_URI;
	// }

	return w4os_sanitize_login_uri( get_option( 'w4os_login_uri' ) );
}

function w4os_grid_name() {
	return get_option( 'w4os_grid_name' );
}

function w4os_grid_running() {
	$login_uri = w4os_grid_login_uri();
	if ( empty( $login_uri ) ) {
		return false;
	}
	$url         = w4os_grid_login_uri() . '/get_grid_info';
	$headers     = @get_headers( $url, true );
	$status_code = preg_replace( '/.* ([0-9]+) .*/', '$1', $headers['0'] );
	return ( $status_code == 200 );
}

function w4os_status_icon( $bool = null, $ignore_null = false ) {
	if ( $bool === true ) {
		$status_icon = 'yes';
	} elseif ( $bool === false ) {
		$status_icon = 'warning';
	} elseif ( $ignore_null ) {
		return;
	} else {
		$status_icon = 'no';
	}
	return sprintf( '<span class="w4os-status-icon w4os-url-status w4os-url-status-%1$s dashicons dashicons-%1$s"></span>', $status_icon );
}

function w4os_format_ini( $array ) {
	if ( empty( $array ) ) {
		return;
	}
	$content = '<div class=iniconfig>';
	foreach ( $array as $inifile => $sections ) {
		$content .= '<p class="inifile dashicons-before dashicons-media-text">';
		$content .= sprintf( __( '%s', 'w4os' ), $inifile );
		$content .= '<pre>';
		foreach ( $sections as $section => $params ) {
			$content .= "$section<br>";
			if ( is_array( $params ) ) {
				foreach ( $params as $param => $value ) {
					if ( is_numeric( $param ) ) {
						$content .= "  $value<br>";
					} else {
						$content .= "  $param = $value<br>";
					}
				}
			} else {
				$content .= "  $params<br>";
			}
			// foreach ( $params as $param => $value ) {
			// if ( is_numeric( $param ) ) {
			// $content .= "  $value<br>";
			// } else {
			// $content .= "  $param = $value<br>";
			// }
			// }
		}
		$content  = preg_replace( '/<br>$/', '', $content );
		$content .= '</pre></p>';
	}
	$content .= '</div>';
	return $content;
}

function w4os_demask( $mask, $values, $additionalvalue ) {
	$array = array();
	foreach ( $values as $key => $value ) {
		$bit = pow( 2, $key );
		if ( $mask & $bit ) {
			$array[ $key ] = $value;
		}
	}
	$array[] = $additionalvalue;
	return array_filter( $array );
}

function w4os_hop( $url = null, $string = null, $format = true ) {
	if ( empty( $url ) ) {
		// $url = get_option( 'w4os_login_uri' );
		return $string;
	}
	$url = opensim_format_tp( $url, TPLINK_HOP );

	if ( ! $format ) {
		return $url;
	}

	$string    = ( empty( $string ) ) ? $url : $string;
	$classes[] = 'hop';
	if ( preg_match( ':/app/agent/:', $url ) ) {
		$classes[] = 'profile';
	}

	return sprintf( '<a class="%3$s" href="%1$s">%2$s</a>', esc_attr( $url ), $string, implode( ' ', $classes ) );
}

function w4os_age( $time ) {
	if ( empty( $time ) ) {
		return;
	}
	$age = number_format( ( current_time( 'timestamp' ) - $time ) / 24 / 3600 );
	if ( $age == 0 ) {
		$ageshown = __( 'Joined today', 'w4os' );
	} else {
		$ageshown = sprintf( __( '%s days old', 'w4os' ), $age );
	}
	return sprintf(
		'%s (%s)',
		wp_date( get_option( 'date_format' ), $time ),
		$ageshown,
	);
}

function w4os_option_exists( $option_name ) {
	global $wpdb;
	$row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", $option_name ) );
	if ( is_object( $row ) ) {
		return true;
	}
	return false;
}

function w4os_get_option( $option, $default = false ) {
	$settings_page = null;
	$result        = $default;
	if ( preg_match( '/:/', $option ) ) {
		$settings_page = strstr( $option, ':', true );
		$option        = trim( strstr( $option, ':' ), ':' );
	} else {
		$settings_page = 'w4os';
	}

	$settings = get_option( $settings_page );
	if ( $settings && isset( $settings[ $option ] ) ) {
		$result = $settings[ $option ];
	} else {
		$result = get_option( $option, $default );
	}

	return $result;
}

function w4os_update_option( $option, $value, $autoload = null ) {
	$settings_page = null;
	if ( preg_match( '/:/', $option ) ) {
		$settings_page       = strstr( $option, ':', true );
		$option              = trim( strstr( $option, ':' ), ':' );
		$settings            = get_option( $settings_page );
		$settings[ $option ] = $value;
		$result              = update_option( $settings_page, $settings, $autoload );
	} else {
		$result = update_option( $option, $value, $autoload );
	}
	return $result;
}

function w4os_replace( $content, $args ) {
	if ( ! is_array( $args ) ) {
		error_log( 'args ' . $args . ' is not an array' );
		return $content;
	}
	$keys   = array_map(
		function ( $key ) {
			return "/\[$key\]/";
		},
		array_keys( $args )
	);
	$values = array_values( $args );

	$result = $content = preg_replace( $keys, $values, $content );
	return $result;
}

function w4os_camelcase( $string ) {
	if ( ! is_string( $string ) ) {
		return $string;
	}
	return str_replace( ' ', '', ucwords( str_replace( '-', ' ', sanitize_title( $string ) ) ) );
}

function w4os_check_requirements() {
	$errors             = array();
	$php_missing_module = __( "%s is required but is not installed. Please refer to the PHP manual or consult your hosting provider's support resources for specific instructions.", 'w4os' );
	if ( ! function_exists( 'xmlrpc_encode_request' ) ) {
		$errors[] = sprintf(
			$php_missing_module,
			'PHP xml-rpc',
		);
	}
	if ( ! function_exists( 'curl_init' ) ) {
		$errors[] = sprintf(
			$php_missing_module,
			'PHP curl',
		);
	}
	if ( ! extension_loaded( 'imagick' ) ) {
		$errors[] = sprintf(
			$php_missing_module,
			'ImageMagick',
		);
	}
	$permalink_structure = get_option( 'permalink_structure' );
	if ( empty( $permalink_structure ) ) {
		// TRANSLATORS: %s will be replaced by permalink page link.
		$errors[] = sprintf(
			__( "Permalinks are not enabled. They are required for proper operations. Choose any permalink structure other than 'Plain' in %s.</a>", 'w4os' ),
			'<a href="' . admin_url( 'options-permalink.php' ) . '">' . __( 'Permalink settings page', 'w4os' ) . '</a>',
		);
	}
	if ( ! empty( $errors ) ) {
		w4os_admin_notice(
			sprintf(
				'<strong>%s</strong><ul class=warning-list><li>%s</li></ul>',
				__( 'Requirements not met for w4os plugin', 'w4os' ),
				join( '</li><li>', $errors ),
			),
			'error',
		);
	}
}
