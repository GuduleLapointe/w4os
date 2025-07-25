<?php if ( ! defined( 'W4OS_PLUGIN' ) ) {
	die;}

if ( ! defined( 'W4OS_UPDATES' ) ) {
	define( 'W4OS_UPDATES', 6 );
}

$w4os_updated = get_option( 'w4os_updated', get_option( 'w4os_upated' ) );

if ( $w4os_updated < W4OS_UPDATES ) {
	w4os_updates();
}

function w4os_updates( $args = array() ) {
	$u      = get_option( 'w4os_updated', get_option( 'w4os_upated' ) ) + 1;
	$return = null;

	$messages = array();
	if ( @$args['message'] ) {
		$messages[] = $args['message'];
	}
	while ( $u <= W4OS_UPDATES ) {
		$update = "w4os_update_$u";
		if ( function_exists( $update ) ) {
			error_log( "processing $update" );
			$result = $update();
			if ( $result && $result === 'wait' ) {
				// not a success nor an error, will be processed after confirmation
				break;
			} elseif ( $result ) {
				$success[] = $u;
				if ( $result != 1 ) {
					$messages[] = $result;
				} else {
					$messages[] = W4OS::sprintf_safe( __( 'Update %s applied', 'w4os' ), $u );
				}
				update_option( 'w4os_updated', $u );
			} else {
				$errors[] = $u;
				break;
			}
		}
		++$u;
	}
	if ( @$success ) {
		if ( empty( $messages ) ) {
			$messages[] = W4OS::sprintf_safe( _n( 'Update %s applied successfully', 'Updates %s applied successfully', count( $success ), 'w4os' ), join( ', ', $success ) );
		}
		$class  = 'success';
		$return = true;
	}
	if ( @$errors ) {
		$messages[] = W4OS::sprintf_safe(
			__( 'Error processing update %s', 'w4os' ),
			$errors[0]
		);
		$class      = 'error';
		$return     = false;
	}
	if ( ! $messages ) {
		$messages = array( __( 'W4OS updated', 'w4os' ) );
	}
	if ( $messages ) {
		w4os_admin_notice( join( '<br/>', $messages ), $class );
	}
	return $return;
}

/*
 * Rewrite rules for first implementation of assets/ permalink
 */
function w4os_update_1() {
	global $wpdb;
	// $results=array();
	update_option( 'w4os_flush_rewrite_rules', true );
	// if(!empty($results)) return join("<br/>", $results);
	return true;
}

/**
 * Add grid_user role
 *
 * @return [type] update success
 */
function w4os_update_2() {
	function w4os_update_custom_roles() {
		$role      = 'grid_user';
		$role_name = __( 'Grid user', 'w4os' );
		add_role( $role, $role_name, get_role( 'subscriber' )->capabilities );
		w4os_admin_notice(
			__(
				W4OS::sprintf_safe( 'Added %s role', 'w4os' . $role_name . 'w4os' ),
				'w4os',
			),
			'success',
		);
	}
	add_action( 'init', 'w4os_update_custom_roles' );
	return true;
}

/*
 * Sync all existing profiles
 */
function w4os_update_3() {
	if ( function_exists( 'w4os_profile_sync_all' ) ) {
		add_action( 'admin_init', 'w4os_profile_sync_all' );
	} else {
		w4os_admin_notice( __( 'Profiles service is not configured on your Robust server. It is required for full functionalities.', 'w4os' ), 'error' );
	}
	return true;
}

/*
 * Set default values for profile (provide and slug=profile)
 * Force user sync and rules rewrite
 */
function w4os_update_4() {
	global $wpdb;
	if ( empty( get_option( 'w4os_profile_page' ) ) ) {
		update_option( 'w4os_profile_page', 'provide' );
	}
	if ( empty( get_option( 'w4os_profile_slug' ) ) ) {
		update_option( 'w4os_profile_slug', 'profile' );
	}
	update_option( 'w4os_sync_users', true );
	update_option( 'w4os_flush_rewrite_rules', true );
	return __( 'Grid and WordPress users synchronized.', 'w4os' );
}

/*
 * Create search tables if SEARCH_DB is set but tables do not exist.
 * Add gatekeeperURL column.
 */
function w4os_update_5() {
	if ( get_option( 'w4os_provide_search' ) == true ) {
		require_once dirname( __DIR__ ) . '/helpers/includes/config.php';
		require_once dirname( __DIR__ ) . '/helpers/includes/search.php';
		if ( $SearchDB ) {
			$tables = array( 'allparcels', 'classifieds', 'events', 'hostsregister', 'objects', 'parcels', 'parcelsales', 'popularplaces', 'regions' );
			foreach ( $tables as $table ) {
				if ( ! count( $SearchDB->query( "SHOW COLUMNS FROM `$table` LIKE 'gatekeeperURL'" )->fetchAll() ) ) {
					$SearchDB->query( "ALTER TABLE $table ADD gatekeeperURL varchar(255)" );
				}
			}
			return __( 'OpenSim Search tables updated.', 'w4os' );
		}
	}
	return true;
}

/*
 * Migrate Avatar Models settings to new dedicated settings page.
 * Migrate typo w4os_upated to w4os_updated
 */
function w4os_update_6() {
	$w4os_updated = get_option( 'w4os_updated', get_option( 'w4os_upated' ) );
	update_option( 'w4os_updated', $w4os_updated );
	delete_option( 'w4os_upated' );

	$first_name = get_option( 'w4os_model_firstname', false );
	$last_name  = get_option( 'w4os_model_lastname', false );
	if ( true ) {
		$name                   = empty( $last_name ) ? $first_name : $last_name;
		empty( $name ) && $name = 'Default';
		if ( empty( $first_name ) ) {
			$match = 'last';
		} elseif ( empty( $last_name ) ) {
			$match = 'first';
		} else {
			$match = 'any';
		}

		w4os_update_option( 'w4os-models:match', w4os_get_option( 'w4os-models:match', $match ) );
		w4os_update_option( 'w4os-models:name', w4os_get_option( 'w4os-models:name', $name ) );
		delete_option( 'w4os_model_firstname' );
		delete_option( 'w4os_model_lastname' );
		$new_match = w4os_get_option( 'w4os-models:match', 'any' );
		$new_name  = w4os_get_option( 'w4os-models:name', 'Default' );
		$notice    = "Updated model naming rule to $new_match = $new_name";
		return $notice;
	}

	return;
}

// Not ready yet, but keep it for when it's time to migrate.
// /*
// * Migrate Avatar Models settings to a subset of w4os-avatars settings.
// */
// function w4os_update_7() {
// $models = get_option( 'w4os-models', array() );
// error_log( 'w4os-models ' . print_r( $models, true ) );
// $avatars = get_option( 'w4os-avatars', array() );
// error_log( 'w4os-avatars ' . print_r( $avatars, true ) );
// $avatars['models'] = $models;
// update_option( 'w4os-avatars', $avatars );
// delete_option( 'w4os-models' );
// return true;
// }
