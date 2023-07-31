<?php
/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://github.com/magicoli/w4os
 * @since      0.1.0
 *
 * @package    GuduleLapointe/w4os
 * @subpackage w4os/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      0.1.0
 * @package    GuduleLapointe/w4os
 * @subpackage w4os/includes
 * @author     Magiiic <info@magiiic.com>
 */
class W4OS_I18n extends W4OS_Loader {

	public function __construct() {

		$this->actions = array(
			array(
				'hook'     => 'plugins_loaded',
				'callback' => 'load_plugin_textdomain',
			),
		);

		$this->filters = array(
			array(
				'hook'          => 'wp_dropdown_cats',
				'callback'      => 'fix_mb_show_option_all',
				'accepted_args' => 2,
			),
			// array(
			// 'hook' => 'list_cats',
			// 'callback' => 'list_cats_filter',
			// 'accepted_args' => 2,
			// ),
		);

	}

	function w4os_load_textdomain() {
		global $locale;
		if ( is_textdomain_loaded( W4OS_TXDOM ) ) {
			unload_textdomain( W4OS_TXDOM );
		}
		$mofile = W4OS::sprintf_safe( '%s-%s.mo', W4OS_TXDOM, $locale );

		$domain_path = path_join( WP_PLUGIN_DIR, W4OS_SLUG . '/languages' );
		$loaded      = load_textdomain( W4OS_TXDOM, path_join( $domain_path, $mofile ) );
	}
	// add_action( 'init', 'w4os_load_textdomain' );

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    0.1.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'w4os',
			false,
			W4OS_SLUG . '/languages/'
		);

	}

	/**
	 * Workaround to fix localisation bug in mb-admin-columns module.
	 * Set placeholder to $taxonomy->labels->all_items instead of _('All %s').
	 *
	 * @param string $output        Html for filter taxonomy dropdown menu.
	 * @param array  $parsed_args   Context elements.
	 * @return string               Fixed html.
	 */
	public function fix_mb_show_option_all( $output, $parsed_args ) {
		$taxonomy = get_taxonomy( $parsed_args['taxonomy'] );
		if ( ! $taxonomy ) {
			return $output;
		}

		$show_option_all = ( empty( $taxonomy->labels->show_option_all ) ) ? $taxonomy->labels->all_items : $taxonomy->labels->show_option_all;
		if ( empty( $show_option_all ) ) {
			return $output;
		}

		return preg_replace( ":<option value='0'>.*</option>:", "<option value='0'>$show_option_all</option>", $output );
	}

	/**
	 * Not intended to be run, only there to mark some forgottens strings for
	 * translation.
	 *
	 * @return void
	 */
	private function trigger_translate() {
		$trigger_translate = array(
			// __( 'Forgotten string', 'w4os' ),
		);

	}
}

$this->loaders[] = new W4OS_I18n();
