<?php
namespace MetaBox\UserProfile\Forms;

use RWMB_Helpers_Array as Arr;

class Register extends Base {
	protected $type = 'register';

	public static function normalize( $config ) {
		$config = shortcode_atts( [
			// Meta Box ID.
			'id'                => '',

			'redirect'          => '',
			'form_id'           => 'register-form',

			// Google reCaptcha v3
			'recaptcha_key'       => '',
			'recaptcha_secret'    => '',

			// Appearance options.
			'label_username'    => __( 'Username', 'mb-user-profile' ),
			'label_email'       => __( 'Email', 'mb-user-profile' ),
			'label_password'    => __( 'Password', 'mb-user-profile' ),
			'label_password2'   => __( 'Confirm Password', 'mb-user-profile' ),
			'label_submit'      => __( 'Register', 'mb-user-profile' ),

			'id_username'       => 'user_login',
			'id_email'          => 'user_email',
			'id_password'       => 'user_pass',
			'id_password2'      => 'user_pass2',
			'id_submit'         => 'submit',

			'confirmation'		 => __( 'Your account has been created successfully.', 'mb-user-profile' ),
			'email_confirmation' => false,

			'password_strength' => 'strong',

			'email_as_username' => false,
			'show_if_user_can'  => '',
			'role'              => 'subscriber',
		], $config );
		if ( isset( $config[ 'email_confirmation' ] ) && 'true' === $config[ 'email_confirmation' ] ) {
			$config[ 'confirmation' ] = __( 'Your account has been created and is pending. Please check your email to activate your account.', 'mb-user-profile' );
		}

		// Compatible with old shortcode attributes.
		Arr::change_key( $config, 'submit_button', 'label_submit' );

		return $config;
	}

	protected function has_privilege() {
		// Always show the form for non-logged in users.
		if ( ! is_user_logged_in() ) {
			return true;
		}

		// Show the form for users with proper capability like admins (for registering other users).
		if ( ! empty( $this->config['show_if_user_can'] ) && current_user_can( $this->config['show_if_user_can'] ) ) {
			return true;
		}

		esc_html_e( 'You are already logged in.', 'mb-user-profile' );
		return false;
	}

	protected function submit_button() {
		?>
		<div class="rwmb-field rwmb-button-wrapper rwmb-form-submit">
			<div class="rwmb-input">
				<button class="rwmb-button" id="<?= esc_attr( $this->config['id_submit'] ) ?>" name="rwmb_profile_submit_register" value="1"><?= esc_html( $this->config['label_submit'] ) ?></button>
			</div>
		</div>
		<?php
	}
}
