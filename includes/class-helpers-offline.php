<?php
/**
 * Register all actions and filters for the plugin
 *
 * @package    GuduleLapointe/w4os
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
	protected $default_offline_url;

	public function __construct() {
		$this->default_offline_url = get_home_url( null, '/' . get_option( 'w4os_helpers_slug', 'helpers' ) . '/offline.php' );
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

		// $offline_url = ( ! empty( W4OS_GRID_INFO['OfflineMessageURL'] ) ) ? W4OS_GRID_INFO['OfflineMessageURL'] : $this->default_offline_url ;
		$offline_url = get_option( 'w4os_offline_helper_uri' );
		// $offline_url = $this->default_offline_url;

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
							__( 'OpenSimulator core offline messages module doesn\'t handle e-mail forwarding option available in the viewer settings.', 'w4os' ),
						)
					),
				),
				array(
					'name'        => __( 'Sender e-mail address', 'w4os' ),
					'id'          => $prefix . 'offline_sender',
					'type'        => 'email',
					'placeholder' => 'no-reply@example.org',
					'required'    => true,
					'save_field'  => false,
					'std'         => get_option( 'w4os_offline_sender', 'no-reply@' . parse_url( get_site_url(), PHP_URL_HOST ) ),
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
					. __( 'Set the URL in OpenSimulator configurations. Go to Settings > Permalinks and', 'w4os' )
					. w4os_format_ini(
						array(
							'OpenSim.ini'   => array(
								'[Messaging]' => array(
									'OfflineMessageModule' => 'OfflineMessageModule',
									'OfflineMessageURL'    => $offline_url,
								),
							),
							'Robust.HG.ini' => array(
								'[GridInfoService]' => array(
									';; Optional. To allow different grid to communicate their offline messages service',
									';; In previous versions, we recommended the "message" variable to add',
									';; the URL in Robust.HG.ini GridInfoService section, but this value seems',
									';; to be intended for other purposes, although not enforced (yet?).',
									'OfflineMessageURL' => $offline_url,
								),
							),
						)
					) . '</p>',
				),
			),
		);

		return $meta_boxes;
	}

	static function set_offline_uri() {
		$offline_provide = get_option( 'w4os_provide_offline_messages', false );
		$offline_url     = ( $offline_provide ) ? get_home_url( null, '/' . get_option( 'w4os_helpers_slug', 'helpers' ) . '/offline.php' ) : null;
		update_option( 'w4os_offline_helper_uri', $offline_url );
	}

	function sanitize_options() {
		if ( empty( $_POST ) ) {
			return;
		}

		if ( isset( $_POST['nonce_offline-messages-settings'] ) && wp_verify_nonce( $_POST['nonce_offline-messages-settings'], 'rwmb-save-offline-messages-settings' ) ) {
			$provide = isset( $_POST['w4os_provide_offline_messages'] ) ? true : false;
			update_option( 'w4os_provide_offline_messages', $provide );
			self::set_offline_uri();
			// if ( $provide ) {
			// update_option( 'w4os_offline_sender', isset( $_POST['w4os_offline_sender'] ) ? $_POST['w4os_offline_sender'] : null );
			// update_option( 'w4os_offline_helper_uri', isset( $_POST['w4os_offline_helper_uri'] ) ? $_POST['w4os_offline_sender'] : $this->default_offline_url );
			// }
		}
	}
}

$this->loaders[] = new W4OS_Offline();
