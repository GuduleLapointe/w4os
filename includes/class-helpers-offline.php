<?php
/**
 * Register all actions and filters for the plugin
 *
 * @package    w4os
 * @subpackage w4os/includes
 */

/**
 * Register all actions and filters for the plugin.
 *
 * Maintain a list of all hooks that are registered throughout
 * the plugin, and register them with the WordPress API. Call the
 * run function to execute the list of actions and filters.
 */
class W4OS_Offline extends W4OS_Loader {
	protected $actions;
	protected $filters;

	public function __construct() {
	}

	public function init() {

		$this->actions = array(
			array(
				'hook'     => 'init',
				'callback' => 'sanitize_options',
			),
		);

		$this->filters = array(
			array(
				'hook'     => 'rwmb_meta_boxes',
				'callback' => 'register_settings_fields',
			),
			array(
				'hook'     => 'mb_settings_pages',
				'callback' => 'register_settings_pages',
			),
		);
	}

	function register_settings_pages( $settings_pages ) {
		$settings_pages[] = array(
			'menu_title' => __( 'Offline Messages', 'w4os' ),
			'page_title' => __( 'Offline Messages Settings', 'w4os' ),
			'id'         => 'w4os-offline',
			'position'   => 25,
			'parent'     => 'w4os',
			'capability' => 'manage_options',
			'class'      => 'w4os-settings',
			'style'      => 'no-boxes',
			'columns'    => 1,
			'icon_url'   => 'dashicons-admin-generic',
		);

		return $settings_pages;
	}

	function register_settings_fields( $meta_boxes ) {
		$prefix = 'w4os_';

		$offline_url = ( ! empty( W4OS_GRID_INFO['message'] ) ) ? W4OS_GRID_INFO['message'] : get_home_url( null, '/helpers/offline/' );

		// $example_url = 'http://example.org/helpers/offline.php';
		// $offline_url = get_option( 'w4os_offline_helper_uri' );
		// $offline_url = get_home_url( null, '/helpers/offline.php' );

		$meta_boxes[] = array(
			'title'          => __( 'Offline Messages Settings', 'w4os' ),
			'id'             => 'offline-messages-settings',
			'settings_pages' => array( 'w4os-offline' ),
			'class'          => 'w4os-settings',
			'fields'         => array(
				array(
					'name'       => __( 'Provide Offline Messages Service', 'w4os' ),
					'id'         => $prefix . 'provide_offline_messages',
					'type'       => 'switch',
					'style'      => 'rounded',
					'std'        => get_option( 'w4os_provide_offline_messages', true ),
					'save_field' => false,
					'desc'       => join(
						'<br/>',
						array(
							__( 'Honor "Email me IMs when I\'m offline" viewer option.', 'w4os' ),
							__( 'OpenSimulator core offline messages module delivers messages sent to an offline user when they come back online but don\'t handle e-mail forwarding option available in the viewer.', 'w4os' ),
						)
					),
				),
				array(
					'name'        => __( 'Sender E-mail Address', 'w4os' ),
					'id'          => $prefix . 'offline_sender',
					'type'        => 'email',
					'placeholder' => 'no-reply@example.org',
					'required'    => true,
					'save_field'  => false,
					'std'         => get_option( 'w4os_offline_sender', 'no-reply@' . $_SERVER['SERVER_NAME'] ),
					'visible'     => array(
						'when'     => array( array( 'provide_offline_messages', '=', 1 ) ),
						'relation' => 'or',
					),
					'desc'        => __( 'A no-reply e-mail address used to forward messages for users enabling "Email me IMs when I\'m offline" option.', 'w4os' ),
				),
				array(
					'name'        => __( 'Offline Messages Helper', 'w4os' ),
					'id'          => $prefix . 'offline_messages_helper_uri',
					'type'        => 'url',
					'placeholder' => $offline_url,
					'readonly'    => true,
					'save_field'  => false,
					'class'       => 'copyable',
					'std'         => $offline_url,
					'visible'     => array(
						'when'     => array( array( 'provide_offline_messages', '=', 1 ) ),
						'relation' => 'or',
					),
					'desc'        => '<p>'
					. __( 'Set the URL in Robust and OpenSimulator configurations.', 'w4os' )
					. w4os_format_ini(
						array(
							'Robust.HG.ini' => array(
								'[GridInfoService]' => array(
									'message' => $offline_url,
								),
							),
							'OpenSim.ini'   => array(
								'[Messaging]' => array(
									'OfflineMessageModule' => 'OfflineMessageModule',
									'OfflineMessageURL'    => $offline_url,
								),
							),
						)
					) . '</p>',
				),
			),
		);

		return $meta_boxes;
	}

	function sanitize_options() {
		if ( empty( $_POST ) ) {
			return;
		}

		if ( isset( $_POST['nonce_offline-messages-settings'] ) && wp_verify_nonce( $_POST['nonce_offline-messages-settings'], 'rwmb-save-offline-messages-settings' ) ) {
			error_log( print_r( $_POST, true ) );
			$provide = isset( $_POST['w4os_provide_offline_messages'] ) ? true : false;
			update_option( 'w4os_provide_offline_messages', $provide );

			if ( $provide ) {
				update_option( 'w4os_offline_sender', isset( $_POST['w4os_offline_sender'] ) ? $_POST['w4os_offline_sender'] : null );
				update_option( 'w4os_offline_helper_uri', isset( $_POST['w4os_offline_helper_uri'] ) ? $_POST['w4os_offline_sender'] : get_home_url( null, '/helpers/offline.php' ) );
			}
		}
	}
}

$this->loaders[] = new W4OS_Offline();
