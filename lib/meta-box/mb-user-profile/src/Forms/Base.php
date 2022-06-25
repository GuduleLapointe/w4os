<?php
namespace MetaBox\UserProfile\Forms;

use MetaBox\UserProfile\ConfigStorage;
use WP_Error;

abstract class Base {
	public $config;
	public $error;
	public $meta_boxes;
	protected $user;
	protected $localize_data = [];
	protected $type;

	/**
	 * Constructor.
	 *
	 * @param array                     $meta_boxes Meta box settings.
	 * @param \MetaBox\UserProfile\User $user       User object.
	 * @param array                     $config     Form configuration.
	 */
	public function __construct( $meta_boxes, $user, $config ) {
		$this->meta_boxes = array_filter( $meta_boxes );
		$this->user       = $user;
		$this->config     = $config;
		$this->error      = new WP_Error;
		if ( $this->user ) {
			$this->user->error = $this->error;
		}
	}

	public function render() {
		if ( ! $this->has_privilege() ) {
			return;
		}

		$this->enqueue();
		$this->enqueue_recaptcha();
		$this->localize();

		if ( $this->is_processed() ) {
			do_action( 'rwmb_profile_before_display_confirmation', $this->config );
			$this->display_confirmation();
			do_action( 'rwmb_profile_after_display_confirmation', $this->config );

			// Don't show login/register forms after submission.
			if ( get_class( $this ) !== __NAMESPACE__ . '\Info' ) {
				return;
			}
		}

		$this->display_errors();

		// Don't show the form if no meta boxes.
		if ( empty( $this->meta_boxes ) ) {
			return;
		}

		do_action( 'rwmb_profile_before_form', $this->config );

		echo '<form class="rwmb-form mbup-form" method="post" enctype="multipart/form-data" id="' . esc_html( $this->config['form_id'] ) . '">';
		$this->render_hidden_fields();

		// Register wp color picker scripts for frontend.
		$this->register_scripts();
		wp_localize_jquery_ui_datepicker();

		foreach ( $this->meta_boxes as $meta_box ) {
			$meta_box->enqueue();
			$meta_box->show();
		}

		do_action( 'rwmb_profile_before_submit_button', $this->config );
		$this->submit_button();
		do_action( 'rwmb_profile_after_submit_button', $this->config );

		echo '</form>';

		do_action( 'rwmb_profile_after_form', $this->config );
	}

	/**
	 * Process the form.
	 * Meta box auto hooks to 'save_post' action to save its data, so we only need to save the post.
	 *
	 * @return int User ID.
	 */
	public function process() {
		$this->check_recaptcha();

		$is_valid = true;
		foreach ( $this->meta_boxes as $meta_box ) {
			$is_valid = $is_valid && $meta_box->validate();
		}

		$is_valid = apply_filters( 'rwmb_profile_validate', $is_valid, $this->config );

		if ( true !== $is_valid ) {
			$this->error->add( 'invalid', is_string( $is_valid ) ? $is_valid : __( 'Invalid form submission, please try again.', 'mb-user-profile' ) );
			return;
		}

		do_action( 'rwmb_profile_before_process', $this->config );
		$user_id = $this->user->save();
		do_action( 'rwmb_profile_after_process', $this->config, $user_id );

		return $user_id;
	}

	protected function has_privilege() {
		return true;
	}

	protected function display_errors() {
		if ( $this->error->has_errors() ) {
			printf( '<div class="rwmb-error">%s</div>', wp_kses_post( implode( '<br>', $this->error->get_error_messages() ) ) );
		}
	}

	protected function submit_button() {
	}

