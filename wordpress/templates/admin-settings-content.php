<?php
/**
 * Template for the main settings page (v3).
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
printf(
	'<form action="options.php" method="post">
	<input type="hidden" name="%s[%s][prevent-empty-array]" value="1">',
	esc_attr( $option_name ),
	esc_attr( $selected_tab ),
);
settings_fields( $option_group );
do_settings_sections( $menu_slug );
submit_button();
echo '</form>';
