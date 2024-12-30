<?php
/**
 * Settings page template.
 */

$tab_data = $tabs[ $selected_tab ];
$callback = $tab_data['callback'] ?? false;
if ( $callback && ! is_callable( $callback ) ) {
	w4os_admin_notice( sprintf( __( 'Invalid callback function %s.', 'w4os' ), $callback ), 'error' );
	$callback = false;
}

$try_templates = array(
	W4OS_TEMPLATES_DIR . "admin-$menu_slug-$selected_tab-content.php",
	W4OS_TEMPLATES_DIR . "admin-$menu_slug-content.php",
	W4OS_TEMPLATES_DIR . 'admin-settings-content.php',
);
foreach ( $try_templates as $try_template ) {
	if ( file_exists( $try_template ) ) {
		$template = $try_template;
		break;
	}
}

// make sure required vars are set
if ( ! isset( $menu_slug ) ) {
	w4os_admin_notice( __( 'Required parameters missing for this page.', 'w4os' ), 'error' );
	error_log( sprintf( 'Required parameters missing, menu_slug not defined - %s.', $_SERVER['REQUEST_URI'] ) );
	do_action( 'admin_notices' );
	return;
}

$option_name  = $option_name ?? $menu_slug;
$option_group = $option_group ?? $menu_slug . '_group';

W4OS3::enqueue_style( 'w4os-admin-settings', 'v3/css/admin-settings.css' );
?>
<div class="wrap w4os">
	<header>
		<h1><?php echo esc_html( $page_title ); ?></h1>
		<?php echo isset( $action_links_html ) ? $action_links_html : ''; ?>
		<?php
			echo W4OS3_Settings::get_tabs_html();
			// echo $tabs_navigation;
		?>
	</header>
	<?php settings_errors( $menu_slug ); ?>
	<body>
		<?php do_action( 'admin_notices' ); ?>
		<div class="wrap w4os-settings <?php echo esc_attr( $menu_slug ); ?>">
			<div class="main-content">
				<?php
				if ( ! empty( $tab_data['before-form'] ) ) {
					printf( '<p class="before-form">%s</p>', $tab_data['before-form'] );
				}
				if ( $callback && is_callable( $callback ) ) {
					call_user_func( $callback );
				} elseif ( file_exists( $template ) ) {
					include $template;
				} else {
					printf( '<p>%s</p>', __( 'No content template available for this page.', 'w4os' ) );
					echo $template;
				}
				if ( ! empty( $tab_data['after-form'] ) ) {
					printf( '<p class="after-form">%s</p>', $tab_data['after-form'] );
				}
				?>
			</div>
			<?php
			if ( ! empty( $tab_data['sidebar-content'] ) ) {
				printf( '<div id="sidebar-content">%s</div>', $tab_data['sidebar-content'] );
			}
			?>
		</div>
	</body>
</div>
