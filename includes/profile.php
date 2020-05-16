<?php
// require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class.php';

add_action( 'show_user_profile', 'w4os_profile_fields' );
add_action( 'edit_user_profile', 'w4os_profile_fields' );
add_action( 'personal_options_update', 'w4os_profile_fields_save' );
add_action( 'edit_user_profile_update', 'w4os_profile_fields_save' );

function w4os_profile_fields( $user ) {
  global $w4osdb;
  $uuid = get_the_author_meta( 'w4os_uuid', $user->ID );
  if(empty($uuid)) {
    $uuid = $w4osdb->get_var("SELECT PrincipalID FROM UserAccounts WHERE Email = '$user->user_email'");
    if(!empty($uuid)) update_user_meta( $user->ID, 'w4os_uuid', $uuid );
  }
  if(!empty($uuid)) {
    if(empty(get_the_author_meta( 'w4os_firstname', $user->ID )) && empty(get_the_author_meta( 'w4os_lastname', $user->ID ))  ) {
      update_user_meta( $user->ID, 'w4os_firstname', $w4osdb->get_var("SELECT FirstName FROM UserAccounts WHERE PrincipalID = '$uuid'") );
      update_user_meta( $user->ID, 'w4os_lastname', $w4osdb->get_var("SELECT LastName FROM UserAccounts WHERE PrincipalID = '$uuid'") );
    }
  }
   ?>
    <h3><?php _e("OpenSimulator", "blank"); ?></h3>

    <table class="form-table">
    <tr>
        <th><label for="w4os_uuid"><?php _e("Avatar UUID"); ?></label></th>
        <td>
            <?php echo esc_attr( get_the_author_meta( 'w4os_uuid', $user->ID ) ); ?>
        </td>
    </tr>
    <tr>
        <th><label for="w4os_firstname"><?php _e("Avatar name"); ?></label></th>
        <td>
          <?php echo esc_attr( get_the_author_meta( 'w4os_firstname', $user->ID ) ) . " " . esc_attr( get_the_author_meta( 'w4os_lastname', $user->ID ) ); ?>
        </td>
    </tr>
    <tr>
        <th><label for="w4os_dummy"><?php _e("Useless parameter"); ?></label></th>
        <td>
            <input type="text" name="w4os_dummy" id="w4os_dummy" value="<?php echo esc_attr( get_the_author_meta( 'w4os_dummy', $user->ID ) ); ?>" class="regular-text" /><br />
            <span class="description"><?php _e("I mean it. It's not even stored. I only want to keep the code on hand."); ?></span>
        </td>
    </tr>
    <tr>
    </table>
<?php }

function w4os_profile_edit( $user ) {
  global $w4osdb;
  $content.="<h3>" . _("OpenSimulator profile") . "</h3>";
  $content.='<table class="form-table">'
  . '<tr>'
  . '<th>' . _("Avatar UUID") . '</th>'
  . '<td>' . get_the_author_meta( 'w4os_uuid', $user->ID ) . '</td>'
  . '</tr>'
  . '<tr>'
  . '<th>' . _("Avatar name") . '</th>'
  . '<td>' . get_the_author_meta( 'w4os_firstname', $user->ID ) . " " . get_the_author_meta( 'w4os_lastname', $user->ID ) . '</td>'
  . '</tr>'
  . '<tr>'
  . '<th><label for="w4os_dummy">' . _("Useless parameter") . '</label></th>'
  . '<td><input type="text" name="w4os_dummy" id="w4os_dummy" value="' . esc_attr( get_the_author_meta( 'w4os_dummy', $user->ID ) ) . '" class="regular-text" /></td>'
  . '</tr>'
  . '</table>';
  return $content;
}

function w4os_profile_fields_save( $user_id ) {
    if ( !current_user_can( 'edit_user', $user_id ) ) {
        return false;
    }
    // update_user_meta( $user_id, 'w4os_uuid', $_POST['w4os_uuid'] );
    // update_user_meta( $user_id, 'w4os_firstname', $_POST['w4os_firstname'] );
    // update_user_meta( $user_id, 'w4os_lastname', $_POST['w4os_lastname'] );
}

function w4os_profile_shortcodes_init()
{
	function w4os_profile_shortcode($atts = [], $content = null)
	{
    $current_user = wp_get_current_user();
    $content .= w4os_profile_edit($current_user);
    // $content .= "<pre>" . print_r($atts, true) . "</pre>";
    // $content .= "<pre>" . print_r($current_user, true) . "</pre>";
		return $content;
	}
	add_shortcode('w4os_profile', 'w4os_profile_shortcode');
}
add_action('init', 'w4os_profile_shortcodes_init');
