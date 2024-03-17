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
class W4OS_Search extends W4OS_Loader {
	protected $actions;
	protected $filters;
	protected $helpers;
	protected $helpers_internal;
	protected $helpers_external;
	protected $login_uri;
	protected $gatekeeper;

	public function __construct() {
		$this->login_uri               = get_option( 'w4os_login_uri', 'yourgrid.org:8002' );
		$this->helpers_internal        = str_replace( 'https:', 'http:', get_home_url() ) . '/' . get_option( 'w4os_helpers_slug', 'helpers' ) . '/';
		$this->helpers_external        = 'http://2do.directory/helpers/';
		$this->helpers                 = ( get_option( 'w4os_provide_search' ) ) ? $this->helpers_internal : $this->helpers_external;
		$this->default_search_url      = $this->helpers . 'query.php';
		$this->default_search_register = $this->helpers . 'register.php';
		$this->gatekeeper              = preg_match( '#https?://#', $this->login_uri ) ? $this->login_uri : 'http://' . $this->login_uri;
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
			'menu_title' => __( 'Search Engine', 'w4os' ),
			'page_title' => __( 'Search Engine Settings', 'w4os' ),
			'id'         => 'w4os-search',
			'position'   => 25,
			'parent'     => 'w4os',
			'capability' => 'manage_options',
			'class'      => 'w4os-settings',
			'style'      => 'no-boxes',
			// 'columns'    => 1,
			'icon_url'   => 'dashicons-admin-generic',
		);

