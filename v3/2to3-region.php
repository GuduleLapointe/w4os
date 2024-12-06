<?php
/**
 * This is a test class to finetune menu integration.
 * - Create a Settings page for Regions-specific settings, as a submenu of the main 'w4os' menu
 * - We don't care about the main menu here, it is defined in another file.
 * - The rendering is made efficiently, by W4OS3_Settings::render_settings_page()
 * - We don't include html code of the pages here, only the settings registration.
 * - The header and content are managed by the render_settings_page() method.
 */

class W4OS3_Region {
    public function __construct() {
        // We will define region objects properties here later, not implemented now.

        // $args = func_get_args();
        // if ( ! empty ( $args ) ) {
        // }
    }

    /**
     * Initialize the class. Register actions and filters.
     */
    public function init() {
        add_action( 'admin_init', [ __CLASS__, 'register_settings_page' ] );
        add_action( 'admin_menu', [ __CLASS__, 'add_submenus' ] );
    }

    /**
	 * Add submenu for Region settings page
	 */
	public static function add_submenus() {
        W4OS3::add_submenu_page(
            'w4os',                         
            __( 'Regions Settings', 'w4os' ),
            __( 'Regions', 'w4os' ),
            'manage_options',
            'w4os-region-settings',
            [ 'W4OS3_Settings', 'render_settings_page' ],
            3,
        );
    }

    /**
     * Register settings using the Settings API, templates and the method W4OS3_Settings::render_settings_section().
     */
    public static function register_settings_page() {
        if (! W4OS_ENABLE_V3) {
            return;
        }
        // Add v3 settings below
		$option_group = 'w4os_settings_region';
		$option_name = 'w4os_settings_region'; // Changed option name
		$page = 'w4os-region-settings'; // Updated to match menu slug

		register_setting(
			$option_group, // Option group
			$option_name, // Option name
			array(
				'type' => 'array',
				'description' => __( '  Regions Settings', 'w4os' ),
				'sanitize_callback' => [ __CLASS__, 'sanitize_options' ], // recieves empty args for now
				// 'show_in_rest' => false,
				'default' => array(
					'create_wp_account' => true,
					'multiple_regions' => false,
				),
			),
		);

		# add_settings_section( string $id, string $title, callable $callback, string $page, array $args = array() )

		$section = "${option_group}_default";
		add_settings_section(
			$section,				// ID
			null,	// Title
			[ 'W4OS3_Settings', 'render_settings_section' ],  // Callback
			$page,				// Page
			array(
				'description' => __( 'Settings for regions.', 'w4os' ),
			)
		);

		// add_settings_field( string $id, string $title, callable $callback, string $page, string $section = ‘default’, array $args = array() );
		
		add_settings_field(
			'region_parameter_1', // id
			__( 'Region parameter 1', 'w4os' ), // title
			[ 'W4OS3_Settings', 'render_settings_field' ], // callback
			$page, // page
			$section, // section
			array(
				'type' => 'checkbox',
				'label' => __( 'Enable option 1.', 'w4os' ),
				'description' => __( 'This is a placeholder parameter.', 'w4os' ),
				'option_name' => $option_name, // Pass option name
                'label_for' => 'region_parameter_1',
			),
		);

		add_settings_field(
			'region_parameter_2', // id
			__( 'Region parameter 2', 'w4os' ), // title
			[ 'W4OS3_Settings', 'render_settings_field' ], // callback
			$page, // page
			$section, // section
			array(
				'type' => 'checkbox',
				'label' => __( 'Enable option 2.', 'w4os' ),
				'description' => __( 'This is a placeholder parameter.', 'w4os' ),
				'option_name' => $option_name, // Pass option name
                // 'label_for' => 'region_parameter_1',
			),
		);
    }

	public static function sanitize_options( $input ) {
		return $input;
	}

}
