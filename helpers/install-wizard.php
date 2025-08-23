<?php
/**
 * Standalone Setup wizard for helpers installation.
 */

// Start session for wizard state
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Handle session cleanup
if (isset($_POST['action']) && $_POST['action'] === 'clean_wizard_session') {
    unset($_SESSION['wizard_data']);
    exit; // Just clean up and exit, no response needed
}

## Standalone mode workaround
// helpers/ and engine/ directories are not loaded by WordPress, so we need to
// define a dummy get_option() function to avoid fatal errors in standalone mode.
// This is a only needed by the installation wizard as config.php might contain
// calls to get_option() if initially setup by the WP plugin.
if(!function_exists('get_option')) {
    define('W4OS_PLUGIN', 'installation-wizard');
    function get_option($option_name, $default = false) {
        error_log('[NOTICE] get_option(' . $option_name . ') called in standalone mode. Returning default value.');
        return $default;
    }
}

// Bootstrap the helpers system (this defines OPENSIM_ENGINE_PATH)
require_once dirname(__FILE__) . '/bootstrap.php';

require_once __DIR__ . '/bootstrap.php';

$return_url = $return_url ?? null;
$return_pagename = $return_pagename ?? null;

// Check if external data passed via session
$external_data = $_SESSION['wizard_data'] ?? null;

if ($external_data && !empty($external_data['data'])) {
    // Validate the session data is recent (within 1 hour)
    if (time() - $external_data['timestamp'] > 3600) {
        unset($_SESSION['wizard_data']);
        $external_data = null;
    } else {
        $return_url = $external_data['return_url'] ?? $return_url;
        $return_pagename = $external_data['return_pagename'] ?? $return_pagename;
        
        // Pass imported options to Engine_Settings for this session
        if (!empty($external_data['data'])) {
            Engine_Settings::set_imported_options($external_data['data']);
        }
    }
}

// Include required files (now OPENSIM_ENGINE_PATH is defined)
require_once OPENSIM_ENGINE_PATH . '/class-installation-wizard.php';

// Initialize wizard
$wizard = new Installation_Wizard();

// Handle form submission
$message = '';
$message_type = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'submit':
                $result = $wizard->process_form($_POST);
                if ($result['success']) {
                    $message = 'Configuration saved successfully';
                    $message_type = 'success';
                } else {
                    $message = isset($result['errors']) ? implode('<br>', $result['errors']) : $result['message'];
                    $message_type = 'error';
                }
                break;
                
            case 'reset':
                $wizard->reset();
                break;
        }
    }
}

// Set page variables for template
$site_title = 'OpenSimulator Helpers';
$page_title = 'OpenSimulator Installation Wizard';

// Get wizard content
$content = '';
if ($message) {
    $alert_class = $message_type === 'error' ? 'danger' : ($message_type === 'success' ? 'success' : 'info');
    $content .= '<div class="alert alert-' . $alert_class . ' alert-dismissible fade show" role="alert">';
    $content .= $message;
    $content .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    $content .= '</div>';
}

// We don't want header and footer in the wizard
$branding = '';
$footer = '';

$content = $wizard->get_content();

// Use the existing template system
require_once dirname(__FILE__) . '/templates/templates.php';
