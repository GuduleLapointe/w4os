<?php
namespace MetaBox\UserProfile;

class DefaultFields {
	public function __construct() {
		add_filter( 'rwmb_meta_boxes', [$this, 'register_fields'] );
	}

	public function register_fields( $meta_boxes ) {
		$meta_boxes[] = $this->get_register_fields();
		$meta_boxes[] = $this->get_login_fields();
		$meta_boxes[] = $this->get_lost_password_fields();
		$meta_boxes[] = $this->get_reset_password_fields();
		$meta_boxes[] = $this->get_info_fields();

		return $meta_boxes;
	}

	private function get_register_fields() {
		return [
			'id'         => 'rwmb-user-register',
			'title'      => 'register',
			'post_types' => ['rwmb-user-profile'],
			'fields'     => apply_filters( 'rwmb_profile_register_fields', [
				'username'  => [
					'name'     => __( 'Username', 'mb-user-profile' ),
					'id'       => 'user_login',
					'type'     => 'text',
					'required' => true,
				],
				'email'     => [
					'name'     => __( 'Email', 'mb-user-profile' ),
					'id'       => 'user_email',
					'type'     => 'email',
					'required' => true,
				],
				'password'  => [
					'name'     => __( 'Password', 'mb-user-profile' ),
					'id'       => 'user_pass',
					'type'     => 'password',
					'required' => true,
					'desc'     => '<span id="password-strength" class="rwmb-password-strength"></span>',
				],
				'password2' => [
					'name'     => __( 'Confirm Password', 'mb-user-profile' ),
					'id'       => 'user_pass2',
					'type'     => 'password',
					'required' => true,
				],
			] ),
		];
	}

	private function get_login_fields() {
		return [
			'id'         => 'rwmb-user-login',
			'title'      => 'login',
			'post_types' => ['rwmb-user-profile'],
			'fields'     => apply_filters( 'rwmb_profile_login_fields', [
				'username' => [
					'name'     => __( 'Username or Email Address', 'mb-user-profile' ),
					'id'       => 'user_login',
					'type'     => 'text',
					'required' => true,
				],
				'password' => [
					'name'     => __( 'Password', 'mb-user-profile' ),
					'id'       => 'user_pass',
					'type'     => 'password',
					'required' => true,
				],
				'remember' => [
					'desc' => __( 'Remember Me', 'mb-user-profile' ),
					'id'   => 'remember',
					'type' => 'checkbox',
				],
				'submit' => [
					'std'        => __( 'Log In', 'mb-user-profile' ),
					'id'         => 'submit',
					'type'       => 'button',
					'attributes' => [
						'type'  => 'submit',
						'name'  => 'rwmb_profile_submit_login',
						'value' => 1,
					],
				],
				'lost_password' => [
					'type' => 'custom_html',
					'std'  => '<a href="' . esc_url( add_query_arg( 'rwmb-lost-password', 'true' ) ) . '">' . __( 'Lost Password?', 'mb-user-profile' ) . '</a>',
				],
			] ),
		];
	}

	private function get_lost_password_fields() {
		return [
			'id'         => 'rwmb-user-lost-password',
			'title'      => 'lost-password',
			'post_types' => ['rwmb-user-profile'],
			'fields'     => apply_filters( 'rwmb_profile_lost_password_fields', [
				'message' => [
					'type' => 'custom_html',
					'std' => '<div class="rwmb-info">' . esc_html__( 'Please enter your username or email address. You will receive a link to create a new password via email.', 'mb-user-profile' ) . '</div>',
				],
				'username' => [
					'name'     => __( 'Username or Email Address', 'mb-user-profile' ),
					'id'       => 'user_login',
					'type'     => 'text',
					'required' => true,
				],
				'submit' => [
					'std'        => __( 'Get New Password', 'mb-user-profile' ),
					'id'         => 'submit',
					'type'       => 'button',
					'attributes' => [
						'type'  => 'submit',
						'name'  => 'rwmb_profile_submit_login',
						'value' => 1,
					],
				],
				'login' => [
					'type' => 'custom_html',
					'std'  => '<a href="' . esc_url( remove_query_arg( ['rwmb-lost-password', 'rwmb-reset-password', 'rwmb-form-submitted'] ) ) . '">' . __( 'Login', 'mb-user-profile' ) . '</a>',
				],
			] ),
		];
	}

	private function get_reset_password_fields() {
		return [
			'id'         => 'rwmb-user-reset-password',
			'title'      => 'reset-password',
			'post_types' => ['rwmb-user-profile'],
			'fields'     => apply_filters( 'rwmb_profile_reset_password_fields', [
				'message' => [
					'type' => 'custom_html',
					'std' => '<div class="rwmb-info">' . esc_html__( 'Please enter a new password below.', 'mb-user-profile' ) . '</div>',
				],
				'password'  => [
					'name'     => __( 'New Password', 'mb-user-profile' ),
					'id'       => 'user_pass',
					'type'     => 'password',
					'required' => true,
					'desc'     => '<span id="password-strength" class="rwmb-password-strength"></span>',
				],
				'password2' => [
					'name'     => __( 'Confirm Password', 'mb-user-profile' ),
					'id'       => 'user_pass2',
					'type'     => 'password',
					'required' => true,
				],
				'submit' => [
					'std'        => __( 'Save', 'mb-user-profile' ),
					'id'         => 'submit',
					'type'       => 'button',
					'attributes' => [
						'type'  => 'submit',
						'name'  => 'rwmb_profile_submit_login',
						'value' => 1,
					],
				],
				'login' => [
					'type' => 'custom_html',
					'std'  => '<a href="' . esc_url( remove_query_arg( ['rwmb-lost-password', 'rwmb-reset-password', 'rwmb-form-submitted', 'key', 'login'] ) ) . '">' . __( 'Login', 'mb-user-profile' ) . '</a>',
				],
			] ),
		];
	}

	private function get_info_fields() {
		return [
			'id'         => 'rwmb-user-info',
			'title'      => 'info',
			'post_types' => ['rwmb-user-profile'],
			'fields'     => apply_filters( 'rwmb_profile_info_fields', [
				'password'  => [
					'name'     => __( 'New Password', 'mb-user-profile' ),
					'id'       => 'user_pass',
					'type'     => 'password',
					'required' => true,
					'desc'     => '<span id="password-strength" class="rwmb-password-strength"></span>',
				],
				'password2' => [
					'name'     => __( 'Confirm Password', 'mb-user-profile' ),
					'id'       => 'user_pass2',
					'type'     => 'password',
					'required' => true,
				],
			] ),
		];
	}
}