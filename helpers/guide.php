<?php
/**
 * guide.php
 *
 * Provide a destination guide for V3 viewers.
 *
 * Requires OpenSimulator Search module
 *   [OpenSimSearch](https://github.com/kcozens/OpenSimSearch)
 * Events need to be fetched with a separate script, from an HYPEvents server
 *
 * @package    magicoli/opensim-helpers
 * @subpackage    magicoli/opensim-helpers/guide
 * @author     Gudule Lapointe <gudule@speculoos.world>
 * @link       https://github.com/magicoli/opensim-helpers
 * @license    AGPLv3
 */

// require_once __DIR__ . '/includes/config.php'; // DEBUG: disabled until we're ready with WP
require_once __DIR__ . '/includes/functions.php';

class OpenSim_Guide {
	private $destinations = array();
	private $fullHTML     = false; // Flag to determine whether to output HTML tags
	private $url_base;
	private $url_args = array();
	private $source;

	public function __construct() {
		error_log( 'language ' . getPreferredLanguage() );

		$this->url_base = $this->getFullURL();

		if ( defined( 'OPENSIM_GUIDE_SOURCE' ) && ! empty( OPENSIM_GUIDE_SOURCE ) ) {
			$this->source = OPENSIM_GUIDE_SOURCE;
		} else {
			$this->source             = isset( $_GET['source'] ) ? $_GET['source'] : null;
			$this->url_args['source'] = $this->source;
		}

		// If it's the main script, output the html, otherwise let the main app
		// decide how and when, with build_html and output_html methods
		if ( $this->is_main_script() ) {
			$this->fullHTML = true;
			$this->output_html();
		}
	}

	public function output_html() {
		echo $this->build_html();
	}

	public function build_html() {
		$this->load_destinations( $this->source );

		$content = $this->html_prefix();

		if ( count( $this->destinations ) === 1 ) {
			$keys     = array_keys( $this->destinations );
			$category = $keys[0];
		} else {
			$category = isset( $_GET['category'] ) ? $_GET['category'] : null;
			$category = isset( $this->destinations[ $category ] ) ? $category : null;
		}

		if ( empty( $this->destinations ) ) {
			$content .= $this->no_result();
		} elseif ( empty( $category ) ) {
			$content .= $this->categories_list();
		} else {
			$content .= $this->destinations_list( $category );
		}
		$content .= $this->html_suffix();

		return $content;
	}

	private function load_destinations( $source ) {
		$fileContent = null;
		// Check if the source is a URL or a file path
		if ( filter_var( $source, FILTER_VALIDATE_URL ) ) {
			$fileContent = file_get_contents( $source );
		} elseif ( file_exists( $source ) ) {
			$fileContent = file_get_contents( $source );
		}

		$lines = explode( "\n", $fileContent );

		$categoryTitle = 'Destinations list';
		foreach ( $lines as $line ) {
			// Exclude lines starting with "#" or "//" or containing no actual characters
			if ( substr( trim( $line ), 0, 1 ) === '#' || substr( trim( $line ), 0, 2 ) === '//' || ! trim( $line ) ) {
				continue;
			}

			$line  = rtrim( $line, '|' ); // Remove trailing '|' to handle empty values
			$parts = explode( '|', $line );

			if ( empty( $parts[0] ) ) {
				continue;
			}

			$name = trim( $parts[0] );
			$url  = '';

			if ( isset( $parts[3] ) && trim( $parts[3] ) !== '' ) {
				// Use the 4th and 5th elements to support old format
				$url = trim( $parts[3] ) . '/' . ( isset( $parts[4] ) ? trim( $parts[4] ) : null );
			} else {
				// Use the 2nd element as in current format
				$url = isset( $parts[1] ) ? trim( $parts[1] ) : null;
			}

			if ( empty( $url ) ) {
				// New category found
				$categoryTitle = $name;
			} else {
				// Destination within a category
				$this->destinations[ $categoryTitle ][] = array(
					'name' => $name,
					'url'  => $url,
				);
			}
		}
	}