		return $settings_pages;
	}

	function register_settings_fields( $meta_boxes ) {
		$prefix = 'w4os_';

		$meta_boxes[] = array(
			'title'          => __( 'Search Engine Settings', 'w4os' ),
			'id'             => 'search-settings',
			'settings_pages' => array( 'w4os-search' ),
			'save_field'     => false,
			'save'           => false,
			'fields'         => array(
				array(
					'name'       => __( 'Provide Search', 'w4os' ),
					'id'         => $prefix . 'provide_search',
					'type'       => 'switch',
					// 'desc'       => __( 'Enable to use a local search engine, allowing only local results (recommended for private grids). Disable to use an external search engine like 2do Directory, allowing results from both your grid and other public grids.', 'w4os' ),
					'style'      => 'rounded',
					'std'        => get_option( 'w4os_provide_search' ),
					'save_field' => false,
					'attributes' => array(
						'data-helpers-internal' => $this->helpers_internal,
						'data-helpers-external' => $this->helpers_external,
					),
				),
				array(
					'name'       => __( 'OS Search Database', 'w4os' ),
					'id'         => $prefix . 'search-db',
					'type'       => 'w4osdb_field_type',
					'save_field' => false,
					// 'desc' => __('If set to default, the main (ROBUST) database will be used to fetch search data.', 'w4os'),
					'visible'    => array(
						'when'     => array( array( 'provide_search', '=', 1 ) ),
						'relation' => 'or',
					),
					'std'        => array(
						// 'is_main'     => true,
						'use_default' => get_option( 'w4os_search_use_robust_db', true ),
						'type'        => get_option( 'w4os_search_db_type', 'mysql' ),
						'port'        => get_option( 'w4os_search_db_port', 3306 ),
						'host'        => get_option( 'w4os_search_db_host', 'localhost' ),
						'database'    => get_option( 'w4os_search_db_database', 'robust' ),
						'user'        => get_option( 'w4os_search_db_user', 'opensim' ),
						'pass'        => get_option( 'w4os_search_db_pass' ),
					),
				),
				array(
					'name'        => __( 'Search Engine URL', 'w4os' ),
					'id'          => $prefix . 'search_url',
					'type'        => 'url',
					'placeholder' => $this->default_search_url,
					'std'         => get_option( 'w4os_search_url', $this->default_search_url ),
					'class'       => 'copyable',
					'save_field'  => false,
					'desc'        => W4OS::sprintf_safe(
						'<ul classs="description"><li>%s</li><li>%s</li><li>%s</li></ul>',
						__( 'URL of the search engine used by the viewer to provide search results (without arguments).', 'w4os' ),
						__( 'Only one can be set, other lines are alternative examples.', 'w4os' ),
						__( 'Services using w4os engine need the gatekeeper URI (usually the login URI) to be passed as gk argument. Requirements may vary for other engines.', 'w4os' ),
					) . w4os_format_ini(
						array(
							'OpenSim.ini' => array(
								'[Search]' => array(
									';; Save your settings before copying the values below',
									'Module'    => 'OpenSimSearch',
									'SearchURL' => '"' . $this->helpers . 'query.php?gk=' . $this->gatekeeper . '"',
									// '; SearchURL ' => '"http://example.org/query.php"',
								),
							),
						)
					)
							. '<p>' . __( 'Please note that Search URL is different from Web search URL, which is not handled by W4OS currently. Web search is relevant if you have a web search page dedicated to grid content, providing results with in-world URLs (hop:// or secondlife://). It is optional and is referenced here only to disambiguate settings which unfortunately have similar names.', 'w4os' ) . '</p>'
								. w4os_format_ini(
									array(
										'Robust.HG.ini' => array(
											'[LoginService]' => array(
												'SearchURL' => ( ! empty( get_option( 'w4os_websearch_url' ) ) ) ? get_option( 'w4os_websearch_url' ) : 'https://example.org/search/',
											),
											'[GridInfoService]' => array(
												'search' => ( ! empty( get_option( 'w4os_websearch_url' ) ) ) ? get_option( 'w4os_websearch_url' ) : 'https://example.org/search/',
											),
										),
									)
								),
				),
				array(
					'name'       => __( 'Search Register URL', 'w4os' ),
					'id'         => $prefix . 'search_register',
					'type'       => 'url',
					'std'        => get_option( 'w4os_search_register', $this->default_search_register ),
					'save_field' => false,
					'desc'       => '<ul><li>' . join(
						'</li><li>',
						array(
							__( 'Data service, used to register regions, objects or land for sale.', 'w4os' ),
							__( 'You can register to several search engines.', 'w4os' ),
							__( 'Each line must have a unique identifier beginning with "DATA_SRV_"', 'w4os' ),
						)
					) . '</li></ul>'
					. w4os_format_ini(
						array(
							'OpenSim.ini' => array(
								'[DataSnapshot]' => array(
									'index_sims' => 'true',
									'gridname'   => '"' . get_option( 'w4os_grid_name' ) . '"',
									( get_option( 'w4os_provide_search' ) ? '' : '; ' ) . 'DATA_SRV_' . w4os_camelcase( get_option( 'w4os_grid_name', 'Your Grid' ) ) => '"' . ( ! empty( get_option( 'w4os_search_register' ) ) ? get_option( 'w4os_search_register' ) : 'http://yourgrid.org/helpers/register.php' ) . '"',
									( get_option( 'w4os_provide_search' ) ? '; ' : '' ) . 'DATA_SRV_2do' => '"http://2do.directory/helpers/register.php"',
									'; DATA_SRV_OtherEngine' => '"http://example.org/register.php"',
								),
							),
						)
					),
					'class'      => 'copyable',
				),
				array(
					'name'        => __( 'Events Server URL', 'w4os' ),
					'id'          => $prefix . 'hypevents_url',
					'type'        => 'url',
					'class'       => 'copyable',
					'std'         => get_option( 'w4os_hypevents_url', 'http://2do.directory/events/' ),
					'placeholder' => 'http://2do.directory/events/',
					'save_field'  => false,
					'visible'     => array(
						'when'     => array( array( 'provide_search', '=', 1 ) ),
						'relation' => 'or',
					),
					'desc'        => '<p>' . __( 'HYPEvents Server URL, used to fetch upcoming events and make them available in search.', 'w4os' )
					. ' ' . __( 'Leave blank to ignore events or if you have an other events implementation.', 'w4os' )
					. ' <a href=https://2do.pm/ target=_blank>2do HYPEvents project</a></p>',
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
			'opensimulator_page_w4os-search', // Settings page slug where the sidebar appears
			'side' // Position of the meta box (sidebar)
		);
	}

	function sidebar_content() {
		echo '<ul><li>' . join(
			'</li><li>',
			array(
				__( 'Enable to use a local search engine, allowing only local results (recommended for private grids).', 'w4os' ),
				__( 'Disable to use an external search engine like 2do Directory, allowing results from both your grid and other public grids.', 'w4os' ),
				'<strong>' . __( 'Search features require the installation of OpenSimSearch Module on OpenSimulator.', 'w4os' ) . '</strong>',
				__( 'Ready to use OpenSimSearch.Modules.dll binary can be found here:', 'w4os' )
				. '<br><a href="https://github.com/magicoli/opensim-helpers/tree/master/bin">github.com/magicoli/opensim-helpers</a>',
			)
		) . '</li></ul>';
	}

	static function set_search_url() {
		$provide = get_option( 'w4os_provide_search', false );
		if ( $provide ) {
			$search_url   = get_home_url( null, '/' . get_option( 'w4os_helpers_slug', 'helpers' ) . '/query.php' );
			$register_url = get_home_url( null, '/' . get_option( 'w4os_helpers_slug', 'helpers' ) . '/register.php' );
			update_option( 'w4os_search_url', $search_url );
			update_option( 'w4os_search_register', $register_url );
		}
	}

	function sanitize_options() {
		if ( empty( $_POST ) ) {
			return;
		}

		if ( isset( $_POST['nonce_search-settings'] ) && wp_verify_nonce( $_POST['nonce_search-settings'], 'rwmb-save-search-settings' ) ) {
			$provide = isset( $_POST['w4os_provide_search'] ) ? true : false;
			update_option( 'w4os_provide_search', $provide );

			update_option( 'w4os_hypevents_url', empty( $_POST['w4os_hypevents_url'] ) ? 'http://2do.directory/events/' : $_POST['w4os_hypevents_url'] );
			self::set_search_url();
			if ( $provide ) {

				$use_default = isset( $_POST['w4os_search-db']['use_default'] );
				update_option( 'w4os_search_use_default_db', $use_default );
				if ( ! $use_default ) {
					$credentials = array_map( 'esc_attr', $_POST['w4os_search-db'] );
					update_option( 'w4os_search_db_host', $credentials['host'] );
					update_option( 'w4os_search_db_port', $credentials['port'] );
					update_option( 'w4os_search_db_database', $credentials['database'] );
					update_option( 'w4os_search_db_user', $credentials['user'] );
					update_option( 'w4os_search_db_pass', $credentials['pass'] );
				}
			} else {
				update_option( 'w4os_search_url', isset( $_POST['w4os_search_url'] ) ? $_POST['w4os_search_url'] : null );
				update_option( 'w4os_search_register', isset( $_POST['w4os_search_register'] ) ? $_POST['w4os_search_register'] : null );
			}
		}
	}

}

$this->loaders[] = new W4OS_Search();
