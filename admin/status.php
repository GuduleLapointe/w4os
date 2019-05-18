<div class="wrap">
	<!-- <h1>OpenSimulator</h1>	 -->
	<h1><?= esc_html(get_admin_page_title()); ?></h1>
  <h2><?php echo __("Grid info") ?></h2>
  <?php echo opensim_gridinfo_shortcode(); ?>
  <h2><?php echo __("Grid status") ?></h2>
  <?php echo opensim_gridstatus_shortcode(); ?>
</div>
