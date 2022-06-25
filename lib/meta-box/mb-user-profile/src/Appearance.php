<?php
/**
 * Change the default meta box appearance based on shortcode params.
 */

namespace MetaBox\UserProfile;

class Appearance {
	private $meta_box;

	public function __construct( $meta_box ) {
		$this->meta_box = $meta_box;
	}

	public function set( $field_attribute, $value ) {
		if ( ! $this->meta_box ) {
			return;
		}

		list( $field, $attribute ) = explode( '.', $field_attribute );
		if ( ! isset( $this->meta_box->meta_box['fields'][ $field ] ) ) {
			return;
		}
		$this->meta_box->meta_box['fields'][ $field ][ $attribute ] = $value;
	}
}