<?php
/**
 * The template file for post date.
 *
 * @package    Meta Box
 * @subpackage MB Frontend Submission
 */

$field = apply_filters( 'rwmb_frontend_post_date', [
	'type' => 'datetime',
	'name' => $data->config['label_date'],
	'id'   => 'post_date',
] );
$field = RWMB_Field::call( 'normalize', $field );
RWMB_Field::call( $field, 'add_actions' );
RWMB_Field::call( $field, 'admin_enqueue_scripts' );
RWMB_Field::call( 'show', $field, false, $data->post_id );
