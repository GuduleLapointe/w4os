<?php
/**
 * Register all actions and filters for the plugin
 *
 * @package    GuduleLapointe/w4os
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
		$this->models = W4OS3_Model::get_models();
	}

	public function init() {

		$this->actions = array(
			array(
				'hook'     => 'admin_menu',
				'callback' => 'register_settings_sidebar',
			),
			array(
				'hook'     => 'wp_ajax_update_models_preview_content',
				'callback' => 'update_models_preview_content',
			),
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

		add_filter( 'parent_file', array( __CLASS__, 'set_active_menu' ) );
		add_filter( 'submenu_file', array( __CLASS__, 'set_active_submenu' ) );
	}

	public static function set_active_menu( $parent_file ) {
		global $pagenow;

		if ( $pagenow === 'admin.php' ) {
			$current_page = isset( $_GET['page'] ) ? $_GET['page'] : '';
			if ( $current_page === 'w4os-models' ) {
				$parent_file = 'w4os'; // Set to main plugin menu slug
			}
		}

		return $parent_file;
	}

	public static function set_active_submenu( $submenu_file ) {
		global $pagenow, $typenow;

		if ( $pagenow === 'admin.php' ) {
			$current_page = isset( $_GET['page'] ) ? $_GET['page'] : '';
			if ( $current_page === 'w4os-models' ) {
				$submenu_file = 'w4os-avatars';
			}
		}

		return $submenu_file;
	}


	function register_post_types() {
	}

	function register_settings_pages( $settings_pages ) {
		$parent           = 'w4os-avatars';
		$settings_pages[] = array(
			'menu_title' => __( 'Avatar Models', 'w4os' ),
			'page_title' => __( 'Avatar Models Settings', 'w4os' ),
			'id'         => 'w4os-models',
			'position'   => 0,
			'parent'     => $parent,
			'capability' => 'manage_options',
			'style'      => 'no-boxes',
			'icon_url'   => 'dashicons-admin-users',
			// 'class' => 'no-hints',
		);

		return $settings_pages;
	}

	function register_fields( $meta_boxes ) {
		$prefix = '';

		$meta_boxes[] = array(
			'title'          => __( 'Avatar Models', 'w4os' ),
			'id'             => 'w4os-models-fields',
			'settings_pages' => array( 'w4os-models' ),
			'class'          => 'w4os-settings',
			'fields'         => array(
				array(
					// 'name' => __( 'Description', 'w4os' ),
					'id'   => $prefix . 'description',
					'type' => 'custom_html',
				),
				array(
					'name'    => __( 'Match', 'w4os' ),
					'id'      => $prefix . 'match',
					'type'    => 'button_group',
					'options' => array(
						'first' => __( 'First Name', 'w4os' ),
						'any'   => __( 'Any', 'w4os' ),
						'last'  => __( 'Last Name', 'w4os' ),
						'uuid'  => __( 'Custom list', 'w4os' ),
					),
					'std'     => 'any',
				),
				array(
					'name'    => __( 'Name', 'w4os' ),
					'id'      => $prefix . 'name',
					'type'    => 'text',
					'std'     => 'Model',
					'visible' => array(
						'when'     => array( array( 'match', '!=', 'uuid' ) ),
						'relation' => 'or',
					),
				),
				array(
					'name'        => __( 'Select Models', 'w4os' ),
					'id'          => $prefix . 'uuids',
					'type'        => 'select_advanced',
					// 'type'        => 'autocomplete',
					'placeholder' => __( 'Select one or more existing avatars', 'w4os' ),
					'multiple'    => true,
					// 'clone' => true,
					'options'     => self::get_avatars(),
					'visible'     => array(
						'when'     => array( array( 'match', '=', 'uuid' ) ),
						'relation' => 'or',
					),
				),
				// array(
				// 'name'     => __( 'Available Models', 'w4os' ),
				// 'id'       => $prefix . 'models_preview',
				// 'type'     => 'custom_html',
				// 'callback' => array( $this, 'models_preview' ),
				// ),
			),
		);

		$meta_boxes[] = array(
			'id'             => 'w4os-models-preview-container',
			'settings_pages' => array( 'w4os-models' ),
			'class'          => 'w4os-settings no-hints',
			'fields'         => array(
				array(
					'name' => __( 'Available Models', 'w4os' ),
					'id'   => $prefix . 'models_preview_container',
					'type' => 'custom_html',
					'std'  => '<div class="available-models-container">' . $this->models_preview() . '</div>',
				),
			),
		);

		return $meta_boxes;
	}

	function register_settings_sidebar() {
		// Add a custom meta box to the sidebar
		add_meta_box(
			'sidebar-content', // Unique ID
			'Settings Sidebar', // Title
			array( $this, 'sidebar_content' ), // Callback function to display content
			'opensimulator_page_w4os-models', // Settings page slug where the sidebar appears
			'side' // Position of the meta box (sidebar)
		);
	}

	function sidebar_content() {
			echo '<ul class="description"><li>' . join(
				'</li><li>',
				array(
					__( 'If avatar models are defined, new users will be presented with a choice on the avatar creation form, which will determine the initial outfit of the created avatar.', 'w4os' ),
					__( 'The grid administrator needs to create each model from the ROBUST console, log in with the viewer, customize the outfit, and add a profile picture.', 'w4os' ),
					__( 'The avatars used for the models should never be real user accounts.', 'w4os' ),
					__( 'The new avatar will wear the same outfit as the model at the time of registration.', 'w4os' ),
					__( 'If the model assignment rule is based on the name, each newly created avatar matching that rule will be automatically added to the list.', 'w4os' ),
				)
			) . '</li></ul>';
	}

	static function get_avatars( $args = array(), $format = OBJECT ) {
		return W4OS3_Avatar::get_avatars( array(), $format );
	}

	static function get_models( $atts = array(), $format = OBJECT ) {
		return W4OS3_Model::get_models( $atts, $format );
	}

	public function model_thumb( $model, $placeholder = W4OS_NOTFOUND_IMG ) {
		return W4OS3_Model::model_thumb( $model, $placeholder );
	}

	public function models_preview( $atts = array() ) {
		return W4OS3_Model::models_preview( $atts );
	}

	function select_model_field() {
		$models = self::get_models();
		if ( empty( $models ) ) {
			return __( 'No models', 'w4os' );
		}
		if ( ! is_array( $models ) ) {
			return 'Error, wrong data format received';
		}

		$options      = '';
		$m            = 0;
		$random_model = rand( 1, count( $models ) );
		foreach ( $models as $model ) {
			++$m;
			$checked = ( $m == $random_model ) ? 'checked' : '';
			// if($model_name == W4OS_DEFAULT_AVATAR) $checked = " checked"; else $checked="";
			$model_name = $model->FirstName . ' ' . $model->LastName;

			$options .= sprintf_safe(
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
			$content = sprintf_safe(
				'<div class="clear"></div>
				<p class=form-row>
					<ul class="models-list">
						%s
					</ul>
				</p>',
				$options,
			);
		}

		return $content;
	}

	// AJAX handler to update the available models content
	public function update_models_preview_content() {
		return;
	}
}

$this->loaders[] = new W4OS_Model();
