<?php
/**
 * Hide metabox fields which is duplicated from default fields.
 * This class only hides fields from profile screen, not disabling saving process (which can be use elsewhere).
 */

namespace MBFS;

class DuplicatedFields {
	private $fields = [
		'post_title' => 'title',
		'post_content' => 'editor',
		'post_excerpt' => 'excerpt',
		'post_date' => 'date',
		'_thumbnail_id' => 'thumbnail',
	];

	public function __construct() {
		add_filter( 'rwmb_outer_html', [$this, 'remove_field'], 10, 2 );
	}

	public function remove_field( $html, $field ) {
		if ( ! is_admin() || ! isset( $this->fields[ $field['id'] ] ) ) {
			return $html;
		}

		global $post;
		if ( ! isset( $post->post_type ) ) {
			return $html;
		}
		$support = $this->fields[ $field['id'] ];
		return post_type_supports( $post->post_type, $support ) ? '' : $html;
	}
}