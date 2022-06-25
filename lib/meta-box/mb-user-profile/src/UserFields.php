<?php
namespace MetaBox\UserProfile;

class UserFields {
	private $fields = [
		'user_login',
		'user_email',
		'user_pass',
		'user_pass2',
		'user_nicename',
		'user_url',
		'display_name',
		'role',
		'user_registered',
	];

	public function __construct() {
		// Hide user fields in the admin.
		add_filter( 'rwmb_outer_html', [$this, 'hide_in_admin'], 10, 2 );

		// Get correct user field value to show in the form.
		add_action( 'rwmb_profile_before_form', [ $this, 'get_user_field_value' ] );

		// Get user data to create or update users.
		add_filter( 'rwmb_profile_update_user_data', [$this, 'get_user_data'], 0 );
		add_filter( 'rwmb_profile_insert_user_data', [$this, 'get_user_data'], 0 );

		// Don't let Meta Box save user fields in the user meta table.
		add_action( 'rwmb_profile_before_save_user', [$this, 'ignore_saving'] );
	}

	public function hide_in_admin( $html, $field ) {
		if ( ! is_admin() ) {
			return $html;
		}
		$screen = get_current_screen();
		if ( ! is_object( $screen ) || ! in_array( $screen->id, ['profile', 'user-edit', 'profile-network', 'user-edit-network'], true ) ) {
			return $html;
		}
		return in_array( $field['id'], $this->fields, true ) ? '' : $html;
	}

	public function get_user_field_value( $config ) {
		$fields = array_diff( $this->fields, ['user_pass', 'user_pass2'] );
		foreach ( $fields as $field ) {
			add_filter( "rwmb_{$field}_field_meta", function( $meta ) use ( $field, $config ) {
				if ( empty( $config['user_id'] ) ) {
					return $meta;
				}
				$user = get_userdata( $config['user_id'] );
				return $user ? ( 'role' === $field ? reset( $user->roles ) : $user->$field ) : $meta;
			} );
		}
	}

	public function ignore_saving() {
		foreach ( $this->fields as $field ) {
			add_filter( "rwmb_{$field}_value", '__return_empty_string' );
		}
	}

	public function get_user_data() {
		$data = [];
		foreach ( $this->fields as $field ) {
			$data[ $field ] = (string) filter_input( INPUT_POST, $field );
		}
		return array_filter( $data );
	}
}
