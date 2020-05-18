<?php
/*
 *
 */

// Check if user has already bought something

function has_bought( $user_id = 0 ) {
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

  // Count number of orders and return a boolean value depending if higher than 0
  return count( $results ) > 0 ? true : false;
}

// Remove unneeded woocommerce menus

function w4os_remove_my_account_links( $menu_links ){
  $endpoint = WC()->query->get_current_endpoint();

  //unset( $menu_links['dashboard'] ); // Remove Dashboard
  // unset( $menu_links['payment-methods'] ); // Remove Payment Methods
  //unset( $menu_links['downloads'] ); // Disable Downloads
  //unset( $menu_links['edit-account'] ); // Remove Account details tab
  unset( $menu_links['edit-address'] ); // Addresses
  // unset( $menu_links['customer-logout'] ); // Remove Logout link

  // if(!has_bought()) {
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
add_filter ( 'woocommerce_account_menu_items', 'w4os_remove_my_account_links' );


// Rename woocommerce menus

function w4os_rename_downloads( $menu_links ){
  global $pagenow;
  // $menu_links['TAB ID HERE'] = 'NEW TAB NAME HERE';
  // $menu_links['orders'] = "- $pagenow -";
  return $menu_links;
}
add_filter ( 'woocommerce_account_menu_items', 'w4os_rename_downloads' );


// Add woocommerce menu and page

/*
 * Step 1. Add Link (Tab) to My Account menu
 */
add_filter ( 'woocommerce_account_menu_items', 'w4os_log_history_link', 40 );
function w4os_log_history_link( $menu_links ){

	$menu_links = array_slice( $menu_links, 0, 2, true )
	+ array( 'avatar' => 'Avatar' )
	+ array_slice( $menu_links, 2, NULL, true );

	return $menu_links;

}

/*
 * Step 2. Register Permalink Endpoint
 */
add_action( 'init', 'w4os_add_endpoint' );
function w4os_add_endpoint() {

	// WP_Rewrite is my Achilles' heel, so please do not ask me for detailed explanation
	add_rewrite_endpoint( 'avatar', EP_PAGES );

}
/*
 * Step 3. Content for the new page in My Account, woocommerce_account_{ENDPOINT NAME}_endpoint
 */
function w4os_my_account_endpoint_content() {
  $user = wp_get_current_user();
  echo w4os_profile_wc_edit( $user );
}
add_action( 'woocommerce_account_avatar_endpoint', 'w4os_my_account_endpoint_content' );

add_filter("woocommerce_get_query_vars", function ($vars) {
    foreach (["avatar"] as $e) {
        $vars[$e] = $e;
    }
    return $vars;
});

/*
 * Step 4
 */
// Go to Settings > Permalinks and just push "Save Changes" button.
// rough and dirty method. Move that to install section as soon as possible. Doesn't work anyway
// flush_rewrite_rules();
