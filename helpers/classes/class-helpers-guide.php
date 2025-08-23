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
 * 
 * TODO: use templates for HTML output, with a custom template for viewers.
 * TODO: implement parcel image thumbnail, fallback to bigger title instead of placeholder.
 * TODO: implement user/admin-curated destinations lists
 * 
 * @package    magicoli/opensim-helpers
 * @subpackage    magicoli/opensim-helpers/guide
 * @author     Gudule Lapointe <gudule@speculoos.world>
 * @link       https://github.com/magicoli/opensim-helpers
 * @license    AGPLv3
 */

class OpenSim_Helpers_Guide {
	private $destinations = array();
	private $fullHTML     = false; // Flag to determine whether to output HTML tags
	private $public_url;
	private $url_args = array();
	private $source;
	private $locale;

	private $page_title;
	private $head_title;
	private $disclaimer = null; // Disclaimer text, can be set to null if not needed

	public function __construct( $source = null ) {
		$this->locale = set_helpers_locale();

		$this->public_url   = $this->get_public_url();
		$this->page_title = '<em>' . _( 'Destinations Guide' ) . '</em>';
		$this->head_title = fix_utf_encoding($this->page_title);
		$this->disclaimer = _( 'This is a work in progress, please report any issues or suggestions.' );

		if( empty( $this->public_url ) ) {
			$request_uri = getenv( 'REQUEST_URI' );
			error_log( '[WARNING] ' . $request_uri . ' called without grid configuration.' );
			die();
		}
		
		if ( ! empty( $source ) ) {
			$this->source = $source;
		} else if ( ! empty( $_GET['source'] ) ) {
			$this->source             = isset( $_GET['source'] ) ? $_GET['source'] : null;
			$this->url_args['source'] = $this->source;
		} else if( $source = Engine_Settings::get('engine.DestinationGuide.GuideSource') ?? false ) {
			$this->source = $source;
		} else if ( defined( 'OPENSIM_GUIDE_SOURCE' ) && ! empty( OPENSIM_GUIDE_SOURCE ) ) {
			Engine_Settings::log_migration_required();
			$this->source = OPENSIM_GUIDE_SOURCE;
		}

		// We don't output HTML from constructor anymore, the caller decides:
		// 		if( $guide = new OpenSim_Helpers_Guide( $this->source ) ) {
		// 			$guide->output_page(); // Output full HTML page, including <html> tags
		// 		    $guide->output_html(); // Output only the guide block
		// 		    $html = $guide->build_html(); // Return guide block for further processing
		// 		}
	}

	public function output_page() {
		$this->fullHTML = true;
		$this->output_html();
	}

	public function output_html() {
		if ( empty( $fullHTML ) ) {
			echo $this->build_html();
		}
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
			die_knomes();
			$content .= $this->no_result();
		} elseif ( empty( $category ) ) {
			$content .= $this->categories_list();
		} else {
			$content .= $this->destinations_list( $category );
		}

		$content .= $this->html_suffix();

