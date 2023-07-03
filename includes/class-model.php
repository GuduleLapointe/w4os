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
	public $models;

	public function __construct() {
		$this->models = $this->get_models();
	}

	public function init() {

		$this->actions = array(
			// array(
			// 'hook'     => 'init',
			// 'callback' => 'register_post_types',
			// ),
		);

		$this->filters = array(
			array(
				'hook'     => 'mb_settings_pages',
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
		$settings_pages[] = array(
			'menu_title' => __( 'Avatar Models', 'w4os' ),
			'id'         => 'w4os-models',
			'position'   => 0,
			'parent'     => 'w4os',
			'capability' => 'manage_options',
			'style'      => 'no-boxes',
			'icon_url'   => 'dashicons-admin-users',
		);

		return $settings_pages;
	}

	function register_fields( $meta_boxes ) {
		$prefix = '';

		$meta_boxes[] = array(
			'title'          => __( 'Avatar Models', 'w4os' ),
			'id'             => 'w4os-models-fields',
			'settings_pages' => array( 'w4os-models' ),
			'fields'         => array(
				array(
					'name'    => __( 'Match', 'w4os' ),
					'id'      => $prefix . 'match',
					'type'    => 'button_group',
					'options' => array(
						'first' => __( 'First Name', 'w4os' ),
						'any'   => __( 'Any', 'w4os' ),
						'last'  => __( 'Last Name', 'w4os' ),
					),
					'std'     => 'any',
				),
				array(
					'name' => __( 'Name', 'w4os' ),
					'id'   => $prefix . 'name',
					'type' => 'text',
					'std'  => 'Model',
				),
				array(
					'name'     => __( 'Available Models', 'w4os' ),
					'id'       => $prefix . 'available_models',
					'type'     => 'custom_html',
					'callback' => array( $this, 'available_models' ),
				),
			),
		);

		return $meta_boxes;
	}

	static function get_models( $format = OBJECT ) {
		global $w4osdb;
		if ( empty( $w4osdb ) ) {
			return false;
		}

		$models = array();

		$match = w4os_get_option( 'w4os-models:match', 'any' );
		$name  = w4os_get_option( 'w4os-models:name', false );
		if ( ! $name ) {
			return array();
		}

		$select = 'SELECT FirstName, LastName, profileImage, profileAboutText FROM
		UserAccounts LEFT JOIN userprofile ON PrincipalID = userUUID WHERE active =
		true AND ';
		$order  = ' ORDER BY FirstName, LastName';

		switch ( $match ) {
			case 'first':
				$conditions = "FirstName = '%1\$s'";
				break;

			case 'last':
				$conditions = "LastName = '%1\$s'";
				break;

			default:
				$conditions = "( FirstName = '%1\$s' OR LastName = '%1\$s' )";
		}
		$sql    = $w4osdb->prepare(
			"SELECT PrincipalID, FirstName, LastName, profileImage, profileAboutText FROM
			UserAccounts LEFT JOIN userprofile ON PrincipalID = userUUID WHERE active =
			true AND {$conditions} ORDER BY FirstName, LastName",
			$name,
		);
		$models = $w4osdb->get_results( $sql, $format );

		return $models;
	}

	public function model_thumb( $model, $placeholder = W4OS_NOTFOUND_IMG ) {
		$output = '';

		$name         = $model->FirstName . ' ' . $model->LastName;
		$display_name = $name;
		$filter_name  = w4os_get_option( 'w4os-models:name', false );
		if ( ! empty( $filter_name ) ) {
			$display_name = preg_replace( '/ *' . $filter_name . ' */', '', $display_name );
		}
		$display_name = preg_replace( '/(.*) *Ruth2 *(.*)/', '\1 \2 <span class="r2">Ruth 2.0</span>', $display_name );
		$display_name = preg_replace( '/(.*) *Roth2 *(.*)/', '\1 \2 <span class="r2">Roth 2.0</span>', $display_name );
		$alt_name     = wp_strip_all_tags( $display_name );

		$imgid = ( w4os_empty( $model->profileImage ) ) ? $placeholder : $model->profileImage;
		if ( $imgid ) {
			$output = sprintf(
				'<figure>
				<img class="model-picture" alt="%2$s" src="%3$s">
				<figcaption>%1$s</figcaption>
				</figure>',
				$display_name,
				$alt_name,
				w4os_get_asset_url( $imgid ),
			);
		} elseif ( ! empty( $display_name ) ) {
			$output = sprintf(
				'<span class="model-name">%s</span>',
				$display_name,
			);
		}

		return $output;
	}

	public function available_models() {
		$content = '';

		$models = $this->get_models();
		foreach ( $models as $model ) {
			$content .= '<li class=model>' . $this->model_thumb( $model ) . '</li>';
		}
		if ( ! empty( $content ) ) {
			$content = '<ul class="models-list">' . $content . '</ul>';
		}

		return $content;
	}

	function select_model_field() {
		$models = self::get_models();
		if ( empty( $models ) ) {
			return 'No models';
		}
		if ( ! is_array( $models ) ) {
			return 'Error, wrong data format received';
		}

		$options      = '';
		$m            = 0;
		$random_model = rand( 1, count( $models ) );
		foreach ( $models as $model ) {
			$m++;
			$checked = ( $m == $random_model ) ? 'checked' : '';
			// if($model_name == W4OS_DEFAULT_AVATAR) $checked = " checked"; else $checked="";
			$model_name = $model->FirstName . ' ' . $model->LastName;

			$options .= sprintf(
				'<li >
					<label class="model">
						<input type="radio" name="w4os_model" value="%s" %s>
						%s
					</label>
				</li>',
				$model_name,
				$checked,
				$this->model_thumb( $model ),
			);
		}
		if ( ! empty( $options ) ) {
			$content = sprintf(
				'<div class="clear"></div>
				<p class=form-row>
					<label>%s</label>
					<p class="description">%s</p>
					<ul class="models-list">
						%s
					</ul>
				</p>',
				__( 'Your initial appearance', 'w4os' ),
				__( 'You can change it as often as you want in the virtual world.', 'w4os' ),
				$options,
			);
		}

		return $content;
	}

}

$this->loaders[] = new W4OS_Model();
