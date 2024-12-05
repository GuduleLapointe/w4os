<?php
/**
 * Avatar settings page content
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

echo '<form action="options.php" method="post">';
do_settings_sections( 'w4os_settings_avatar' );
settings_fields( 'w4os_settings_avatar' );
submit_button();
echo '</form>';
?>
