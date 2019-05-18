<div class="wrap">
	<!-- <h1>OpenSimulator</h1>	 -->
	<h1><?= esc_html(get_admin_page_title()); ?></h1>
  <h2><?php echo __("Grid info") ?></h2>
  <?php echo w4os_gridinfo_shortcode(); ?>
  <h2><?php echo __("Grid status") ?></h2>
  <?php echo w4os_gridstatus_shortcode(); ?>
</div>