	protected function register_scripts() {
		if ( wp_script_is( 'wp-color-picker', 'registered' ) ) {
			return;
		}
		wp_register_script( 'iris', admin_url( 'js/iris.min.js' ), [
			'jquery-ui-draggable',
			'jquery-ui-slider',
			'jquery-touch-punch',
		], '1.0.7', true );
		wp_register_script( 'wp-color-picker', admin_url( 'js/color-picker.min.js' ), ['iris', 'wp-i18n'], '', true );
		wp_localize_script( 'wp-color-picker', 'wpColorPickerL10n', [
			'clear'            => __( 'Clear', 'mb-user-profile' ),
			'clearAriaLabel'   => __( 'Clear color', 'mb-user-profile' ),
			'defaultString'    => __( 'Default', 'mb-user-profile' ),
			'defaultAriaLabel' => __( 'Select default color', 'mb-user-profile' ),
			'pick'             => __( 'Select Color', 'mb-user-profile' ),
			'defaultLabel'     => __( 'Color value', 'mb-user-profile' ),
		] );
	}

	protected function enqueue() {
		if ( ! isset( $this->config['password_strength'] ) || 'false' === $this->config['password_strength'] ) {
			return;
		}
		wp_enqueue_script( 'mbup', MBUP_URL . 'assets/user-profile.js', ['jquery', 'password-strength-meter'], MBUP_VER, true );

		$this->localize_data = array_merge( $this->localize_data, [
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'very-weak' => __( 'Very weak', 'mb-user-profile' ),
			'weak'      => __( 'Weak', 'mb-user-profile' ),
			'medium'    => _x( 'Medium', 'password strength', 'mb-user-profile' ),
			'strong'    => __( 'Strong', 'mb-user-profile' ),
			'mismatch'  => __( 'Mismatch', 'mb-user-profile' ),
			'strength'  => $this->config['password_strength'],
		] );
	}

	protected function enqueue_recaptcha() {
		if ( ! $this->config['recaptcha_key'] ) {
			return;
		}

		wp_enqueue_script( 'mbup', MBUP_URL . 'assets/user-profile.js', ['jquery', 'password-strength-meter'], MBUP_VER, true );
		wp_enqueue_script( 'google-recaptcha', 'https://www.google.com/recaptcha/api.js?render=' . $this->config['recaptcha_key'], [], '3', true );

		$this->localize_data = array_merge( $this->localize_data, [
			'recaptchaKey'        => $this->config['recaptcha_key'],
			'captchaExecuteError' => __( 'Error trying to execute grecaptcha.', 'mb-user-profile' ),
		] );
	}

	protected function localize() {
		$key = ConfigStorage::store( $this->config );
		wp_localize_script( 'mbup', 'MBUP_Data_' . $key, $this->localize_data );
	}

	protected function render_hidden_fields() {
		$key = ConfigStorage::store( $this->config );
		echo '<input type="hidden" name="mbup_key" value="', esc_attr( $key ), '">';
		echo '<input type="hidden" name="mbup_type" value="', esc_attr( $this->type ), '">';
		// add hidden input if has recaptcha v3
		if ( $this->config[ 'recaptcha_key' ] ) {
			echo '<input type="hidden" name="mbup_recaptcha_token" value="">';
		}
	}

	protected function check_recaptcha() {
		if ( ! $this->config['recaptcha_secret'] ) {
			return;
		}

		$token = (string) rwmb_request()->post( 'mbup_recaptcha_token' );
		if ( ! $token ) {
			$error = __( 'Invalid captcha token', 'mb-user-profile' );
			wp_die( $error );
		}

		$url = 'https://www.google.com/recaptcha/api/siteverify';
		$url = add_query_arg( [
			'secret'   => $this->config['recaptcha_secret'],
			'response' => $token,
		], $url );

		$response = wp_remote_retrieve_body( wp_remote_get( $url ) );
		$response = json_decode( $response, true );

		if ( empty( $response[ 'success' ] ) || empty( $response['action'] ) || 'mbup' !== $response[ 'action' ] ) {
			$error = __( 'Cannot verify captcha', 'mb-user-profile' );
			wp_die( $error );
		}
	}

	protected function is_processed() {
		return ConfigStorage::get_key( $this->config ) === filter_input( INPUT_GET, 'rwmb-form-submitted' );
	}

	protected function display_confirmation() {
		if ( ! $this->config['confirmation'] ) {
			return;
		}
		?>
		<div class="rwmb-confirmation"><?= wp_kses_post( $this->config['confirmation'] ); ?></div>
		<?php
	}
}
