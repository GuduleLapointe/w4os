<?php
namespace MetaBox\UserProfile\Forms;

use MetaBox\UserProfile\Appearance;
use MetaBox\UserProfile\ConfigStorage;
use MetaBox\UserProfile\User;
use RWMB_Helpers_Array as Arr;

class Factory {
	private static $forms = [];

	public static function make( $config, $type ) {
		$config = (array) $config;

		$class = __NAMESPACE__ . '\\' . ucfirst( $type );
		$config = $class::normalize( $config );

		$key = ConfigStorage::get_key( $config );
		if ( ! isset( self::$forms[ $key ] ) ) {
			self::$forms[ $key ] = self::$type( $config );
		}
		return self::$forms[ $key ];
	}

	private static function register( $config ) {
		// Apply changes to appearance.
		$base_meta_box = rwmb_get_registry( 'meta_box' )->get( 'rwmb-user-register' );
		$appearance = new Appearance( $base_meta_box );

		$appearance->set( 'username.name', $config['label_username'] );
		$appearance->set( 'username.id', $config['id_username'] );

		$appearance->set( 'email.name', $config['label_email'] );
		$appearance->set( 'email.id', $config['id_email'] );

		$appearance->set( 'password.name', $config['label_password'] );
		$appearance->set( 'password.id', $config['id_password'] );

		$appearance->set( 'password2.name', $config['label_password2'] );
		$appearance->set( 'password2.id', $config['id_password2'] );

		if ( 'true' === $config['email_as_username'] ) {
			unset( $base_meta_box->meta_box['fields']['username'] );
		}

		$meta_box_ids      = Arr::from_csv( $config['id'] );
		$meta_boxes        = [];
		$meta_boxes_unvail = [];
		foreach ( $meta_box_ids as $meta_box_id ) {
			$meta_box = rwmb_get_registry( 'meta_box' )->get( $meta_box_id );
			if ( empty( $meta_box ) ) {
				$meta_boxes_unvail[] = $meta_box_id;
			} else {
				$meta_boxes[] = $meta_box;
			}
		}

		array_unshift( $meta_boxes, $base_meta_box );

		$user = new User( $config );
		$form = new Register( $meta_boxes, $user, $config );

		// Show warning if some meta boxes are not available.
		if ( ! empty( $meta_boxes_unvail ) ) {
			$form->error->add( 'meta-boxes-unavailable', sprintf(
				_n(
					'Warning: The following meta box are not available: "%s".',
					'Warning: The following meta boxes are not available: "%s".',
					count( $meta_boxes_unvail ),
					'mb-user-profile'
				),
				implode( ', ', $meta_boxes_unvail )
			) );
		}

		return $form;
	}

	private static function login( $config ) {
		if ( isset( $_GET['rwmb-lost-password'] ) ) {
			return self::lost_password( $config );
		}

		if ( isset( $_GET['rwmb-reset-password'] ) ) {
			return self::reset_password( $config );
		}

		// Apply changes to appearance.
		$base_meta_box = rwmb_get_registry( 'meta_box' )->get( 'rwmb-user-login' );
		$appearance    = new Appearance( $base_meta_box );

		$appearance->set( 'username.name', $config['label_username'] );
		$appearance->set( 'username.id', $config['id_username'] );
		$appearance->set( 'username.std', $config['value_username'] );

		$appearance->set( 'password.name', $config['label_password'] );
		$appearance->set( 'password.id', $config['id_password'] );

		$appearance->set( 'remember.desc', $config['label_remember'] );
		$appearance->set( 'remember.id', $config['id_remember'] );
		$appearance->set( 'remember.std', $config['value_remember'] );

		$appearance->set( 'submit.std', $config['label_submit'] );
		$appearance->set( 'submit.id', $config['id_submit'] );

		$appearance->set( 'lost_password.std', '<a href="' . esc_url( add_query_arg( 'rwmb-lost-password', 'true' ) ) . '">' . esc_html( $config['label_lost_password'] ). '</a>' );

		$meta_boxes = [ $base_meta_box ];

		return new Login( $meta_boxes, null, $config );
	}

	private static function lost_password( $config ) {
		$meta_box = rwmb_get_registry( 'meta_box' )->get( 'rwmb-user-lost-password' );
		return new Login( [ $meta_box ], null, $config );
	}

	private static function reset_password( $config ) {
		$meta_box = rwmb_get_registry( 'meta_box' )->get( 'rwmb-user-reset-password' );
		return new Login( [ $meta_box ], null, $config );
	}

	private static function info( $config ) {
		// Apply changes to appearance.
		$base_meta_box = rwmb_get_registry( 'meta_box' )->get( 'rwmb-user-info' );
		$appearance = new Appearance( $base_meta_box );

		$appearance->set( 'password.name', $config['label_password'] );
		$appearance->set( 'password.id', $config['id_password'] );

		$appearance->set( 'password2.name', $config['label_password2'] );
		$appearance->set( 'password2.id', $config['id_password2'] );

		$meta_box_ids = Arr::from_csv( $config['id'] );

		$meta_boxes        = [];
		$meta_boxes_unvail = [];
		$flag_not_user     = false;
		foreach ( $meta_box_ids as $meta_box_id ) {
			$meta_box = rwmb_get_registry( 'meta_box' )->get( $meta_box_id );
			if ( empty( $meta_box ) ) {
				$meta_boxes_unvail[] = $meta_box_id;
			} else {
				if( 'user' !== $meta_box->type ){
					$flag_not_user = true;
					continue;
				}
				$meta_box->object_id = $config['user_id'];
				$meta_boxes[]        = $meta_box;
			}
		}

		$user = new User( $config );
		$user->user_id = $config['user_id'];

		$form = new Info( $meta_boxes, $user, $config );

		// Show error if no meta boxes available.
		if ( empty( $meta_boxes ) && $flag_not_user === false) {
			$form->error->add( 'no-meta-boxes', __( 'Error: No meta boxes are available!', 'mb-user-profile') );
		}

		// Show warning if some meta boxes are not available.
		if ( ! empty( $meta_boxes_unvail ) ) {
			$form->error->add( 'meta-boxes-unavailable', sprintf(
				// Translators: %s - ID of meta boxes.
				_n(
					'Warning: The following meta box are not available: "%s".',
					'Warning: The following meta boxes are not available: "%s".',
					count( $meta_boxes_unvail ),
					'mb-user-profile'
				),
				implode( ', ', $meta_boxes_unvail )
			) );
		}

		return $form;
	}
}