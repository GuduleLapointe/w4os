<?php
/**
 * OpenSimulator Helpers installationn script
 * 
 * This script will scan Robust configuration file to get your grid settings and generate the helpers configuration file.
 * 
 * It is only needed to run this tool once, after that you delete this install.php file.
 * 
 * @package		magicoli/opensim-helpers
**/

if ( __FILE__ !== $_SERVER['SCRIPT_FILENAME'] ) {
    // The file must only be called directly.
    http_response_code(403);
    exit( "I'm not that kind of girl, I don't want to be included." );
}

require_once( __DIR__ . '/classes/init.php' ); // Common to all main scripts
require_once( __DIR__ . '/classes/class-page.php' ); // Specific, because we generate a page
require_once( __DIR__ . '/classes/class-form.php' ); // Specific, because we use forms

class OpenSim_Install extends OpenSim_Page {
    private $user_notices = array();
    private $errors = array();
    private $forms = array();
    private $form;

    const FORM_ID = 'installation';

    public function __construct() {
        $this->page_title = _('Helpers Installation');

        $this->init();
        $this->register_form_installation();
        $this->process_form();
        $this->content = $this->render_content();
    }    

    public function init() {

        if( ! isset( $_SESSION[self::FORM_ID] ) ) {
            $_SESSION[self::FORM_ID] = array();
        }

        $this->handle_reset();
    }

    public function process_form() {
        $form = $this->form;
        if( ! $form ) {
            OpenSim::notify_error( 'Could not create form');
        } else {
            
            $next_step_key = $form->get_next_step();
            $next_step_label = array_key_exists( $next_step_key, $form->steps ) ? $form->steps[$next_step_key]['label'] : false;

            if ( isset( $_POST['step_key'] ) && $_POST['step_key'] == $next_step_key && ! empty( $form->tasks ) ) {
                error_log( "Starting tasks of $next_step_key label $next_step_label" );
                $result = false;
                foreach( $form->tasks as $key => $task ) {
                    $callback_name = OpenSim::callback_name_string( $task['callback'] );
                    // error_log( 'starting task ' . $task['label'] );

                    try {
                        $result = call_user_func( $task['callback'] );
                        if( ! $result ) {
                            throw new OpenSim_Error( $task['error'] ?? null );
                        }
                    } catch (Throwable $e) {
                        $result = false;
                        // error_log( $message );
                        OpenSim::notify_error( $e );
                        break;
                    }
                    // if( ! $result ) {
                    //     $message = $callback_name . '() ' . ( $task['error'] ?? 'Failed' );
                    //     error_log( $message );
                    //     OpenSim::notify_error( $message );
                    //     break;
                    // }
                    $message = ( $task['label'] ?? $callback_name . '()' ) . ': ' . ( $task['success'] ?? 'Success' );
                    error_log( '[' . __CLASS__ . '] ' . $message );
                    OpenSim::notify( $message, 'task-checked' );
                }

                $prefix = '<strong>' . $next_step_label . '</strong>: ';
                if( ! $result ) {
                    $message = $prefix . ( $form->steps[$next_step_key]['error'] ?? 'Failed' );
                    OpenSim::notify_error( $message, 'danger' );
                } else if ( $result instanceof Error ) {
                    $message = $prefix . $result->getMessage();
                    OpenSim::notify_error( $message, 'danger' );
                } else {
                    $message = $prefix . ( $form->steps[$next_step_key]['success'] ?? 'Success' );
                    OpenSim::notify( $message, 'success' );
                    // Register the form again to update values
                    $form->complete( $next_step_key );
                    $this->register_form_installation();
                }
            }
        }
    }

