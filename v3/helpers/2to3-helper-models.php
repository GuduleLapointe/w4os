<?php
/**
 * Avatar Model Class
 * 
 * Helper class including the functions related the Avatar Models.
 */

class W4OS3_Model {

	public function init() {
		add_filter( 'w4os_settings_tabs', array( $this, 'register_settings_tabs' ) );
        add_action( 'admin_init', [ __CLASS__, 'register_settings_page' ] );
    //     add_filter( 'parent_file', [ __CLASS__, 'set_active_menu' ] );
    //     add_filter( 'submenu_file', [ __CLASS__, 'set_active_submenu' ] );
	}

	function register_settings_tabs($tabs) {

		$tabs['w4os-avatars']['models'] = array(
			'title' => __( 'Avatar Models', 'w4os' ),
			// 'url'   => admin_url('admin.php?page=w4os-models')
		);

		return $tabs;
	}

    /**
     * Register settings using the Settings API, templates and the method W4OS3_Settings::render_settings_section().
     */
    public static function register_settings_page() {
        if (! W4OS_ENABLE_V3) {
            return;
        }
		
        $option_name = 'w4os-avatars'; // Hard-coded here is fine to make sure it matches intended submenu slug
        $option_group = $option_name . '_group';

        // Register the main option with a sanitize callback
        // register_setting( $option_group, $option_name, [ __CLASS__, 'sanitize_options' ] );

        // Get the current tab
        $tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'settings';
        $section = $option_group . '_section_' . $tab;

        // Add settings sections and fields based on the current tab
        if ( $tab == 'models' ) {
            add_settings_section(
                $section,
                null, // No title for the section
                '', // [ __CLASS__, 'section_callback' ],
                $option_name // Use dynamic option name
            );

			$prefix = $tab . '-';
			$fields = array(
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
					'default'     => 'any',
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
				array(
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
				),
			);

			foreach ( $fields as $field ) {
				$field_id = $field['id'];

				add_settings_field(
					$field_id, 
					$field['name'] ?? '',
					'W4OS3_Settings::render_settings_field',
					$option_name, // Use dynamic option name as menu slug
					$section,
					$field,
				);
			}

        }
    }

	public static function available_models( $atts = array() ) {
		$content = '';

		$models = W4OS3_Model::get_models( $atts );

		if ( empty( $models ) ) {
			$content = '<divclass="models-list">' . __( 'No models found.', 'w4os' ) . '</div>';
		} else {
			foreach ( $models as $model ) {
				$content .= '<li class=model>' . self::model_thumb( $model ) . '</li>';
			}
			$content = '<ul class="models-list">' . $content . '</ul>';
		}

		return $content;
	}

	public static function model_thumb( $model, $placeholder = W4OS_NOTFOUND_IMG ) {
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
			$output = W4OS::sprintf_safe(
				'<figure>
				<img class="model-picture" alt="%2$s" src="%3$s">
				<figcaption>%1$s</figcaption>
				</figure>',
				$display_name,
				$alt_name,
				w4os_get_asset_url( $imgid ),
			);
		} elseif ( ! empty( $display_name ) ) {
			$output = W4OS::sprintf_safe(
				'<span class="model-name">%s</span>',
				$display_name,
			);
		}

		return $output;
	}


	// public static function set_active_menu( $parent_file ) {
    //     global $pagenow;

    //     if ( $pagenow === 'admin.php' ) {
	// 		$current_page = isset( $_GET['page'] ) ? $_GET['page'] : '';
	// 		if ( $current_page === 'w4os-models' ) {
    //             $parent_file = 'w4os-avatars'; // Set to main plugin menu slug
    //         }
    //     }

    //     return $parent_file;
    // }

    // public static function set_active_submenu( $submenu_file ) {
    //     global $pagenow, $typenow;

	// 	if ( $pagenow === 'admin.php' ) {
	// 		$current_page = isset( $_GET['page'] ) ? $_GET['page'] : '';
	// 		if ( $current_page === 'w4os-models' ) {
    //             $submenu_file = 'w4os-avatars'; // Set to submenu slug
    //         }
    //     }

	// 	return $submenu_file;
    // }


	static function is_model( $atts ) {
		if (empty($atts)) {
			return false;
		}
		if( is_string( $atts ) ) {
			$uuid = $atts;
		} else {
			$item = $atts;
			$uuid = $item->PrincipalID;
		}
		if ( empty( $w4osdb ) ) {
			return false;
		}
		$name = W4OS3_Avatar::get_name( $atts );
		$model_options = get_option( 'w4os-models', array() );
		$match       = $model_options['match'] ?? 'any';
		$match_name	 = $model_options['name'] ?? 'Default';
		$match_uuids = $model_options['uuids'] ?? [];
		
		switch ( $match ) {
			case 'uuid':
				if ( ! empty( $match_uuids ) ) {
					return in_array( $uuid, $match_uuids );
				} else {
					return false;
				}
				break;
				
			case 'first':
				if ( ! empty( $match_name ) ) {
					$first_name = preg_replace( '/\s+.*$/', '', $name );
					return $first_name == $match_name;
				} else {
					return false;
				}
				break;

			case 'last':
				if ( ! empty( $match_name ) ) {
					$last_name = preg_replace( '/^.*\s+/', '', $name );
					return $last_name == $match_name;
				} else {
					return false;
				}
				break;

			default:
				$first_name = preg_replace( '/\s+.*$/', '', $name );
				$last_name  = preg_replace( '/^.*\s+/', '', $name );
				return $first_name == $match_name || $last_name == $match_name;
		}
	}

    static function get_models( $atts = array(), $format = OBJECT ) {
		global $w4osdb;
		if ( empty( $w4osdb ) ) {
			return false;
		}

		$models = array();

		if ( ! empty( $atts['match'] ) ) {
			$match = $atts['match'];
			$name  = $atts['name'];
			$uuids = $atts['uuids'];
		} else {
			if (
				isset( $_REQUEST['page'] )
				&& $_REQUEST['page'] == 'w4os-models'
				&& isset( $_POST['match'] )
				&& isset( $_POST['name'] )
				&& isset( $_POST['uuids'] )
			) {
				$match = esc_attr( $_POST['match'] );
				$name  = esc_attr( $_POST['name'] );
				$uuids = array_map( 'esc_attr', $_POST['uuids'] );
			} else {
				$match = w4os_get_option( 'w4os-models:match', 'any' );
				$name  = w4os_get_option( 'w4os-models:name', false );
				$uuids = w4os_get_option( 'w4os-models:uuids', array() );
			}
		}

		switch ( $match ) {
			case 'uuid':
				if ( ! empty( $uuids ) ) {
					$conditions = "PrincipalID IN ('" . implode( "','", $uuids ) . "')";
				} else {
					$conditions = 'FALSE';
				}
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

}
