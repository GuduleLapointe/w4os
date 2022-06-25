<?php
namespace MetaBox\UserProfile\Forms;

use MetaBox\UserProfile\ConfigStorage;
use RWMB_Helpers_Array as Arr;

class Login extends Base {
	protected $type = 'login';

	public static function normalize( $config ) {
		// Compatible with old shortcode attributes.
		Arr::change_key( $config, 'remember', 'label_remember' );
		Arr::change_key( $config, 'lost_pass', 'label_lost_password' );
		Arr::change_key( $config, 'submit_button', 'label_submit' );

		$config = shortcode_atts( [
			'redirect'            => '',
			'form_id'             => 'login-form',

			// Google reCaptcha v3
			'recaptcha_key'       => '',
			'recaptcha_secret'    => '',

			// Appearance options.
			'label_username'      => __( 'Username or Email Address', 'mb-user-profile' ),
			'label_password'      => __( 'Password', 'mb-user-profile' ),
			'label_remember'      => __( 'Remember Me', 'mb-user-profile' ),
			'label_lost_password' => __( 'Lost Password?', 'mb-user-profile' ),
			'label_submit'        => __( 'Log In', 'mb-user-profile' ),

			'id_username'         => 'user_login',
			'id_password'         => 'user_pass',
			'id_remember'         => 'remember',
			'id_submit'           => 'submit',

			'value_username'      => '',
			'value_remember'      => false,

			'confirmation'        => __( 'You are now logged in.', 'mb-user-profile' ),

			'password_strength'   => 'weak',
		], $config );

		return $config;
	}

	protected function has_privilege() {
		if ( is_user_logged_in() && ! $this->is_processed() ) {
			esc_html_e( 'You are already logged in.', 'mb-user-profile' );
			return false;
		}
		return true;
	}

	public function process() {
		if ( isset( $_GET['rwmb-lost-password'] ) ) {
			return $this->retrieve_password();
		}

		if ( isset( $_GET['rwmb-reset-password'] ) ) {
			return $this->reset_password();
		}
		$request     = rwmb_request();
		$credentials = [
			'user_login'    => $request->post( 'user_login' ),
			'user_password' => $request->post( 'user_pass' ),
			'remember'      => (bool) $request->post( 'remember' ),
		];
		$user = $this->get_user( $credentials['user_login'] );
		if ( ! $user ) {
			$this->error->add( 'invalid-login', __( 'Invalid username or email.', 'mb-user-profile' ) );
			return;
		}
		$user_confirmation_code = get_user_meta( $user->ID, 'mbup_confirmation_code', true );
		if ( $user_confirmation_code ) {
			$this->error->add( 'not-confirmed', __( 'Your account is not confirmed yet. Please, check your email.', 'mb-user-profile' ) );
			return;
		}

		add_filter( 'lostpassword_url', [ $this, 'change_lost_password_url' ] );
		$user = wp_signon( $credentials, is_ssl() );
		remove_filter( 'lostpassword_url', [ $this, 'change_lost_password_url' ] );

		if ( is_wp_error( $user ) ) {
			$this->error = $user;
		}
	}

	private function retrieve_password() {
		$login = rwmb_request()->post( 'user_login' );

		if ( ! $login ) {
			$this->error->add( 'empty-login', __( 'Please enter a username or email address.', 'mb-user-profile' ) );
			return;
		}

		$user = $this->get_user( $login );
		if ( ! $user ) {
			$this->error->add( 'invalid-login', __( 'Invalid username or email.', 'mb-user-profile' ) );
			return;
		}

		$key = get_password_reset_key( $user );

		$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );

		$url = remove_query_arg( ['rwmb-lost-password', 'rwmb-form-submitted'], $current_url );
		$url = add_query_arg( [
			'rwmb-reset-password' => 'true',
			'key'                 => $key,
			'login'               => $user->user_login,
		], $url );