	public function categories_list() {
		$content = '<div class=header>'
		. '<h1>Destinations List</h1>'
		. '<span class="disclaimer">This is a work in progress, please be indulgent.</span>'
		. '</div>'
		. '<div class="list">';
		foreach ( $this->destinations as $categoryTitle => $destinations ) {
			if ( ! empty( $destinations ) ) {
				$content .= '<a href="' . $this->build_url( $categoryTitle ) . '">'
				. '<div class="item">'
				. '<img class="thumbnail" src="' . $this->place_thumbnail() . '" alt="' . $categoryTitle . '">'
				. '<div class="name">' . $categoryTitle . '</div>'
				. '<div class="data">' . count( $destinations ) . ' destinations</div>'
				. '</div>'
				. '</a>';
			}
		}
		$content .= '</div>';
		return $content;
	}

	public function destinations_list( $categoryTitle ) {
		// Build header
		$content = '<div class=header><h2>' . $categoryTitle . '</h2>';
		if ( count( $this->destinations ) > 1 ) {
			$content .= '<a href="' . $this->build_url() . '" class="back">Back to categories</a>';
		}
		$content .= '<span class="disclaimer">This is a work in progress, please be indulgent.</span>';
		$content .= '</div>';

		// Build list
		$content .= '<div class="list">';
		foreach ( $this->destinations[ $categoryTitle ] as $destination ) {
			$traffic  = $this->place_traffic();
			$people   = $this->place_people();
			$content .= '<a href="' . opensim_format_tp( $destination['url'], TPLINK_HG ) . '">'
			. '<div class="item">'
			. '<img class="thumbnail" src="' . $this->place_thumbnail() . '" alt="' . $destination['name'] . '">'
			. '<div class="name">' . $destination['name'] . '</div>'
			. '<div class="data">';
			if ( $people > 0 ) {
				$content .= ' <span>' . $this->place_people() . ' people</span> ';
			}
			if ( $traffic > 0 ) {
				$content .= ' <span>Traffic: ' . $this->place_traffic() . '</span> ';
			}
			$content .= '</div></div></a>';
		}
		$content .= '</div>';

		return $content;
	}

	private function no_result() {
		echo '<div class="error">';
		echo 'The realm of destinations you seek has eluded our grasp, spirited away by elusive knomes. Rally the grid managers, let them venture forth to curate a grand tapestry of remarkable places for your exploration!';
		echo '</div>';
		echo $this->getFullURL() . "?source=$this->source";
	}

	// Rest of the class remains unchanged...

	private function place_thumbnail() {
		// Replace this with the actual URL for the thumbnail placeholder
		return 'no-img.jpg';
	}

	private function place_traffic() {
		// Replace this with the actual traffic placeholder value
		return null;
	}

	private function place_people() {
		// Replace this with the actual number of people placeholder value
		return null;
	}

	private function is_main_script() {
		if ( defined( 'OPENSIM_GUIDE_SOURCE' ) ) {
			return false;
		}
		if ( defined( 'ABSPATH' ) ) {
			return false;
		}
		if ( function_exists( 'debug_backtrace' ) ) {
			$trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
			foreach ( $trace as $trace_info ) {
				if ( isset( $trace_info['function'] ) && in_array( $trace_info['function'], array( 'include', 'require' ) ) ) {
					return false;
				}
			}
		}
		return true; // Probably true actually
	}

	private function html_prefix() {
		$content = '';
		if ( $this->fullHTML ) {
			$content = '<!DOCTYPE html>'
			. '<html lang="en">'
			. '<head>'
			. '<meta charset="UTF-8">'
			. '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
			. '<title>Destination Guide</title>'
			. '</head>'
			. '<body class="destination-guide">';
		}

		$content .= '<link rel="stylesheet" type="text/css" href="css/guide.css?' . time() . '">'
		. '<div id="guide">';
		return $content;
	}

	private function html_suffix() {
		$content  = '</div>';
		$content .= '<script src="js/guide.js?' . time() . '"></script>';
		if ( $this->fullHTML ) {
			$content .= '</body></html>';
		}
		return $content;
	}

	private function getFullURL() {
		$protocol = isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
		$host     = $_SERVER['HTTP_HOST'];
		$request  = $_SERVER['PHP_SELF'];
		return $protocol . $host . $request;
	}

	private function build_url( $category = null ) {
		$args = array_filter(
			array_merge(
				$this->url_args,
				array(
					'category' => $category,
				)
			)
		);
		if ( empty( $args ) ) {
			return $this->url_base;
		}
		return $this->url_base . '?' . http_build_query( $args );
	}
}

$destinations_guide = new OpenSim_Guide();
