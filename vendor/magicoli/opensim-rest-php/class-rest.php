<?php

/**
 * OpenSimulator REST PHP library and command-line client
 *
 * This class provides functionality to communicate with a Robust or OpenSimulator instance
 * with REST console enabled.
 *
 * @package opensim-rest-php
 * @category Libraries
 * @version 1.0.5
 * @license AGPLv3
 * @link https://github.com/magicoli/opensim-rest-php
 *
 * Donate to support the project:
 * @link https://magiiic.com/donate/project/?project=opensim-rest-php
 */

class OpenSim_Rest {
	private $url;
	private $ch;
	private $sessionID;

	/**
	 * OpenSim_Rest constructor.
	 *
	 * Initializes a new instance of the OpenSim_Rest class.
	 *
	 * @param array $args An array of arguments for configuring the REST connection.
	 *   - uri: The base URI for the REST connection.
	 *   - ConsoleUser: The username for authentication.
	 *   - ConsolePass: The password for authentication.
	 */
	public function __construct( $args = array() ) {

		$c = array_merge(
			array(
				'uri'         => null,
				'ConsoleUser' => null,
				'ConsolePass' => null,
			),
			$args
		);
		$c = array_merge(
			array(
				'scheme' => 'http',
				'host'   => 'localhost',
				'port'   => ( $c['uri'] == (int) $c['uri'] ) ? $c['uri'] : null,
			),
			$c,
			parse_url( $c['uri'] )
		);

		$this->url = "{$c['scheme']}://{$c['host']}:{$c['port']}";

		if ( empty( $c['host'] ) || empty( $c['port'] ) ) {
			$this->error = new Error( "Invalid URL $this->url from {$c['uri']}" );
			return;
		}

		$this->ch = curl_init();

		$session = $this->startSession( $c['ConsoleUser'], $c['ConsolePass'] );
		if ( is_opensim_rest_error( $session ) ) {
			$this->error = $session;
			return;
		}
	}

	/**
	 * OpenSim_Rest destructor.
	 *
	 * Cleans up any resources used by the OpenSim_Rest instance.
	 */
	public function __destruct() {
		if ( ! empty( $this->ch ) ) {
			// Close the session
			$this->closeSession();

			curl_close( $this->ch );
		}
	}

	/**
	 * Starts a session with the REST console.
	 *
	 * @param string $ConsoleUser The username for authentication.
	 * @param string $ConsolePass The password for authentication.
	 * @return string|Error The session ID if successful, or an Error object if an error occurred.
	 */
	private function startSession( $ConsoleUser, $ConsolePass ) {
		$startSessionUrl    = $this->url . '/StartSession/';
		$startSessionParams = array(
			'USER' => $ConsoleUser,
			'PASS' => $ConsolePass,
		);

		curl_setopt( $this->ch, CURLOPT_URL, $startSessionUrl );
		curl_setopt( $this->ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $this->ch, CURLOPT_POST, true );
		curl_setopt( $this->ch, CURLOPT_POSTFIELDS, http_build_query( $startSessionParams ) );

		// Execute the request to start a session
		$startSessionResponse = curl_exec( $this->ch );
		// Check for errors
		if ( ! $startSessionResponse ) {
			return new Error( trim( 'Unable to start session ' . curl_error( $this->ch ) ) );
		}

		// Parse the session ID from the start session response
		$startSessionXml = simplexml_load_string( $startSessionResponse );
		if ( $startSessionXml !== false && isset( $startSessionXml->SessionID ) ) {
			$this->sessionID = (string) $startSessionXml->SessionID;
		}

		if ( empty( $this->sessionID ) ) {
			return new Error( trim( 'Unable to get a session ID ' . curl_error( $this->ch ) ) );
		}

		return $this->sessionID;
	}

