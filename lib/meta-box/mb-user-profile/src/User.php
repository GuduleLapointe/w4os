<?php
namespace MetaBox\UserProfile;
use MetaBox\UserProfile\Email;

class User {
	public $user_id;
	public $config;
	public $error;

	public function __construct( $config = [] ) {
		$this->config = $config;
	}

	public function save() {
		do_action( 'rwmb_profile_before_save_user', $this );

		if ( $this->user_id ) {
			$this->update();
		} else {
			$this->create();
		}

		do_action( 'rwmb_profile_after_save_user', $this );

		return $this->user_id;
	}

	private function update() {
		$data = apply_filters( 'rwmb_profile_update_user_data', [], $this->config );

		// Do not update user data, only trigger an action for Meta Box to update custom fields.
		if ( empty( $data ) ) {
			$old_user_data = get_userdata( $this->user_id );
			if ( ! $old_user_data ) {
				$this->error->add( 'invalid-id', __( 'Invalid user ID.', 'mb-user-profile' ) );
				return;
			}
			do_action( 'profile_update', $this->user_id, $old_user_data );
			return;
		}

		// Update user data.
		$data['ID'] = $this->user_id;
		if ( isset( $data['user_pass'] ) && isset( $data['user_pass2'] ) && $data['user_pass'] !== $data['user_pass2'] ) {
			$this->error->add( 'passwords-not-match', __( 'Passwords do not match.', 'mb-user-profile' ) );
			return;
		}
		unset( $data['user_pass2'] );

		$result = wp_update_user( $data );
		if ( is_wp_error( $result ) ) {
			$this->error = $result;
		}
	}

	private function create() {
		$data = apply_filters( 'rwmb_profile_insert_user_data', [], $this->config );
		if ( isset( $data['user_email'] ) && ! is_email( $data['user_email'] ) ) {
			$this->error->add( 'invalid-email', __( 'Invalid email.', 'mb-user-profile' ) );
			return;
		}

		if ( isset( $data['user_email'] ) && email_exists( $data['user_email'] ) ) {
			$this->error->add( 'email-exists', __( 'Your email already exists.', 'mb-user-profile' ) );
			return;
		}

		if ( isset( $this->config['email_as_username'] ) && 'true' === $this->config['email_as_username'] && isset( $data['user_email'] ) ) {
			$data['user_login'] = $data['user_email'];
		}

		$role = $this->config['role'];
		if ( ! empty( $role ) && $GLOBALS['wp_roles']->is_role( $role ) ) {
			$data['role'] = $role;
		}
		if ( isset( $data['user_login'] ) && username_exists( $data['user_login'] ) ) {
			$this->error->add( 'username-exists', __( 'Your username already exists.', 'mb-user-profile' ) );
			return;
		}
		if ( isset( $data['user_pass'] ) && isset( $data['user_pass2'] ) && $data['user_pass'] !== $data['user_pass2'] ) {
			$this->error->add( 'passwords-not-match', __( 'Passwords do not match.', 'mb-user-profile' ) );
			return;
		}
		unset( $data['user_pass2'] );

		$result = wp_insert_user( $data );
		if ( is_wp_error( $result ) ) {
			$this->error = $result;
		} else {
			$this->user_id = $result;

			// Check if sent email confirmation
			if ( isset( $this->config['email_confirmation'] ) && 'true' === $this->config['email_confirmation'] ) {
				$email = new Email();
				$email->send_confirmation_email( $result, $data['user_email'], $data['user_login'] );
			}
		}
	}
}