		return fix_utf_encoding($content);
	}

	private function load_destinations( $source ) {
		if(empty($source)) {
			die_knomes( 'No source provided for destinations guide.' );
		}
		$fileContent = null;
		// Check if the source is a URL or a file path
		if ( filter_var( $source, FILTER_VALIDATE_URL ) ) {
			$fileContent = file_get_contents( $source );
		} elseif ( file_exists( $source ) ) {
			$fileContent = file_get_contents( $source );
		}

		$lines = explode( "\n", $fileContent );

		$categoryTitle = $this->page_title;
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
		$content = sprintf(
			'<div class="header">
				<h1>%s</h1>
				%s
			</div>
			<div class="list">',
			opensim_sanitize_basic_html($this->page_title),
			$this->disclaimer ? '<span class="disclaimer">' . opensim_sanitize_basic_html($this->disclaimer) . '</span>' : ''
		);
		foreach ( $this->destinations as $categoryTitle => $destinations ) {
			if ( ! empty( $destinations ) ) {
				$content .= '<a href="' . $this->build_url( $categoryTitle ) . '">'
				. '<div class="item">'
				. '<img class="thumbnail" src="' . $this->place_thumbnail() . '" alt="' . $categoryTitle . '">'
				. '<div class="name">' . $categoryTitle . '</div>'
				// Translators: %s will be replaced with the number of destinations
				. '<div class="data">' . sprintf( _( '%s destinations' ), count( $destinations ) ) . '</div>'
				. '</div>'
				. '</a>';
			}
		}
		$content .= '</div>';
		return $content;
	}

	public function destinations_list( $categoryTitle ) {
		// Build header
		$back_url = (count($this->destinations) > 1) ? sprintf(
			'<a href="%s" class="back">%s</a>',
			$this->build_url(),
			_( 'Back to categories' )
		) : '';

		$content = sprintf(
			'<div class="header">
				<h2>%s</h2>
				%s
				%s
			</div>',
			opensim_sanitize_basic_html($categoryTitle),
			$back_url,
			$this->disclaimer ? '<span class="disclaimer">' . opensim_sanitize_basic_html($this->disclaimer) . '</span>' : ''
		);

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
				$content .= ' <span>' . sprintf( _( '%s people' ), $this->place_people() ) . '</span> ';
			}
			if ( $traffic > 0 ) {
				$content .= ' <span>' . sprintf( _( 'traffic %s' ), $this->place_traffic() ) . '</span> ';
			}
			$content .= '</div></div></a>';
		}
		$content .= '</div>';

		return $content;
	}

	private function place_thumbnail() {
		// Replace this with the actual URL for the thumbnail placeholder
		return OSHELPERS_URL . '/no-img.jpg';
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
			// Send headers if not yet sent
			if ( headers_sent() ) {
				error_log( '[WARNING] Headers already sent, cannot set HTML headers.' );
			} else {
				header( 'Content-Type: text/html; charset=UTF-8' );
				header( 'Content-Language: ' . $this->locale );

				// header( 'Cache-Control: no-cache, no-store, must-revalidate' );
				// header( 'Pragma: no-cache' );
				// header( 'Expires: 0' );
				// header( 'Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\'; style-src \'self\' \'unsafe-inline\'; img-src \'self\' data:; font-src \'self\';' );
			}
			// Output the full HTML structure
			$content = sprintf(
				'<!DOCTYPE html>
				<html lang="%s">
					<head>
						<meta charset="UTF-8">
						<meta name="viewport" content="width=device-width, initial-scale=1.0">
						<title>%s</title>
					</head>
				<body class="os-helpers destination-guide">',
				$this->locale,
				strip_tags($this->head_title),
			);
		}
		// TODO: use enqueue_style(), but it might need the templates
		$css_url = Helpers::url('css/guide.css');
		error_log( '[DEBUG] css url: ' . $css_url );
		$content .= sprintf(
			'<link rel="stylesheet" type="text/css" href="%s">',
			$css_url
		);
		$content .= '<div id="guide">';
		return $content;
	}

	private function html_suffix() {
		$content  = '</div>';
		$content .= '<script src="' . OSHELPERS_URL . '/js/guide.js?' . time() . '"></script>';
		if ( $this->fullHTML ) {
			$content .= '</body></html>';
		}
		return $content;
	}

	static function url() {
		return Engine_Settings::get(
			'robust.LoginService.DestinationGuide', 
			Engine_Settings::get(
				'opensim.SimulatorFeatures.DestinationGuide',
				defined('OSHELPERS_URL') ? OSHELPERS_URL . '/' . basename( __FILE__ ) : null
			)
		);
	}

	private function get_public_url() {
		$protocol    = isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
		$host        = $_SERVER['HTTP_HOST'];
		$request_uri = $_SERVER['REQUEST_URI'];

		// Parse the request URI to extract only the path part
		$parsed_url = parse_url( $request_uri );
		$path       = $parsed_url['path'];

		return $protocol . $host . $path;
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
			return $this->public_url;
		}
		return $this->public_url . '?' . http_build_query( $args );
	}
}
