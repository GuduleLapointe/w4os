<?php if ( ! defined( 'W4OS_ADMIN' ) ) die;

$result = $w4osdb->get_results("SELECT Email as email, PrincipalID FROM UserAccounts
	WHERE active = 1
	AND Email is not NULL AND Email != ''
	AND FirstName != '" . get_option('w4os_model_firstname') . "'
	AND LastName != '" . get_option('w4os_model_lastname') . "'
	");
foreach (	$result as $row ) {
	$GridAccounts[$row->email] = (array)$row;
	$MergedAccounts[$row->email] = (array)$row;
}

$result = $wpdb->get_results("SELECT user_email as email, ID as user_id, m.meta_value AS w4os_uuid
	FROM $wpdb->users as u, $wpdb->usermeta as m
	WHERE u.ID = m.user_id AND m.meta_key = 'w4os_uuid' AND m.meta_value != '' AND m.meta_value != '" . W4OS_NULL_KEY . "'");

foreach (	$result as $row ) {
	$WPGridAccounts[$row->email] = (array)$row;
	$MergedAccounts[$row->email] = (!empty($MergedAccounts[$row->email])) ? $MergedAccounts[$row->email] = array_merge($MergedAccounts[$row->email], (array)$row) : (array)$row;
}

$count_wp_only = NULL;
$count_grid_only = NULL;
foreach ($MergedAccounts as $key => $account) {
	if(w4os_empty($account['PrincipalID'])) $account['PrincipalID'] = NULL;
	if(w4os_empty($account['w4os_uuid'])) $account['w4os_uuid'] = NULL;
	if($account['PrincipalID'] && $account['w4os_uuid'] && $account['PrincipalID'] == $account['w4os_uuid'])
	$count_sync += 1;
	else if($account['PrincipalID']) $count_grid_only += 1;
	else $count_wp_only += 1;
}

$count_models = $w4osdb->get_var("SELECT count(*) FROM UserAccounts
	WHERE FirstName = '" . get_option('w4os_model_firstname') . "'
	OR LastName = '" . get_option('w4os_model_lastname') . "'
	");

$count_tech = $w4osdb->get_var("SELECT count(*) FROM UserAccounts
	WHERE (Email IS NULL OR Email = '')
	AND FirstName != '" . get_option('w4os_model_firstname') . "'
	AND LastName != '" . get_option('w4os_model_lastname') . "'
	-- AND FirstName != 'GRID'
	-- AND LastName != 'SERVICE'
	");

$count_wp_users = count_users()['total_users'];
$count_wp_linked = count($WPGridAccounts);
$count_grid_accounts = count($GridAccounts);

?><div class="w4os-status-page wrap">
	<h1><?php echo esc_html(get_admin_page_title()); ?></h1>
	<p><?php echo W4OS_PLUGIN_NAME . " " . W4OS_VERSION ?></p>
	<div class=sync>

		<table class="w4os-table user-sync">
			<thead>
				<tr>
					<th><h2>
						<?php _e("Users", 'w4os') ?>
					</h2></th>
					<th><?php _e('Total', 'w4os');?></th>
					<th><?php if($count_wp_only > 0) _e('WP only', 'w4os');?></th>
					<th><?php _e('Sync', 'w4os');?></th>
					<th><?php if($count_grid_only > 0) _e('Grid only', 'w4os');?></th>
				</tr>
			</thead>
			<tr>
				<th><?php _e("Grid accounts", 'w4os') ?></th>
				<td><?php echo $count_grid_accounts; ?></td>
				<td></td>
				<td class=success rowspan=2><?php echo $count_sync; ?></td>
				<td class=error><?php echo $count_grid_only; ?></td>
			</tr>
			<tr>
				<th><?php _e("Linked WordPress accounts", 'w4os') ?></th>
				<td><?php echo $count_wp_linked; ?></td>
				<td class=error><?php echo $count_wp_only; ?></td>
			</tr>
			<tr>
				<th><?php _e("Avatar models", 'w4os') ?></th>
				<td><?php
					echo $count_models;
				?></td>
			</tr>
			<?php if($count_tech > 0) { ?>
			<tr>
				<th><?php _e("Other service accounts", 'w4os') ?></th>
				<td><?php
					echo $count_tech;
				?></td>
			</tr>
			<?php } ?>
		</table>
			<?php	if($count_wp_only + $count_grid_only > 0 ) { ?>
		<table class="w4os-table .notes">
			<tr class=notes>
				<th></th>
				<td>
						<?php
						if($count_grid_only  > 0 ) {
							echo '<p>' . sprintf(_n(
								'%d grid account has no linked WP account. Syncing will create a new WP account.',
								'%d grid accounts have no linked WP account. Syncing will create new WP accounts.',
								$count_grid_only,
								'w4os'), $count_grid_only) . '</p>';
						}
						if($count_wp_only  > 0 ) {
							echo '<p>' . sprintf(_n(
								'%d WordPress account is linked to an unexisting avatar (wrong UUID). Syncing accounts will keep this WP account but remove the avatar link.',
								'%d WordPress accounts are linked to unexisting avatars (wrong UUID). Syncing accounts will keep these WP accounts but remove their avatar link.',
								$count_wp_only,
								'w4os'), $count_wp_only) . '</p>';
						}
						if($count_tech > 0) {
							echo '<p>' . sprintf(_n(
								"%d grid account (other than models) has no email address, which is fine as long as it is used only for maintenance or service tasks.",
								"%s grid accounts (other than models) have no email address, which is fine as long as they are used only for maintenance or service tasks.",
								$count_tech,
								'w4os') . ' ' . __('Real accounts need a unique email address for W4OS to function properly.', 'w4os'), $count_tech) . '</p>';
						}
						// echo sprintf('<form action="%s"><button type=submit>%s</button>', '#', __('Sync WordPress and Grid users now', 'w4os'));
						echo '<form method="post" action="options.php" autocomplete="off">';
						settings_fields( 'w4os_status' );
						echo '<input type="hidden" input-hidden" id="w4os_sync_users" name="w4os_sync_users" value="1">';
						// do_settings_sections( 'w4os_status' );

						submit_button(__('Sync WordPress and Grid users now', 'w4os'));
						echo '</form>';
						?>
				</td>
			</tr>
		</table>
			<?php	} ?>

	</div>

	<div class=shortcodes>
		<h2>
			<?php _e("Available shortcodes", 'w4os') ?>
		</h2>
		<table class="w4os-table shortcodes">
			<tr><th>
				<code>[gridinfo]</code>
				<p><?php _e("General information (grid name and login uri)", 'w4os') ?></p>
			</th><td>
				<?php echo w4os_gridinfo_shortcode(); ?>
			</td></tr>
			<tr><th>
				<code>[gridstatus]</code>
				<p><?php _e("Online users, regions, etc.", 'w4os') ?></p>
			</th><td>
				<?php echo w4os_gridstatus_shortcode(); ?>
			</td></tr>
			<tr><th>
				<code>[gridprofile]</code>
				<p><?php _e("Grid profile if user is connected and has an avatar, avatar registration form otherwise", 'w4os') ?>
				<?php echo sprintf(__("(formerly %s)", 'w4os'), "<code>[w4os_profile]</code>"); ?></p>
			</th><td>
				<?php echo do_shortcode('[gridprofile]'); ?>
			</td></tr>
		</table>
	</div>

	<?php
	  if ( ! function_exists('curl_init') ) $php_missing_modules[]='curl';
	  if ( ! function_exists('simplexml_load_string') ) $php_missing_modules[]='xml';
		if(!empty($php_missing_modules)) {
			echo sprintf(
				'<div class="missing-modules warning"><h2>%s</h2>%s</div>',
				__('Missing PHP modules', 'w4os'),
				sprintf(
					__('These modules were not found: %s. Install them to get the most of this plugin.', 'w4os'),
					'<strong>' . join('</strong>, <strong>', $php_missing_modules) . '</strong>',
				),
			);
		}
	?>

</div>
