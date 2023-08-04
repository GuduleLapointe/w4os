<?php if ( ! defined( 'W4OS_PLUGIN' ) ) {
	die;}

class W4OS_Assets extends W4OS_Loader {
	public $provide;
	public $internal_url;
	public $external_url;
	public $url;

	public function __construct() {
		$this->constants();

		// This is supposed to be handled by rewrite rules hook, but doing it
		// manually prevents waiting for the whole init process to load
		// $this->w4os_redirect_if_asset();
	}

	public function init() {
		$this->provide      = get_option( 'w4os_provide_asset_server', false );
		$this->internal_url = get_option( 'w4os_internal_asset_server_uri', false );
		$this->external_url = get_option( 'w4os_external_asset_server_uri', false );
		$this->url          = w4os_asset_server_uri();

		$this->actions = array(
			array(
				'hook'     => 'init',
				'callback' => 'sanitize_options',
			),
			array(
				'hook'     => 'init',
				'callback' => 'rewrite_rules',
			),
			array(
				'hook'     => 'admin_init',
				'callback' => 'register_permalinks_options',
			),
			array(
				'hook'     => 'template_include',
				'callback' => 'template_include',
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
				'callback' => 'register_query_vars',
			),
		);
	}

	function constants() {
		define( 'W4OS_ASSETS_SERVER_TIMEOUT', 8 ); // timeout in seconds, to wait while requesting an asset (default to 8)
		define( 'W4OS_ASSETS_DO_RESIZE', false ); // shall we resize picture to width=W4OS_ASSETS_RESIZE_FIXED_WIDTH ?
		define( 'W4OS_ASSETS_RESIZE_FIXED_WIDTH', 256 ); // width in pixels
		define( 'W4OS_NULL_KEY_IMG', dirname( dirname( __FILE__ ) ) . '/images/assets-no-img' ); // no extension here
		define( 'W4OS_ASSETS_CACHE_TTL', 86400 ); // 1 day
		define( 'W4OS_ASSETS_CACHE_IMG_FOLDER', 'assets/images' );

		define(
			'W4OS_WEB_ASSETS_SERVER_URI',
			( get_option( 'w4os_provide_asset_server' ) == 1 )
			? get_home_url( null, '/' . get_option( 'w4os_assets_slug' ) . '/' )
			: esc_attr( get_option( 'w4os_external_asset_server_uri' ) )
		);
		if ( get_option( 'w4os_provide_asset_server' ) == 1 ) {
			update_option( 'w4os_internal_asset_server_uri', W4OS_WEB_ASSETS_SERVER_URI );
		}
		if ( ! get_option( 'w4os_login_page' ) ) {
			update_option( 'w4os_login_page', 'profile' );
		}
	}

	function register_settings_pages( $settings_pages ) {
		$settings_pages[] = array(
			'menu_title' => __( 'Assets Server', 'w4os' ),
			'page_title' => __( 'Web Assets Server', 'w4os' ),
			'id'         => 'assets-server',
			'position'   => 0,
			'parent'     => 'w4os',
			'style'      => 'no-boxes',
			'icon_url'   => 'dashicons-admin-generic',
		);

		return $settings_pages;
	}

	function register_settings_fields( $meta_boxes ) {
		$prefix = 'w4os_';

		$meta_boxes[] = array(
			'title'          => __( 'Assets Server Fields', 'w4os' ),
			'id'             => 'assets-server',
			'settings_pages' => array( 'assets-server' ),
			'class'          => 'w4os-settings',
			'fields'         => array(
				array(
					'name'       => __( 'Provide Web Assets Server', 'w4os' ),
					'id'         => $prefix . 'provide',
					'type'       => 'switch',
					'desc'       => __( 'A web assets server is required to display in-world assets (from the grid) on the website (e.g. profile pictures).', 'w4os' ),
					'style'      => 'rounded',
					'std'        => $this->provide,
					'save_field' => false,
				),
				array(
					'name'       => __( 'Web Assets Server URL', 'w4os' ),
					'id'         => $prefix . 'internal_url',
					'type'       => 'url',
					'desc'       => preg_replace(
						'/\[(.*)\]/',
						'<a href="' . get_admin_url( '', 'options-permalink.php' ) . '">$1</a>',
						__( 'You can set the assets slug in [permalinks settings page]', 'w4os' ),
					),
					'std'        => $this->internal_url,
					'disabled'   => true,
					'readonly'   => true,
					'save_field' => false,
					'visible'    => array(
						'when'     => array( array( 'provide', '=', 1 ) ),
						'relation' => 'or',
					),
				),
				array(
					'name'        => __( 'Web Assets Server URL', 'w4os' ),
					'id'          => $prefix . 'external_url',
					'type'        => 'url',
					'desc'        => __( 'If \'Provide Web Assets Server\' is disabled, it is necessary to install a third-party service and enter its URL here to ensure the proper functioning of the plugin.', 'w4os' ),
					'placeholder' => 'http://example.org/assets/assets.php?id=',
					'std'         => $this->external_url,
					'required'    => true,
					'save_field'  => false,
					'visible'     => array(
						'when'     => array( array( 'provide', '!=', 1 ) ),
						'relation' => 'or',
					),
				),
			),
		);

		return $meta_boxes;
	}

