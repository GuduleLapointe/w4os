<?php if ( ! defined( 'W4OS_PLUGIN' ) ) die;
/**
 * WooCommerce fixes
 *
 * @package	w4os
 * @author Olivier van Helden <olivier@van-helden.net>
 *
 * Remove Dashboard sections that have no content, to clarify My Account page.
 * As OpenSimulator is virtual-product oriented, no need for sections like
 * Adresses until the user actually buy something and gives a billing address)
 */

/**
 * Check if user has already bought something
 * @param  integer $user_id User ID
 * @return boolean          True if user bought something
 */
function w4os_has_bought( $user_id = 0 ) {
  if ( ! class_exists( 'woocommerce' ) ) return false;
  global $wpdb;

  $customer_id = $user_id == 0 ? get_current_user_id() : $user_id;
  $paid_order_statuses = array_map( 'esc_sql', wc_get_is_paid_statuses() );

  $results = $wpdb->get_col( "
  SELECT p.ID FROM {$wpdb->prefix}posts AS p
  INNER JOIN {$wpdb->prefix}postmeta AS pm ON p.ID = pm.post_id
  WHERE p.post_status IN ( 'wc-" . implode( "','wc-", $paid_order_statuses ) . "' )
  AND p.post_type LIKE 'shop_order'
  AND pm.meta_key = '_customer_user'
  AND pm.meta_value = $customer_id
  " );

  return count( $results ) > 0 ? true : false;
}

/**
 * Remove unneeded woocommerce menus
 * @param  array $menu_links [description]
 * @return array             updated menu links
 */
function w4os_remove_my_account_links( $menu_links ){
  $endpoint = WC()->query->get_current_endpoint();

  //unset( $menu_links['dashboard'] ); // Remove Dashboard
  // unset( $menu_links['payment-methods'] ); // Remove Payment Methods
  //unset( $menu_links['downloads'] ); // Disable Downloads
  //unset( $menu_links['edit-account'] ); // Remove Account details tab
  unset( $menu_links['edit-address'] ); // Addresses
  // unset( $menu_links['customer-logout'] ); // Remove Logout link

  // if(!w4os_has_bought()) {
    // unset( $menu_links['orders'] ); // Remove Orders
    // unset( $menu_links['subscriptions'] ); // Remove Subscriptions
  // }
  $linkname=$menu_links['edit-account'];
  unset( $menu_links['edit-account'] ); // Remove Account details tab
  $menu_links = array_slice( $menu_links, 0, 1, true )
	+ array( 'edit-account' => $linkname )
	+ array_slice( $menu_links, 1, NULL, true );

  return $menu_links;
}
// add_filter ( 'woocommerce_account_menu_items', 'w4os_remove_my_account_links' );


/**
 * Rename woocommerce download tab (not implemented yet)
 * @param  arrray $menu_links [description]
 * @return array              [description]
 */
function w4os_rename_downloads( $menu_links ){
  global $pagenow;
  // $menu_links['TAB ID HERE'] = 'NEW TAB NAME HERE';
  // $menu_links['orders'] = "- $pagenow -";
  return $menu_links;
}
// add_filter ( 'woocommerce_account_menu_items', 'w4os_rename_downloads' );


/**
 * Add Avatar link to woocommerce
 * Step 1. Add Link (Tab) to My Account menu
 * @param  array $menu_links [description]
 * @return array             [description]
 */
function w4os_log_history_link( $menu_links ){
	$menu_links = array_slice( $menu_links, 0, 2, true )
	+ array( 'avatar' => 'Avatar' )
	+ array_slice( $menu_links, 2, NULL, true );

	return $menu_links;
}
// add_filter ( 'woocommerce_account_menu_items', 'w4os_log_history_link', 40 );

/**
 * Add Avatar link to woocommerce
 * Step 2. Register Permalink Endpoint
 */
function w4os_add_endpoint() {
	// WP_Rewrite is my Achilles' heel, so please do not ask me for detailed explanation
	add_rewrite_endpoint( 'avatar', EP_PAGES );
}
add_action( 'init', 'w4os_add_endpoint' );

/**
 * Add Avatar link to woocommerce
 * Step 3. Content for the new page in My Account, woocommerce_account_{ENDPOINT NAME}_endpoint
 * @return void
 */
function w4os_my_account_endpoint_content() {
  require_once plugin_dir_path( __FILE__ ) . 'profile.php';
  $user = wp_get_current_user();
  echo w4os_profile_display( $user );
}
add_action( 'woocommerce_account_avatar_endpoint', 'w4os_my_account_endpoint_content' );

/**
 * Add filter for avatar to WooCommerce
 * @var array
 * @return array             [description]
 */
add_filter("woocommerce_get_query_vars", function ($vars) {
    foreach (["avatar"] as $e) {
        $vars[$e] = $e;
    }
    return $vars;
});

/**
 * Add Avatar link to woocommerce
 * Step 4:
 *  Go to Settings > Permalinks and just push "Save Changes" button.
 *
 *  To replace rough, dirty and not working "flush_rewrite_rules()" method.
 *  To implement in install section as soon as possible. Doesn't work anyway.
 */

/**
 * Catch password change from WooCommerceand save it to OpenSimulator
 * @param  integer $user_id
 */
add_action('woocommSerce_save_account_details', 'w4os_woocommerce_save_account_details', 10, 1);
function w4os_woocommerce_save_account_details ( $user_id ) {
	if($_REQUEST['password_1'] == $_REQUEST['password_2'])
	w4os_set_avatar_password( $user_id, $_REQUEST['password_1'] );
}

add_action('woocommerce_before_customer_login_form', 'w4os_verify_user', 5);
function w4os_verify_user() {
  if(!is_user_logged_in()) {
    if(isset($_GET['action']) && $_GET['action'] == 'verify_account') {
      $verify = 'false';
      if(isset($_GET['user_login']) && isset($_GET['key'])) {
        global $wpdb;
        $user = $wpdb->get_row($wpdb->prepare("select * from ".$wpdb->prefix."users where user_login = %s and user_activation_key = %s", $_GET['user_login'], $_GET['key']));
        $uuid = w4os_profile_sync($user); // refresh opensim data for this user
        if($uuid) {
          $salt = get_user_meta( $user->ID, 'w4os_tmp_salt', true );
          $hash = get_user_meta( $user->ID, 'w4os_tmp_hash', true );
          if( $salt && $hash ) {
            global $w4osdb;
            $w4osdb->update (
              'auth',
              array (
                'passwordHash'   => $hash,
                'passwordSalt'   => $salt,
                // 'webLoginKey' => W4OS_NULL_KEY,
              ),
              array (
                'UUID' => $uuid,
              )
            );
          }
        }
      }
    }
  }
}
