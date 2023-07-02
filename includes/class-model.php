<?php
/**
 * Register all actions and filters for the plugin
 *
 * @package    w4os
 * @subpackage w4os/includes
 */

/**
 * Register all actions and filters for the plugin.
 *
 * Maintain a list of all hooks that are registered throughout
 * the plugin, and register them with the WordPress API. Call the
 * run function to execute the list of actions and filters.
 */
class W4OS_Model extends W4OS_Loader {

	protected $actions;
	protected $filters;

	public function __construct() {
	}

	public function init() {

		$this->actions = array(
			// array(
			// 	'hook'     => 'init',
			// 	'callback' => 'register_post_types',
			// ),
		);

		$this->filters = array(
			array(
				'hook' => 'mb_settings_pages',
				'callback' => 'register_settings_pages',
			),
			array(
				'hook'     => 'rwmb_meta_boxes',
				'callback' => 'register_fields',
			),
		);

		// $this->register_hooks();
	}

	function register_post_types() {
	}

	function register_settings_pages( $settings_pages ) {
		$settings_pages[] = [
	        'menu_title' => __( 'Avatar Models', 'w4os' ),
	        'id'         => 'w4os-models',
	        'position'   => 0,
	        'parent'     => 'w4os',
	        'capability' => 'manage_options',
	        'style'      => 'no-boxes',
	        'icon_url'   => 'dashicons-admin-users',
	    ];

		return $settings_pages;
	}

	function register_fields( $meta_boxes ) {
		$prefix = '';
		
		$meta_boxes[] = [
			'title'          => __( 'Avatar Models', 'w4os' ),
			'id'             => 'w4os-models-fields',
			'settings_pages' => ['w4os-models'],
			'fields'         => [
				[
					'name'    => __( 'Match', 'w4os' ),
					'id'      => $prefix . 'match',
					'type'    => 'button_group',
					'options' => [
						'first' => __( 'First Name', 'w4os' ),
						'any'      => __( 'Any', 'w4os' ),
						'last'  => __( 'Last Name', 'w4os' ),
					],
					'std' => 'any',
				],
				[
					'name' => __( 'Name', 'w4os' ),
					'id'   => $prefix . 'name',
					'type' => 'text',
					'std'  => 'Model',
				],
				[
					'name'     => __( 'Current Models', 'w4os' ),
					'id'       => $prefix . 'current_models',
					'type'     => 'custom_html',
					'callback' => 'w4os_current_models',
				],
			],
		];

		return $meta_boxes;
	}

}

$this->loaders[] = new W4OS_Model();