		// Translators: %s - Website title.
		$subject = sprintf( __( '[%s] Password Reset', 'mb-user-profile' ), get_bloginfo( 'name' ) );

		// Translators: %s - User display name.
		$message = '<p>' . esc_html( sprintf( __( 'Hi, %s', 'mb-user-profile' ), $user->display_name ) ) . '</p>';

		// Translators: %s - Website title.
		$message .= '<p>' . esc_html( sprintf( __( 'Someone has requested a new password for your account on %s site.', 'mb-user-profile' ), get_bloginfo( 'name' ) ) ) . '</p>';
		$message .= '<p><a href="' . esc_url( $url ) . '">' . esc_html__( 'Click here to reset your password', 'mb-user-profile' ) . '</a></p>';

		$headers = ['Content-type: text/html'];

		$result = wp_mail( $user->user_email, $subject, $message, $headers );

		if ( ! $result ) {
			$this->error->add( 'email-error', __( 'Error sending email. Please try again.', 'mb-user-profile' ) );
			return;
		}

		$redirect = add_query_arg( 'rwmb-form-submitted', ConfigStorage::get_key( $this->config ), $current_url );
		wp_safe_redirect( $redirect );
		die;
	}

	private function reset_password() {
		$request = rwmb_request();

		$key = $request->get( 'key' );
		$login = $request->get( 'login' );

		$user = check_password_reset_key( $key, $login );

		if ( is_wp_error( $user ) ) {
			$this->error->add( 'invalid-key', __( 'This key is invalid or has already been used. Please reset your password again if needed.', 'mb-user-profile' ) );
			$redirect = remove_query_arg( ['rwmb-reset-password', 'key', 'login', 'rwmb-form-submitted'] );
			$redirect = add_query_arg( 'rwmb-lost-password', 'true', $redirect );
			wp_safe_redirect( $redirect );
			die;
		}

		$password = $request->post( 'user_pass' );
		$password2 = $request->post( 'user_pass2' );

		if ( ! $password || ! $password2 ) {
			$this->error->add( 'empty-passwords', __( 'Please enter a valid password.', 'mb-user-profile' ) );
			return;
		}
		if ( $password !== $password2 ) {
			$this->error->add( 'passwords-not-match', __( 'Passwords do not match.', 'mb-user-profile' ) );
			return;
		}

		$result = wp_update_user( [
			'ID'        => $user->ID,
			'user_pass' => $password,
		] );

		if ( is_wp_error( $result ) ) {
			$this->error = $result;
			return;
		}

		$redirect = add_query_arg( 'rwmb-form-submitted', ConfigStorage::get_key( $this->config ) );
		wp_safe_redirect( $redirect );
		die;
	}

	protected function display_confirmation() {
		$confirmation = $this->config['confirmation'];
		if ( isset( $_GET['rwmb-lost-password'] ) ) {
			$confirmation = __( 'Please check your email for the confirmation link.', 'mb-user-profile' );
		}
		if ( isset( $_GET['rwmb-reset-password'] ) ) {
			$confirmation = __( 'Your password has been reset.', 'mb-user-profile' ) . ' <a href="' . remove_query_arg( ['rwmb-lost-password', 'rwmb-reset-password', 'rwmb-form-submitted', 'key', 'login'] ) . '">' . __( 'Log in', 'mb-user-profile' ) . '</a>';
		}
		?>
		<div class="rwmb-confirmation"><?= wp_kses_post( $confirmation ); ?></div>
		<?php
	}

	public function change_lost_password_url( $url ) {
		$url = remove_query_arg( 'rwmb-form-submitted' );
		return add_query_arg( 'rwmb-lost-password', 'true', $url );
	}

	public function get_user( $user_login ) {
		$attrs = [ 'login', 'email' ];
		foreach ( $attrs as $attr ) {
			if ( ! empty( $user = get_user_by( $attr, $user_login ) ) ) {
				return $user;
			}
		}
		return false;
	}

}
