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
require_once __DIR__ . '/engine/includes/functions.php';

class OpenSim_Guide {
	private $destinations = array();
	private $fullHTML     = false; // Flag to determine whether to output HTML tags
	private $public_url;
	private $url_args = array();
	private $source_url;
	private $source_content;
	private $strict_mode = true; // If true, source with any error is rejected
	                          // If false, best-effort parsing is done

	public function __construct( $source_url = null ) {
		set_helpers_locale();

		$this->public_url   = $this->get_public_url();
		$this->internal_url = $this->get_child_script_url();

		# Good logic: let filter_url make all the checks
		$this->source_url = $this->filter_url( $source_url );	
		$this->url_args['source'] = $this->source_url;

		$this->source_content = $this->filter_content( $this->source_url );

		// Content is valid, parse it into destinations
		$this->parse_destinations( $this->source_content );

		// If it's the main script, output the html, otherwise let the main app
		// decide how and when, with build_html and output_html methods
		if ( $this->is_main_script() ) {
			$this->fullHTML = true;
			$this->output_html();
		}
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
		// Destinations are already loaded and validated in constructor
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

	/**
	 * Parse validated content into destinations array
	 * Content is already validated at this point
	 */
	private function parse_destinations( $content ) {
		$lines = explode( "\n", $content );
		$categoryTitle = _( 'Destinations Guide' );

		foreach ( $lines as $line ) {
			$line = trim( $line );
			// Skip empty lines and comments (should already be filtered but double-check)
			if ( empty( $line ) || substr( $line, 0, 1 ) === '#' || substr( $line, 0, 2 ) === '//' ) {
				continue;
			}

			if ( strpos( $line, '|' ) === false ) {
				// Category line
				$categoryTitle = $line;
			} else {
				// Destination line
				$parts = explode( '|', $line );
				$name = trim( $parts[0] );
				$source_url  = '';

				if ( isset( $parts[3] ) && trim( $parts[3] ) !== '' ) {
					// Use the 4th and 5th elements to support old format
					$source_url = trim( $parts[3] ) . '/' . ( isset( $parts[4] ) ? trim( $parts[4] ) : null );
				} else {
					// Use the 2nd element as in current format
					$source_url = isset( $parts[1] ) ? trim( $parts[1] ) : null;
				}

				if ( empty( $source_url ) ) {
					// New category found
					$categoryTitle = $name;
				} else {
					// Destination within a category
					$this->destinations[ $categoryTitle ][] = array(
						'name' => $name,
						'url'  => $source_url,
					);
				}
			}
		}
	}

	public function categories_list() {
		$content = '<div class=header>'
		. '<h1>' . _( 'Destinations Guide' ) . '</h1>'
		. '<span class="disclaimer">' . _( 'This is a work in progress, please be indulgent.' ) . '</span>'
		. '</div>'
		. '<div class="list">';
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
		$content = '<div class=header><h2>' . $categoryTitle . '</h2>';
		if ( count( $this->destinations ) > 1 ) {
			$content .= '<a href="' . $this->build_url() . '" class="back">' . _( 'Back to categories' ) . '</a>';
		}
		$content .= '<span class="disclaimer">' . _( 'This is a work in progress, please be indulgent.' ) . '</span>';
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

	private function no_result() {
		$content = '<div class="error">'
		. _( 'The realm of destinations you seek has eluded our grasp, spirited away by elusive knomes. Rally the grid managers, let them venture forth to curate a grand tapestry of remarkable places for your exploration!' )
		. '</div>';
		return $content;
	}

	// Rest of the class remains unchanged...

	private function place_thumbnail() {
		// Replace this with the actual URL for the thumbnail placeholder
		return $this->internal_url . '/no-img.jpg';
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

		$content .= '<link rel="stylesheet" type="text/css" href="' . $this->internal_url . '/css/guide.css?' . time() . '">'
		. '<div id="guide">';
		return $content;
	}

	private function html_suffix() {
		$content  = '</div>';
		$content .= '<script src="' . $this->internal_url . '/js/guide.js?' . time() . '"></script>';
		if ( $this->fullHTML ) {
			$content .= '</body></html>';
		}
		return $content;
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

	private function get_child_script_url() {
		// Get the full path of the current file (the helper script)
		$helper_script_path = __FILE__;

		// Get the directory path of the current file
		$directory_path = dirname( $helper_script_path );

		// Get the server's document root path
		$document_root = $_SERVER['DOCUMENT_ROOT'];

		// Convert the directory path to a URL by replacing the document root with an empty string
		$child_script_url = str_replace( $document_root, '', $directory_path );

		// Ensure the URL starts with a slash to make it an absolute URL
		$child_script_url = '/' . ltrim( $child_script_url, '/' );

		// Get the current protocol (http or https)
		$protocol = isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';

		// Get the host
		$host = $_SERVER['HTTP_HOST'];

		// Combine the protocol, host, and child script URL to get the full URL of the child script
		$full_child_script_url = $protocol . $host . $child_script_url;

		return $full_child_script_url;
	}

	/**
	 * Validate URL for security (filter IPs, invalid patterns)
	 * 
	 * @param string $source	Source URL to validate
	 * @return bool True if URL is safe, false otherwise
	 */
	private function filter_url( $source ) {
		if ( ! empty( $source ) ) {
			$source_url = $source;
		} elseif ( defined( 'OPENSIM_GUIDE_SOURCE' ) && ! empty( OPENSIM_GUIDE_SOURCE ) ) {
			$source_url = OPENSIM_GUIDE_SOURCE;
		} else {
			$source_url = isset( $_GET['source'] ) ? $_GET['source'] : null;
		}
		if ( empty( $source_url ) ) {
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

		$url_lower = strtolower( $source_url );
		foreach ( $dangerous_patterns as $pattern ) {
			if ( strpos( $url_lower, $pattern ) !== false ) {
				error_log( __METHOD__ . ": URL rejected - contains dangerous pattern '$pattern': $source_url" );
				die_knomes("Source URL rejected", 400 );
			}
		}

		// For URLs, validate scheme and check IP resolution
		if ( filter_var( $source_url, FILTER_VALIDATE_URL ) ) {
			$parsed = parse_url( $source_url );
			
			if ( ! in_array( $parsed['scheme'], array( 'http', 'https' ) ) ) {
				error_log( __METHOD__ . ": URL rejected - invalid scheme '{$parsed['scheme']}': $source_url" );
				die_knomes("Invalid URL scheme", 400 );
			}
			
			if ( isset( $parsed['host'] ) ) {
				$ip = gethostbyname( $parsed['host'] );
				if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
					error_log( __METHOD__ . ": URL rejected - resolves to private or reserved IP '$ip': $source_url" );
					die_knomes("Source URL resolves to private IP", 403 );
				}
			}
		} else {
			// For local files, check path safety
			$real_path = realpath( $source_url );
			if ( $real_path === false || ! file_exists( $real_path ) ) {
				error_log( __METHOD__ . ": Local file does not exist: $source_url" );
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
				error_log( __METHOD__ . ": Local file path not allowed: $source_url" );
				die_knomes("Access denied", 403 );
			}
		}

		return $source_url;
	}

	/**
	 * Validate content and return filtered source content
	 * 
	 * @param string $source_url URL or file path to fetch and validate
	 * @return string|false Validated content or false on failure
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
					if ( $this->strict_mode ) {
						error_log( __METHOD__ . "Strict mode enabled, rejecting content due to invalid line format at line $line_number" );
						die_knomes("Invalid content format at line $line_number", 415 );
					} else {
						error_log( __METHOD__ . "Line $line_number invalid format, skipping" );
						continue;
					}
				}
			}
			$filtered[] = $line;
		}
		$content = implode( "\n", $filtered );
		return $content;
	}
}

$destinations_guide = new OpenSim_Guide();
