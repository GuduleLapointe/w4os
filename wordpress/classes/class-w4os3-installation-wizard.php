<?php
/**
 * WordPress Installation Wizard Integration
 * 
 * Integrates the Engine Installation_Wizard with WordPress admin interface
 */

class W4OS3_Installation_Wizard {
    private $wizard;
    
    public function __construct() {
        // $this->wizard = new Installation_Wizard();
        
        // Add admin menu hook
        add_action('admin_menu', array($this, 'add_admin_menu'), 999);
        
        // Handle AJAX requests
        add_action('wp_ajax_w4os_installation_step', array($this, 'handle_ajax_step'));
    }
    
    /**
     * Add installation wizard to WordPress admin menu
     */
    public function add_admin_menu() {
        // Check if the user has permission to manage options
        if (!current_user_can('manage_options')) {
            return;
        }

        // Only show if not configured or if explicitly requested
        if (!Engine_Settings::configured() || isset($_GET['force_wizard'])) {
            // Scan existing childs of 'w4os' menu and use remove_submenu_page('w4os', $child_slug) to remove them
            global $submenu;
            // if (isset($submenu['w4os'])) {
            //     foreach ($submenu['w4os'] as $key => $child) {
            //         // Remove all existing submenu items under 'w4os'
            //         if (!in_array($child[2], array('w4os', 'w4os-settings-test', 'w4os-settings-validation'))) {
            //             remove_submenu_page('w4os', $child[2]);
            //         }
            //     }
            // }

            // Add the installation wizard page to the admin menu
            add_submenu_page(
                'w4os',
                'W4OS Installation Wizard',
                'Installation Wizard',
                'manage_options',
                'w4os_installation_wizard',
                array($this, 'render_wizard_page'),
                1
            );
        }
    }
    
    /**
     * Render the WordPress admin page
     */
    public function render_wizard_page() {
        // Check if returning from wizard
        if (isset($_GET['wizard_completed'])) {
            printf(
                '<div class="notice notice-success"><p>%s</p></div>',
                __('Installation wizard completed successfully!', 'w4os')
            );
            
            // Redirect to main settings page after showing success
            echo '<script>setTimeout(function(){ window.location.href = "' . admin_url('admin.php?page=w4os') . '"; }, 2000);</script>';
            return;
        }
        
        // Check if new installation or migration of a currently working installation
        if( Engine_Settings::configured() ) {
            $mode = 'update';
        } else if ( defined('W4OS_DB_CONNECTED') && W4OS_DB_CONNECTED) {
            $mode = 'migration';
        } else {
            $mode = 'new_installation';
        }
        switch($mode) {
            case 'update':
                $title = sprintf(
                    __('Update %s Current Configuration to %s', 'w4os'),
                    W4OS_PLUGIN_NAME,
                    W4OS_VERSION,
                );
                $description = sprintf(
                    __('Use the installation wizard to review and update your current configuration.', 'w4os'),
                    W4OS_PLUGIN_NAME,
                );
                break;
            case 'migration':
                $title = sprintf(
                    __('Migrate %s Configuration to %s', 'w4os'),
                    W4OS_PLUGIN_NAME,
                    W4OS_VERSION,
                );
                $description = sprintf(
                    __('Your configuration needs to be upgraded to use version %s of the plugin.', 'w4os'),
                    W4OS_VERSION,
                );
                break;
            default:
                $title = sprintf(
                    __('New %s Setup', 'w4os'),
                    W4OS_PLUGIN_NAME,
                );
                $description = sprintf(
                    __('Use the installation wizard to setup plugin for the fist time.', 'w4os'),
                );
        }
        
        $wizard_url = get_home_url(null, get_option('w4os_helpers_slug', 'helpers') . '/install-wizard.php');
        $migration = W4OS3_Migration_2to3::migrate_wordpress_options(false); // only get options, do not save them
        $data = $migration['values'] ?? null;

        if(! $data) {
            w4os_admin_notice(
                __('No options found. I am afraid I have to give up.', 'w4os'),
                'error'
            );
            $description = __('An error occurred while trying to load the current options. Please check your WordPress installation and try again.', 'w4os');
        } else {
            // Start session if not already started
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            
            // Store data in session for the wizard
            $_SESSION['wizard_data'] = array(
                'return_data' => array( // Not processed by the wizard, but will be returned with the response
                    'mode' => $mode,
                    'nonce' => wp_create_nonce('w4os_wizard_return'),
                    'user_id' => get_current_user_id(),
                ),
                'data' => $data,
                'return_url' => admin_url('admin.php?page=w4os_installation_wizard'),
                'return_pagename' => get_admin_page_title(),
                'timestamp' => time()
            );
            
            $wizard_button = sprintf(
                '<div class="w4os-wizard-start-button text-center">
                    <a href="%s" class="button button-primary button-hero">%s</a>
                </div>',
                esc_url($wizard_url),
                __('Launch Installation Wizard', 'w4os'),
            );
        }

        // Add a big beautiful button to start the wizard, should pass the whole options array and the current page url as return URL
        printf(
            '<div class="container">
                <h1>%s</h1>
                <p class="lead text-center" style="font-size:1rem;">
                    %s
                </p>
                <p>%s</p>
                %s
            </div>',
            $title,
            $description,
            $wizard_button,
            '', // '<h2>Debug</h2><pre>' . print_r($data['values'] ?? "no data", true) . '</pre>'
        );
    }
    
    /**
     * Process form submissions
     */
    private function process_form() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'w4os_installation_wizard')) {
            wp_die('Security check failed');
        }
        
        switch ($_POST['action']) {
            case 'next_step':
                $result = $this->wizard->process_step($_POST);
                if ($result['success']) {
                    $this->wizard->next_step();
                    add_settings_error('w4os_wizard', 'success', $result['message'] ?? 'Step completed successfully', 'success');
                } else {
                    add_settings_error('w4os_wizard', 'error', $result['message'] ?? 'Please correct the errors below');
                    if (!empty($result['errors'])) {
                        foreach ($result['errors'] as $error) {
                            add_settings_error('w4os_wizard', 'error', $error);
                        }
                    }
                }
                break;
                
            case 'previous_step':
                $this->wizard->previous_step();
                break;
                
            case 'reset':
                $this->wizard->reset();
                add_settings_error('w4os_wizard', 'info', 'Wizard has been reset', 'info');
                break;
        }
    }

}