	// $this->internal_url = get_option( 'w4os_internal_asset_server_uri', false );
	// $this->external_url = get_option( 'w4os_external_asset_server_uri', false );
	function sanitize_options() {
		if ( empty( $_POST ) ) {
			return;
		}

		if ( isset( $_POST['nonce_assets-server'] ) ) {
			$this->provide = isset( $_POST['w4os_provide'] ) ? $_POST['w4os_provide'] : false;
			if ( $this->provide != get_option( 'w4os_provide_asset_server' ) ) {
				update_option( 'w4os_flush_rewrite_rules', true );
			}
			update_option( 'w4os_provide_asset_server', $this->provide );
			$this->external_url = isset( $_POST['w4os_external_url'] ) ? $_POST['w4os_external_url'] : null;
			update_option( 'w4os_external_asset_server_uri', $this->external_url );
			$this->internal_url = trailingslashit( get_home_url( null, '/' . get_option( 'w4os_assets_slug' ) ) );
			// update_option('w4os_internal_asset_server_uri', $this->internal_url );
			return;
		}

		if ( isset( $_POST['w4os-permalinks-assets-nonce'] ) ) {
			$nonce = isset( $_REQUEST['w4os-permalinks-assets-nonce'] ) ? $_REQUEST['w4os-permalinks-assets-nonce'] : false;
			if ( wp_verify_nonce( $nonce, 'w4os-permalinks-assets' ) ) {
				$this->slug = empty( $_POST['w4os_assets_slug'] ) ? 'assets' : sanitize_title( $_POST['w4os_assets_slug'] );
				if ( $this->slug !== get_option( 'w4os_assets_slug' ) ) {
					update_option( 'w4os_flush_rewrite_rules', true );
				}
				update_option( 'w4os_assets_slug', $this->slug );
			}
			return;
		}

		// error_log(print_r($_POST, true));
	}

	function register_permalinks_options() {
		add_settings_section(
			'w4os_permalinks',
			'W4OS',
			array( $this, 'w4os_permalinks_output' ),
			'permalink',
		);
		add_settings_field(
			'w4os_assets_slug',
			__( 'Assets base', 'w4os' ),
			array( $this, 'w4os_assets_slug_output' ),
			'permalink',
			'w4os_permalinks'
		);
	}

	function w4os_permalinks_output() {
		// Permalinks W4OS Section desciption
		return;
	}

	function w4os_assets_slug_output() {
		printf(
			'<input type="hidden" name="w4os-permalinks-assets-nonce" value="%s">',
			wp_create_nonce( 'w4os-permalinks-assets' ),
		);
		printf(
			'<input name="w4os_assets_slug" type="text" class="regular-text code" value="%s" placeholder="assets" />',
			esc_attr( get_option( 'w4os_assets_slug' ) ),
		);
	}

	function register_query_vars( $query_vars ) {
		$query_vars[] = 'asset_uuid';
		$query_vars[] = 'asset_format';
		return $query_vars;
	}

	function rewrite_rules() {
		add_rewrite_rule( esc_attr( get_option( 'w4os_assets_slug' ), 'assets' ) . '/([a-fA-F0-9-]+)(\.[a-zA-Z0-9]+)?[/]?$', 'index.php?asset_uuid=$matches[1]&asset_format=$matches[2]', 'top' );
	}

	/**
	 * This seems like an oldd method to do the stuff and has been replaced by
	 * template_include
	 */
	// function w4os_redirect_if_asset() {
	// $url          = getenv( 'REDIRECT_URL' );
	// $uuid_pattern = '[a-fA-F0-9-]{8}-[a-fA-F0-9-]{4}-[a-fA-F0-9-]{4}-[a-fA-F0-9-]{4}-[a-fA-F0-9-]{12}';
	// $ext_pattern  = '[a-zA-Z0-9]{3}[a-zA-Z0-9]?';
	// if ( ! preg_match(
	// '#' . preg_replace( ':^/:', '', esc_attr( parse_url( wp_upload_dir()['baseurl'], PHP_URL_PATH ) ) ) . '/w4os/assets/images/' . $uuid_pattern . '\.' . $ext_pattern . '$' . '#',
	// $url,
	// ) ) {
	// return false;
	// }
	//
	// $image = explode( '.', basename( $url ) );
	// if ( count( $image ) != 2 ) {
	// return false;
	// }
	// $query_asset  = $image[0];
	// $query_format = $image[1];
	// if ( ! preg_match( '/^(jpg|png)$/i', $query_format ) ) {
	// return false;
	// }
	//
	// require W4OS_DIR . '/templates/assets-render.php';
	// die();
	// }

	function template_include( $template ) {
		if ( empty( get_query_var( 'asset_uuid' ) ) ) {
			return $template;
		}
		return W4OS_DIR . '/templates/assets-render.php';
	}
}

$this->loaders[] = new W4OS_Assets();