	/**
	 * Sends a command to the REST console.
	 *
	 * @param string $command The command to send.
	 * @return array|Error An array containing the lines of response if successful, or an Error object if an error occurred.
	 */
	public function sendCommand( $command ) {
		$sessionCommandUrl    = $this->url . '/SessionCommand/';
		$sessionCommandParams = array(
			'ID'      => $this->sessionID,
			'COMMAND' => $command,
		);

		curl_setopt( $this->ch, CURLOPT_URL, $sessionCommandUrl );
		curl_setopt( $this->ch, CURLOPT_TIMEOUT, 3 );
		// curl_setopt($this->ch, CURLOPT_TCP_KEEPALIVE, 30); // test
		// curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true); // test
		// curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false); // test
		// curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, false); // test
		// curl_setopt($this->ch, CURLOPT_ENCODING, true); // test
		curl_setopt( $this->ch, CURLOPT_POSTFIELDS, http_build_query( $sessionCommandParams ) );

		// Execute the request to send a command
		$sessionCommandResponse = curl_exec( $this->ch );

		// Check for errors
		if ( $sessionCommandResponse == false ) {
			return new Error( trim( 'Rest command error ', curl_error( $this->ch ) ) );
		}

		// Add a small delay to make sure the response is ready beore fetching it.
		usleep( 10000 ); // in microseconds

		// Retrieve the command output
		$readResponsesUrl = $this->url . "/ReadResponses/{$this->sessionID}/";

		// Prepare cURL request for reading responses
		// curl_setopt($this->ch, CURLOPT_TIMEOUT, 30);
		curl_setopt( $this->ch, CURLOPT_URL, $readResponsesUrl );

		// Execute the request to read responses
		$readResponsesResponse = curl_exec( $this->ch );

		// Check for errors
		if ( $readResponsesResponse === false ) {
			return new Error( trim( 'Rest response error ' . curl_error( $this->ch ) ) );
		}

		// // Close the session
		// $this->closeSession();
		//
		// Extract the lines of the response
		$commandResponseXml = simplexml_load_string( $readResponsesResponse );
		$lines              = array();
		$answers            = array();
		$storeNumber        = false;
		if ( $commandResponseXml !== false && isset( $commandResponseXml->Line ) ) {
			foreach ( $commandResponseXml->Line as $line ) {
				$lineNumberAttr = (int) $line->attributes()->Number;
				$isCommand      = ( $line->attributes()->Command == 'true' );
				$isInput        = ( $line->attributes()->Input == 'true' );
				$lineValue      = (string) $line;

				if ( $isInput ) {
					$storeNumber = ( $lineValue === $command ) ? $lineNumberAttr : false;
					continue;
				}

				if ( $storeNumber && ! $isCommand && ! $isInput ) {
					// if($isCommand) {
					// $lines[] = (string) $line;
					$answers[ $storeNumber ][] = (string) $line;
					// }
				}
			}
		}
		// echo "Full response " . print_r($answers, true);
		$answers = array_filter( $answers );
		$lines   = end( $answers );
		// echo "Answer " . print_r($lines, true);

		return $lines;
	}

	/**
	 * Closes the session with the REST console.
	 *
	 * @return string|Error The response if successful, or an Error object if an error occurred.
	 */
	private function closeSession() {
		$closeSessionUrl    = $this->url . '/CloseSession/';
		$closeSessionParams = array(
			'ID' => $this->sessionID,
		);

		curl_setopt( $this->ch, CURLOPT_URL, $closeSessionUrl );
		curl_setopt( $this->ch, CURLOPT_POSTFIELDS, http_build_query( $closeSessionParams ) );

		// Execute the request to close the session
		$closeSessionResponse = curl_exec( $this->ch );

		// Check for errors
		if ( $closeSessionResponse === false ) {
			return new Error( trim( 'Rest close session_error ' . curl_error( $this->ch ) ) );
		}

		return $closeSessionResponse;
	}
}

/**
 * Creates a new OpenSim_Rest session.
 *
 * @param array $args An array of arguments for configuring the REST connection.
 * @return OpenSim_Rest|Error An instance of the OpenSim_Rest class if successful, or an Error object if an error occurred.
 */
function opensim_rest_session( $args ) {
	$rest = new OpenSim_Rest( $args );

	if ( isset( $rest->error ) && is_opensim_rest_error( $rest->error ) ) {
		return $rest->error;
	}

	return $rest;
}

/**
 * Checks if a given thing is an OpenSim_Rest error.
 *
 * @param mixed $thing The thing to check.
 * @return bool Returns true if the thing is an OpenSim_Rest error, false otherwise.
 */
function is_opensim_rest_error( $thing ) {
	if ( $thing instanceof Error ) {
		return true;
	}
}
