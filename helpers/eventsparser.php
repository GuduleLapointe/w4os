<?php
/**
 * Events Parser - Updated for v3 with structured response handling
 */

require_once __DIR__ . '/bootstrap.php';

// Parse HypEvents and handle the structured response
$response = OpenSim_HypEvents::parse();

if ($response['success']) {
	// Nothing ro report, everything went smoothly

	// DEBUG - log summary for cron monitoring
    $data = $response['data'];
    error_log('[SUCCESS] HypEvents parsing completed: ' . 
              $data['processed_count'] . ' events processed, ' .
              $data['inserted_count'] . ' inserted in ' . 
              $data['total_time'] . ' seconds');
	// End debug

	die();

} else {
    $error_code = $response['error_code'] ?? 500;

	// For 4xx errors, use die_knomes to handle gracefully the output and exit
	if(strpos($error_code, '4') === 0) {
		die_knomes($response['message'], $error_code);
	}

	// osDie() handles gracefully http error headers and error_log for silent outputs
	osDie($response);
}
