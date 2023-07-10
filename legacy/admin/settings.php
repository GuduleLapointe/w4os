<?php if ( ! defined( 'W4OS_ADMIN' ) ) {
	die;}

function w4os_camelcase( $string ) {
	if ( ! is_string( $string ) ) {
		return $string;
	}
	return str_replace( ' ', '', ucwords( str_replace( '-', ' ', sanitize_title( $string ) ) ) );
}
function w4os_register_settings() {
	$grid_info = w4os_update_grid_info();
	// $check_login_uri = 'http://' . (!empty(get_option('w4os_login_uri'))) ? esc_attr(get_option('w4os_login_uri')) : 'http://localhost:8002';
	$default_loginuri        = ( isset( $grid_info['login'] ) ) ? $grid_info['login'] : '';
	$default_gridname        = ( isset( $grid_info['gridname'] ) ) ? $grid_info['gridname'] : '';
	$default_search_url      = ( get_option( 'w4os_provide_search' ) ? str_replace( 'https:', 'http:', get_home_url() ) : 'http://2do.directory' ) . '/helpers/query.php';
	$default_search_register = ( get_option( 'w4os_provide_search' ) ? str_replace( 'https:', 'http:', get_home_url() ) : 'http://2do.directory' ) . '/helpers/register.php';
	$login_uri               = get_option( 'w4os_login_uri', 'yourgrid.org:8002' );
	$gatekeeperURL           = preg_match( '#https?://#', $login_uri ) ? $login_uri : 'http://' . $login_uri;

	if ( get_option( 'w4os_provide_search' ) || empty( get_option( 'w4os_search_url' ) ) ) {
		update_option( 'w4os_search_url', $default_search_url );
	}
	if ( get_option( 'w4os_provide_search' ) || empty( get_option( 'w4os_search_register' ) ) ) {
		update_option( 'w4os_search_register', $default_search_register );
	}

	if ( ! w4os_option_exists( 'w4os_configuration_instructions' ) ) {
		update_option( 'w4os_configuration_instructions', true );
	}
	if ( ! w4os_option_exists( 'w4os_hypevents_url' ) ) {
		update_option( 'w4os_hypevents_url', 'http://2do.pm/events/' );
	}

	if ( get_option( 'w4os_search_use_robust_db' ) ) {
		update_option( 'w4os_search_db_host', get_option( 'w4os_db_host' ) );
		update_option( 'w4os_search_db_database', get_option( 'w4os_db_database' ) );
		update_option( 'w4os_search_db_user', get_option( 'w4os_db_user' ) );
		update_option( 'w4os_search_db_pass', get_option( 'w4os_db_pass' ) );
	}

	$settings_pages = array(
		'w4os_status'   => array(
			'sections' => array(
				'default' => array(
					'fields' => array(
						'w4os_sync_users' => array(
							'type'  => 'hidden',
							'value' => 1,
							'name'  => 'Synchronize users now',
						),
						// 'w4os_check_urls_now' => array(
						// 'type' => 'hidden',
						// 'value' => 1,
						// 'name' => 'Check urls now',
						// ),
					),
				),
			),
		),
		'w4os_settings' => array(
			'sections' => array(
				'w4os_options_database' => array(
					'name'   => __( 'Robust server database', 'w4os' ),
					'fields' => array(
						'w4os_db_host'     => array(
							'name' => __( 'Hostname', 'w4os' ),
						),
						'w4os_db_database' => array(
							'name' => __( 'Database Name', 'w4os' ),
						),
						'w4os_db_user'     => array(
							'name'         => __( 'Username', 'w4os' ),
							'autocomplete' => 'off',
						),
						'w4os_db_pass'     => array(
							'name'         => __( 'Password', 'w4os' ),
							'type'         => 'password',
							'autocomplete' => 'off',
						),
					),
				),
				'w4os_options_users'    => array(
					'name'   => __( 'Grid users', 'w4os' ),
					'fields' => array(
						'w4os_profile_page'               => array(
							'type'        => 'radio',
							'name'        => __( 'Profile Page', 'w4os' ),
							'values'      => array(
								'provide' => __( 'Provide web profile page for avatars', 'w4os' ),
								// 'custom' => __('Custom page (with shortcode)', 'w4os'),
								'default' => __( 'Defaults', 'w4os' ),
							),
							'readonly'    => true,
							'default'     => 'provide',
							'description' => sprintf(
								__( 'The page %1$s must exist, as defined in %2$spermalinks settings%3$s.', 'w4os' ),
								'<code>' . get_home_url( null, get_option( 'w4os_profile_slug', 'profile' ) ) . '</code>',
								'<a href=' . get_admin_url( '', 'options-permalink.php' ) . '>',
								'</a>',
							),
						),
						'w4os_profile_page'               => array(
							'type'        => 'radio',
							'name'        => __( 'Profile Page', 'w4os' ),
							'values'      => array(
								'provide' => __( 'Provide web profile page for avatars', 'w4os' ),
								// 'custom' => __('Custom page (with shortcode)', 'w4os'),
								'default' => __( 'Defaults', 'w4os' ),
							),
							'default'     => 'provide',
							'description' => ( w4os_check_db_tables( 'userprofile' ) ) ? sprintf(
								__( 'The page %1$s must exist, as defined in %2$spermalinks settings%3$s.', 'w4os' ),
								'<code>' . get_home_url( null, get_option( 'w4os_profile_slug', 'profile' ) ) . '</code>',
								'<a href=' . get_admin_url( '', 'options-permalink.php' ) . '>',
								'</a>',
							) : sprintf(
								__( 'Table %s not found. User Profiles must be activated in Robust to enable profile page.', 'w4os' ),
								'<code>userprofile</code>',
							),
						),
						'w4os_configuration_instructions' => array(
							'type'  => 'boolean',
							'name'  => '', // __('Configuration instructions', 'w4os'),
							'label' => __( 'Show configuration instructions to new users.', 'w4os' ),
						),

						'w4os_login_page'                 => array(
							'type'        => 'radio',
							'name'        => __( 'Login Page', 'w4os' ),
							'values'      => array(
								'profile' => __( 'Use profile page as login page', 'w4os' ),
								// 'custom' => __('Custom page (with shortcode)', 'w4os'),
								'default' => __( 'Default', 'w4os' ),
							),
							'default'     => 'profile',
							'description' => __( '', 'w4os' ),
						),
						'w4os_userlist_replace_name'      => array(
							'type'  => 'boolean',
							'name'  => __( 'Replace user name', 'w4os' ),
							'label' => __( 'Show avatar name instead of user name in users list.', 'w4os' ),
						),
						'w4os_exclude'                    => array(
							'type'        => 'checkbox',
							'name'        => __( 'Exclude from stats', 'w4os' ),
							'values'      => array(
								'models'    => __( 'Models', 'w4os' ),
								'nomail'    => __( 'Accounts without mail address', 'w4os' ),
								'hypergrid' => __( 'Hypergrid visitors', 'w4os' ),
							),
							'description' => __( 'Accounts without email address are usually test accounts created from the console. Uncheck only if you have real avatars without email address.', 'w4os' ),
						),
					),
				),
				// 'w4os_options_misc' => array(
				// 'name' => __('Misc', 'w4os'),
				// 'fields' => array(
				// 'w4os_assets_permalink' => array(
				// 'type' => 'description',
				// 'name' => __('Permalinks', 'w4os'),
				// 'description' => sprintf(__('Set w4os slugs on %spermalink options page%s.', 'w4os'), '<a href=' . get_admin_url('', 'options-permalink.php').'>', '</a>'),
				// ),
				// ),
				// ),
			),
		),
		'w4os_helpers'  => array(
			'sections' => array(
				'w4os_options_search'            => array(
					'name'   => __( 'Search', 'w4os' ),
					'fields' => array(
						'w4os_provide_search'       => array(
							'type'        => 'boolean',
							'name'        => __( 'Provide In-world Search', 'w4os' ),
							'onchange'    => 'onchange="valueChanged(this)"',
							'description' => sprintf(
								'<ul><li>%s</li><li>%s</li></ul>',
								__( 'Enable to use a local search engine, allowing only local results (recommended for private grids).', 'w4os' ),
								__( 'Disable to use an external search engine like 2do Directory, allowing results from both your grid and other public grids.', 'w4os' ),
							),
						),
						'w4os_search_use_robust_db' => array(
							'type'     => 'boolean',
							'name'     => __( 'Search database', 'w4os' ),
							'default'  => false,
							'onchange' => 'onchange="valueChanged(this)"',
							'label'    => __( 'Use the same database as Robust' ),
						),
						'w4os_search_db_host'       => array(
							'name'    => __( 'Hostname', 'w4os' ),
							'default' => esc_attr( get_option( 'w4os_db_host', 'localhost' ) ),
						),
						'w4os_search_db_database'   => array(
							'name'    => __( 'Database Name', 'w4os' ),
							'default' => esc_attr( get_option( 'w4os_db_database', 'currency' ) ),
						),
						'w4os_search_db_user'       => array(
							'name'         => __( 'Username', 'w4os' ),
							'autocomplete' => 'off',
							'default'      => esc_attr( get_option( 'w4os_db_user', 'opensim' ) ),
						),
						'w4os_search_db_pass'       => array(
							'name'         => __( 'Password', 'w4os' ),
							'type'         => 'password',
							'autocomplete' => 'off',
							'default'      => esc_attr( get_option( 'w4os_db_pass' ) ),
						),
						'w4os_search_url'           => array(
							'name'        => __( 'Search Engine URL', 'w4os' ),
							'placeholder' => $default_search_url,
							'default'     => $default_search_url,
							'description' => sprintf(
								'<ul><li>%s</li><li>%s</li><li>%s</li></ul>',
								__( 'URL of the search engine used by the viewer to provide search results (without arguments)', 'w4os' ),
								__( 'In OpenSim.ini, only one can be set', 'w4os' ),
								__( 'Services using w4os engine need the gatekeeper URI (usually the login URI) to be passed as gk argument. Requirements may vary for other engines.', 'w4os' ),
							) . w4os_format_ini(
								array(
									'OpenSim.ini' => array(
										'[Search]' => array(
											'Module'       => 'OpenSimSearch',
											( get_option( 'w4os_provide_search' ) ? '' : '; ' ) . 'SearchURL' => '"' . ( empty( get_option( 'w4os_search_url' ) ) ? $default_search_url : get_option( 'w4os_search_url' ) ) . '?gk=' . $gatekeeperURL . '"',
											( get_option( 'w4os_provide_search' ) ? '; ' : '' ) . 'SearchURL' => '"' . 'http://2do.directory/helpers/query.php?gk=' . $gatekeeperURL . '"',
											'; SearchURL ' => '"http://example.org/query.php"',
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
						'w4os_search_register'      => array(
							'name'        => __( 'Search register', 'w4os' ),
							'placeholder' => 'http://2do.directory/helpers/register.php',
							'description' =>
							__( 'Data service, used to register regions, objects or land for sale. You can resgister to several search engines.', 'w4os' )
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
						),
						'w4os_hypevents_url'        => array(
							'name'        => __( 'Events Server URL', 'w4os' ),
							'placeholder' => 'https://2do.pm/events/',
							'description' => __( 'HYPEvents Server URL, used to fetch upcoming events and make them available in search.', 'w4os' )
							. ' ' . __( 'Leave blank to ignore events or if you have an other events implementation.', 'w4os' )
							. ' <a href=https://2do.pm/ target=_blank>2do HYPEvents project</a>',
						),
					),
				),
				'w4os_options_offline'           => array(
					'name'   => __( 'Offline messages', 'w4os' ),
					'fields' => array(
						'w4os_provide_offline_messages' => array(
							'type'     => 'boolean',
							'name'     => __( 'Provide offline helper', 'w4os' ),
							'default'  => W4OS_DEFAULT_PROVIDE_ASSET_SERVER,
							'onchange' => 'onchange="valueChanged(this)"',
							// 'description' => __('Using ')
						),
						'w4os_offline_helper_uri'       => array(
							'name'        => __( 'Offline helper URI', 'w4os' ),
							'default'     => ( ! empty( W4OS_GRID_INFO['message'] ) ) ? W4OS_GRID_INFO['message'] : get_home_url( null, '/helpers/offline/' ),
							'readonly'    => true,
							'description' =>
							__( 'Set the URL in Robust and OpenSimulator configurations.', 'w4os' )
							. w4os_format_ini(
								array(
									'Robust.HG.ini' => array(
										'[GridInfoService]' => array(
											'message' => get_option( 'w4os_offline_helper_uri', get_home_url( null, '/helpers/offline/' ) ),
										),
									),
									'OpenSim.ini'   => array(
										'[Messaging]' => array(
											'OfflineMessageModule' => 'OfflineMessageModule',
											'OfflineMessageURL' => get_option( 'w4os_offline_helper_uri', get_home_url( null, '/helpers/offline/' ) ),
										),
									),
								)
							),
						),
						'w4os_offline_sender'           => array(
							'name'        => __( 'Sender e-mail address', 'w4os' ),
							'placeholder' => 'no-reply@example.com',
							// 'default' => 'no-reply@' . parse_url(W4OS_GRID_LOGIN_URI)['host'],
							'default'     => 'no-reply@' . $_SERVER['SERVER_NAME'],
							'description' => __( 'A no-reply e-mail address used to forward messages for users enabling "Email me IMs when I\'m offline" option.', 'w4os' ),
						),
					),
				),
				'w4os_options_economy'           => array(
					'name'   => 'Economy',
					'fields' => array(
						'w4os_provide_economy_helpers' => array(
							'type'        => 'boolean',
							'name'        => __( 'Provide Economy Helpers', 'w4os' ),
							'default'     => false,
							'onchange'    => 'onchange="valueChanged(this)"',
							'description' => '<p>' . __( 'Economy helpers are additional scripts needed if you implement economy on your grid (with real or fake currency).', 'w4os' ) . '</p>'
							. '<p>' . __( 'Helper scripts allow communication between the money server and the grid: current balance update, currency cost estimation, land and object sales, payments...', 'w4os' ) . '</p>'
							. '<p>' . sprintf(
								__( 'Money server is not included in OpenSimulator distribution and require a separate installation, e.g. from %s.', 'w4os' ),
								// '<a href=https://github.com/BigManzai/OpenSimCurrencyServer-2021>BigManzai OpenSimCurrencyServer</a>',
								'<a href=http://www.nsl.tuis.ac.jp/xoops/modules/xpwiki/?OpenSim%2FMoneyServer>DTL/NSL Money Server for OpenSim</a>',
								// '<a href=http://dev.gloebit.com/opensim/configuration-instructions/>Gloebit</a>',
							) . '</p>',
							// . '<p>' . __('A money server is also needed if you implement a fake currency or want to allow zero (no cost) operations.', 'w4os') . '</p>'
						),
						'w4os_economy_helper_uri'      => array(
							'name'        => __( 'Economy Base URI', 'w4os' ),
							'default'     => ( ! empty( W4OS_GRID_INFO['economy'] ) ) ? W4OS_GRID_INFO['economy'] : get_home_url( null, '/economy/' ),
							'readonly'    => true,
							'description' =>
							__( 'The URL must be set in Robust configuration.', 'w4os' )
							. w4os_format_ini(
								array(
									'Robust.HG.ini' => array(
										'[GridInfoService]' => array(
											'economy' => ( ! empty( W4OS_GRID_INFO['economy'] ) ) ? W4OS_GRID_INFO['economy'] : get_home_url( null, '/economy/' ),
										),
									),
								)
							),
						),
						'w4os_economy_use_robust_db'   => array(
							'type'        => 'boolean',
							'name'        => __( 'Economy Database', 'w4os' ),
							'default'     => true,
							'onchange'    => 'onchange="valueChanged(this)"',
							'label'       => __( 'Use the same database as Robust', 'w4os' ),
							'description' => w4os_format_ini(
								array(
									'MoneyServer.ini' => array(
										'[MySql]' => array(
											'hostname' => get_option( 'w4os_economy_db_host' ),
											'database' => get_option( 'w4os_economy_db_database' ),
											'username' => get_option( 'w4os_economy_db_user' ),
											'password' => '(hidden)',
										),
									),
								)
							),
						),
						'w4os_economy_db_host'         => array(
							'name'    => __( 'Hostname', 'w4os' ),
							'default' => esc_attr( get_option( 'w4os_db_host', 'localhost' ) ),
						),
						'w4os_economy_db_database'     => array(
							'name'    => __( 'Database Name', 'w4os' ),
							'default' => esc_attr( get_option( 'w4os_db_database', 'currency' ) ),
						),
						'w4os_economy_db_user'         => array(
							'name'         => __( 'Username', 'w4os' ),
							'autocomplete' => 'off',
							'default'      => esc_attr( get_option( 'w4os_db_user', 'opensim' ) ),
						),
						'w4os_economy_db_pass'         => array(
							'name'         => __( 'Password', 'w4os' ),
							'type'         => 'password',
							'autocomplete' => 'off',
							'default'      => esc_attr( get_option( 'w4os_db_pass' ) ),
						),
						'w4os_currency_provider'       => array(
							'name'     => __( 'Currency Provider', 'w4os' ),
							'type'     => 'radio',
							'default'  => 'internal',
							'values'   => array(
								''        => __( 'No provider, use fake money.', 'w4os' ),
								'podex'   => 'Podex (<a href=http://www.podex.info/p/info-for-grid-owners.html target=_blank>www.podex.info</a>)',
								'gloebit' => 'Gloebit (<a href=http://dev.gloebit.com/opensim/configuration-instructions/ target=_blank>www.gloebit.com</a>)',
							),
							'onchange' => 'onchange="valueChanged(this)"',
						),
						'w4os_currency_rate'           => array(
							'name'        => __( 'Currency Conversion Rate', 'w4os' ),
							'description' => __( 'Amount to pay in US$ for 1000 in-world money units. Used for cost estimation. If not set, the rate will be 10/1000 (1 cent per money unit)', 'w4os' ),
						),
						'w4os_podex_error_message'     => array(
							'name'    => __( 'Podex redirect message', 'w4os' ),
							'default' => __( 'Please use our terminals in-world to proceed. Click OK to teleport to terminals region.', 'w4os' ),
						),
						'w4os_podex_redirect_url'      => array(
							'name'        => __( 'Podex redirect URL', 'w4os' ),
							'placeholder' => 'secondlife://Welcome/128/128/21',
						),
						'w4os_money_script_access_key' => array(
							'name'        => __( 'Money Script Access Key', 'w4os' ),
							// 'default' => '123456789',
							'description' => w4os_format_ini(
								array(
									'MoneyServer.ini' => array(
										'[MoneyServer]' => array(
											'EnableScriptSendMoney' => 'true',
											'MoneyScriptAccessKey' => esc_attr( get_option( 'w4os_money_script_access_key' ) ),
										),
									),
								)
							),
						),
					),
				),
			),
		),
	);

	foreach ( $settings_pages as $page_slug => $page ) {
		add_settings_section( $page_slug, '', '', $page_slug );

		foreach ( $page['sections'] as $section_slug => $section ) {
			add_settings_section( $section_slug, ( isset( $section['name'] ) ) ? $section['name'] : $section_slug, ( isset( $section['section_callback'] ) ) ? $section['section_callback'] : '', $page_slug );
			foreach ( $section['fields'] as $field_slug => $field ) {
				$field['section'] = $section_slug;
				w4os_register_setting( $page_slug, $field_slug, $field );
			}
		}
	}
	// die();
	return;
}
add_action( 'admin_init', 'w4os_register_settings' );

function w4os_register_setting( $option_page, $option_slug, $args = array() ) {
	if ( empty( $args['type'] ) ) {
		$args['type'] = 'string';
	}
	switch ( $args['type'] ) {
		case 'checkbox':
			foreach ( $args['values'] as $option_key => $option_value ) {
				register_setting( $option_page, $option_slug . '_' . $option_key, $args );
			}
			break;

		case 'radio':
			$register_args = array(
				'name' => $args['name'],
				'type' => 'string',
			);
			register_setting( $option_page, $option_slug, $register_args );
			break;

		default:
			// w4os_admin_notice("register_setting( $option_page, $option_slug, <pre>" . print_r($args, true) . "</pre> );");
			// if(!in_array($args['type'], [ 'string', 'boolean', 'integer', 'number', 'array', 'object'] ) ) $args['type'] = 'string';
			register_setting( $option_page, $option_slug, $args );
	}
	// if(!isset($args['name'])) $args['name'] = $option_slug;
	if ( empty( $args['sanitize_callback'] ) ) {
		$args['sanitize_callback'] = 'w4os_settings_field';
	}
	// add_settings_field( $option_slug, $args['name'], $args['sanitize_callback'], 'w4os_settings', $option_page, $args);
	$args['option_slug'] = $option_slug;
	add_settings_field(
		$option_slug,                   // Field ID
		( isset( $args['name'] ) ) ? $args['name'] : $option_slug,  // Title
		$args['sanitize_callback'],            // Callback to display the field
		$option_page,                // Page
		$args['section'],
		$args,                      // Section
	);
}

function w4os_settings_field( $args, $user = false ) {
	if ( $args['option_slug'] ) {
		$field_id = $args['option_slug'];
	} elseif ( $args['label_for'] ) {
		$field_id = $args['label_for'];
	} else {
		return;
	}
	// echo "<pre>" . print_r($args, true) . "</pre>";
	// return;

	$parameters = array();
	if ( isset( $args['readonly'] ) && $args['readonly'] ) {
		$parameters[] .= 'readonly';
	}
	if ( isset( $args['disabled'] ) && $args['disabled'] ) {
		$parameters[] .= 'disabled';
	}
	if ( isset( $args['onchange'] ) ) {
		$parameters[] = $args['onchange'];
	}
	if ( isset( $args['placeholder'] ) ) {
		$parameters[] = "placeholder='" . esc_attr( $args['placeholder'] ) . "'";
	}
	if ( isset( $args['autocomplete'] ) ) {
		$parameters[] = "autocomplete='" . $args['autocomplete'] . "'";
	}
	if ( isset( $args['onfocus'] ) ) {
		$parameters[] = "onfocus='" . esc_attr( $args['onfocus'] ) . "'";
	}
	if ( isset( $args['value'] ) ) {
		$value = $args['value'];
	} elseif ( $user ) {
		$value = get_user_meta( $user->ID, $field_id, true );
	} else {
		$value = esc_attr( get_option( $field_id ) );
	}

	switch ( $args['type'] ) {
		case 'url':
			if ( $args['readonly'] ) {
				if ( ! empty( $value ) ) {
					echo sprintf( '<a href="%1$s">%1$s</a>', esc_html( $value ) );
				}
				break;
			}

		case 'string':
			echo "<input type='text' class='regular-text input-${args['type']}' id='$field_id' name='$field_id' value='" . esc_attr( $value ) . "' " . join( ' ', $parameters ) . ' />';
			break;

		case 'password':
			echo "<input type='password' class='regular-text' id='$field_id' name='$field_id' value='" . esc_attr( get_option( $field_id ) ) . "' " . join( ' ', $parameters ) . ' />';
			break;

		case 'radio':
			foreach ( $args['values'] as $option_key => $option_name ) {
				$option_id = $field_id . '_' . $option_key;
				$option    = "<input type='radio' id='$option_id' name='$field_id' value='$option_key'";
				// if (get_option($option_key)==$option_key) $parameters[] = "checked";
				$parameters['checked'] = checked( get_option( $field_id ), $option_key, false );
				$option               .= ' ' . join( ' ', $parameters ) . ' ';
				$option               .= '/>';
				$option               .= " <label for='$option_id'>$option_name</label>";
				$options[]             = $option;
			}
			if ( is_array( $options ) ) {
				echo join( '<br>', $options );
			}
			break;

		case 'boolean':
			$args['values'][ $field_id ] = true;
		case 'checkbox':
			foreach ( $args['values'] as $option_key => $option_name ) {
				if ( $args['type'] == 'checkbox' ) {
					$option_id = $field_id . '_' . $option_key;
				} else {
					$option_id   = $field_id;
					$option_name = isset( $args['label'] ) ? $args['label'] : '';
					// unset($args['description']);
				}
				if ( $user ) {
					$value = get_user_meta( $user->ID, $option_id, true );
				} else {
					$value = esc_attr( get_option( $option_id ) );
				}

				$option                = "<input type='checkbox' id='$option_id' name='$option_id' value='1'";
				$parameters['checked'] = checked( $value, true, false );
				$option               .= ' ' . join( ' ', $parameters ) . ' ';
				$option               .= '/>';
				// if($args['type']=='checkbox')
				$option .= " <label for='$option_id'>$option_name</label>";
				// else if($args['type']=='boolean') $option .= " <label for='$option_id'>$option_name</label>";
				$options[] = $option;
			}
			if ( is_array( $options ) ) {
				echo join( '<br>', $options );
			}
			break;

		case 'hidden':
			echo "<input type='hidden' class='regular-text input-${args['type']}' id='$field_id' name='$field_id' value='" . esc_attr( get_option( $field_id ) ) . "' " . join( ' ', $parameters ) . ' />';
			break;

		case 'description':
			break;

		case 'os_asset':
			echo ( ! empty( $value ) ) ? w4os_render_asset( $value ) : $args['placeholder'];
			break;

		default:
			echo "type ${args['type']} not recognized";
	}
	if ( ! empty( $args['description'] ) ) {
		echo "<p class=description>${args['description']}</p>";
	}
	// echo "<input type=text value='$one'/><pre>" . print_r($args, true) . "</pre>";
}

function w4os_settings_callback_gridinfo() {
	echo sprintf(
		'<p>%1$s %2$s</p>',
		__( 'Values must match Robust.HG.ini (or Robust.HG.ini) config file.', 'w4os' ),
		__( 'Robust server must be running. Values entered here will be checked against your Robust server and updated if needed.', 'w4os' ),
	);
}

function w4os_settings_callback_models( $arg ) {
	echo '<p>' . __( 'Grid accounts matching first name or last name set below are considered as avatar models. They will appear on the avatar registration form, with their in-world profile picture.', 'w4os' ) . '</p>';
}

// function w4os_settings_callback_webassets($arg) {
// echo "<p class=help>" . __('A web assets server is needed to display in-world assets (from the grid) on the website (e.g. profile pictures). You can use an external web assets server if you already have one installed, or use the one provided by w4os plugin.', 'w4os') . "</p>";
// }

function w4os_settings_link( $links ) {
	$url = esc_url(
		add_query_arg(
			'page',
			'w4os_settings',
			get_admin_url() . 'admin.php'
		)
	);

	array_push(
		$links,
		"<a href='$url'>" . __( 'Settings' ) . '</a>'
	);

	return $links;
}
add_filter( 'plugin_action_links_' . W4OS_PLUGIN, 'w4os_settings_link' );
