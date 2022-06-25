<?php if ( ! defined( 'W4OS_ADMIN' ) ) die;

$count = w4os_count_users();

// Note for future me, count broken assets
// SELECT inventoryname, inventoryID, assetID, a.id FROM inventoryitems LEFT JOIN assets AS a ON id = assetID WHERE a.id IS NULL;

?><div class="w4os-status-page wrap">
	<h1><?php echo esc_html(get_admin_page_title()); ?></h1>
	<p><?php echo W4OS_PLUGIN_NAME . " " . W4OS_VERSION ?></p>
	<?php
	if(!function_exists('xmlrpc_encode_request')) {
		printf(
			'<div class="warning error notice notice-error"><p>%s</p></div>',
			__('PHP xml-rpc is required but is not installed.', 'w4os'),
		);
	}
 	?>
	<div class=content>
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
						<?php echo sprintf(__("(formerly %s)", 'w4os'), "<code>[w4os_profile]</code>"); ?>
					</p>
				</th><td>
					<?php echo do_shortcode('[gridprofile]'); ?>
				</td></tr>
			</table>
		</div>
	<?php if(W4OS_DB_CONNECTED) { ?>
		<div class=column>
		<div class=sync>
			<h2><?php _e("Users", 'w4os') ?></h2>
			<table class="w4os-table user-sync">
				<thead>
					<tr>
						<th></th>
						<th><?php _e('Total', 'w4os');?></th>
						<th><?php if($count['wp_only'] > 0) _e('WP only', 'w4os');?></th>
						<th><?php _e('Sync', 'w4os');?></th>
						<th><?php if($count['grid_only'] > 0) _e('Grid only', 'w4os');?></th>
					</tr>
				</thead>
				<tr>
					<th><?php _e("Grid accounts", 'w4os') ?></th>
					<td><?php echo $count['grid_accounts']; ?></td>
					<td></td>
					<td class=success rowspan=2><?php echo $count['sync']; ?></td>
					<td class=error><?php echo $count['grid_only']; ?></td>
				</tr>
				<tr>
					<th><?php _e("Linked WordPress accounts", 'w4os') ?></th>
					<td><?php echo $count['wp_linked']; ?></td>
					<td class=error><?php echo $count['wp_only']; ?></td>
				</tr>
				<tr>
					<th><?php _e("Avatar models", 'w4os') ?></th>
					<td><?php
					echo $count['models'];
					?></td>
				</tr>
				<?php if($count['tech'] > 0) { ?>
					<tr>
						<th><?php _e("Other service accounts", 'w4os') ?></th>
						<td><?php
						echo $count['tech'];
						?></td>
					</tr>
				<?php } ?>
			</table>
			<?php	if($count['wp_only'] + $count['grid_only'] > 0 |! empty($sync_result)) { ?>
				<table class="w4os-table .notes">
					<tr class=notes>
						<th></th>
						<td>
						<?php
						if($count['grid_only']  > 0 ) {
							echo '<p>' . sprintf(_n(
								'%d grid account has no linked WP account. Syncing will create a new WP account.',
								'%d grid accounts have no linked WP account. Syncing will create new WP accounts.',
								$count['grid_only'],
								'w4os'
							), $count['grid_only']) . '</p>';
						}
						if($count['wp_only']  > 0 ) {
							echo '<p>' . sprintf(_n(
								'%d WordPress account is linked to an unexisting avatar (wrong UUID). Syncing accounts will keep this WP account but remove the broken reference.',
								'%d WordPress accounts are linked to unexisting avatars (wrong UUID). Syncing accounts will keep these WP accounts but remove the broken reference.',
								$count['wp_only'],
								'w4os'
							), $count['wp_only']) . '</p>';
						}
						if($count['tech'] > 0) {
							echo '<p>' . sprintf(_n(
								"%d grid account (other than models) has no email address, which is fine as long as it is used only for maintenance or service tasks.",
								"%d grid accounts (other than models) have no email address, which is fine as long as they are used only for maintenance or service tasks.",
								$count['tech'],
								'w4os'
								) . ' ' . __('Real accounts need a unique email address for W4OS to function properly.', 'w4os'
							), $count['tech']) . '</p>';
						}
						if($count['grid_only'] + $count['wp_only'] > 0) {
							echo '<form method="post" action="options.php" autocomplete="off">';
							settings_fields( 'w4os_status' );
							echo '<input type="hidden" input-hidden" id="w4os_sync_users" name="w4os_sync_users" value="1">';

							submit_button(__('Synchronize users now', 'w4os'));
							echo '</form>';
							echo '<p class=description>' . __('Synchronization is made at plugin activation and is handled automatically afterwards, but in certain circumstances it may be necessary to initiate it manually to get an immediate result, especially if users have been added or deleted directly from the grid administration console.', 'w4os') . '<p>';
						}
						if($sync_result)
						echo '<p class=info>' . $sync_result . '<p>';
							?>
						</td>
					</tr>
				</table>
			<?php	} ?>
		</div>
	<?php } ?>
	<?php
	  if ( ! function_exists('curl_init') ) $php_missing_modules[]='curl';
	  if ( ! function_exists('simplexml_load_string') ) $php_missing_modules[]='xml';
		if (!extension_loaded('imagick')) $php_missing_modules[]='imagick';
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
	<div class="pages">
		<h2>
			<?php _e("OpenSimulator pages", 'w4os') ?>
		</h2>
		<p><?php _e("The following page are needed by OpenSimulator and/or by W4OS plugin. Make sure they exist or adjust your simulator .ini file.", 'w4os'); ?></p>
		<?php

		$grid_info = W4OS_GRID_INFO;
		$grid_info['profile'] = W4OS_LOGIN_PAGE;

		$grid_running = w4os_grid_running();
		if(!w4os_grid_running()) {
			$other_attributes['disabled'] = true;
			echo '<p class="notice warning">' . sprintf(
				__('Grid not running, start Robust server or %scheck OpenSimulator settings%s.', 'w4os'),
				'<a href="' . get_admin_url('', 'admin.php?page=w4os_settings') . '">',
				'</a>',
			);
		}

		$required = W4OS_PAGES;
		echo '<table class="w4os-table requested-pages">';
		echo '<tr>';
		echo '<td>'. w4os_status_icon($grid_running) . '</td>';
		echo '<td>';
		echo sprintf(
			'<a class=button href="%s">%s</a>',
			admin_url('admin.php?' . wp_nonce_url(
				http_build_query(array(
					'page'=>'w4os',
					'action' => 'w4os_check_urls_now',
				)),
				'w4os_check_urls_now',
			)),
			__('Check pages now', 'w4os'),
		);
		echo '</td>';
		echo '<td>';
		echo '<p class=description>' . sprintf(__('Last checked %s ago.', 'w4os'), human_time_diff(get_transient('w4os_get_url_status_checked') )) . '</p>';
		echo '<p class=description>' . __('OpenSimulator pages are checked regularly in the background. Synchronize now only if you made changes and want an immediate status.', 'w4os') . '<p>';
		echo '</td></tr>';

		if($grid_running)
		foreach($required as $key => $data) {
			$url = (!empty($grid_info[$key])) ? $grid_info[$key] : '';
			// if (empty($grid_info[$key]) ) $url = "''";
			// else $url = sprintf('<a href="%1$s" target=_blank>%1$s</a>', $grid_info[$key]);
		 	$success = w4os_get_url_status($url, 'boolean');
			$status_icon = w4os_get_url_status($url, 'icon');
			echo sprintf(
				'<tr>
				<td>%2$s</td>
				<td><h3>%1$s</h3>%3$s%4$s%5$s%7$s</td>
				<td>%6$s</td>
				</tr>
				',
				$data['name'],
				$status_icon,
				(!empty($url)) ? sprintf('<p class=url><a href="%1$s">%1$s</a></p>', $url) : '',
				(!empty($data['description'])) ? '<p class=description>' . $data['description'] . '</p>' : '',
				(!empty($data['recommended']) && $url != $data['recommended']) ? '<p class=warning><span class="w4os-status dashicons dashicons-warning"></span> ' . sprintf(__('Should be %s', 'w4os'), $data['recommended']) . '</p>' : '',
				(!empty($data['os_config']))
				? sprintf(w4os_format_ini($data['os_config']),(!empty($data['recommended'])) ? $data['recommended'] : $url)
			 	: '',
				( $success == false && (!empty($data['third_party_url']))
				? '<p class=third_party>' .
				sprintf(__('This service requires a separate web application.<br>Try <a href="%1$s" target=_blank>%1$s</a>.', '<w4os>'),
				$data['third_party_url'],
				) . '</p>'
				: ( ( $success == false && (!empty($url)) )
					? '<a class=button href="' . admin_url(sprintf('admin.php?%s', wp_nonce_url(http_build_query(array('page'=>'w4os', 'action' => 'create_page', 'helper' => $key, 'guid' => $url, 'slug' => basename(strtok($url, '?')) )), 'create_page_'.$key))) . '">'
						. sprintf(
							'Create %s page',
							$data['name'],
						) . '</a>'
					: ''
					)
				),
			);
		}
		echo '</table>';
		?>
		</div>
	</div>
	</div>
</div>
