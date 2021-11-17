<div class="w4os-status-page wrap">
	<h1><?php echo esc_html(get_admin_page_title()); ?></h1>
	<p><?php echo W4OS_PLUGIN_NAME . " " . W4OS_VERSION ?></p>

	<div class=shortcodes>
		<h4>
			<?php _e("Available shortcodes", 'w4os') ?>
		</h4>
		<table class="shortcodes">
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
				'<div class="missing-modules warning"><h4>%s</h4>%s</div>',
				__('Missing PHP modules', 'w4os'),
				sprintf(
					__('These modules were not found: %s. Install them to get the most of this plugin.', 'w4os'),
					'<strong>' . join('</strong>, <strong>', $php_missing_modules) . '</strong>',
				),
			);
		}
	?>

</div>