    private function robust_generate_config() {
        $template = 'includes/config.example.php';
        if ( ! file_exists( $template )) {
            OpenSim::notify_error( _('Template file not found.') );
            return false;
        }

        try {
            $php_template = file_get_contents($template);
        } catch (Throwable $e) {
            OpenSim::notify_error( $e );
            return false;
        }
        try {
            $config = $_SESSION[self::FORM_ID]['config'] ?? null;
            if( empty( $config ) ) {
                throw new OpenSim_Error( _('No configuration found.') );
            }
        } catch (Throwable $e) {
            OpenSim::notify_error( $e );
            return false;
        }
        // $config = $_SESSION[self::FORM_ID]['config'] ?? null;
        // if( empty( $config ) ) {
        //     OpenSim::notify_error( __FUNCTION__ . '() ' . _('No configuration found.') );
        //     return false;
        // }
        $robust_creds = OpenSim::connectionstring_to_array($config['DatabaseService']['ConnectionString']);

        $registrars = array(
            'DATA_SRV_W4OSDev' => "http://dev.w4os.org/helpers/register.php",
            'DATA_SRV_2do' => 'http://2do.directory/helpers/register.php',
            'DATA_SRV_MISearch' => 'http://metaverseink.com/cgi-bin/register.py',
        );

        $console = array(
            'ConsoleUser' => $config['Network']['ConsoleUser'],
            'ConsolePass' => $config['Network']['ConsolePass'],
            'ConsolePort' => $config['Network']['ConsolePort'],
            'numeric' => 123456789,
            'boolean_string' => 'true',
        );
        
        // Define mapping between config array keys and template constants
        $mapping = array(
            'OPENSIM_GRID_NAME'   => $config['Const']['BaseURL'],
            'OPENSIM_LOGIN_URI'   => $config['Const']['BaseURL'] . ':' . $config['Const']['PublicPort'],
            'OPENSIM_MAIL_SENDER' => "no-reply@" . parse_url($config['Const']['BaseURL'], PHP_URL_HOST),
            'ROBUST_DB'           => $robust_creds,
            'OPENSIM_DB'          => true, // Changed from string to boolean
            'OPENSIM_DB_HOST'     => $robust_creds['host'],
            'OPENSIM_DB_PORT'     => $robust_creds['port'] ?? null,
            'OPENSIM_DB_NAME'     => $robust_creds['name'],
            'OPENSIM_DB_USER'     => $robust_creds['user'],
            'OPENSIM_DB_PASS'     => $robust_creds['pass'],
            'SEARCH_REGISTRARS'   => $registrars,
            'ROBUST_CONSOLE'     => $console,
            'CURRENCY_NAME'       => $config['LoginService']['Currency'] ?? 'L$',
            'CURRENCY_HELPER_URL' => $config['GridInfoService']['economy'] ?? '',

            // Add more mappings as needed
        );

        // Replace placeholders in the template
        foreach ($mapping as $constant => $value) {
            $pattern = "/define\(\s*'{$constant}'\s*,\s*(?:array\s*\([^;]*?\)|'[^']*'|\"[^\"]*\"|[^)]+)\s*\);/s";

            if (is_array($value)) {
                $exported = var_export($value, true);
                // Remove quotes for numeric and boolean strings if necessary
                $exported = preg_replace("/'([0-9]+)'/", '$1', $exported);
                $exported = str_replace("'true'", 'true', $exported);
                $exported = str_replace("'false'", 'false', $exported);
                $replacement = "define( '{$constant}', {$exported} );";
            } else if( $value === null ) {
                $exported = "NULL";
                $replacement = "define( '{$constant}', {$exported} );";
            } else if (is_bool($value)) {
                $bool = $value ? 'true' : 'false';
                $replacement = "define( '{$constant}', {$bool} );";
            } else if (is_numeric($value)) {
                $replacement = "define( '{$constant}', {$value} );";
            } else {
                $replacement = "define( '{$constant}', '" . addslashes($value) . "' );";
            }
            $php_template = preg_replace($pattern, $replacement, $php_template);
        }

        // Write the updated config to config.php
        if( empty( $_SESSION[self::FORM_ID]['config_file'] ) ) {
            // Should not happen, it has been validated before
            $message = _( 'No config file specified, should be possible at this stage.' );
            error_log( 'ERROR ' . __FUNCTION__ . '() ' . $message );
            OpenSim::notify_error( __FUNCTION__ . '() ' . _('No config file specified, should not have occured.'), 'danger');
            return false;
        }
        $temp_config_file = $_SESSION[self::FORM_ID]['config_file'] . '.install.temp';

        try {
            $result = file_put_contents($temp_config_file, $php_template);
            if ( ! $result ) {
                throw new OpenSim_Error( sprintf(
                    _( 'Error writing temporary file, make sure the web server has read/write permissions to %s directory.'),
                    '<nobr><code>' . dirname( $temp_config_file ) . '/</code></nobr>'
                ) );
            }
        } catch (Throwable $e) {
            OpenSim::notify_error( $e );
            return false;
        }
        // OpenSim::notify(_('Configuration file generated successfully.'), 'success');
        return true;
    }

