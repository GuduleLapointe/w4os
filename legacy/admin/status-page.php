<?php if ( ! defined( 'W4OS_ADMIN' ) ) {
	die;}

$count = w4os_count_users();

// Note for future me, count broken assets
// SELECT inventoryname, inventoryID, assetID, a.id FROM inventoryitems LEFT JOIN assets AS a ON id = assetID WHERE a.id IS NULL;

?><div class="w4os-status-page wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<p><?php echo W4OS_PLUGIN_NAME . ' ' . W4OS_VERSION; ?></p>
	<?php
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
		// TRANSLATORS: The text inside the square brackets [] will be linked to the WordPress permalink settings page.
		$errors[] = preg_replace(
			'/\[(.*)\]/',
			'<a href="' . admin_url( 'options-permalink.php' ) . '">$1</a>',
			__( "Permalinks are not enabled. Choose any permalink structure other than 'Plain' in the [permalink settings page].</a>", 'w4os' ),
		);
	}
	if ( ! empty( $errors ) ) {
		printf(
			'<style>
		</style>'
		);
		printf(
			'<div class="warning error notice notice-error"><h3>%s</h3><ul class=warning-list><li>%s</li></ul></div>',
			__( 'Requirements not met for w4os plugin', 'w4os' ),
			join( '</li><li>', $errors ),
		);
	}
	?>
	<div class=content>
	<?php if ( W4OS_DB_CONNECTED ) { ?>
		<div class=column>
		<div class=sync>
			<h2><?php _e( 'Users', 'w4os' ); ?></h2>
			<table class="w4os-table user-sync">
				<thead>
					<tr>
						<th></th>
						<th><?php _e( 'Total', 'w4os' ); ?></th>
						<th>
						<?php
						if ( $count['wp_only'] > 0 ) {
							_e( 'WP only', 'w4os' );}
						?>
						</th>
						<th><?php _e( 'Sync', 'w4os' ); ?></th>
						<th>
						<?php
						if ( $count['grid_only'] > 0 ) {
							_e( 'Grid only', 'w4os' );}
						?>
						</th>
					</tr>
				</thead>
				<tr>
					<th><?php _e( 'Grid accounts', 'w4os' ); ?></th>
					<td><?php echo $count['grid_accounts']; ?></td>
					<td></td>
					<td class=success rowspan=2><?php echo $count['sync']; ?></td>
					<td class=error><?php echo $count['grid_only']; ?></td>
				</tr>
				<tr>
					<th><?php _e( 'Linked WordPress accounts', 'w4os' ); ?></th>
					<td><?php echo $count['wp_linked']; ?></td>
					<td class=error><?php echo $count['wp_only']; ?></td>
				</tr>
				<tr>
					<th><?php _e( 'Avatar Models', 'w4os' ); ?></th>
					<td>
					<?php
					echo $count['models'];
					?>
					</td>
				</tr>
				<?php if ( $count['tech'] > 0 ) { ?>
					<tr>
						<th><?php _e( 'Other service accounts', 'w4os' ); ?></th>
						<td>
						<?php
						echo $count['tech'];
						?>
						</td>
					</tr>
				<?php } ?>
			</table>
			<?php	if ( $count['wp_only'] + $count['grid_only'] > 0 | ! empty( $sync_result ) ) { ?>
				<table class="w4os-table .notes">
					<tr class=notes>
						<th></th>
						<td>
						<?php
						if ( $count['grid_only'] > 0 ) {
							echo '<p>' . W4OS::sprintf_safe(
								_n(
									'%d grid account has no linked WP account. Syncing will create a new WP account.',
									'%d grid accounts have no linked WP account. Syncing will create new WP accounts.',
									$count['grid_only'],
									'w4os'
								),
								$count['grid_only']
							) . '</p>';
						}
						if ( $count['wp_only'] > 0 ) {
							echo '<p>' . W4OS::sprintf_safe(
								_n(
									'%d WordPress account is linked to an unexisting avatar (wrong UUID). Syncing accounts will keep this WP account but remove the broken reference.',
									'%d WordPress accounts are linked to unexisting avatars (wrong UUID). Syncing accounts will keep these WP accounts but remove the broken reference.',
									$count['wp_only'],
									'w4os'
								),
								$count['wp_only']
							) . '</p>';
						}
						if ( $count['tech'] > 0 ) {
							echo '<p>' . W4OS::sprintf_safe(
								_n(
									'%d grid account (other than models) has no email address, which is fine as long as it is used only for maintenance or service tasks.',
									'%d grid accounts (other than models) have no email address, which is fine as long as they are used only for maintenance or service tasks.',
									$count['tech'],
									'w4os'
								) . ' ' . __(
									'Real accounts need a unique email address for W4OS to function properly.',
									'w4os'
								),
								$count['tech']
							) . '</p>';
						}
						if ( $count['grid_only'] + $count['wp_only'] > 0 ) {
							echo '<form method="post" action="options.php" autocomplete="off">';
							settings_fields( 'w4os_status' );
							echo '<input type="hidden" input-hidden" id="w4os_sync_users" name="w4os_sync_users" value="1">';

							submit_button( __( 'Synchronize users now', 'w4os' ) );
							echo '</form>';
							echo '<p class=description>' . __( 'Synchronization is made at plugin activation and is handled automatically afterwards, but in certain circumstances it may be necessary to initiate it manually to get an immediate result, especially if users have been added or deleted directly from the grid administration console.', 'w4os' ) . '<p>';
						}
						if ( ! empty( $sync_result ) ) {
							echo '<p class=info>' . $sync_result . '<p>';
						}
						?>
						</td>
					</tr>
				</table>
			<?php	} ?>
		</div>
	<?php } ?>
	<div class="pages">
		<h2>
			<?php _e( 'OpenSimulator pages', 'w4os' ); ?>
		</h2>
		<p><?php _e( 'The following page are needed by OpenSimulator and/or by W4OS plugin. Make sure they exist or adjust your simulator .ini file.', 'w4os' ); ?></p>
		<?php

		$grid_info            = W4OS_GRID_INFO;
		$grid_info['profile'] = W4OS_LOGIN_PAGE;

		$grid_running = w4os_grid_running();
		if ( ! w4os_grid_running() ) {
			$other_attributes['disabled'] = true;
			echo '<p class="notice warning">' . W4OS::sprintf_safe(
				__( 'Grid not running, start Robust server or %1$scheck OpenSimulator settings%2$s.', 'w4os' ),
				'<a href="' . get_admin_url( '', 'admin.php?page=w4os_settings' ) . '">',
				'</a>',
			);
		}

		$required = W4OS_PAGES;
		echo '<table class="w4os-table requested-pages">';
		echo '<tr>';
		echo '<td>' . w4os_status_icon( $grid_running ) . '</td>';
		echo '<td>';
		echo W4OS::sprintf_safe(
			'<a class=button href="%s">%s</a>',
			admin_url(
				'admin.php?' . wp_nonce_url(
					http_build_query(
						array(
							'page'   => 'w4os',
							'action' => 'w4os_check_urls_now',
						)
					),
					'w4os_check_urls_now',
				)
			),
			__( 'Check pages now', 'w4os' ),
		);
		echo '</td>';
		echo '<td>';
		echo '<p class=description>' . W4OS::sprintf_safe( __( 'Last checked %s ago.', 'w4os' ), human_time_diff( get_transient( 'w4os_get_url_status_checked' ) ) ) . '</p>';
		echo '<p class=description>' . __( 'OpenSimulator pages are checked regularly in the background. Synchronize now only if you made changes and want an immediate status.', 'w4os' ) . '<p>';
		echo '</td></tr>';

		if ( $grid_running ) {
			foreach ( $required as $key => $data ) {
				$url = ( ! empty( $grid_info[ $key ] ) ) ? $grid_info[ $key ] : '';
				// if (empty($grid_info[$key]) ) $url = "''";
				// else $url = W4OS::sprintf_safe('<a href="%1$s" target=_blank>%1$s</a>', $grid_info[$key]);
				$success     = w4os_get_url_status( $url, 'boolean' );
				$status_icon = w4os_get_url_status( $url, 'icon' );
				echo W4OS::sprintf_safe(
					'<tr>
				<td>%2$s</td>
				<td><h3>%1$s</h3>%3$s%4$s%5$s%7$s</td>
				<td>%6$s</td>
				</tr>
				',
					$data['name'],
					$status_icon,
					( ! empty( $url ) ) ? W4OS::sprintf_safe( '<p class=url><a href="%1$s">%1$s</a></p>', $url ) : '',
					( ! empty( $data['description'] ) ) ? '<p class=description>' . $data['description'] . '</p>' : '',
					( ! empty( $data['recommended'] ) && $url != $data['recommended'] ) ? '<p class=warning><span class="w4os-status dashicons dashicons-warning"></span> '
					. W4OS::sprintf_safe( __( 'Should be %1$s, got %2$s', 'w4os' ), $data['recommended'], $url ) . '</p>' : '',
					( ! empty( $data['os_config'] ) )
					? W4OS::sprintf_safe( w4os_format_ini( $data['os_config'] ), ( ! empty( $data['recommended'] ) ) ? $data['recommended'] : $url )
					: '',
					( $success == false && ( ! empty( $data['third_party_url'] ) )
					? '<p class=third_party>'
					. __( 'This service requires a separate web application.', '<w4os>' )
					. (
						empty( $data['third_party_url'] ) ? null :
						' ' . W4OS::sprintf_safe(
							__( 'Try %s', '<w4os>' ),
							preg_replace( '/%url%/', $data['third_party_url'], '<a href="%url%" target=_blank>%url%</a>' ),
						)
					)
					. '</p>'
					: ( ( $success == false && empty( $data['settings_page_url'] ) && ! empty( $url ) )
					? '<a class=button href="' . admin_url(
						W4OS::sprintf_safe(
							'admin.php?%s',
							wp_nonce_url(
								http_build_query(
									array(
										'page'   => 'w4os',
										'action' => 'create_page',
										'helper' => $key,
										'guid'   => $url,
										'slug'   => basename(
											strtok(
												$url,
												'?'
											)
										),
									)
								),
								'create_page_' . $key
							)
						)
					) . '">'
						. W4OS::sprintf_safe(
							__( 'Create %s page', 'w4os' ),
							$data['name'],
						) . '</a>'
					// : ''
					: ( ! empty( $data['settings_page_url'] )
					? sprintf( '<a href="%s">%s</a>', $data['settings_page_url'], __( 'Settings', 'w4os' ) )
					: ''
					) )
					),
				);
			}
		}
		echo '</table>';
		?>
		</div>
	</div>
	</div>
</div>
