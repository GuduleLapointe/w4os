<div class="wrap">
	<!-- <h1>OpenSimulator</h1>	 -->
	<h1><?= esc_html(get_admin_page_title()); ?></h1>
	<p><?php echo $version_info; ?></p>
	<p>
		<dt><code>[gridinfo]</code> General information (grid name and login uri)</dt>
		<dd>
			<?php echo w4os_gridinfo_shortcode(); ?>
		</dd>
	</p>
	<p>
		<dt><code>[gridstatus]</code> Online users, regions, etc.</dt>
		<dd>		<?php echo w4os_gridstatus_shortcode(); ?></dd>
	</p>
</div>