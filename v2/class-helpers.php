<?php if ( ! defined( 'W4OS_PLUGIN' ) ) {
	die;}

class W4OS_Helpers extends W4OS_Loader {
	public $provide;
	public $internal_url;
	public $external_url;
	public $url;

	public function __construct() {
		$this->constants();

		// This is supposed to be handled by rewrite rules hook, but doing it
		// manually prevents waiting for the whole init process to load
		// $this->w4os_redirect_if_helpers();
	}

	public function init() {
		// $this->provide      = get_option( 'w4os_provide_helpers_server', false );
		// $this->internal_url = get_option( 'w4os_internal_helpers_server_uri', false );
		// $this->external_url = get_option( 'w4os_external_helpers_server_uri', false );
		// $this->url          = w4os_helpers_server_uri();

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
				'priority' => 9,
			),
			// array(
			// 'hook'     => 'template_include',
			// 'callback' => 'template_include',
			// ),
		);

		$this->filters = array(
			// array(
			// 'hook'     => 'mb_settings_pages',
			// 'callback' => 'register_settings_pages',
			// ),
			// array(
			// 'hook'     => 'rwmb_meta_boxes',
			// 'callback' => 'register_settings_fields',
			// ),
			// array(
			// 'hook'     => 'query_vars',
			// 'callback' => 'register_query_vars',
			// ),
		);
	}

	function constants() {
		if ( empty( get_option( 'w4os_helpers_slug' ) ) ) {
			update_option( 'w4os_helpers_slug', 'helpers' );
		}

		// define(
		// 'W4OS_WEB_HELPERS_SERVER_URI',
		// ( get_option( 'w4os_provide_helpers_server' ) == 1 )
		// ? get_home_url( null, '/' . get_option( 'w4os_helpers_slug' ) . '/' )
		// : esc_attr( get_option( 'w4os_external_helpers_server_uri' ) )
		// );
		// if ( get_option( 'w4os_provide_helpers_server' ) == 1 ) {
		// update_option( 'w4os_internal_helpers_server_uri', W4OS_WEB_HELPERS_SERVER_URI );
		// }
	}

	function sanitize_options() {
		if ( empty( $_POST ) ) {
			return;
		}

		// if ( isset( $_POST['nonce_helpers-server'] ) ) {
		// $this->provide = isset( $_POST['w4os_provide'] ) ? $_POST['w4os_provide'] : false;
		// if ( $this->provide != get_option( 'w4os_provide_asset_server' ) ) {
		// update_option( 'w4os_flush_rewrite_rules', true );
		// }
		// update_option( 'w4os_provide_asset_server', $this->provide );
		// $this->external_url = isset( $_POST['w4os_external_url'] ) ? $_POST['w4os_external_url'] : null;
		// update_option( 'w4os_external_asset_server_uri', $this->external_url );
		// $this->internal_url = trailingslashit( get_home_url( null, '/' . get_option( 'w4os_helpers_slug' ) ) );
		// update_option('w4os_internal_asset_server_uri', $this->internal_url );
		// return;
		// }
		//
		if ( isset( $_POST['w4os-permalinks-helpers-nonce'] ) ) {
			$nonce = isset( $_REQUEST['w4os-permalinks-helpers-nonce'] ) ? $_REQUEST['w4os-permalinks-helpers-nonce'] : false;
			if ( wp_verify_nonce( $nonce, 'w4os-permalinks-helpers' ) ) {
				$this->slug = empty( $_POST['w4os_helpers_slug'] ) ? 'helpers' : sanitize_title( $_POST['w4os_helpers_slug'] );
				if ( $this->slug !== get_option( 'w4os_helpers_slug' ) ) {
					update_option( 'w4os_flush_rewrite_rules', true );
				}
				update_option( 'w4os_helpers_slug', $this->slug );

				W4OS_Offline::set_offline_uri();
				W4OS_Search::set_search_url();
			}
			return;
		}
		//
		// // error_log(print_r($_POST, true));
	}

	function register_permalinks_options() {
		add_settings_section(
			'w4os_permalinks',
			'W4OS',
			array( $this, 'w4os_permalinks_output' ),
			'permalink',
		);
		add_settings_field(
			'w4os_helpers_slug',
			__( 'Helpers base', 'w4os' ),
			array( $this, 'w4os_helpers_slug_output' ),
			'permalink',
			'w4os_permalinks'
		);
	}

	function w4os_permalinks_output() {
		// Permalinks W4OS Section desciption
		return;
	}

	function w4os_helpers_slug_output() {
		printf(
			'<input type="hidden" name="w4os-permalinks-helpers-nonce" value="%s">',
			wp_create_nonce( 'w4os-permalinks-helpers' ),
		);
		printf(
			'<input name="w4os_helpers_slug" type="text" class="regular-text code" value="%s" placeholder="helpers" />',
			esc_attr( get_option( 'w4os_helpers_slug' ) ),
		);
	}

	function rewrite_rules() {
		// add_rewrite_rule( esc_attr( get_option( 'w4os_helpers_slug' ), 'helpers' ) . '/([a-fA-F0-9-]+)(\.[a-zA-Z0-9]+)?[/]?$', 'index.php?helpers_uuid=$matches[1]&helpers_format=$matches[2]', 'top' );
	}

	// function template_include( $template ) {
	// if ( empty( get_query_var( 'helpers_uuid' ) ) ) {
	// return $template;
	// }
	// return W4OS_DIR . '/templates/helpers-render.php';
	// }
}

$this->loaders[] = new W4OS_Helpers();
