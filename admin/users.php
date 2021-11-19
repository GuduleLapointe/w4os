<?php if ( ! defined( 'W4OS_ADMIN' ) ) die;

function w4os_get_users_ids_and_uuids() {
		global $wpdb, $w4osdb;

		$result = $w4osdb->get_results("SELECT Email as email, PrincipalID FROM UserAccounts
			WHERE active = 1
			AND Email is not NULL AND Email != ''
			AND FirstName != '" . get_option('w4os_model_firstname') . "'
			AND LastName != '" . get_option('w4os_model_lastname') . "'
			");
		foreach (	$result as $row ) {
			$GridAccounts[$row->email] = (array)$row;
			$accounts[$row->email] = (array)$row;
		}

		$result = $wpdb->get_results("SELECT user_email as email, ID as user_id, m.meta_value AS w4os_uuid
			FROM $wpdb->users as u, $wpdb->usermeta as m
			WHERE u.ID = m.user_id AND m.meta_key = 'w4os_uuid' AND m.meta_value != '' AND m.meta_value != '" . W4OS_NULL_KEY . "'");

		foreach (	$result as $row ) {
			$WPGridAccounts[$row->email] = (array)$row;
			$accounts[$row->email] = (!empty($accounts[$row->email])) ? $accounts[$row->email] = array_merge($accounts[$row->email], (array)$row) : (array)$row;
		}

		return $accounts;
}

function w4os_count_users() {
  global $wpdb, $w4osdb;

  $accounts = w4os_get_users_ids_and_uuids();

  $count['wp_users'] = count_users()['total_users'];
  $count['grid_accounts'] = 0;
  $count['wp_linked'] = 0;
  $count['wp_only'] = NULL;
  $count['grid_only'] = NULL;
  foreach ($accounts as $key => $account) {
    if(w4os_empty($account['PrincipalID'])) $account['PrincipalID'] = NULL;
    else $count['grid_accounts']++;
    if(w4os_empty($account['w4os_uuid'])) $account['w4os_uuid'] = NULL;
    else $count['wp_linked']++;
    if($account['PrincipalID'] && $account['w4os_uuid'] && $account['PrincipalID'] == $account['w4os_uuid'])
    $count['sync']++;
    else if($account['PrincipalID']) $count['grid_only'] += 1;
    else $count['wp_only']++;
  }

  $count['models'] = $w4osdb->get_var("SELECT count(*) FROM UserAccounts
  WHERE FirstName = '" . get_option('w4os_model_firstname') . "'
  OR LastName = '" . get_option('w4os_model_lastname') . "'
  ");

  $count['tech'] = $w4osdb->get_var("SELECT count(*) FROM UserAccounts
  WHERE (Email IS NULL OR Email = '')
  AND FirstName != '" . get_option('w4os_model_firstname') . "'
  AND LastName != '" . get_option('w4os_model_lastname') . "'
  AND FirstName != 'GRID'
  AND LastName != 'SERVICE'
  ");
  return $count;
}


function w4os_sync_users() {
  $accounts = w4os_get_users_ids_and_uuids();
  foreach ($accounts as $key => $account) {
    // First cleanup NULL_KEY and other empty UUIDs
    if(w4os_empty($account['PrincipalID'])) $account['PrincipalID'] = NULL;
    if(w4os_empty($account['w4os_uuid'])) $account['w4os_uuid'] = NULL;

    if($account['PrincipalID'] && $account['w4os_uuid'] && $account['PrincipalID'] == $account['w4os_uuid']) {
      // already linked, no action needed
      // $count['no action']++;
    } else if($account['PrincipalID'] && $account['w4os_uuid'] && $account['PrincipalID'] != $account['w4os_uuid']) {
        // Nwrong reference, but an avatar exists for this WP user, fix reference and resync
        $count['delete ref and resync']++;
    } else if($account['PrincipalID']) {
      // no WP account for this avatar or wrong reference
      $count['create wp account']++;
    } else {
      // no such avatar, delete reference
      $count['remove avatar ref']++;
    }
  }
  $count['delete ref and resync'] = 1;
  if($count['create wp account']) $messages[] = sprintf(_n(
    '%d new WordPress account created',
    '%d new WordPress accounts created',
    $count['create wp account'],
    'w4os',
  ), $count['create wp account']);
  if($count['delete ref and resync']) $messages[] = sprintf(_n(
    '%d reference updated',
    '%d references updated',
    $count['delete ref and resync'],
    'w4os',
  ), $count['delete ref and resync']);
  if($count['remove avatar ref']) $messages[] = sprintf(_n(
    '%d broken reference removed',
    '%d broken references removed',
    $count['remove avatar ref'],
    'w4os',
  ), $count['remove avatar ref']);
  if($messages) w4os_admin_notice(join(', ', $messages) . ' <em>(debug mode, no actual action taken)</em>.' );
  else w4os_admin_notice('<pre>' . print_r($count, true) . '</pre>');
  update_option('w4os_sync_users', NULL);
}

if(get_option('w4os_sync_users')) w4os_sync_users();
