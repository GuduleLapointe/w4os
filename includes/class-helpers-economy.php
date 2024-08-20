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
class W4OS_Economy extends W4OS_Loader {
	protected $actions;
	protected $filters;

	public function __construct() {
		$this->gloebit_url = '<a href=http://dev.gloebit.com/opensim/configuration-instructions/ target=_blank>gloebit.com</a>';
	}

	public function init() {
		if ( empty( get_option( 'w4os_economy_slug' ) ) ) {
			update_option( 'w4os_economy_slug', 'economy' );
		}

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
			'menu_title' => __( 'Economy', 'w4os' ),
			'page_title' => __( 'Economy Settings', 'w4os' ),
			'id'         => 'w4os-economy',
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

		$economy_url    = ( ! empty( W4OS_GRID_INFO['economy'] ) ) ? W4OS_GRID_INFO['economy'] : get_home_url( null, '/economy/' );
		$use_default_db = get_option( 'w4os_economy_use_default_db', true );
		// $example_url = 'http://example.org/helpers/economy.php';
		// $economy_url = get_option( 'w4os_economy_helper_uri' );
		// $economy_url = get_home_url( null, '/helpers/economy.php' );

		$meta_boxes[] = array(
			'title'          => __( 'Economy Settings', 'w4os' ),
			'id'             => 'economy-settings',
			'settings_pages' => array( 'w4os-economy' ),
			// 'class'          => 'w4os-settings',
			'fields'         => array(
				array(
					'name'       => __( 'Provide Economy Helper', 'w4os' ),
					'id'         => $prefix . 'provide_economy',
					'type'       => 'switch',
					'style'      => 'rounded',
					'std'        => get_option( 'w4os_provide_economy_helpers', true ),
					'save_field' => false,
					// 'desc'       => '',
				),
				array(
					'name'        => __( 'Economy Helper URI', 'w4os' ),
					'id'          => $prefix . 'economy_helper_uri',
					'type'        => 'url',
					'placeholder' => $economy_url,
					'readonly'    => true,
					'save_field'  => false,
					'class'       => 'copyable',
					'std'         => $economy_url,
					'visible'     => array(
						'when'     => array( array( 'provide_economy', '=', 1 ) ),
						'relation' => 'or',
					),
					'desc'        => '<p>'
					. __( 'The URL must be set in Robust configuration.', 'w4os' )
					. w4os_format_ini(
						array(
							'Robust.HG.ini' => array(
								'[GridInfoService]' => array(
									'economy' => ( ! empty( W4OS_GRID_INFO['economy'] ) ) ? W4OS_GRID_INFO['economy'] : get_home_url( null, '/economy/' ),
								),
								'[LoginService]'    => array(
									'; Currency' => 'YC$ ;; Your Currency symbol, optional',
								),
							),
						)
					) . '</p>',
				),
				array(
					'name'       => __( 'Economy Database', 'w4os' ),
					'id'         => $prefix . 'economy-db',
					'type'       => 'w4osdb_field_type',
					'save_field' => false,
					// 'desc' => __('If set to default, the main (ROBUST) database will be used to fetch economy data.', 'w4os'),
					'visible'    => array(
						'when'     => array( array( 'provide_economy', '=', 1 ) ),
						'relation' => 'or',
					),
					'std'        => array(
						// 'is_main'     => true,
						'use_default' => $use_default_db,
						'type'        => get_option( 'w4os_economy_db_type', 'mysql' ),
						'port'        => get_option( 'w4os_economy_db_port', 3306 ),
						'host'        => get_option( 'w4os_economy_db_host', 'localhost' ),
						'database'    => get_option( 'w4os_economy_db_database', 'robust' ),
						'user'        => get_option( 'w4os_economy_db_user', 'opensim' ),
						'pass'        => get_option( 'w4os_economy_db_pass' ),
					),
					// 'desc'       => __( 'Set the same credentials here and in MoneyServer.ini', 'w4os' )
					// . w4os_format_ini(
					// array(
					// 'MoneyServer.ini' => array(
					// '[MySql]' => array(
					// 'hostname' => ( $use_default_db ) ? get_option( 'w4os_db_host' ) : get_option( 'w4os_economy_db_host' ),
					// 'database' => ( $use_default_db ) ? get_option( 'w4os_db_database' ) : get_option( 'w4os_economy_db_database' ),
					// 'username' => ( $use_default_db ) ? get_option( 'w4os_db_user' ) : get_option( 'w4os_economy_db_user' ),
					// 'password' => '(your password)',
					// ),
					// ),
					// )
					// ),
				),
				array(
					'name'       => __( 'Currency Provider', 'w4os' ),
					'id'         => $prefix . 'currency_provider',
					'type'       => 'radio',
					'std'        => empty( get_option( 'w4os_currency_provider' ) ) ? 'none' : get_option( 'w4os_currency_provider' ),
					'save_field' => false,
					'visible'    => array(
						'when'     => array( array( 'provide_economy', '=', 1 ) ),
						'relation' => 'or',
					),
					'options'    => array(
						'gloebit' => 'Gloebit (<a href=http://dev.gloebit.com/opensim/configuration-instructions/ target=_blank>www.gloebit.com</a>)',
						'podex'   => 'Podex (<a href=http://www.podex.info/p/info-for-grid-owners.html target=_blank>www.podex.info</a>)',
						'none'    => __( 'Generic MoneyServer, for fake money or alternate providers.', 'w4os' ),
					),
					'inline'     => false,
				),
				array(
					'name'        => __( 'Currency Conversion Rate', 'w4os' ),
					'id'          => $prefix . 'currency_rate',
					'type'        => 'number',
					'desc'        => __( 'Amount to pay in US$ for 1000 in-world money units. Used for cost estimation. If not set, the rate will be 10/1000 (1 cent per money unit).', 'w4os' ),
					'step'        => 'any',
					'placeholder' => 10,
					'size'        => 5,
					'std'         => get_option( 'w4os_currency_rate' ),
					'save_field'  => false,
					'visible'     => array(
						'when'     => array(
							array( 'provide_economy', '=', 1 ),
							array( 'currency_provider', '!=', 'gloebit' ),
						),
						'relation' => 'and',
					),
				),
				array(
					'name'       => __( 'Gloebit Configuration', 'w4os' ),
					'id'         => $prefix . 'money_script_access_key',
					'type'       => 'custom_html',
					'std'        => '<ol><li>' . join(
						'</li><li>',
						array(
							'<strong>' . __( 'Gloebit module needs to be configured before restarting the region, otherwise it could crash the simulator.', 'w4os' ) . '</strong>',
							'<strong>' . W4OS::sprintf_safe(
								__( 'For Linux, see %s to avoid certificate-related errors.', 'w4os' ),
								'<a href=https://github.com/magicoli/opensim-helpers/blob/master/README-Gloebit.md target=_blank>README-Gloebit.md</a>',
							) . '</strong>',
							W4OS::sprintf_safe(
								__( 'Register an account or connect on %1$s and Follow instructions on %2$s to setup an app for your grid/simulator.', 'w4os' ),
								'<a href=https://www.gloebit.com/ target=_blank>gloebit.com</a>',
								'<a href=http://dev.gloebit.com/opensim/configuration-instructions/ target=_blank>dev.gloebit.com</a>',
							),
							__( 'Add Gloebit configuration in OpenSim.ini.', 'w4os' ),
							W4OS::sprintf_safe(
								'Download the latest dll in your OpenSimulator bin/ folder (rename it Gloebit.dll), from %1$s or %2$s',
								'<a href="https://github.com/GuduleLapointe/opensim-debian" target="_blank">github.com/GuduleLapointe/opensim-debian</a>',
								'<a href="http://dev.gloebit.com/opensim/downloads/" target="_blank">dev.gloebit.com</a>',
							),
						)
					) . '</li></ol>'
					. w4os_format_ini(
						array(
							'OpenSim.ini' => array(
								'[Economy]' => array(
									'economymodule'      => 'Gloebit',
									'economy'            => ( ! empty( W4OS_GRID_INFO['economy'] ) ) ? W4OS_GRID_INFO['economy'] : get_home_url( null, '/economy/' ),
									'SellEnabled'        => 'true',
									'; PriceUpload'      => '0',
									'; PriceGroupCreate' => '0',
								),
								'[Gloebit]' => array(
									'Enabled'        => 'true',
									'GLBEnvironment' => 'production',
									'GLBKey'         => '(your Gloebit app key)',
									'GLBSecret'      => '(your Gloebit app secret)',
									'GLBOwnerName'   => 'Banker Name',
									'GLBOwnerEmail'  => 'banker@example.org',
									'GLBSpecificConnectionString' => W4OS::sprintf_safe(
										'"Data Source=%1$s;Database=%2$s;User ID=%3$s;Password=%4$s;Old Guids=true;',
										( $use_default_db ? get_option( 'w4os_db_host' ) : get_option( 'w4os_economy_db_host' ) ),
										( $use_default_db ? get_option( 'w4os_db_database' ) : get_option( 'w4os_economy_db_database' ) ),
										( $use_default_db ? get_option( 'w4os_db_user' ) : get_option( 'w4os_economy_db_user' ) ),
										'your_password',
									),
								),
							),
							'Robust.HG.ini (optional, for grid-wide support)' => array(
								'[GridInfoService]' => array(
									'economy' => ( ! empty( W4OS_GRID_INFO['economy'] ) ) ? W4OS_GRID_INFO['economy'] : get_home_url( null, '/economy/' ),
								),
								'[LoginService]'    => array(
									'Currency' => 'G$',
								),
							),
						)
					) . '</p>',
					'save_field' => false,
					'visible'    => array(
						'when'     => array( array( 'currency_provider', '=', 'gloebit' ) ),
						'relation' => 'or',
					),
				),
				array(
					'name'       => __( 'Money Script Access Key', 'w4os' ),
					'id'         => $prefix . 'money_script_access_key',
					'type'       => 'text',
					'std'        => get_option( 'w4os_money_script_access_key' ),
					'save_field' => false,
					'visible'    => array(
						'when'     => array( array( 'currency_provider', '=', 'none' ) ),
						'relation' => 'or',
					),
					'desc'       => '<p>'
					. __( 'Choose a unique access key and set it in MoneyServer.ini', 'w4os' )
					. w4os_format_ini(
						array(
							'MoneyServer.ini' => array(
								'[MoneyServer]' => array(
									'EnableScriptSendMoney' => 'true',
									'MoneyScriptAccessKey' => esc_attr( get_option( 'w4os_money_script_access_key' ) ),
								),
								'[MySql]'       => array(
									'hostname' => ( $use_default_db ) ? get_option( 'w4os_db_host' ) : get_option( 'w4os_economy_db_host' ),
									'database' => ( $use_default_db ) ? get_option( 'w4os_db_database' ) : get_option( 'w4os_economy_db_database' ),
									'username' => ( $use_default_db ) ? get_option( 'w4os_db_user' ) : get_option( 'w4os_economy_db_user' ),
									'password' => '(your password)',
								),
							),
						)
					),
				),
				array(
					'name'       => __( 'Podex Options', 'w4os' ),
					'id'         => $prefix . 'podex_options',
					'type'       => 'group',
					'visible'    => array(
						'when'     => array( array( 'currency_provider', '=', 'podex' ) ),
						'relation' => 'or',
					),
					'save_field' => false,
					'fields'     => array(
						array(
							'name'        => __( 'Podex Redirect Message', 'w4os' ),
							'id'          => $prefix . 'podex_error_message',
							'type'        => 'text',
							'std'         => get_option( 'w4os_podex_error_message' ),
							'placeholder' => __( 'Please use our terminals in-world to proceed. Click OK to teleport to Podex Exchange area.', 'w4os' ),
						),
						array(
							'name'        => __( 'Exchange Teleport URL', 'w4os' ),
							'id'          => $prefix . 'podex_teleport_url',
							'type'        => 'text',
							'required'    => true,
							'std'         => get_option( 'w4os_podex_redirect_url' ),
							'placeholder' => 'secondlife://Podex Exchange/128/128/21',
						),
					),
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
			'opensimulator_page_w4os-economy', // Settings page slug where the sidebar appears
			'side' // Position of the meta box (sidebar)
		);
	}

	function sidebar_content() {
		echo '<ul><li>' . join(
			'</li><li>',
			array(
				__( 'Economy helpers are additional scripts needed if you implement economy on your grid (with real or fake currency).', 'w4os' ),
				__( 'Helper scripts allow communication between the money server and the grid: current balance update, currency cost estimation, land and object sales, payments...', 'w4os' ),
				'<strong>' . __( 'This plugin only provides the web helpers required by the money server module. A third-party module must be installed and configured on the simulator, for example:', 'w4os' ) . '</strong>',
				'<ul><li>' . join(
					'</li><li>',
					array(
						'Gloebit (<a href=https://www.gloebit.com/ target=_blank>gloebit.com</a>)',
						'Podex (<a href=http://www.podex.info/p/info-for-grid-owners.html target=_blank>podex.info</a>)',
						'DTL/NSL Money Server (<a href=http://www.nsl.tuis.ac.jp/xoops/modules/xpwiki/?OpenSim%2FMoneyServer>nsl.tuis.ac.jp</a>)',
					)
				) . '</li></ul>',
				'&nbsp;',
				__( 'Ready to use binaries and example config files can be downloaded here:', 'w4os' )
				. '<br><a href="https://github.com/magicoli/opensim-helpers/tree/master/bin">github.com/magicoli/opensim-helpers</a>',
			)
		) . '</li></ul>';
	}

	function sanitize_options() {
		if ( empty( $_POST ) ) {
			return;
		}

		if ( isset( $_POST['nonce_economy-settings'] ) && wp_verify_nonce( $_POST['nonce_economy-settings'], 'rwmb-save-economy-settings' ) ) {
			$options = array_merge(
				array(
					// 'w4os_provide_economy' => false,
					'w4os_economy_helper_uri'      => null,
					'w4os_currency_rate'           => null,
					'w4os_money_script_access_key' => null,
					'w4os_currency_provider'       => null,
					'w4os_podex_options'           => array(),
				),
				$_POST
			);
			$provide = isset( $_POST['w4os_provide_economy'] ) ? true : false;
			update_option( 'w4os_provide_economy_helpers', $provide );

			if ( $provide ) {
				update_option( 'w4os_economy_helper_uri', $options['w4os_economy_helper_uri'] );
				update_option( 'w4os_currency_rate', $options['w4os_currency_rate'] );
				update_option( 'w4os_money_script_access_key', $options['w4os_money_script_access_key'] );

				$use_default_db = isset( $_POST['w4os_economy-db']['use_default'] );
				update_option( 'w4os_economy_use_default_db', $use_default_db );
				if ( ! $use_default_db ) {
					$credentials = array_map( 'esc_attr', $_POST['w4os_economy-db'] );
					update_option( 'w4os_economy_db_host', $credentials['host'] );
					update_option( 'w4os_economy_db_port', $credentials['port'] );
					update_option( 'w4os_economy_db_database', $credentials['database'] );
					update_option( 'w4os_economy_db_user', $credentials['user'] );
					update_option( 'w4os_economy_db_pass', $credentials['pass'] );
				}

				$provider = ( $options['w4os_currency_provider'] == 'none' ) ? null : $options['w4os_currency_provider'];
				update_option( 'w4os_currency_provider', $provider );

				switch ( $provider ) {
					case 'podex':
						$podex = array_merge(
							array(
								'w4os_podex_error_message' => null,
								'w4os_podex_teleport_url'  => null,
							),
							$_POST['w4os_podex_options']
						);
						update_option( 'w4os_podex_error_message', $podex['w4os_podex_error_message'] );
						update_option( 'w4os_podex_redirect_url', $podex['w4os_podex_teleport_url'] );
						break;

					case null:
						update_option( 'w4os_money_script_access_key', $options['w4os_money_script_access_key'] );
						break;
				}
			}
		}
	}
}

$this->loaders[] = new W4OS_Economy();
