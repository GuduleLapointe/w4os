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
	// static $rendered = false;	// Temporary workaround

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
	}

	public function output_page() {
		$this->fullHTML = true;
		$this->output_html();
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
			die_knomes();
			$content .= $this->no_result();
		} elseif ( empty( $category ) ) {
			$content .= $this->categories_list();
		} else {
			$content .= $this->destinations_list( $category );
		}

		$content .= $this->html_suffix();
		$content = fix_utf_encoding($content);
		return $content;
	}

	private function load_destinations( $source ) {
		if(empty($source)) {
			die_knomes( 'No source provided for destinations guide.' );
		}

		// Apply security validation
		$source = $this->filter_url( $source );
		$fileContent = $this->filter_content( $source );

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

	/**
	 * Validate URL for security (filter IPs, invalid patterns)
	 * 
	 * @param string $source        Source URL to validate
	 * @return string Validated source URL
	 */
	private function filter_url( $source ) {
		if ( empty( $source ) ) {
			error_log( __METHOD__ . ": No source URL provided" );
			die_knomes("No source URL provided", 400 );
		}

		// Check for dangerous patterns
		$dangerous_patterns = array(
			'localhost',
			'127.0.0.1',
			'::1',
			'0.0.0.0',
			'169.254.', // AWS metadata service
			'10.',      // Private networks
			'172.',     // Private networks  
			'192.168.', // Private networks
			'file://',
			'ftp://',
			'gopher://',
			'dict://',
			'ldap://',
		);

		$url_lower = strtolower( $source );
		foreach ( $dangerous_patterns as $pattern ) {
			if ( strpos( $url_lower, $pattern ) !== false ) {
				error_log( __METHOD__ . ": URL rejected - contains dangerous pattern '$pattern': $source" );
				die_knomes("Source URL rejected", 400 );
			}
		}

		// For URLs, validate scheme and check IP resolution
		if ( filter_var( $source, FILTER_VALIDATE_URL ) ) {
			$parsed = parse_url( $source );

			if ( ! in_array( $parsed['scheme'], array( 'http', 'https' ) ) ) {
				error_log( __METHOD__ . ": URL rejected - invalid scheme '{$parsed['scheme']}': $source" );
				die_knomes("Invalid URL scheme", 400 );
			}

			if ( isset( $parsed['host'] ) ) {
				$ip = gethostbyname( $parsed['host'] );
				if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
					error_log( __METHOD__ . ": URL rejected - resolves to private or reserved IP '$ip': $source" );
					die_knomes("Source URL resolves to private IP", 403 );
				}
			}
		} else {
			// For local files, check path safety
			$real_path = realpath( $source );
			if ( $real_path === false || ! file_exists( $real_path ) ) {
				error_log( __METHOD__ . ": Local file does not exist: $source" );
				die_knomes("Source file not found", 404 );
			}

			// Only allow files in safe directories
			$safe_directories = array( realpath( __DIR__ ), realpath( __DIR__ . '/../' ) );
			$path_allowed = false;
			foreach ( $safe_directories as $safe_dir ) {
				if ( strpos( $real_path, $safe_dir ) === 0 ) {
					$path_allowed = true;
					break;
				}
			}

			if ( ! $path_allowed ) {
				error_log( __METHOD__ . ": Local file path not allowed: $source" );
				die_knomes("Access denied", 403 );
			}
		}

		return $source;
	}

	/**
	 * Validate content and return filtered source content
	 * 
	 * @param string $source_url URL or file path to fetch and validate
	 * @return string Validated content
	 */
	private function filter_content( $source_url ) {
		$content = null;

		// Get content
		if ( filter_var( $source_url, FILTER_VALIDATE_URL ) ) {
			// Use engine function for headers (associative format)
			$headers = os_get_headers( $source_url, true );
			if ( $headers === false ) {
				die_knomes("Failed to get headers from source", 502 );
			}

			// Check content type
			if ( isset( $headers['content-type'] ) ) {
				$content_type_lower = strtolower( trim( explode( ';', $headers['content-type'] )[0] ) );
				$valid_types = array( 'text/plain', 'text/csv', 'text/tab-separated-values', 'application/octet-stream' );
				
				if ( ! in_array( $content_type_lower, $valid_types ) && strpos( $content_type_lower, 'text/' ) !== 0 ) {
					error_log( __METHOD__ . "Invalid content-type '" . $headers['content-type'] . "' from URL: $source_url" );
					die_knomes("Invalid content-type from source URL", 415 );
				}
			}

			// Use engine function for content
			$content = os_file_get_contents( $source_url );
			if ( $content === false ) {
				die_knomes("Failed to fetch content from source", 502 );
			}

			if ( strlen( $content ) > 1048576 ) {
				error_log( __METHOD__ . "Content too large (" . strlen( $content ) . " bytes) from URL: $source_url" );
				die_knomes("Content too large from source", 413 );
			}
		} else {
			// Local file
			try {
				set_error_handler( function( $errno, $errstr ) {
					throw new Exception( $errstr, $errno );
				} );
				$content = file_get_contents( $source_url );
				restore_error_handler();
			} catch ( Exception $e ) {
				restore_error_handler();
				error_log( __METHOD__ . "Error reading local file: " . $e->getMessage() . " from: $source_url" );
				die_knomes("Failed to read local file", 500 );
			}
			if ( $content === false ) {
				error_log( __METHOD__ . "Failed to read local file: $source_url" );
				die_knomes("Failed to read source file", 500 );
			}
		}

		// Validate content format
		if ( ! mb_check_encoding( $content, 'UTF-8' ) && ! mb_check_encoding( $content, 'ASCII' ) ) {
			error_log( __METHOD__ . "Invalid text encoding (" . mb_detect_encoding( $content ) . ") from: $source_url" );
			die_knomes("Invalid text encoding from source URL", 415 );
		}

		if ( empty( trim( $content ) ) ) {
			error_log( __METHOD__ . "Empty content from: $source_url" );
			die_knomes("Empty source content", 204 );
		}

		// Strict format validation
		$lines = explode( "\n", $content );
		$line_number = 0;

		$filtered = array();
		foreach ( $lines as $line ) {
			$line_number++;
			$line = trim( $line );

			// Skip empty lines and comments
			if ( empty( $line ) || substr( $line, 0, 1 ) === '#' || substr( $line, 0, 2 ) === '//' ) {
				// Skipping silently comments and empty lines
				continue;
			}

			// Validate line format
			if ( strpos( $line, '|' ) === false ) {
				// Category, only truncate excessively long lines
				$line = substr( $line, 0, 100 );
			} elseif ( $line === '|' ) {
				// Standalone pipe is a valid separator line
				// Keep as-is
			} else {
				// Destination line - validate pipe-separated format
				$parts = explode( '|', $line );
				if ( count( $parts ) < 2 || empty( trim( $parts[0] ) ) ) {
					error_log( __METHOD__ . "Invalid line format at line $line_number" );
					die_knomes("Invalid content format at line $line_number", 415 );
				}
			}
			$filtered[] = $line;
		}
		$content = implode( "\n", $filtered );
		return $content;
	}
}
