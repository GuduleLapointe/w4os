<?php if ( ! defined( 'WPINC' ) ) die;

if ( ! defined( 'W4OS_UPDATES' ) ) define('W4OS_UPDATES', 1 );

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
 * Rewrite rules to make assets/ permalink functional
 */
function w4os_update_1() {
  global $wpdb;
  // $results=array();
  update_option('w4os_rewrite_rules', true);
  // if(!empty($results)) return join("<br/>", $results);
  return true;
}
