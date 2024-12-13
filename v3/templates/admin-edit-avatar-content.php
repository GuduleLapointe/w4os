<?php
/**
 * Avatar settings page content
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
?>
<form method="post" action="options.php">
	<?php
		settings_fields( 'w4os_settings_avatar' );
		do_settings_sections( 'w4os-avatar-settings' );
		submit_button();
	?>
</form>
