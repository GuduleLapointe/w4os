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
class W4OS_Profile extends W4OS_Loader {
	protected $actions;
	protected $filters;
	protected static $base_url;
	protected static $slug;
	// private $base_url;

	public function __construct() {
		self::$slug     = get_option( 'w4os_profile_slug', 'profile' );
		self::$base_url = get_home_url( null, self::$slug );
	}

	public static function slug() {
		return self::$slug;
	}

	public static function url( $firstname = null, $lastname = null ) {
		if ( empty( $firstname ) || empty( $lastname ) ) {
			return self::$base_url;
		} else {
			$firstname = sanitize_title( $firstname );
			$lastname  = sanitize_title( $lastname );
			return self::$base_url . '/' . $firstname . '.' . $lastname;
		}
	}

	public function init() {

		$this->actions = array();

		$this->filters = array();

	}
}

$this->loaders[] = new W4OS_Profile();
