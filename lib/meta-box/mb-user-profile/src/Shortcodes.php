<?php
namespace MetaBox\UserProfile;

use MetaBox\UserProfile\ConfigStorage;
use MetaBox\UserProfile\Forms\Factory;

class Shortcodes {
	public function __construct() {
		$types = ['register', 'login', 'info'];
		foreach ( $types as $type ) {
			add_shortcode( "mb_user_profile_$type", function( $atts ) use ( $type ) {
				return $this->render_shortcode( $atts, $type );
			} );
		}

		add_action( 'template_redirect', [ $this, 'handle_submission' ] );
	}

	public function render_shortcode( $atts, $type ) {
		/*
		 * Do not render the shortcode in the admin.
		 * Prevent errors with enqueue assets in Gutenberg where requests are made via REST to preload the post content.
		 */
		if ( is_admin() ) {
			return '';
		}

		wp_enqueue_style( 'mbup', MBUP_URL . 'assets/user-profile.css', [], MBUP_VER );

		$form = Factory::make( $atts, $type );
		ob_start();
		$form->render();

		return ob_get_clean();
	}

	public function handle_submission() {
		$key    = (string) rwmb_request()->post( 'mbup_key' );
		$type   = (string) rwmb_request()->post( 'mbup_type' );
		$config = ConfigStorage::get( $key );
		if ( empty( $config ) || empty( $type ) ) {
			return;
		}
		$form = Factory::make( $config, $type );
		if ( empty( $form->meta_boxes ) ) {
			return;
		}

		// Make sure to include the WordPress media uploader functions to process uploaded files.
		if ( ! function_exists( 'media_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
		}

		// Remove existings errors.
		$codes = $form->error->get_error_codes();
		foreach ( $codes as $code ) {
			$form->error->remove( $code );
		}

		$form->process();

		// Don't redirect if errors to get the same form object in handle_submission() and render().
		if ( $form->error->has_errors() ) {
			return;
		}

		$redirect = empty( $config['redirect'] ) ? add_query_arg( 'rwmb-form-submitted', $key ) : $config['redirect'];
		$redirect = apply_filters( 'rwmb_profile_redirect', $redirect, $config );
		wp_safe_redirect( $redirect );
		die;
	}
}
