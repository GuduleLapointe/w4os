<?php

class OpenSim_Rest {
  private $url;
  private $ch;
  private $sessionID;

  public function __construct($args = [])
  {

    $c = array_merge(array(
      'uri' => null,
      'ConsoleUser' => null,
      'ConsolePass' => null,
    ), $args);
    $c = array_merge(array(
      'scheme' => 'http',
      'host' => 'localhost',
      'port' => ($c['uri'] == (int)$c['uri'] ) ? $c['uri'] : null,
    ), $c, parse_url($c['uri']) );

    $this->url = "{$c['scheme']}://{$c['host']}:{$c['port']}";

    if ( empty($c['host']) || empty($c['port']) ) {
      $this->error = new Error("Invalid URL $this->url from {$c['uri']}");
      return;
    }

    $this->ch = curl_init();

    $session = $this->startSession($c['ConsoleUser'], $c['ConsolePass']);
    if( is_rest_error($session) ) {
      $this->error = $session;
      return;
    }
  }

  public function __destruct()
  {
    if(!empty($this->ch)) {
      // Close the session
      $this->closeSession();

      curl_close($this->ch);
    }
  }

  private function startSession($ConsoleUser, $ConsolePass)
  {
    $startSessionUrl = $this->url . "/StartSession/";
    $startSessionParams = [
      "USER" => $ConsoleUser,
      "PASS" => $ConsolePass,
    ];

    curl_setopt($this->ch, CURLOPT_URL, $startSessionUrl);
    curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($this->ch, CURLOPT_POST, true);
    curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($startSessionParams));

    // Execute the request to start a session
    $startSessionResponse = curl_exec($this->ch);
    // Check for errors
    if ( ! $startSessionResponse ) {
      return new Error(trim( 'Unable to start session ' . curl_error($this->ch) ) );
    }

    // Parse the session ID from the start session response
    $startSessionXml = simplexml_load_string($startSessionResponse);
    if ($startSessionXml !== false && isset($startSessionXml->SessionID)) {
      $this->sessionID = (string) $startSessionXml->SessionID;
    }

    if (empty($this->sessionID)) {
      return new Error(trim('Unable to get a session ID ' . curl_error($this->ch) ) );
    }

    return $this->sessionID;
  }

  public function sendCommand($command)
  {
    $sessionCommandUrl = $this->url . "/SessionCommand/";
    $sessionCommandParams = [
    "ID" => $this->sessionID,
    "COMMAND" => $command,
    ];

    curl_setopt($this->ch, CURLOPT_URL, $sessionCommandUrl);
    curl_setopt($this->ch, CURLOPT_TIMEOUT, 3);
    // curl_setopt($this->ch, CURLOPT_TCP_KEEPALIVE, 30); // test
    // curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true); // test
    // curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false); // test
    // curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, false); // test
    // curl_setopt($this->ch, CURLOPT_ENCODING, true); // test
    curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($sessionCommandParams));

    // Execute the request to send a command
    $sessionCommandResponse = curl_exec($this->ch);

    // Check for errors
    if ($sessionCommandResponse == false) {
      return new Error(trim( 'Rest command error ', curl_error($this->ch) ) );
    }

    // Add a small delay to make sure the response is ready beore fetching it.
    usleep(10000); // in microseconds

    // Retrieve the command output
    $readResponsesUrl = $this->url . "/ReadResponses/{$this->sessionID}/";

    // Prepare cURL request for reading responses
    // curl_setopt($this->ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($this->ch, CURLOPT_URL, $readResponsesUrl);

    // Execute the request to read responses
    $readResponsesResponse = curl_exec($this->ch);

    // Check for errors
    if ($readResponsesResponse === false) {
      return new Error(trim('Rest response error ' . curl_error($this->ch) ) );
    }

    // // Close the session
    // $this->closeSession();
    //
    // Extract the lines of the response
    $commandResponseXml = simplexml_load_string($readResponsesResponse);
    $lines = [];
    $answers = array();
    $storeNumber = false;
    if ($commandResponseXml !== false && isset($commandResponseXml->Line)) {
      foreach ($commandResponseXml->Line as $line) {
        $lineNumberAttr = (int) $line->attributes()->Number;
        $isCommand = ($line->attributes()->Command == "true");
        $isInput = ($line->attributes()->Input == "true");
        $lineValue = (string) $line;

        if ($isInput) {
          $storeNumber = ($lineValue === $command) ? $lineNumberAttr : false;
          continue;
        }

        if($storeNumber && !$isCommand && !$isInput) {
        // if($isCommand) {
          // $lines[] = (string) $line;
          $answers[$storeNumber][] = (string) $line;
          // }
        }
      }
    }
    // echo "Full response " . print_r($answers, true);
    $answers = array_filter($answers);
    $lines = end($answers);
    // echo "Answer " . print_r($lines, true);

    return $lines;
  }

  private function closeSession()
  {
    $closeSessionUrl = $this->url . "/CloseSession/";
    $closeSessionParams = [
    "ID" => $this->sessionID,
    ];

    curl_setopt($this->ch, CURLOPT_URL, $closeSessionUrl);
    curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($closeSessionParams));

    // Execute the request to close the session
    $closeSessionResponse = curl_exec($this->ch);

    // Check for errors
    if ($closeSessionResponse === false) {
      return new Error( trim( 'Rest close session_error ' . curl_error($this->ch) ) );
    }

    return $closeSessionResponse;
  }
}

function opensim_rest_session($args) {
  $rest = new OpenSim_Rest($args);

  if (isset($rest->error) && is_rest_error($rest->error)) {
    return $rest->error;
  }

  return $rest;
}
