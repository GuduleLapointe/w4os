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
				'hook'     => 'admin_enqueue_scripts',
				'callback' => 'enqueue_custom_settings_script',
			),
			array(
				'hook'     => 'wp_ajax_update_available_models_content',
				'callback' => 'update_available_models_content',
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

        add_filter( 'parent_file', [ __CLASS__, 'set_active_menu' ] );
        add_filter( 'submenu_file', [ __CLASS__, 'set_active_submenu' ] );
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
		$settings_pages[] = array(
			'menu_title' => __( 'Avatar Models', 'w4os' ),
			'page_title' => __( 'Avatar Models Settings', 'w4os' ),
			'id'         => 'w4os-models',
			'position'   => 0,
			'parent'     => 'w4os-avatars',
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
			'title' => 'Avatar Models Settings header',
			'id'    => 'w4os-models-header',
			'settings_pages' => array( 'w4os-models' ),
			'class' => 'w4os-settings no-hints',
			'fields' => array(
				array(
					'id'   => $prefix . 'main_tabs',
					'type' => 'custom_html',
					'std'  => self::get_tabs_html(),
				),
			),
		);
		
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
					'options'     => W4OS3_Avatar::get_avatars(),
					'visible'     => array(
						'when'     => array( array( 'match', '=', 'uuid' ) ),
						'relation' => 'or',
					),
				),
				// array(
				// 'name'     => __( 'Available Models', 'w4os' ),
				// 'id'       => $prefix . 'available_models',
				// 'type'     => 'custom_html',
				// 'callback' => array( $this, 'available_models' ),
				// ),
			),
		);

		$meta_boxes[] = array(
			'id'             => 'w4os-available-models-container',
			'settings_pages' => array( 'w4os-models' ),
			'class'          => 'w4os-settings no-hints',
			'fields'         => array(
				array(
					'name' => __( 'Available Models', 'w4os' ),
					'id'   => $prefix . 'available_models_container',
					'type' => 'custom_html',
					'std'  => '<div class="available-models-container">' . W4OS3_Model::available_models() . '</div>',
				),
			),
		);

		return $meta_boxes;
	}

	/**
	 * get_tabs_html: 
	 * 	- exit if not on w4os-models admin page
	 *  - get the main tabs from W4OS3_Avatar::main_tabs()
	 * - return the tabs as a string
	 */
	function get_tabs_html() {
		// TODO: fix, currently the page appears briefly then disappears
		return W4OS3_Settings::get_tabs_html( 'w4os-avatars' );

		// Bad workaround: return a link to the Avatar Settings page
		// $avatar_page_url = admin_url( 'admin.php?page=w4os-avatar' );
		// return sprintf(
		// 	'<a href="%s" class="nav-tab">%s</a>',
		// 	$avatar_page_url,
		// 	__( 'Back to Avatar Settings', 'w4os' ),
		// );
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

	/* Moved to W4OS3_Avatar */
	// static function get_avatars( $format = OBJECT ) {
	// }

	/* Moved to W4OS3_Model */
	// static function get_models( $atts = array(), $format = OBJECT ) {
	// }

	/* Moved to W4OS3_Model */
	// public function model_thumb( $model, $placeholder = W4OS_NOTFOUND_IMG ) {
	// }

	/* Moved to W4OS3_Model */
	// public function available_models( $atts = array() ) {
	// }

	function select_model_field() {
		$models = W4OS3_Model::get_models();
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
			$m++;
			$checked = ( $m == $random_model ) ? 'checked' : '';
			// if($model_name == W4OS_DEFAULT_AVATAR) $checked = " checked"; else $checked="";
			$model_name = $model->FirstName . ' ' . $model->LastName;

			$options .= W4OS::sprintf_safe(
				'<li >
					<label class="model">
						<input type="radio" name="w4os_model" value="%s" %s>
						%s
					</label>
				</li>',
				$model_name,
				$checked,
				W4OS3_Model::model_thumb( $model ),
			);
		}
		if ( ! empty( $options ) ) {
			$content = W4OS::sprintf_safe(
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

	function enqueue_custom_settings_script( $hook ) {
		// Enqueue the script only on the specific settings page
		if ( $hook === 'opensimulator_page_w4os-models' ) {
			wp_enqueue_script( 'w4os-settings-models', plugin_dir_url( __DIR__ ) . 'includes/admin/settings-models.js', array( 'jquery' ), '1.0', true );
			wp_localize_script(
				'w4os-settings-models',
				'w4osSettings',
				array(
					'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
					'nonce'          => wp_create_nonce( 'update_available_models_content_nonce' ), // Nonce for security
					'loadingMessage' => __( 'Refreshing list...', 'w4os' ),
					'updateAction'   => 'update_available_models_content',
				)
			);
		}
	}

	// AJAX handler to update the available models content
	public function update_available_models_content() {
		// Verify the AJAX request
		check_ajax_referer( 'update_available_models_content_nonce', 'nonce' );

		// Check if the action parameter is set to 'update_available_models_content'
		if ( isset( $_POST['action'] ) && $_POST['action'] === 'update_available_models_content' ) {
			// Sanitize the input values
			$atts = array(
				'match' => isset( $_POST['preview_match'] ) ? esc_attr( $_POST['preview_match'] ) : null,
				'name'  => isset( $_POST['preview_name'] ) ? esc_attr( $_POST['preview_name'] ) : null,
				'uuids' => isset( $_POST['preview_uuids'] ) ? array_map( 'esc_attr', $_POST['preview_uuids'] ) : null,
			);

			// Generate the updated available models content
			$output = W4OS3_Model::available_models( $atts );

			// Send the updated content as the AJAX response
			wp_send_json( $output );
		} else {
			// Invalid action parameter
			wp_send_json_error( 'Invalid action' );
		}
	}
}

$this->loaders[] = new W4OS_Model();
