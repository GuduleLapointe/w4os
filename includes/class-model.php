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
				[
						// 'name' => __( 'Description', 'w4os' ),
						'id'   => $prefix . 'description',
						'type' => 'custom_html',
				],
				array(
					'name'    => __( 'Match', 'w4os' ),
					'id'      => $prefix . 'match',
					'type'    => 'button_group',
					'desc'  => '<ul class="description"><li>' . join('</li><li>', array(
						__('If avatar models are defined, new users will be presented with a choice on the avatar creation form, which will determine the initial outfit of the created avatar.', 'w4os'),
						__('The grid administrator needs to create each model from the ROBUST console, log in with the viewer, customize the outfit, and add a profile picture.', 'w4os'),
						__('The avatars used for the models should never be real user accounts.', 'w4os'),
						__('The new avatar will wear the same outfit as the model at the time of registration.', 'w4os'),
						__('If the model assignment rule is based on the name, each newly created avatar matching that rule will be automatically added to the list.', 'w4os'),
					)) . '</li></ul>',
					'options' => array(
						'first' => __( 'First Name', 'w4os' ),
						'any'   => __( 'Any', 'w4os' ),
						'last'  => __( 'Last Name', 'w4os' ),
						'uuid'  => __( 'Custom list', 'w4os' ),
					),
					'std'     => 'any',
				),
				array(
					'name' => __( 'Name', 'w4os' ),
					'id'   => $prefix . 'name',
					'type' => 'text',
					'std'  => 'Model',
					'visible' => [
							'when'     => [['match', '!=', 'uuid']],
							'relation' => 'or',
					],
				),
				[
					'name'        => __( 'UUID', 'w4os' ),
					'id'          => $prefix . 'uuids',
					'type'        => 'select_advanced',
					'placeholder' => __( 'Select one or more existing avatars', 'w4os' ),
					'multiple'    => true,
					'options' => self::get_avatars(),
					'visible'     => [
						'when'     => [['match', '=', 'uuid']],
						'relation' => 'or',
					],
				],
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

	static function get_avatars( $format = OBJECT ) {
		global $w4osdb;
		if ( empty( $w4osdb ) ) {
			return false;
		}

		$avatars = array();

		$sql = "SELECT PrincipalID, FirstName, LastName FROM UserAccounts WHERE active = true";
		$result = $w4osdb->get_results( $sql, $format );

		foreach($result as $avatar) {
			$avatars[$avatar->PrincipalID] = trim( "$avatar->FirstName $avatar->LastName" );
		}
		return $avatars;
	}

	static function get_models( $format = OBJECT ) {
		global $w4osdb;
		if ( empty( $w4osdb ) ) {
			return false;
		}

		$models = array();

		$match = w4os_get_option( 'w4os-models:match', 'any' );
		$name  = w4os_get_option( 'w4os-models:name', false );
		$uuids  = w4os_get_option( 'w4os-models:uuids', false );
		if ( ! $name ) {
			return array();
		}

		switch ( $match ) {
			case 'uuid':
				$conditions = "PrincipalID IN ('" . implode("','", $uuids) . "')";
				break;

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

function custom_admin_sidebar_content() {
    // Add a custom meta box to the sidebar
    add_meta_box(
        'custom_sidebar_content', // Unique ID
        'Custom Sidebar Content', // Title
        'custom_sidebar_callback', // Callback function to display content
        'settings_page_slug', // Settings page slug where the sidebar appears
        'side' // Position of the meta box (sidebar)
    );
}

function custom_sidebar_callback() {
    // Output the desired content for the sidebar
    echo '<p>This is my custom sidebar content.</p>';
}

// Hook into the 'admin_menu' action to add content to the sidebar
add_action('admin_menu', 'custom_admin_sidebar_content');