    private function robust_test_config() {
        $temp_config_file = $_SESSION[self::FORM_ID]['config_file'] . '.install.temp';

        // The file was validated by the previous task, so it should always exist
        if ( ! file_exists( $temp_config_file )) {
            throw new OpenSim_Error( _( 'The generated configuration file could not be found.' ) . '<br><code>' . $temp_config_file . '</code>' );
        }
        
        try {
            include_once( $temp_config_file );
            // error_log( 'OPENSIM_GRID_NAME ' . OPENSIM_GRID_NAME );
        } catch (Throwable $e) {
            OpenSim::notify_error( $e );
            return false;
        }

        // TODO: more extensive tests with all the constants used by the other scripts
        $required_constants=array(
            'CURRENCY_DB_HOST',
            'CURRENCY_DB_NAME',
            'CURRENCY_DB_PASS',
            'CURRENCY_DB_USER',
            'CURRENCY_HELPER_URL',
            'CURRENCY_MONEY_TBL',
            'CURRENCY_PROVIDER',
            'CURRENCY_RATE',
            'CURRENCY_RATE_PER',
            'CURRENCY_SCRIPT_KEY',
            'CURRENCY_TRANSACTION_TBL',
            'CURRENCY_USE_MONEYSERVER',
            'HYPEVENTS_URL',
            'OFFLINE_MESSAGE_TBL',
            'OPENSIM_DB',
            'OPENSIM_DB_HOST',
            'OPENSIM_DB_NAME',
            'OPENSIM_DB_PASS',
            'OPENSIM_DB_USER',
            'OPENSIM_GRID_NAME',
            'OPENSIM_MAIL_SENDER',
            'SEARCH_DB_HOST',
            'SEARCH_DB_NAME',
            'SEARCH_DB_PASS',
            'SEARCH_DB_USER',
            'SEARCH_REGISTRARS',
            'SEARCH_TABLE_EVENTS',
        );
        foreach( $required_constants as $constant ) {
            if( ! defined( $constant ) ) {
                $missing[$constant] = sprintf( _('Constant %s is missing.'), $constant );
            }
            $missing_count = ( ! empty( $missing ) ) ? count( $missing ) : 0;
        }
        if( $missing_count > 0 ) {
            $message = sprintf( _('%s error(s) while generating config file.'), $missing_count );
            $message .= '<ul><li>' . implode( '</li><li>', $missing ) . '</li></ul>';
            throw new OpenSim_Error( $message );
        }
        if( ! defined( 'OPENSIM_GRID_NAME' ) ) {
            throw new OpenSim_Error( _('Some required values are missing from the configuration file.') );
        }

        // Connect to Robust to check credential and get up-to-date grid info
        global $OpenSim;
        try {
            $OpenSim->db_connect();
            if( ! OpenSim::$robust_db ) {
                throw new OpenSim_Error( _('Could not connect to the database.') );
            }
        } catch (Throwable $e) {
            OpenSim::notify_error( $e );
            return false;
        }
        // OpenSim::notify( _('Configuration file loaded successfully.'), 'success' );
        // TODO: copy the temp file to the final location on success.
        return true;
        // throw new OpenSim_Error(  _('The robust_test_config routine is not finished yet, but so far, so good.' ) );
    }

    public function process_form_installation() {
        $form = $this->form ?? false;
        if( ! $form ) {
            error_log( __FUNCTION__ . ' form not set' );
            return false;
        }

        $next_step_key = $form->get_next_step();
        $values = $form->get_values();
        $errors = 0;
        if( ! empty( $values['robust_ini_path'] ) ) {
            try {
                // At this stage, we only check if the file exists
                $valid = $form->is_robust_ini_file( 'robust_ini_path', $values['robust_ini_path'] );
                if( $valid === false ) {
                    // $message = 
                    // OpenSim::notify_error( _('Invalid answer from is_robust_ini_file') );
                    // Should not happen, is_robust_ini_file should have thrown an error instead of returning false
                    throw new OpenSim_Error( _('is_robust_ini_file returned an invalid value.') );
                    $errors++;
                }
                // $ini = new OpenSim_Ini( $values['robust_ini_path'] );
            } catch (Throwable $e) {
                // OpenSim::notify_error( $e );
                $errors++;
            }
            if( file_exists($values['robust_ini_path']) ) {
                $_SESSION[self::FORM_ID]['robust_ini_path'] = realpath( $values['robust_ini_path'] );
            } else {
                OpenSim::notify_error( _('File not found') );
                $form->task_error('robust_ini_path', _('File not found'), 'danger' );
                $errors++;
            }
        } else {
            $form->task_error('robust_ini_path', _('A file must be specified'));
            $errors++;
        }

        if( file_exists( $values['config_file'] ) ) {
            $_SESSION[self::FORM_ID]['config_file'] = realpath( $values['config_file'] );
            $form->task_error('config_file', _('File will be overwritten, any existing config wil be lost.'), 'warning' );
        } else {
            $_SESSION[self::FORM_ID]['config_file'] = $values['config_file'] ?? 'includes/config.php';
        }

        return ( $errors > 0 ) ? false : true;
    }

