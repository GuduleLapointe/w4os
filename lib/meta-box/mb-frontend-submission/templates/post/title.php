<?php
/**
 * The template file for post title.
 *
 * @package    Meta Box
 * @subpackage MB Frontend Submission
 */

$field = apply_filters( 'rwmb_frontend_post_title', [
	'type' => 'text',
	'name' => $data->config['label_title'],
	'id'   => 'post_title',
] );
$field = RWMB_Field::call( 'normalize', $field );
RWMB_Field::call( $field, 'add_actions' );
RWMB_Field::call( $field, 'admin_enqueue_scripts' );
RWMB_Field::call( 'show', $field, false, $data->post_id );
