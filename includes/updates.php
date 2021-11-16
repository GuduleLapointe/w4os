<?php if ( ! defined( 'WPINC' ) ) die;

if ( ! defined( 'W4OS_UPDATES' ) ) define('W4OS_UPDATES', 3 );

if(get_option('w4os_upated') < W4OS_UPDATES ) {
  w4os_updates();
}

function w4os_updates($args = array()) {
  $u = get_option('w4os_upated') + 1;
  $messages = array();
  if($args['message']) $messages[] = $args['message'];
  while ($u <= W4OS_UPDATES) {
    $update="w4os_update_$u";
    if(function_exists($update)) {
      $result=$update();
      if($result && $result==='wait') {
        // not a success nor an error, will be processed after confirmation
        break;
      } else if($result) {
        $success[]=$u;
        if($result != 1)
        $messages[] = $result;
        else $messages[] = sprintf(__('Update %s applied', 'band-tools'), $u );
        update_option('w4os_upated', $u);
      } else {
        $errors[]=$u;
        break;
      }
    }
    $u++;
  }
  if($success) {
    $messages[] = sprintf( _n('Update %s applied sucessfully', 'Updates %s applied sucessfully', count($success), 'band-tools'), join(', ', $success) );
    $class='success';
    $return=true;
  }
  if($errors) {
    $messages[] = sprintf(
      __('Error processing update %s', 'band-tools'),
      $errors[0] );
    $class='error';
    $return=false;
  }
  if(! $messages) $messages = array(__("W4OS updated", 'w4os'));
  if($messages)
  w4os_admin_notice(join('<br/>', $messages), $class);
  return $return;
}

/*
 * Rewrite rules for first implementation of assets/ permalink
 */
function w4os_update_1() {
  global $wpdb;
  // $results=array();
  update_option('w4os_rewrite_rules', true);
  // if(!empty($results)) return join("<br/>", $results);
  return true;
}

/**
 * Add grid_user role
 * @return [type] update success
 */
function w4os_update_2() {
  function w4os_update_custom_roles() {
    $role = 'grid_user';
    $role_name = __('Grid user', 'w4os');
    add_role( $role, $role_name, get_role( 'subscriber' )->capabilities );
    w4os_admin_notice(
      __(
        sprintf('Added %s role', '<strong>' . $role_name . '</strong>' ),
        'w4os',
      ),
      'success',
    );
  }
  add_action( 'init', 'w4os_update_custom_roles' );
  return true;
}

function w4os_update_3() {
  if(function_exists('w4os_profile_sync_all')) {
    add_action('admin_init', 'w4os_profile_sync_all');
  } else {
    w4os_admin_notice(__('Profiles service is not configured on your Robust server. It is required for full functionalities.', 'w4os' ),'error' );
  }
}
add_action('init', 'w4os_update_3');
