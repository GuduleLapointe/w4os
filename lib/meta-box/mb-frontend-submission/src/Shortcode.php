<?php
namespace MBFS;

class Shortcode {
	private $key;
	private $config;
	private $form;

	public function __construct() {
		add_shortcode( 'mb_frontend_form', [ $this, 'shortcode' ] );

		add_action( 'wp_ajax_mbfs_submit', [ $this, 'submit' ] );
		add_action( 'wp_ajax_nopriv_mbfs_submit', [ $this, 'submit' ] );

		add_action( 'wp_ajax_mbfs_delete', [ $this, 'delete' ] );
		add_action( 'wp_ajax_nopriv_mbfs_delete', [ $this, 'delete' ] );

		add_action( 'template_redirect', [ $this, 'handle_request' ] );
	}

	public function shortcode( $atts ) {
		/*
		 * Do not render the shortcode in the admin.
		 * Prevent errors with enqueue assets in Gutenberg where requests are made via REST to preload the post content.
		 */
		if ( is_admin() ) {
			return '';
		}

		$form = FormFactory::make( $atts );
		ob_start();
		$form->render();

		return ob_get_clean();
	}

	public function handle_request() {
		$action = (string) rwmb_request()->post( 'action' );
		$action = str_replace( 'mbfs_', '', $action );
		if ( $action && in_array( $action, ['submit', 'delete'], true ) ) {
			$this->{$action}();
		}
	}

	public function submit() {
		$this->prepare_request();

		// Make sure to include the WordPress media uploader functions to process uploaded files.
		if ( ! function_exists( 'media_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
		}

		$this->config['post_id'] = $this->form->process();

		$this->cleanup_request();

		// Don't redirect if errors to get the same form object in handle_request() and render().
		if ( $this->form->error->has_errors() ) {
			if ( $this->is_ajax() ) {
				$this->send_error_message( $this->form->error->get_error_message() );
			}
			return;
		}

		// For ajax only.
		$this->send_success_message( $this->config['confirmation'] );

		$redirect = empty( $this->config['redirect'] ) ? add_query_arg( 'rwmb-form-submitted', $this->key ) : $this->config['redirect'];

		// Allow to re-edit the submitted post.
		if ( 'true' === $this->config['edit'] ) {
			$redirect = add_query_arg( 'rwmb_frontend_field_post_id', $this->config['post_id'], $redirect );
		}

		$redirect = apply_filters( 'rwmb_frontend_redirect', $redirect, $this->config );

		wp_safe_redirect( $redirect );
		die;
	}

	public function delete() {
		$this->prepare_request();

		$this->form->delete();

		$this->cleanup_request();

		$this->send_success_message( $this->config['delete_confirmation'] );

		$redirect = empty( $this->config['redirect'] ) ? add_query_arg( 'rwmb-form-deleted', $this->key ) : $this->config['redirect'];
		$redirect = apply_filters( 'rwmb_frontend_redirect', $redirect, $this->config );

		wp_safe_redirect( $redirect );
		die;
	}

	private function prepare_request() {
		$this->key    = (string) rwmb_request()->post( 'mbfs_key' );
		$this->config = ConfigStorage::get( $this->key );

		if ( empty( $this->config ) ) {
			$this->send_error_message( __( 'Invalid request. Please try again.', 'mb-frontend-submission' ) );
		}

		$this->check_ajax();
		$this->check_recaptcha( $this->config );

		$this->form = FormFactory::make( $this->config );
	}

	private function cleanup_request() {
		ConfigStorage::delete( $this->key );
	}

	private function check_ajax() {
		if ( $this->is_ajax() && ! check_ajax_referer( 'ajax_nonce' ) ) {
			$this->send_error_message( __( 'Invalid ajax request. Please try again.', 'mb-frontend-submission' ) );
		}
	}

	private function check_recaptcha( $config ) {
		if ( ! $config['recaptcha_secret'] ) {
			return;
		}

		$token = (string) rwmb_request()->post( 'mbfs_recaptcha_token' );
		if ( ! $token ) {
			$this->send_error_message( __( 'Invalid captcha token.', 'mb-frontend-submission' ) );
		}

		$url = 'https://www.google.com/recaptcha/api/siteverify';
		$url = add_query_arg( [
			'secret'   => $config['recaptcha_secret'],
			'response' => $token,
		], $url );

		$response = wp_remote_retrieve_body( wp_remote_get( $url ) );
		$response = json_decode( $response, true );

		if ( empty( $response['action'] ) || 'mbfs' !== $response[ 'action' ] ) {
			$this->send_error_message( __( 'Invalid captcha token.', 'mb-frontend-submission' ) );
		}
	}

	private function send_error_message( $message ) {
		if ( $this->is_ajax() ) {
			wp_send_json_error( ['message' => $message] );
		}
		wp_die( $message );
	}

	private function send_success_message( $message ) {
		if ( ! $this->is_ajax() ) {
			return;
		}
		wp_send_json_success( [
			'message'  => $message,
			'redirect' => $this->form->config['redirect'],
		] );
	}

	private function is_ajax() {
		return defined( 'DOING_AJAX' ) && DOING_AJAX;
	}
}
