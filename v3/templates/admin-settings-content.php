<?php
/**
 * Template for the main settings page (v3).
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
echo '<form action="options.php" method="post">';
settings_fields( 'w4os_settings_beta' );
do_settings_sections( 'w4os_settings_beta' );
submit_button();
echo '</form>';
