<?php
/**
 * Region settings page content
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

$current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'general';

?>
<h2 class="nav-tab-wrapper">
	<a href="?page=w4os-region-settings" class="nav-tab <?php echo $current_tab === 'general' ? 'nav-tab-active' : ''; ?>">
		<?php _e( 'General', 'w4os' ); ?>
	</a>
	<a href="?page=w4os-region-settings&tab=advanced" class="nav-tab <?php echo $current_tab === 'advanced' ? 'nav-tab-active' : ''; ?>">
		<?php _e( 'Advanced', 'w4os' ); ?>
	</a>
</h2>
<form method="post" action="options.php">
	<?php
		settings_fields( $current_section );
		do_settings_sections( $page );
		submit_button();
	?>
</form>
