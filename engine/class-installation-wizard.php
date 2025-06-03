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
    
    /**
     * Initialize wizard
     */
    public function __construct() {
        $this->load_session_data();
        $this->setup_form();
    }
    
    /**
     * Get wizard content for rendering
     */
    public function get_content() {
        // Enqueue required assets
        Helpers::enqueue_style('helpers-wizard', 'css/wizard.css');
        Helpers::enqueue_script('helpers-wizard', 'js/wizard.js');
        Helpers::enqueue_script('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js');
        
        return $this->form->render_form();
    }
    
    /**
     * Setup form with proper field configuration
     */
    private function setup_form() {
        // Get existing configuration for defaults
        $grid_name = Engine_Settings::get('robust.GridInfoService.gridname');
        $login_uri = Engine_Settings::get('robust.GridInfoService.login');
        $robust_db = Engine_Settings::get_db_credentials('robust');
        $robust_console = Engine_Settings::get_console_credentials('robust');
        $asset_db = Engine_Settings::get_db_credentials('asset');
        $profiles_db = Engine_Settings::get_db_credentials('profiles');
        
        $legacy_detected = false;
        $config_detected = !empty($grid_name) || !empty($login_uri);
        if(!$config_detected) {
            // TODO: Check for legacy configuration:
            // If some mandatory constants are defined, try to import constants
            // then check again grid_name and login_uri
            // $legacy_detected = !empty($grid_name) || !empty($login_uri);
            // $grid_name = Engine_Settings::get('robust.GridInfoService.gridname');
            // $login_uri = Engine_Settings::get('robust.GridInfoService.login');
        }
        // Build detected label for current_config option
        $grid_label = ($config_detected || $legacy_detected) ? "{$grid_name} {$login_uri}" : '';

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
                'welcome' => array(
                    'title' => _('Base Configuration'),
                    'description' => join('<br>', array(
                        _('Which base do you want to use for your OpenSimulator installation?'),
                        _('You can adjust these settings in the next steps.'),
                    ) ),
                    'fields' => array(
                        'config_method' => array(
                            'type' => 'select-nested',
                            'description' => 'DEBUG: if there is a description here, it must appear between the label and the options.',
                            'options' => array(
                                'current_config' => array(
                                    'label' => sprintf(_('Use current configuration (%s)'), $grid_label),
                                    'description' => _('The configuration is already done, you can use the wizard to review and adjust settings.'),
                                    'icon' => 'bi-sliders',
                                    'fields' => array(),
                                    'enable' => $config_detected,
                                    
                                ),
                                'import_legacy' => array(
                                    'label' => sprintf(_('Import legacy configuration (%s)'), $grid_label),
                                    'description' => _('A legacy configuration has been found, use this option to migrate the settings.'),
                                    'icon' => 'bi-sliders',
                                    'fields' => array(),
                                    'enable' => $legacy_detected,
                                ),
                                'ini_import' => array(
                                    'label' => _('Import Robust(.HG).ini file'),
                                    'description' => _('If your grid is already up and running, but the console is not enabled, the easiest way is to import settings from OpenSim .ini files.'),
                                    'icon' => 'bi-file-earmark-text',
                                    'fields' => array(
                                        'ini_files' => array(
                                            'type' => 'ini_files',
                                            'label' => _('Robust(.HG).ini file'),
                                            'description' => join('<br>', array(
                                                sprintf(
                                                    _('%s for Hypergrid-enabled grids, %s for standalone grids'),
                                                    '<code>Robust.HG.ini</code>',
                                                    '<code>Robust.ini</code>'
                                                ),
                                            ) ),
                                        )
                                    )
                                ),
                                'start_fresh' => array(
                                    'label' => _('New configuration'),
                                    'description' => _('For a new installations. You can download the OpenSim .ini files at the end of the process.'),
                                    'icon' => 'bi-stars',
                                    'fields' => array()
                                )
                            ),
                            'default' => $config_detected ? 'current_config' : 'start_fresh',
                            'required' => true
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
        if (!$config_detected) {
            unset($form_config['steps']['welcome']['fields']['config_method']['options']['current_config']);
        }

        $this->form = new OpenSim_Form($form_config);
    }
    
    /**
     * Load session data
     */
    private function load_session_data() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $this->wizard_data = $_SESSION[$this->session_key] ?? array();
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
}
