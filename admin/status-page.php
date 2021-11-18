<?php if ( ! defined( 'W4OS_ADMIN' ) ) die;
/*
 * Wrong result, use w4os_grid_status_text() instead
 */
$GridAccounts=$w4osdb->get_results("SELECT PrincipalID, FirstName, LastName, profileImage, profileAboutText, Email
	FROM UserAccounts LEFT JOIN userprofile ON PrincipalID = userUUID	WHERE active = 1");
$count_users = count_users();
$count = array (
	'wp_total' => $count_users['total_users'],
	// 'wp_only' => '1',
	// 'grid_total' => count($GridAccounts),
	// 'grid_only' => '2',
	// 'synced' => '3',
)
?><div class="w4os-status-page wrap">
	<h1><?php echo esc_html(get_admin_page_title()); ?></h1>
	<p><?php echo W4OS_PLUGIN_NAME . " " . W4OS_VERSION ?></p>
	<div class=sync>
		<h2>
			<?php _e("Users", 'w4os') ?>
		</h2>
		<table class="w4os-table user-sync">
			<thead>
				<!-- <tr>
					<th></th>
					<th>Total</th>
					<th>WP only</th>
					<th>Sync'ed</th>
					<th>Grid only</th>
				</tr> -->
			</thead>
			<tr>
				<th><?php _e("WordPress accounts", 'w4os') ?></th>
				<td><?php echo $count['wp_total']; ?></td>
				<td class=error><?php echo $count['wp_only']; ?></td>
				<td class=success rowspan=2><?php echo $count['synced']; ?></td>
			</tr>
			<tr>
				<th><?php _e("Grid accounts", 'w4os') ?></th>
				<td><?php echo $count['grid_total']; ?></td>
				<td></td>
				<td class=error><?php echo $count['grid_only']; ?></td>
			</tr>
			<?php
			if($count['wp_only'] + $count['grid_only'] > 0 ) {
				echo sprintf('<caption><a href="%s">%s</a></caption>', '#', __('Sync WordPress and Grid users now', 'w4os'));
			}
			?>
		</table>
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