    private function register_form_installation() {
        $config_file = $_POST['config_file'] ?? $_SESSION[self::FORM_ID]['config_file'] ?? 'includes/config.php';
        $config_file = ( file_exists( $config_file ) ) ? realpath( $config_file ) : $config_file;
        $form = OpenSim_Form::register(array(
            'form_id' => self::FORM_ID,
            'multistep' => true,
            'success' => _('Robust configuration completed.'),
            'callback' => [$this, 'process_form_installation'],

            // 'steps' => $steps, // Steps can be defined here if all objects needed are available
            'fields' => array(
                'config_robust' => array(
                    'config_file' => array(
                        'label' => _('Target configuration file'),
                        'type' => 'plaintext',
                        'value' => $config_file,
                        'default' => $config_file,
                        'placeholder' => 'includes/config.php',
                        'readonly' => true,
                        // 'disabled' => true,
                        'help' => _('This file will be created or replaced with the settings found in the .ini file.'),
                    ),
                    'robust_ini_path' => array(
                        'label' => _('Robust config file path'),
                        'type' => 'text',
                        'required' => true,
                        'value' => null,
                        'placeholder' => '/opt/opensim/bin/Robust.HG.ini',
                        'help' => _('The full path to Robust.HG.ini (in grid mode) or Robust.ini (standalone mode) on this server.'),
                    ),
                ),
                'config_opensim' => array(
                    'config_file' => array(
                        'label' => _('Target configuration file'),
                        'type' => 'plaintext',
                        'value' => $_SESSION[self::FORM_ID]['config_file'] ?? 'includes/config.php',
                        'readonly' => true,
                    ),
                    'robust_ini_path' => array(
                        'label' => _('Robust config file path'),
                        'type' => 'plaintext',
                        'value' => $_SESSION[self::FORM_ID]['robust_ini_path'] ?? '',
                        'readonly' => true,
                    ),
                    'opensim_ini_path' => array(
                        'label' => _('OpenSim config file path'),
                        'type' => 'text',
                        'required' => true,
                        'value' => isset( $_SESSION[self::FORM_ID]['robust_ini_path']) ? dirname( $_SESSION[self::FORM_ID]['robust_ini_path'] ) . '/OpenSim.ini' : null,
                        'placeholder' => '/opt/opensim/bin/OpenSim.ini',
                        'help' => _('The full path to OpenSim.ini on this server.'),
                    ),
                ),
                'config_others' => array(),
                'config_helpers' =>array(),
                'validation' => array(),
            ),
        ));

        if( ! $form ) {
            error_log( __FUNCTION__ . ' form registration failed' );
            return false;
        }

        $values = $form->get_values();
        // As steps require the form to be registered, we need to register
        // them after the form is created.
        $steps = array(
            'config_robust' => array(
                'label' => _('Setup Robust'),
                'init' => [ $form, 'render_form' ],
                'description' => _('Give the path of your Robust configuration file.
                Robust.HG.ini for grid mode, Robust.ini for standalone mode.
                The file will be parsed and converted to a PHP configuration file.'),
                'success' => _('Configuration parsed and converted successfully.'),
                'tasks' => array(
                    array(
                        'label' => _('Process form'),
                        'callback' => [ $form, 'process' ],
                    ),
                    array(
                        'label' => _('Process ini file'),
                        'callback' => [ $this, 'robust_process_ini' ],
                        'success' => _('Robust ini parsed and converted.'),
                    ),
                    array(
                        'label' => _('Generate config'),
                        'callback' => [ $this, 'robust_generate_config' ],
                        'error' => _('Error generating PHP config file.'),
                        'success' => _('PHP config file generated.'),
                    ),
                    array(
                        'label' => _('Test config'),
                        'callback' => [ $this, 'robust_test_config' ],
                        'success' => _('The PHP configuration file loaded like a breeze.'),
                    )
                )
            ),
            'config_opensim' => array(
                'label' => _('Setup OpenSim'),
                'description' => _('Get OpenSim.ini file and process it'),
            ),
            'config_others' => array(
                'label' => _('Get additional files'),
                'description' => _('Get additional files, e.g. MoneyServer.ini, Gloebit.ini...'),
            ),
            'config_helpers' => array(
                'label' => _('Setup Helpers'),
                'description' => _('Additional settings specific to helpers, not in ini files, e.g. OSSEARCH_DB'),
            ),
            'validation' => array(
                'label' => _('Validation'),
                'description' => _('Validate the configuration'),
            ),
        );
        $form->add_steps( $steps );

        $next_step_key = $form->get_next_step();

        // Validate the values only for user information, keep proceeding even if there are errors
        if( is_callable ( [$this,'validate_form_installation'] ) ) {
            try {
                $this->validate_form_installation( $form, $next_step_key );
            } catch (Throwable $e) {
                OpenSim::notify_error( '$e' );
            }
            $this->validate_form_installation( $form, $next_step_key );
        }
        $this->form = $form;
        return $form;
    }    

    private function validate_form_installation( $form, $step, $values = null ) {
        $errors = 0;
        // $form = $this->form;
        if( ! $form ) {
            error_log( __FUNCTION__ . ' form not set' );
            return false;
        }
        // $step = $form->get_next_step();
        if( $values === null ) {
            $values = $form->get_values();
        }

        switch( $step ) {
            case 'config_robust':
                if( file_exists( $values['config_file'] ) ) {
                    // Only a warning, it's normal to overwrite the file if wanted
                    $form->task_error('config_file', _('File will be overwritten, any existing config wil be lost.'), 'warning' );
                }

                if( file_exists( $values['robust_ini_path'] ) ) {
                    try {
                        // At this stage, we only check if the file exists
                        $valid = $form->is_robust_ini_file( 'robust_ini_path', $values['robust_ini_path'] );
                        if( $valid === false ) {
                            // OpenSim::notify_error( _('Invalid answer from is_robust_ini_file') );
                            // Should not happen, is_robust_ini_file should have thrown an error instead of returning false
                            throw new OpenSim_Error( _('Invalid answer from is_robust_ini_file()') );
                            $errors++;
                        }
                    } catch (Throwable $e) {
                        OpenSim::notify_error( $e );
                        $form->task_error('robust_ini_path', _('Invalid Robust config file'), 'danger' );
                        $errors++;
                    }
                }
                break;

            case 'config_opensim':
                if( ! empty( $values['opensim_ini_path'] ) ) {
                    if( ! $form->validate_file( 'opensim_ini_path', $values['opensim_ini_path'] ) ) {
                        $errors++;
                    }
                }
                break;
            case 'config_others':
                break;
            case 'config_helpers':
                break;
            case 'validation':
                break;
        }
        return;
    }

    public function render_form( $form_id = null ) {
        $form = $this->form ?? false;
        if ( $form ) {
            return $form->render_form();
        }

        return false;
    }

    public function render_content() {

        $content = OpenSim::get_notices();
        $content .= $this->form->render_progress();
        $content .= $this->form->render_form();
        $content .= ( $this->content ?? '' );

        return $content;
    }

    /**
     * Read the ini file and store config in an array.
     */
    public function robust_process_ini() {
        try {
            $ini = new OpenSim_Ini( $_SESSION[self::FORM_ID]['robust_ini_path'] );
        } catch (Throwable $e) {
            OpenSim::notify_error( $e );
            return false;
        }
        if ( ! $ini ) {
            OpenSim::notify( _('Error parsing file.') );
            return false;
        }

        $config = $ini->get_config();
        $_SESSION[self::FORM_ID]['config'] = $config;
        if ( ! $config ) {
            OpenSim::notify( _('Error parsing file.') );
            return false;
        }
        return true;
    }

    /**
     * Handle the restart action by clearing the installation session and redirecting.
     */
    private function handle_reset() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['reset'])) {
                unset($_SESSION[self::FORM_ID]);
                OpenSim::notify(_('Installation session has been cleared. Restarting installation.'), 'success');
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            }
        }

    }
}

$page = new OpenSim_Install();
$page_title = $page->get_page_title();
$content = $page->get_content();

// Last step is to load template to display the page.
require( 'templates/templates.php' );
