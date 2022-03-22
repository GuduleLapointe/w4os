<?php
namespace MBFS;

class FormFactory {
	private static $forms = [];

	public static function make( $config ) {
		$config = (array) $config;
		$config = self::normalize( $config );

		$key = ConfigStorage::get_key( $config );
		if ( ! isset( self::$forms[ $key ] ) ) {
			self::$forms[ $key ] = self::get_form( $config );
		}
		return self::$forms[ $key ];
	}

	private static function normalize( $config ) {
		$config = shortcode_atts( [
			// Meta Box ID.
			'id'                  => '',

			// Allow to edit the submitted post.
			'edit'                => 'false',
			'allow_delete'        => 'false',
			'force_delete'        => 'false',

			'only_delete'         => 'false',

			// Ajax
			'ajax'                => 'false',

			// Redirect
			'redirect'            => '',

			// Google reCaptcha v3
			'recaptcha_key'       => '',
			'recaptcha_secret'    => '',

			// Post fields.
			'post_type'           => '',
			'post_id'             => 0,
			'post_status'         => 'publish',
			'post_fields'         => '',
			'label_title'         => __( 'Title', 'mb-frontend-submission' ),
			'label_content'       => __( 'Content', 'rwmb-frontend-submission' ),
			'label_excerpt'       => __( 'Excerpt', 'rwmb-frontend-submission' ),
			'label_date'          => __( 'Date', 'rwmb-frontend-submission' ),
			'label_thumbnail'     => __( 'Thumbnail', 'rwmb-frontend-submission' ),

			// Appearance options.
			'submit_button'       => __( 'Submit', 'mb-frontend-submission' ),
			'delete_button'       => __( 'Delete', 'mb-frontend-submission' ),
			'confirmation'        => __( 'Your post has been successfully submitted. Thank you.', 'mb-frontend-submission' ),
			'delete_confirmation' => __( 'Your post has been successfully deleted.', 'mb-frontend-submission' ),
		], $config );

		// Quick set the current post ID.
		if ( 'current' === $config['post_id'] ) {
			$config['post_id'] = get_the_ID();
		}

		// Allows developers to dynamically populate shortcode params via query string.
		self::populate_via_query_string( $config );

		// Allows developers to dynamically populate shortcode params via hooks.
		self::populate_via_hooks( $config );

		// Remove unavailable meta boxes.
		$ids      = array_filter( explode( ',', $config['id'] . ',' ) );
		$filtered = [];
		foreach ( $ids as $id ) {
			$meta_box = rwmb_get_registry( 'meta_box' )->get( $id );
			if ( empty( $meta_box ) ) {
				continue;
			}
			$filtered[] = $meta_box->id;
			if ( ! $config['post_type'] ) {
				$post_types          = $meta_box->post_types;
				$config['post_type'] = reset( $post_types );
			}
		}
		$config['id'] = implode( ',', $filtered );

		return $config;
	}

	private static function get_form( $config ) {
		$ids        = array_filter( explode( ',', $config['id'] . ',' ) );
		$meta_boxes = [];
		foreach ( $ids as $id ) {
			$meta_box = rwmb_get_registry( 'meta_box' )->get( $id );
			$meta_box->set_object_id( $config['post_id'] );
			$meta_boxes[] = $meta_box;
		}

		$template_loader = new TemplateLoader;
		$post            = new Post( $config['post_type'], $config['post_id'], $config, $template_loader );

		return new Form( $meta_boxes, $post, $config, $template_loader );
	}

	private static function populate_via_query_string( &$config ) {
		$post_id = filter_input( INPUT_GET, 'rwmb_frontend_field_post_id', FILTER_SANITIZE_NUMBER_INT );
		if ( $post_id ) {
			$config['post_id'] = $post_id;
		}
	}

	private static function populate_via_hooks( &$config ) {
		foreach ( $config as $key => $value ) {
			$config[ $key ] = apply_filters( "rwmb_frontend_field_value_{$key}", $value, $config );
		}
	}
}
