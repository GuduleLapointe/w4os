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
    private $form_config;
    private $wizard_data = array();
    private $return_url = null;
    private $return_name = null;

    /**
     * Initialize wizard
     */
    public function __construct() {
        $this->load_session_data();
        $this->setup_form();
    }

    /**
     * Get return URL
     */
    public function get_return_url() {
        return $this->return_url ?? $_SESSION[$this->session_key]['return_url'] ?? null;
    }
    
    /**
     * Get wizard content for rendering
     */
    public function get_content() {
        return $this->form->render_form();
    }
    
    /**
     * Setup form with proper field configuration
     */
    private function setup_form() {
        if(!empty($_SESSION['wizard_form_config'])) {
            // $this->form = unserialize($_SESSION['wizard_form']);
            $form_config = unserialize($_SESSION['wizard_form_config']);
            // Use session form to preserve config between pages
        }

        if(empty($form_config) || ! is_array($form_config)) {
            $grid_name = OpenSim::grid_name();
            $login_uri = OpenSim::login_uri();
            $configured = Engine_Settings::configured() || ! empty($login_uri);
    
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
                        'callback' => 'process_grid_connection',
                        'fields' => array(
                            'connection_method' => array(
                                'type' => 'select-nested',
                                'label' => _('Connection method'),
                                'required' => true,
                                'mutual-exclusive' => true,
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
                        )
                    ),
                    // 'fields_tests' => array(
                    //     'title' => _('Field type tests'),
                    //     'description' => _('Various field types,<ul><li> for testing purposes only,</li><li> this form has no effect.</li></ul>'),
                    //     'fields' => array(
                    //         // 'dummy_group' => array(
                    //         //     'label' => _('Field group'),
                    //         //     'description' => _('Saved as an array of child fields.'),
                    //         //     // 'type' => 'field-group',
                    //         //     'fields' => array(
                    //         //         'foo' => array(
                    //         //             'label' => _('Foo'),
                    //         //             'required' => true,
                    //         //         ),
                    //         //         'bar' => array(
                    //         //             'label' => _('Bar'),
                    //         //         ),
                    //         //         'child3' => array(
                    //         //             'label' => _('Child 3'),
                    //         //             'default' => 'John Doe',
                    //         //         ),
                    //         //     )
                    //         // ),
                    //         'dummy_switch' => array(
                    //             'type' => 'switch',
                    //             'label' => _('Switch field'),
                    //             'default' => true,
                    //             // 'columns' => 2,
                    //         ),
                    //         'advanced_phone' => array(
                    //             'label' => _('Advanced phone'),
                    //             'multiple' => true,
                    //             'fields' => array(
                    //                 'phone_type' => array(
                    //                     'label' => _('Type'),
                    //                     'type' => 'select',
                    //                     'placeholder' => _('Select phone type'),
                    //                     'columns' => 4, // 1 to 12, number of columns to use
                    //                     'options' => array(
                    //                         'mobile' => _('Mobile'),
                    //                         'landline' => _('Landline'),
                    //                         'office' => _('Office'),
                    //                     )
                    //                 ),
                    //                 'phone_number' => array(
                    //                     'type' => 'tel',
                    //                     'label' => _('Number'),
                    //                     'columns' => 8, // 1 to 12, number of columns to use
                    //                 ),
                    //             ),
                    //         ),
                    //         'dummy_text' => array(
                    //             'label' => _('Dummy text field'),
                    //             'type' => 'string',
                    //             'description' => _('Various <strong>field types</strong>,<ul><li> for testing purposes only,</li><li> this form has no effect.</li></ul>'),
                    //             'multiple' => true,
                    //         ),
                    //         'dummy_textarea' => array(
                    //             'label' => _('Dummy text area'),
                    //             'type' => 'textarea',
                    //             'description' => _('Test area field'),
                    //             'multiple' => true,
                    //         ),
                    //         'dummy_email' => array(
                    //             'label' => _('Dummy email field'),
                    //             'type' => 'email',
                    //             'description' => _('HTML5 email input.'),
                    //             'readonly' => true,
                    //             'multiple' => true,
                    //         ),
                    //         'dummy_url' => array(
                    //             'label' => _('Dummy URL field'),
                    //             'type' => 'url',
                    //             'description' => _('HTML5 URL input.'),
                    //             'disabled' => true,
                    //         ),
                    //         'dummy_tel' => array(
                    //             'label' => _('Dummy telephone field'),
                    //             'type' => 'tel',
                    //             'description' => _('HTML5 telephone input.'),
                    //             'multiple' => true,
                    //         ),
                    //         'dummy_password' => array(
                    //             'label' => _('Dummy password field'),
                    //             'type' => 'password',
                    //             'description' => _('HTML5 password input.'),
                    //         ),
                    //         'dummy_date' => array(
                    //             'label' => _('Dummy date field'),
                    //             'type' => 'date',
                    //             'description' => _('HTML5 date input.'),
                    //             'multiple' => true,
                    //         ),
                    //         'dummy_time' => array(
                    //             'label' => _('Dummy time field'),
                    //             'type' => 'time',
                    //             'description' => _('HTML5 time input.'),
                    //             'multiple' => true,
                    //         ),
                    //         'dummy_datetime' => array(
                    //             'label' => _('Dummy datetime-local field'),
                    //             'type' => 'datetime-local',
                    //             'description' => _('HTML5 datetime-local input.'),
                    //             'multiple' => true,
                    //         ),
                    //         'dummy_month' => array(
                    //             'label' => _('Dummy month field'),
                    //             'type' => 'month',
                    //             'description' => _('HTML5 month input.'),
                    //             'multiple' => true,
                    //         ),
                    //         'dummy_week' => array(
                    //             'label' => _('Dummy week field'),
                    //             'type' => 'week',
                    //             'description' => _('HTML5 week input.'),
                    //             'multiple' => true,
                    //         ),
                    //         'dummy_color' => array(
                    //             'label' => _('Dummy color field'),
                    //             'type' => 'color',
                    //             'description' => _('HTML5 color input.'),
                    //         ),
                    //         'dummy_color_multiple' => array(
                    //             'label' => _('Dummy multiple color field'),
                    //             'type' => 'color',
                    //             'description' => _('HTML5 color input.'),
                    //             'multiple' => true,
                    //         ),
                    //         'dummy_file' => array(
                    //             'label' => _('Dummy file field'),
                    //             'type' => 'file',
                    //             'description' => _('HTML5 file input.'),
                    //             'preview' => true, // Show thumbnails when fields are added
                    //         ),
                    //         'dummy_multiple_files' => array(
                    //             'label' => _('Dummy file field'),
                    //             'type' => 'file',
                    //             'multiple' => true,
                    //             'description' => _('HTML5 multiple files input.'),
                    //             'preview' => true, // Show thumbnails when fields are added, last thumb is an add button, files can be cleared individually
                    //         ),
                    //         'dummy_int' => array(
                    //             'label' => _('Dummy integer field'),
                    //             'type' => 'number',
                    //             'options' => array(
                    //                 'steps' => 1,
                    //                 'min' => 8000,
                    //                 'max' => 8999,
                    //             ),
                    //             'multiple' => true,
                    //             'description' => _('For testing purposes only, this field has no effect.'),
                    //             'icon' => '🚫',
                    //         ),
                    //         'dummy_float' => array(
                    //             'label' => _('Dummy float number field'),
                    //             'type' => 'number',
                    //             'options' => array(
                    //                 'min' => 0.5,
                    //                 'max' => 5.00,
                    //             ),
                    //             'description' => _('For testing purposes only, this field has no effect.'),
                    //             'multiple' => true,
                    //             'icon' => '🚫',
                    //         ),
                    //         'dummy_checkbox' => array(
                    //             'type' => 'checkbox',
                    //             'label' => _('Dummy checkbox'),
                    //             'description' => _('For testing purposes only, this field has no effect.'),
                    //             'options' => array(
                    //                 'one' => _('One'),
                    //                 'two' => _('Two'),
                    //                 'three' => _('Three'),
                    //             ),
                    //         ),
                    //         'dummy_radio' => array(
                    //             'type' => 'radio',
                    //             'label' => _('Dummy radio'),
                    //             'description' => _('For testing purposes only, this field has no effect.'),
                    //             'options' => array(
                    //                 'one' => _('One'),
                    //                 'two' => _('Two'),
                    //                 'three' => _('Three'),
                    //             ),
                    //         ),
                    //         'dummy_select' => array(
                    //             'type' => 'select',
                    //             'label' => _('Dummy select'),   
                    //             'description' => _('For testing purposes only, this field has no effect.'),
                    //             'placeholder' => _('Select the dummiest option'),
                    //             'options' => array(
                    //                 'one' => _('One'),
                    //                 'two' => _('Two'),
                    //                 'three' => _('Three'),
                    //             ),
                    //         ),
                    //         'dummy_select_multiple' => array(
                    //             'type' => 'select',
                    //             'label' => _('Dummy select Multiple'),   
                    //             'description' => _('For testing purposes only, this field has no effect.'),
                    //             'multiple' => true,
                    //             'options' => array(
                    //                 'one' => _('One'),
                    //                 'two' => _('Two'),
                    //                 'three' => _('Three'),
                    //             ),
                    //         ),
                    //         'dummy_select2' => array(
                    //             'type' => 'select2',
                    //             'label' => _('Dummy select2 field'),
                    //             'description' => _('Searchable dropdown with many options'),
                    //             'options' => array(
                    //                 'option1' => _('First Option'),
                    //                 'option2' => _('Second Option'),
                    //                 'option3' => _('Third Option'),
                    //                 'option4' => _('Fourth Option'),
                    //                 'option5' => _('Fifth Option'),
                    //                 'long_option_name_1' => _('Very Long Option Name That Demonstrates Search Functionality'),
                    //                 'long_option_name_2' => _('Another Long Option Name For Testing Purposes'),
                    //             ),
                    //         ),
                    //         'dummy_select2_multiple' => array(
                    //             'type' => 'select2',
                    //             'label' => _('Multiple select2'),
                    //             'description' => _('Multiple selection with search'),
                    //             'multiple' => true,
                    //             'options' => array(
                    //                 'tag1' => _('Tag One'),
                    //                 'tag2' => _('Tag Two'),
                    //                 'tag3' => _('Tag Three'),
                    //                 'category1' => _('Category One'),
                    //                 'category2' => _('Category Two'),
                    //             ),
                    //         ),
                    //         'advanced_group' => array(
                    //             'type' => 'field-group',
                    //             'label' => _('Advanced Configuration Group'),
                    //             'description' => _('A group of related configuration options'),
                    //             'fields' => array(
                    //                 'server_host' => array(
                    //                     'type' => 'text',
                    //                     'label' => _('Server Host'),
                    //                     'required' => true,
                    //                     'columns' => 7,
                    //                     'placeholder' => 'localhost',
                    //                 ),
                    //                 'server_port' => array(
                    //                     'type' => 'number',
                    //                     'label' => _('Server Port'),
                    //                     'required' => true,
                    //                     'columns' => 3,
                    //                     'options' => array(
                    //                         'min' => 1024,
                    //                         'max' => 65535,
                    //                     ),
                    //                     'default' => 8002,
                    //                 ),
                    //                 'enable_ssl' => array(
                    //                     'type' => 'switch',
                    //                     'label' => _('SSL'),
                    //                     'columns' => 2,
                    //                 ),
                    //             ),
                    //         ),
                    //         'readonly_field' => array(
                    //             'type' => 'text',
                    //             'label' => _('Read-only field'),
                    //             'description' => _('This field cannot be edited'),
                    //             'readonly' => true,
                    //             'default' => 'This value cannot be changed',
                    //         ),
                    //         'disabled_field' => array(
                    //             'type' => 'text',
                    //             'label' => _('Disabled field'),
                    //             'description' => _('This field is disabled'),
                    //             'disabled' => true,
                    //             'default' => 'This field is disabled',
                    //         ),
                    //         'pattern_field' => array(
                    //             'type' => 'text',
                    //             'label' => _('Pattern field (letters only)'),
                    //             'description' => _('Regular Expression pattern, test with [A-Za-z]+'),
                    //             'pattern' => '[A-Za-z]+',
                    //             'placeholder' => 'OnlyLetters',
                    //         ),
                    //     )
                    // ),
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
                    'helpers' => array(
                        'title' => _('Helpers'),
                        'description' => _('Helpers are providing or complementing OpenSim services, usually queried directly by the viewer.'),
                        'fields' => array(
                        )
                    ),
                    'economy' => array(
                        'title' => _('Economy'),
                        'description' => _('Manage the money in your grid.'),
                        // 'condition' => array(
                        //     'install_mode' => array('console', 'database', 'ini_import', 'modify_existing')
                        // ),
                        'fields' => array(
                        )
                    ),
                    'validation' => array(
                        'title' => _('Save Settings'),
                        'description' => _('Validate configuration and test connections'),
                        'fields' => array()
                    ),
                    // 'summary' => array(
                    //     'title' => _('Installation Summary'),
                    //     'description' => _('Review configuration before finalizing'),
                    //     'fields' => array()
                    // )
                ),
            );
            
            // Filter out current_config option if no config is detected
            if (!$configured) {
                unset($form_config['steps']['initial_config']['fields']['config_method']['options']['current_config']);
            }
    
            $form_config['return_url'] = $this->return_url ?? null;
            $form_config['return_pagename'] = $this->return_pagename ?? null;

            $_SESSION['wizard_form_config'] = serialize($this->form_config);
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
                $this->return_url = $wizard_data['return_url'];
            }
            
            if (isset($wizard_data['return_pagename'])) {
                $this->return_pagename = $wizard_data['return_pagename'];
            }
        }
    }

    /**
     * Validate Initial Configuration - step 1
     */
    public function process_initial_config($submitted_data) {
        $errors = array();
        $field_errors = array();
        
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
                    // Theorically, if we reach this point, minimal validation has been done, we can proceed.
                    break;
                case 'import_legacy':
                    // TODO: run contants import, minimal config validation and load as work config
                    $errors[] = 'DEBUG config_method ' . $config_method . ' validation not implemented yet';
                    break;
                case 'ini_import':
                    $errors[] = '[DEBUG] config_method ' . $config_method . ' validation not implemented yet';
                    if(empty($submitted_data['robust_ini']['path']) && empty($submitted_data['robust_ini']['upload'])) {
                        $errors[] = _('Please fill the Robust(.HG).ini file path or upload a file');
                        $field_errors['robust_ini_path'] = _('Please provide at least one .ini file path');
                    } else {
                        // TODO: load ini file, check it's thhe right kine of config (presence of certain sections
                        // depending on the config type, check provided by another method), load as work config
                        $errors[] = '[DEBUG] config_method ' . $config_method . ' validation not implemented yet';
                    }
                    break;
                case 'start_fresh':
                    // TODO: make sure to unload any work config so next page doesn't contain random/unrelated alues
                    $errors[] = '[DEBUG] config_method ' . $config_method . ' validation not implemented yet';
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
        return array('success' => true);
    }

    /**
     * Validate Grid Connection config - step 2
     */
    public function process_grid_connection($submitted_data) {
        $step_slug = 'grid_connection';
        $errors = array();
        $field_errors = array();
        
        if (empty($submitted_data)) {
            error_log(__METHOD__ . ' [ERROR] No data received in ' . __FILE__ . ':' . __LINE__);
            $errors[] = _('System error: no data received');
        } else if(empty($submitted_data['step_slug']) || $submitted_data['step_slug'] !== $step_slug) {
            error_log(__METHOD__ . ' [ERROR] Invalid step slug: ' . ($submitted_data['step_slug'] ?? 'empty') . ' in ' . __FILE__ . ':' . __LINE__);
            $errors[] = _('System error: invalid step slug');
        } else {
            $errors[] = __METHOD__ . ' not yet implemented';
        }
        if (!empty($errors)) {
            return array(
                'success' => false, 
                'errors' => $errors,
                'field_errors' => $field_errors
            );
        }
        
        // Save step data to wizard data
        $this->wizard_data[$step_slug] = $submitted_data;
        $this->save_session_data();
        return array('success' => true);
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
     * Handle wizard completion
     */
    private function handle_completion() {
        // TODO: handle completion
        error_log('[ERROR] ' . __METHOD__ . ' completion not implemented');
        // ...existing completion logic...
        
        // Clean up wizard session data
        unset($_SESSION[$this->form_id]);
        unset($_SESSION['wizard_data']);
        
        $return_url = $this->get_return_url();
        if ($return_url) {
            error_log('[DEBUG] ' . __METHOD__ . ' Are we reaching this point?');
            // Redirect back to WordPress admin
            header('Location: ' . $return_url . '&wizard_completed=1');
            exit;
        } else {
            // Show completion page
            // ...existing completion display...
        }
    }
}
