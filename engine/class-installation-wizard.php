<?php
/**
 * Setup Wizard Engine
 * 
 * Framework-independent Setup Wizard logic.
 * Uses the existing Form class architecture.
 */

class Installation_Wizard {
    private $session_key = 'opensim_install_wizard';
    private $form;
    private $wizard_data = array();
    private $return_url = null;

    /**
     * Initialize wizard
     */
    public function __construct() {
        $this->load_session_data();
        $this->setup_form();
    }
    
    /**
     * Set return URL for after wizard completion
     */
    public function set_return_url($url) {
        $this->return_url = $url;
        $_SESSION[$this->session_key]['return_url'] = $url;
        
        // Also set it in the form session for the form to use
        if ($this->form) {
            $_SESSION[$this->form->get_form_id()]['return_url'] = $url;
        }
    }
    
    /**
     * Set return page name for display in back link
     */
    public function set_return_pagename($pagename) {
        $_SESSION[$this->session_key]['return_pagename'] = $pagename;
        
        // Also set it in the form session for the form to use
        if ($this->form) {
            $_SESSION[$this->form->get_form_id()]['return_pagename'] = $pagename;
        }
    }
    
    /**
     * Get return URL
     */
    public function get_return_url() {
        return $this->return_url ?? $_SESSION[$this->session_key]['return_url'] ?? null;
    }

    /**
     * Get reset/cancel button text and URL
     */
    public function get_reset_button_config() {
        $return_url = $this->get_return_url();
        if ($return_url) {
            return array(
                'text' => 'Cancel',
                'url' => $return_url,
                'action' => 'cancel'
            );
        } else {
            return array(
                'text' => 'Reset',
                'url' => '#',
                'action' => 'reset'
            );
        }
    }
    
