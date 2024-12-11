<?php
/**
 * Avatar Model Class
 * 
 * Helper class including the functions related the Avatar Models.
 */

class W4OS3_Model {

	public static function init() {
        add_filter( 'parent_file', [ __CLASS__, 'set_active_menu' ] );
        add_filter( 'submenu_file', [ __CLASS__, 'set_active_submenu' ] );
	}

	public static function set_active_menu( $parent_file ) {
        global $pagenow;

        if ( $pagenow === 'admin.php' ) {
			$current_page = isset( $_GET['page'] ) ? $_GET['page'] : '';
			if ( $current_page === 'w4os-models' ) {
                $parent_file = 'w4os-avatar'; // Set to main plugin menu slug
            }
        }

        return $parent_file;
    }

    public static function set_active_submenu( $submenu_file ) {
        global $pagenow, $typenow;

		if ( $pagenow === 'admin.php' ) {
			$current_page = isset( $_GET['page'] ) ? $_GET['page'] : '';
			if ( $current_page === 'w4os-models' ) {
                $submenu_file = 'w4os-avatar'; // Set to submenu slug
            }
        }

		return $submenu_file;
    }


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
