<?php
namespace MBFS;

use RWMB_Field;

class Post {
	public $post_type;
	public $post_id;

	/**
	 * List of post fields for rendering/saving.
	 *
	 * @var array
	 */
	public $fields;

	public $config;

	private $template_loader;

	public function __construct( $post_type, $post_id, $config, $template_loader ) {
		$this->post_id         = (int) $post_id;
		$this->post_type       = $post_id ? get_post_type( $post_id ) : $post_type;
		$this->config          = $config;
		$this->fields          = ['post_title', 'post_content', 'post_excerpt', 'post_date'];
		$this->template_loader = $template_loader;
	}

	/**
	 * Render post fields in the frontend.
	 */
	public function render() {
		$data = array(
			'post_id'   => $this->post_id,
			'post_type' => $this->post_type,
			'config'    => $this->config,
		);

		// Set value to metabox fields by post fields.
		foreach ( $this->fields as $field ) {
			add_filter( "rwmb_{$field}_field_meta", function() use ( $field ) {
				return $this->post_id ? get_post_field( $field, $this->post_id ) : '';
			} );
		}

		// Get post fields from shortcode
		$fields = $this->get_post_fields();

		foreach ( $fields as $field ) {
			$this->template_loader->set_template_data( $data )->get_template_part( "post/$field" );
		}
	}

	public function save() {
		do_action( 'rwmb_frontend_before_save_post', $this );

		foreach( $this->fields as $field ) {
			add_filter( "rwmb_{$field}_value", '__return_empty_string' );
		}

		if ( $this->post_id ) {
			$this->update();
		} else {
			$this->create();
		}

		$this->save_thumbnail();
		do_action( 'rwmb_frontend_after_save_post', $this );
		return $this->post_id;
	}

	private function update() {
		$data       = $this->get_data();
		$data['ID'] = $this->post_id;
		$data       = apply_filters( 'rwmb_frontend_update_post_data', $data, $this->config );
		$data 		= array_filter( $data );
		wp_update_post( $data );
	}

	private function create() {
		$data                = $this->get_data();
		if ( empty( $data['post_title'] ) ) {
			$data['post_title'] = __( '(No title)', 'mb-frontend-submission' );
		}
		$data['post_type']   = $this->post_type;
		$data['post_status'] = $this->config['post_status'];
		$data                = apply_filters( 'rwmb_frontend_insert_post_data', $data, $this->config );
		$this->post_id       = wp_insert_post( $data );
	}

	/**
	 * Get submitted data to save into the database.
	 *
	 * @return array
	 */
	private function get_data() {
		$data = [];
		foreach ( $this->fields as $field ) {
			$data[ $field ] = (string) filter_input( INPUT_POST, $field );
		}

		// If developer sets the post parent using 'post' field.
		$data['post_parent'] = filter_input( INPUT_POST, 'parent_id', FILTER_SANITIZE_NUMBER_INT );
		return $data;
	}

	private function save_thumbnail() {
		// Get post fields from shortcode
		$fields = $this->get_post_fields();
		if ( ! in_array( 'thumbnail', $fields, true ) ) {
			return;
		}
		$field = [
			'type'             => current_user_can( 'upload_files' ) ? 'single_image' : 'image',
			'name'             => esc_html__( 'Thumbnail', 'rwmb-frontend-submission' ),
			'id'               => '_thumbnail_id',
			'storage'          => rwmb_get_storage( 'post' ),
			'max_file_uploads' => 1,
		];
		$field = RWMB_Field::call( 'normalize', $field );

		$old = RWMB_Field::call( $field, 'raw_meta', $this->post_id );
		$new = isset( $_POST[ $field['id'] ] ) ? $_POST[ $field['id'] ] : array();

		$new = RWMB_Field::process_value( $new, $this->post_id, $field );

		// Filter to allow the field to be modified.
		$field = RWMB_Field::filter( 'field', $field, $field, $new, $old );

		// Call defined method to save meta value, if there's no methods, call common one.
		RWMB_Field::call( $field, 'save', $new, $old, $this->post_id );

		RWMB_Field::filter( 'after_save_field', null, $field, $new, $old, $this->post_id, $field );
	}

	/**
	 * Get post fields from shortcode's "post_fields" attribute.
	 */
	private function get_post_fields() {
		return array_map( 'trim', array_filter( explode( ',', $this->config['post_fields'] . ',' ) ) );
	}
}
