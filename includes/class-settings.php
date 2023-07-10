<?php if ( ! defined( 'W4OS_PLUGIN' ) ) {
	die;}

class W4OS_Settings extends W4OS_Loader {
	protected $login_uri;

	public function __construct() {
	}

	public function init() {
		$this->actions = array(
			array(
				'hook' => 'init',
				'callback' => 'sanitize_options',
				// 'priority' => 1,
			),
			array(
				'hook' => 'admin_menu',
				'callback' => 'register_admin_menu',
				'priority' => 5,
			),
			// array(
			// 	'hook' => 'init',
			// 	'callback' => 'rewrite_rules',
			// ),
			// array(
			// 	'hook' => 'admin_init',
			// 	'callback' => 'register_permalinks_options',
			// ),
			// array(
			// 	'hook' => 'template_include',
			// 	'callback' => 'template_include',
			// ),
		);

		$this->filters = array(
			array(
				'hook' => 'mb_settings_pages',
				'callback' => 'register_settings_pages',
				'priority' => 5,
			),
			array(
				'hook' => 'rwmb_meta_boxes',
				'callback' => 'register_settings_fields',
				// 'priority' => 5,
			),
			// array(
			// 	'hook' => 'query_vars',
			// 	'callback' => 'register_query_vars',
			// ),
		);
	}

	function register_admin_menu() {
		add_menu_page(
			'OpenSimulator', // page title
			'OpenSimulator', // menu title
			'manage_options', // capability
			'w4os', // slug
			'w4os_status_page', // callable function
			// plugin_dir_path(__FILE__) . 'options.php', // slug
			// null,	// callable function
			plugin_dir_url( W4OS_PLUGIN ) . 'images/opensimulator-logo-24x14.png', // icon url
			2 // position
		);
		add_submenu_page( 'w4os', __( 'OpenSimulator Status', 'w4os' ), __( 'Status' ), 'manage_options', 'w4os', 'w4os_status_page' );
	}

	function register_settings_pages( $settings_pages ) {
		$settings_pages[] = [
			'menu_title' => __( 'Settings', 'w4os' ),
			'id'         => 'w4os_settings',
			'position'   => 0,
			'parent'     => 'w4os',
			'capability' => 'manage_options',
			'style'      => 'no-boxes',
			'icon_url'   => 'dashicons-admin-generic',
		];

		return $settings_pages;
	}

	function register_settings_fields( $meta_boxes ) {
		$prefix = 'w4os_';

		$meta_boxes[] = [
			'title'          => __( 'Grid Info', 'w4os' ),
			'name'          => __( 'Grid Info', 'w4os' ),
			'desc'          => __( 'Grid Info', 'w4os' ),
			'description'          => __( 'Grid Info', 'w4os' ),
			'id'             => 'grid-info',
			'settings_pages' => ['w4os_settings'],
			'fields'         => [
				[
						// 'name'       => ,
						'id'         => $prefix . 'grid_info_section',
						'type'       => 'custom_html',
						'std'        => '<h2>' . __( 'Grid Info', 'w4os' ) . '</h2>',
						'std'        => __( 'Grid Info', 'w4os' ),
						'save_field' => false,
				],
				[
					'name'       => __( 'Login URI', 'w4os' ),
					'id'         => $prefix . 'login_uri',
					'type'       => 'url',
					'std'        => w4os_grid_login_uri(),
					'required'   => true,
					'save_field' => false,
					'placeholder' => 'http://yourgrid.org:8002',
				],
				[
						'name'     => __( 'Grid Name', 'w4os' ),
						'id'       => $prefix . 'grid_name',
						'type'     => 'custom_html',
						'callback' => 'w4os_grid_name',
				],
				[
						'name'       => __( 'Grid Status', 'w4os' ),
						'id'         => $prefix . 'grid_status',
						'type'       => 'custom_html',
						'callback'   => 'w4os_grid_status',
						'save_field' => false,
				],
			],
		];

		return $meta_boxes;
	}

	function sanitize_options() {
		if (empty($_POST)) return;

		if( isset($_POST['nonce_grid-info']) && wp_verify_nonce( $_POST['nonce_grid-info'], 'rwmb-save-grid-info' ) ) {
			if(isset($_POST['w4os_login_uri'])) {
				$login_uri = w4os_sanitize_login_uri($_POST['w4os_login_uri']);
				if(empty($login_uri)) {
					w4os_admin_notice(__('Invalid Login URI', 'w4os'), 'error');
				}
				update_option('w4os_login_uri', $login_uri);
			}
			return;
		}
	}

}

$this->loaders[]=new W4OS_Settings();
