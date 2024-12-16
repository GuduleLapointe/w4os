<?php
/**
 * Provide Destinations Guide for Viewer 3
 *
 * @package    GuduleLapointe/w4os
 * @subpackage w4os/includes
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
			array(
				'hook'     => 'init',
				'callback' => 'set_rewrite_rules',
			),
			array(
				'hook'     => 'parse_request',
				'callback' => 'parse_request_custom_guide',
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
			array(
				'hook'     => 'query_vars',
				'callback' => 'custom_query_vars',
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
		$prefix = '';

		$guide_url = get_option( 'w4os_destinations_guide_uri', get_home_url( null, '/guide/' ) );

		$meta_boxes[] = array(
			'title'          => __( 'Destinations Guide Settings', 'w4os' ),
			'id'             => 'destinations-guide-settings',
			'settings_pages' => array( 'w4os-guide' ),
			'class'          => 'w4os-settings',
			'fields'         => array(
				array(
					'name'  => __( 'Provide Destinations Guide Service', 'w4os' ),
					'id'    => $prefix . 'provide',
					'type'  => 'switch',
					'style' => 'rounded',
					'std'   => true,
				),
				array(
					'name'     => __( 'Source', 'w4os' ),
					'id'       => $prefix . 'source',
					'type'     => 'text',
					'required' => true,
					'visible'  => array(
						'when'     => array( array( 'provide', '=', 1 ) ),
						'relation' => 'or',
					),
					'desc'     => '<ul><li>' . join(
						'</li><li>',
						array(
							__( 'A text file with a formatted list of destinations.', 'w4os' ),
							__( 'The source can be an URL or the full path of a local file your web server can access.', 'w4os' ),
							__( 'Destination name and teleport URL are separated by a pipe (|) character.', 'w4os' ),
							__( 'Lines containing only text are interpreted as categories.', 'w4os' ),
							__( 'Lines beginning with "#" or "//" are ignored.', 'w4os' ),
							sprintf(
								__( 'The format is identical to the format used by the in-world object %s so the same source URL can be used for both.', 'w4os' ),
								'<a href="https://github.com/GuduleLapointe/Gudz-Teleport-Board-2">Gudz Teleport Board</a>',
							),
						)
					)
					. '</li></ul>'
					. '<div class="iniconfig"><pre>'
					. join(
						"\n",
						array(
							'Section 1',
							'Display Name|yourgrid.org:8002/Region Name',
							'Display Name|yourgrid.org:8002/Region Name/128/128/22',
							'Section 2',
							'Display Name|othergrid.org:8002',
							'Display Name|othergrid.org:8002/Region Name/128/128/22',
						)
					)
					. '</pre></div>',
				),
				array(
					'name'        => __( 'Destinations Guide URL', 'w4os' ),
					'id'          => $prefix . 'url',
					'type'        => 'url',
					'placeholder' => $guide_url,
					'readonly'    => true,
					'class'       => 'copyable',
					'std'         => $guide_url,
					'visible'     => array(
						'when'     => array( array( 'provide', '=', 1 ) ),
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
							// '[SimulatorFeatures]' => array(
							// 'DestinationGuideURI'    => $guide_url,
							// ),
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
			update_option( 'w4os_flush_rewrite_rules', true );
		}
	}

	function custom_query_vars( $vars ) {
		$vars[] = 'guide_source';
		return $vars;
	}

	// Check conditions and enable rewrite rule
	function set_rewrite_rules() {
		$provide = W4OS::get_option( 'w4os-guide:provide' );
		$url     = W4OS::get_option( 'w4os-guide:url' );

		if ( $provide && ! empty( $url ) ) {
			// Remove the host part of the URL to create the permalink_slug
			$parsed_url     = parse_url( $url );
			$permalink_slug = untrailingslashit( $parsed_url['path'] ); // Automatically adds trailing slash if not already present
			$permalink_slug = ltrim( $permalink_slug, '/' ); // Remove leading slash if present

			// Add an optional match for anything following the slug
			add_rewrite_rule( '^' . $permalink_slug . '(/.*)?$', 'index.php?guide_source=$matches[1]', 'top' );
		}
	}

	// Handle the custom guide request
	function parse_request_custom_guide() {
		global $wp;

		if ( array_key_exists( 'guide_source', $wp->query_vars ) ) {
			require_once W4OS_DIR . '/helpers/guide.php';

			$source  = W4OS::get_option( 'w4os-guide:source' );
			$guide   = new OpenSim_Guide( $source );
			$content = $guide->output_page();

			// Output the guide content
			echo $content;
			exit; // Stop WordPress from loading the default template
		}
	}
}

$this->loaders[] = new W4OS_Guide();
