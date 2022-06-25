<?php
namespace MetaBox\UserProfile\Forms;

use RWMB_Helpers_Array as Arr;

class Info extends Base {
	protected $type = 'info';

	public static function normalize( $config ) {
		$config = shortcode_atts( [
			// Meta Box ID.
			'id'                => '',

			// User fields.
			'user_id'           => get_current_user_id(),

			'redirect'          => '',
			'form_id'           => 'profile-form',

			// Google reCaptcha v3
			'recaptcha_key'       => '',
			'recaptcha_secret'    => '',

			// Appearance options.
			'label_password'    => __( 'New Password', 'mb-user-profile' ),
			'label_password2'   => __( 'Confirm Password', 'mb-user-profile' ),
			'label_submit'      => __( 'Submit', 'mb-user-profile' ),

			'id_password'       => 'user_pass',
			'id_password2'      => 'user_pass2',
			'id_submit'         => 'submit',

			'confirmation'      => __( 'Your information has been successfully submitted. Thank you.', 'mb-user-profile' ),

			'password_strength' => 'strong',
		], $config );

		// Compatible with old shortcode attributes.
		Arr::change_key( $config, 'submit_button', 'label_submit' );

		return $config;
	}

	protected function has_privilege() {
		if ( is_user_logged_in() ) {
			return true;
		}
		$request = rwmb_request();
		if ( 'error' !== $request->get( 'rwmb-form-submitted' ) && ! $request->get( 'rwmb-lost-password' ) && ! $request->get( 'rwmb-reset-password' ) ) {
			echo '<div class="rwmb-notice">';
			esc_html_e( 'Please login to continue.', 'mb-user-profile' );
			echo '</div>';
		}
		$url = remove_query_arg( 'rwmb-form-submitted' ); // Do not show success message after logging in.
		echo do_shortcode( "[mb_user_profile_login redirect='$url' ]" );
		return false;
	}

	protected function submit_button() {
		?>
		<div class="rwmb-field rwmb-button-wrapper rwmb-form-submit">
			<div class="rwmb-input">
				<button class="rwmb-button" id="<?= esc_attr( $this->config['id_submit'] ) ?>" name="rwmb_profile_submit_info" value="1"><?= esc_html( $this->config['label_submit'] ) ?></button>
			</div>
		</div>
		<?php
	}
}
