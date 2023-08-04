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
class W4OS_Guide extends W4OS_Loader {
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
			array(
				'hook'     => 'admin_menu',
				'callback' => 'register_settings_sidebar',
			),
		);

		$this->filters = array(
			array(
				'hook'     => 'mb_settings_pages',
				'callback' => 'register_settings_pages',
			),
			array(
				'hook'     => 'rwmb_meta_boxes',
				'callback' => 'register_settings_fields',
			),
		);
	}

	function register_settings_pages( $settings_pages ) {
		$settings_pages[] = array(
			'menu_title' => __( 'Destinations Guide', 'w4os' ),
			'page_title' => __( 'Destinations Guide Settings', 'w4os' ),
			'id'         => 'w4os-guide',
			'position'   => 25,
			'parent'     => 'w4os',
			'capability' => 'manage_options',
			'class'      => 'w4os-settings',
			'style'      => 'no-boxes',
			'columns'    => 2,
			'icon_url'   => 'dashicons-admin-generic',
		);

		return $settings_pages;
	}

	function register_settings_fields( $meta_boxes ) {
		$prefix = 'w4os_';

		$guide_url = ( ! empty( W4OS_GRID_INFO['message'] ) ) ? W4OS_GRID_INFO['message'] : get_home_url( null, '/helpers/guide/' );

		// $example_url = 'http://example.org/helpers/guide.php';
		$guide_url = get_option( 'w4os_destinations_guide_uri',  get_home_url( null, '/guide/' ) );
		// $guide_url = get_home_url( null, '/helpers/guide.php' );

		$meta_boxes[] = array(
			'title'          => __( 'Destinations Guide Settings', 'w4os' ),
			'id'             => 'destinations-guide-settings',
			'settings_pages' => array( 'w4os-guide' ),
			'class'          => 'w4os-settings',
			'fields'         => array(
				array(
					'name'       => __( 'Provide Destinations Guide Service', 'w4os' ),
					'id'         => $prefix . 'provide_destinations_guide',
					'type'       => 'switch',
					'style'      => 'rounded',
					'std'        => get_option( 'w4os_provide_destinations_guide', true ),
					'save_field' => false,
					// 'desc'       => '';
				),
				array(
					'name'        => __( 'Source', 'w4os' ),
					'id'          => $prefix . 'destinations_guide_source',
					'type'        => 'text',
					// 'placeholder' => $guide_url,
					// 'class'       => 'copyable',
					// 'std'         => $guide_url,
					'visible'     => array(
						'when'     => array( array( 'provide_destinations_guide', '=', 1 ) ),
						'relation' => 'or',
					),
					'desc'        => '<ul><li>' . join('</li><li>', array(
						__( 'A text file with a formatted list of destinations.', 'w4os' ),
						__( 'The source can be an URL or the full path of a local file your web server can access.', 'w4os' ),
						__( 'Destination name and teleport URL are separated by a pipe (|) character.', 'w4os' ),
						__( 'Lines containing only text are interpreted as categories.', 'w4os' ),
						__( 'Lines beginning with "#" or "//" are ignored.', 'w4os' ),
						sprintf(
							__( 'The format is identical to the format used by the in-world object %s so the same source URL can be used for both.', 'w4os' ),
							'<a href="https://github.com/GuduleLapointe/Gudz-Teleport-Board-2">Gudz Teleport Board</a>',
						),
					))
					. '</li></ul>'
					. '<div class="iniconfig"><pre>'
					. join("\n", array(
						'Section 1',
						'Display Name|yourgrid.org:8002/Region Name',
						'Display Name|yourgrid.org:8002/Region Name/128/128/22',
						'Section 2',
						'Display Name|othergrid.org:8002',
						'Display Name|othergrid.org:8002/Region Name/128/128/22',
					))
					. '</pre></div>'
					,
				),
				array(
					'name'        => __( 'Destinations Guide URL', 'w4os' ),
					'id'          => $prefix . 'destinations_guide_url',
					'type'        => 'url',
					'placeholder' => $guide_url,
					'readonly'    => true,
					'save_field'  => false,
					'class'       => 'copyable',
					'std'         => $guide_url,
					'visible'     => array(
						'when'     => array( array( 'provide_destinations_guide', '=', 1 ) ),
						'relation' => 'or',
					),
					'desc'        => '<p>'
					// . __( 'Set the URL in Robust and OpenSimulator configurations.', 'w4os' )
					. w4os_format_ini(
						array(
							'Robust.HG.ini' => array(
								'[LoginService]' => array(
									'DestinationGuide' => $guide_url,
								),
							),
							// 'OpenSim.ini'   => array(
							// 	'[SimulatorFeatures]' => array(
							// 		'DestinationGuideURI'    => $guide_url,
							// 	),
							// ),
						)
					) . '</p>',
				),
			),
		);

		return $meta_boxes;
	}

	function register_settings_sidebar() {
		// Add a custom meta box to the sidebar
		add_meta_box(
			'sidebar-content', // Unique ID
			'Settings Sidebar', // Title
			array( $this, 'sidebar_content' ), // Callback function to display content
			'opensimulator_page_w4os-guide', // Settings page slug where the sidebar appears
			'side' // Position of the meta box (sidebar)
		);
	}

	function sidebar_content() {
		echo '<ul><li>' . join(
			'</li><li>',
			array(
				__( 'Destinations guide is a Viewer 3 feature, providing a window with places suggestions.', 'w4os' ),
				__( 'While it is not mandatory, and not every user will benefit from it, it is a useful way to provide them a list of must-see places, from your own grid or outside.', 'w4os' ),
			)
		) . '</li></ul>';
	}

	function sanitize_options() {
		if ( empty( $_POST ) ) {
			return;
		}

		if ( isset( $_POST['nonce_destinations-guide-settings'] ) && wp_verify_nonce( $_POST['nonce_destinations-guide-settings'], 'rwmb-save-destinations-guide-settings' ) ) {
			error_log( print_r( $_POST, true ) );
			$provide = isset( $_POST['w4os_provide_destinations_guide'] ) ? true : false;
			update_option( 'w4os_provide_destinations_guide', $provide );

			if ( $provide ) {
				update_option( 'w4os_guide_sender', isset( $_POST['w4os_guide_sender'] ) ? $_POST['w4os_guide_sender'] : null );
				update_option( 'w4os_guide_helper_uri', isset( $_POST['w4os_guide_helper_uri'] ) ? $_POST['w4os_guide_sender'] : get_home_url( null, '/helpers/guide.php' ) );
			}
		}
	}
}

$this->loaders[] = new W4OS_Guide();
