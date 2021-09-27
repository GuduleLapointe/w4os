<div class="w4os-status-page wrap">
	<h1><?= esc_html(get_admin_page_title()); ?></h1>
	<p><?= W4OS_PLUGIN_NAME . " " . W4OS_VERSION ?></p>
	<!-- <h1>OpenSimulator</h1>	 -->
	<p>
		<?php echo w4os_gridinfo_shortcode(); ?>
		<?php echo w4os_gridstatus_shortcode(); ?>
	</p>
	<p>
		<h4>
			<?php _e("Available shortcodes", 'w4os') ?>
		</h4>
		<dt>
			<code>[gridinfo]</code>
		</dt>
		<dd>
			<?php _e("General information (grid name and login uri)", 'w4os') ?>
		</dd>
		<dt>
			<code>[gridstatus]</code>
		</dt>
		<dd>
			<?php _e("Online users, regions, etc.", 'w4os') ?>
		</dd>
		<dt>
			<code>[gridprofile]</code>
		</dt>
		<dd>
			<?php _e("Grid profile if user is connected and has an avatar, avatar registration form otherwise", 'w4os') ?>
			<?php echo sprintf(__("(formerly %s)", 'w4os'), "<code>[w4os_profile]</code>"); ?>
		</dd>
	</p>
</div>
