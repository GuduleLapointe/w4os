<?php
/**
 * Region settings page content
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}
?>
<form method="post" action="options.php">
    <?php
        settings_fields( 'w4os_settings_region' );
        do_settings_sections( 'w4os-region-settings' );
        submit_button();
    ?>
</form>