    /**
     * Get wizard content for rendering
     */
    public function get_content() {
        // Handle reset request
        if (isset($_POST['reset_wizard'])) {
            $this->reset();
            // Redirect to avoid resubmission
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
        
        // Process form if submitted
        if (!empty($_POST['form_id'])) {
            $result = $this->form->process();
            // Form processing handles step advancement internally
        }
        
        // Enqueue required assets
        Helpers::enqueue_style('helpers-form', 'css/form.css');
        Helpers::enqueue_script('helpers-form', 'js/form.js');
        
        return $this->form->render_form();
    }
    
    /**
     * Setup form with proper field configuration
     */
    private function setup_form() {
        // error_log('[DEBUG] Session ' . print_r($_SESSION, true));
        $grid_name = OpenSim::grid_name();
        $login_uri = OpenSim::login_uri();
        $configured = Engine_Settings::configured() || ! empty($login_uri);
        error_log("[DEBUG] grid $grid_name $login_uri");

        // Get existing configuration for defaults
        // $grid_name = Engine_Settings::get('robust.GridInfoService.gridname');
        // $login_uri = Engine_Settings::get('robust.GridInfoService.login');
        $robust_db = Engine_Settings::get_db_credentials('robust');
        $robust_console = Engine_Settings::get_console_credentials('robust');
        $asset_db = Engine_Settings::get_db_credentials('asset');
        $profiles_db = Engine_Settings::get_db_credentials('profiles');

        // Set default console host from login URI if available
        if ($login_uri && empty($robust_console['host'])) {
            $parsed_uri = parse_url($login_uri);
            if (!empty($parsed_uri['host'])) {
                $robust_console['host'] = $parsed_uri['host'];
            }
        }

        // Create form configuration
        $form_config = array(
            'form_id' => 'opensim_install_wizard',
            'title' => _('OpenSimulator Installation Wizard'),
            'multistep' => true,
            'callback' => array($this, 'process_form'),
            'steps' => array(
                'initial_config' => array(
                    'title' => _('Initial Configuration'),
                    'description' => join('<br>', array(
                        _('Which base do you want to use for your OpenSimulator installation?'),
                        _('You can adjust these settings in the next steps.'),
                    ) ),
                    'callback' => 'process_initial_config',
                    'fields' => array(
                        'config_method' => array(
                            'type' => 'select-nested',
                            'default' => $configured ? 'current_config' : 'ini_import',
                            'required' => true,
                            'mutual-exclusive' => true,
                            'options' => array(
                                'current_config' => array(
                                    // Engine_Settings class should take cares of loading legacy constants if 
                                    // not yet converted to v3 settings, it makes no difference for the wizard                                     
                                    'label' => sprintf(_('Use current app configuration: %s %s'), "<em>$grid_name</em>", "<code>$login_uri</code>"),
                                    'description' => _('The app is configured, you can use the wizard to review and adjust settings.'),
                                    'icon' => 'bi-sliders',
                                    'fields' => array(),
                                    'enable' => Engine_Settings::configured(),
                                ),
                                'ini_import' => array(
                                    'label' => _('Use current grid configuration (import Robust .ini file)'),
                                    'description' => _('The most efficient way to configure the app is to enable the console (in the next steps), but if you don\'t have it enabled, you can import your existing Robust(.HG).ini file.'),
                                    'icon' => 'bi-file-earmark-text',
                                    'enable' => empty($configured),
                                    'fields' => array(
                                        'robust_ini' => array(
                                            'type' => 'file-ini',
                                            // 'label' => _('Robust(.HG).ini file'),
                                            'required' => true,
                                            'description' => '<ul><li>' . join('</li><li>', array(
                                                sprintf(
                                                    _('%s for public grids, Hypergrid-enabled'),
                                                    '<code>Robust.HG.ini</code>',
                                                ),
                                                sprintf(
                                                    _('%s for private grids, without Hypergrid support'),
                                                    '<code>Robust.ini</code>',
                                                ),
                                            ) ) . '</li></ul>',
                                        )
                                    )
                                ),
                                'start_fresh' => array(
                                    'label' => _('New configuration'),
                                    'description' => _('For a fresh new installation. The app generate OpenSim necessary .ini files at the end of the process.'),
                                    'icon' => 'bi-stars',
                                    'fields' => array()
                                )
                            ),
                        ),
                    ),
                ),
                'grid_connection' => array(
                    'title' => _('Grid Connection'),
                    'description' => _('Select the method for helpers to exchange data with your OpenSimulator grid.'),
                    'fields' => array(
                        'connection_method' => array(
                            'type' => 'select-nested',
                            'label' => _('Connection method'),
                            'options' => array(
                                'use_console' => array(
                                    'label' => _('Console connection (recommended)'),
                                    'description' => _('Using console is necessary to get all helpers features.'),
                                    'icon' => '🖥️',
                                    'fields' => array(
                                        'robust.Network.Console' => array(
                                            'type' => 'console_credentials',
                                            'label' => _('Console credentials'),
                                            'description' => _('Must be set in Robust(.HG).ini file > [Network] > Console(User|Pass|Port)'),
                                            'troubleshooting' => '<ul><li>' . join('</li><li>', array(
                                                _('Make sure ConsoleUser, ConsolePass and ConsolePort are set in Robust(.HG).ini'),
                                                _('Make sure ConsolePort is not used by another service.'),
                                                _('Make sure your grid is up and running (restart the grid after any config change.')
                                            ) ) . '</li></ul>',
                                            'default' => $robust_console,
                                        ),
                                    ),
                                ),
                                'use_db' => array(
                                    'label' => _('Database connection'),
                                    'description' => _('Connect to the grid using database credentials. This allows main features, but some may be limited.'),
                                    'icon' => '🗄️',
                                    'fields' => array(
                                        'robust.DatabaseService.ConnectionString' => array(
                                            'type' => 'db_credentials',
                                            'label' => _('Main database credentials'),
                                            'default' => $robust_db,
                                        ),
                                        'robust.AssetService.ConnectionString' => array(
                                            'type' => 'db_credentials',
                                            'label' => _('Asset database credentials'),
                                            'description' => _('Optional, most likely not necessary, leave empty to get a simpler life.'),
                                            'default' => $asset_db,
                                            'use_default' => empty($asset_db),
                                        ),
                                        'robust.UserProfilesService.ConnectionString' => array(
                                            'type' => 'db_credentials',
                                            'label' => _('Profiles database credentials'),
                                            'description' => _('Optional, most likely not necessary, leave empty to get a simpler life.'),
                                            'default' => $profiles_db,
                                            'use_default' => empty($profiles_db),
                                        ),
                                    ),
                                ),
                            ),
                            'default' => empty($robust_console) && !empty($robust_db) ? 'use_db' : 'use_console',
                            'required' => true
                        ),
                        'dummy' => array(
                            'label' => _('Dummy text field'),
                            'description' => _('For testing purposes only, this field has no effect.'),
                            'type' => 'dummy',
                            'label' => _('Dummy connection'),
                            'description' => _('This is a dummy connection, no real services are connected.'),
                        ),
                        'dummy_int' => array(
                            'label' => _('Dummy integer field'),
                            'description' => _('For testing purposes only, this field has no effect.'),
                            'icon' => '🚫',
                            'type' => 'dummy',
                            'description' => _('This is a dummy field for debug, it does nothing.'),
                        ),
                        'dummy_checkbox' => array(
                            'type' => 'checkbox',
                            'label' => _('Dummy checkbox'),
                            'description' => _('For testing purposes only, this field has no effect.'),
                            'options' => array(
                                'one' => _('One'),
                                'two' => _('Two'),
                                'three' => _('Three'),
                            ),
                        ),
                        'dummy_radio' => array(
                            'type' => 'radio',
                            'label' => _('Dummy radio'),
                            'description' => _('For testing purposes only, this field has no effect.'),
                            'options' => array(
                                'one' => _('One'),
                                'two' => _('Two'),
                                'three' => _('Three'),
                            ),
                        ),
                        'dummy_select' => array(
                            'type' => 'select',
                            'label' => _('Dummy select'),   
                            'description' => _('For testing purposes only, this field has no effect.'),
                            'options' => array(
                                'one' => _('One'),
                                'two' => _('Two'),
                                'three' => _('Three'),
                            ),
                        ),
                        'dummy_switch' => array(
                            'type' => 'switch',
                            'label' => _('Dummy switch'),
                            'description' => _('For testing purposes only, this field has no effect.'),
                            'options' => true, // true is default for switch
                        ),
                    )
                ),
                'grid_info' => array(
                    'title' => _('Grid Information'),
                    'description' => _('Basic grid configuration'),
                    'condition' => array(
                        'install_mode' => array('console', 'database', 'ini_import', 'modify_existing')
                    ),
                    'fields' => array(
                        'robust.GridInfoService.gridname' => array(
                            'type' => 'text',
                            'label' => _('Grid Name'),
                            'default' => Engine_Settings::get('robust.GridInfoService.gridname', ''),
                            'required' => true
                        ),
                        'robust.GridInfoService.login' => array(
                            'type' => 'text',
                            'label' => _('Login URI'),
                            'placeholder' => 'yourgrid.org:8002',
                            'default' => Engine_Settings::get('robust.GridInfoService.login', ''),
                            'required' => true
                        )
                    )
                ),
                'validation' => array(
                    'title' => _('Validation'),
                    'description' => _('Validate configuration and test connections'),
                    'fields' => array()
                ),
                'summary' => array(
                    'title' => _('Installation Summary'),
                    'description' => _('Review configuration before finalizing'),
                    'fields' => array()
                )
            ),
        );
        
        // Filter out current_config option if no config is detected
        if (!$configured) {
            unset($form_config['steps']['initial_config']['fields']['config_method']['options']['current_config']);
        }

        $this->form = new OpenSim_Form($form_config);
    }
    
    /**
     * Get configuration method for setup form
     */
    private function get_config_method() {
        // If imported options are available, use import/migration mode
        if (Engine_Settings::using_imported_options()) {
            return 'import_legacy';
        }
        
        // Otherwise check if system is already configured
        if (Engine_Settings::configured()) {
            return 'update_existing';
        }
        
        return 'new_installation';
    }

    /**
     * Load session data and extract wizard_data if available
     */
    private function load_session_data() {
        // If wizard_data exists, extract return URL and page name
        if (isset($_SESSION['wizard_data'])) {
            $wizard_data = $_SESSION['wizard_data'];
            
            if (isset($wizard_data['return_url'])) {
                $this->set_return_url($wizard_data['return_url']);
            }
            
            if (isset($wizard_data['return_pagename'])) {
                $this->set_return_pagename($wizard_data['return_pagename']);
            }
        }
    }

    /**
     * Validate initial configuration step
     */
    public function process_initial_config($submitted_data) {
        $errors = array();
        $field_errors = array();
        
        // Temporary fix for $submitted_data passed by the caller being empty
        // $submitted_data = empty($submitted_data) ? $_POST : $submitted_data;
        // error_log('[CHECKPOINT] step_data: ' . print_r($submitted_data, true) . ' POST ' . print_r($_POST, true) );

        if (empty($submitted_data)) {
            error_log(__METHOD__ . ' [ERROR] No data received in ' . __FILE__ . ':' . __LINE__);
            $errors[] = _('System error: no data received');
        } else if(empty($submitted_data['step_slug']) || $submitted_data['step_slug'] !== 'initial_config') {
            error_log(__METHOD__ . ' [ERROR] Invalid step slug: ' . ($submitted_data['step_slug'] ?? 'empty') . ' in ' . __FILE__ . ':' . __LINE__);
            $errors[] = _('System error: invalid step slug');
        } else {
            $config_method = $submitted_data['config_method'] ?? false;

            // The required fields and other common requirements sshould have been validated by the form class
            // so we don't check again here, we process and check the result.
            switch($config_method) {
                // The config validation is very minimal, as each step will have its own validation
                case 'current_config':
                    // TODO: minimal config validation and load as work config
                    // (no import needed, it's the live configuration)
                    $errors[] = 'DEBUG config_method ' . $config_method . ' validation not implemented yet';
                    break;
                case 'import_legacy':
                    // TODO: run contants import, minimal config validation and load as work config
                    $errors[] = 'DEBUG config_method ' . $config_method . ' validation not implemented yet';
                    break;
                case 'ini_import':
                    error_log(__METHOD__ . ' [DEBUG] ' . $config_method . ' - submitted_data: ' . print_r($submitted_data, true));
                    if(empty($submitted_data['robust_ini']['path']) && empty($submitted_data['robust_ini']['upload'])) {
                        $errors[] = _('Please fill the Robust(.HG).ini file path or upload a file');
                        $field_errors['robust_ini_path'] = _('Please provide at least one .ini file path');
                    } else {
                        // TODO: load ini file, check it's thhe right kine of config (presence of certain sections
                        // depending on the config type, check provided by another method), load as work config
                        $errors[] = 'DEBUG config_method ' . $config_method . ' validation not implemented yet';
                    }
                    break;
                case 'start_fresh':
                    // TODO: make sure to unload any work config so next page doesn't contain random/unrelated alues
                    $errors[] = 'DEBUG config_method ' . $config_method . ' validation not implemented yet';
                    break;
                default:
                    $errors[] = _('Invalid configuration method');
            }   
        }
        
        if (!empty($errors)) {
            return array(
                'success' => false, 
                'errors' => $errors,
                'field_errors' => $field_errors
            );
        }
        
        // Save step data to wizard data
        $this->wizard_data['initial_config'] = $submitted_data;
        $this->save_session_data();
        // return array('success' => true);
    }
    
    /**
     * Save session data
     */
    private function save_session_data() {
        $_SESSION[$this->session_key] = $this->wizard_data;
    }
    
    /**
     * Process form submission
     */
    public function process_form($form_data) {
        // Validate and process step data
        $this->wizard_data = array_merge($this->wizard_data, $form_data);
        $this->save_session_data();
        
        // Return result
        return array('success' => true);
    }
    
    /**
     * Reset wizard
     */
    public function reset() {
        unset($_SESSION[$this->session_key]);
        $this->wizard_data = array();
    }

    /**
     * Handle wizard completion
     */
    private function handle_completion() {
        // ...existing completion logic...
        
        // Clean up wizard session data
        unset($_SESSION[$this->form_id]);
        unset($_SESSION['wizard_data']);
        
        $return_url = $this->get_return_url();
        if ($return_url) {
            // Redirect back to WordPress admin
            header('Location: ' . $return_url . '&wizard_completed=1');
            exit;
        } else {
            // Show completion page
            // ...existing completion display...
        }
    }
}
