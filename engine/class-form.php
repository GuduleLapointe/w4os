<?php
/**
 * Form class for OpenSimulator
 * 
 * Handles form rendering and processing. Must be used for any form in 
 * the project.
 * 
 * This class doesn't save values, it only returns them to the caller.
 * This class doesn't output html, it only returns the html code to the caller.
 * This class doesn't validate values, it only calls the validation callbacks
 * 
 * This class doesn't render fields by itself, it uses the OpenSim_Field class to get the rendered HTML.
 * defined in the fields definition.
 * 
 * Methods:
 * - register( $args, $fields ) register a new form (used in _construct)
 *      $args = array(
 *          'id' => unique id
 *          'html' => html code
 *          'callback' => callback to call to process form
 * )
 * - render_form()     return the form html code
 * - get_fields()   return the array of defined fields 
 * - process()      process the form callback
 * 
 * @package		magicoli/opensim-helpers
**/

require_once __DIR__ . '/class-form-field.php';

class OpenSim_Form {
    private $form_id;
    private $fields = array();
    private $callback;
    private static $forms = array();
    private $errors = array();
    private $field_errors = array();
    private $html;
    private $completed;
    private $multistep;
    private $current_step_slug;
    public $tasks;

    public function __construct($args = array(), $step = 0) {
        if( is_string( $args )) {
            // If only a string is passed, consider it as form_id for a pending form
            $args = array( 'form_id' => $args );
        }
        if (!is_array($args)) {
            error_log(__METHOD__ . ' invalid argument type ' . gettype($args));
            throw new InvalidArgumentException('Invalid argument type: ' . gettype($args));
        }

        $args = OpenSim::parse_args($args, array(
            'form_id' => uniqid('form-', true),
            'fields' => array(),
            'callback' => null,
            'multistep' => false,
        ));
        $this->form_id = $args['form_id'];
        $this->multistep = $args['multistep'];
        $this->steps = $args['steps'] ?? false;
        $this->callback = $args['callback'];
        $this->add_fields($args['fields']);
        $this->current_step = $step;

        $this->completed = $_SESSION[$this->form_id]['completed'] ?? $this->completed;
        self::$forms[$this->form_id] = $this;
        
        // Process step validation if this is a multistep form and form was submitted
        if ($this->multistep) {
            $this->process_step();
        }
    }

    /**
     * Static factory method to register an instance of OpenSim_Form.
     * Not the preferred way, better call directly $form = new OpenSim_Form( $args )
     * Handles exceptions internally to avoid requiring try-catch blocks during instantiation.
     *
     * @param array $args Arguments for form initialization.
     * @param int $step Optional step parameter.
     * @return OpenSim_Form|false Returns an instance of OpenSim_Form on success, or false on failure.
     */
    public static function register($args = array(), $step = 0) {
        try {
            return new self($args, $step);
        } catch (InvalidArgumentException $e) {
            error_log($e->getMessage());
            OpenSim::notify_error($e->getMessage() );
            return false;
        }
    }

    public function add_steps($steps) {
        if( empty( $steps )) {
            return false;
        }
        $this->steps = OpenSim::parse_args( $steps, $this->steps );
    }

    public function add_fields( $fields) {
        if( empty( $fields )) {
            return;
        }
        $this->fields = OpenSim::parse_args( $fields, $this->fields );
    }

    public function task_error( $field_id, $message, $type = 'warning' ) {
        $this->errors[$field_id] = array(
            'message' => $message ?? 'Error',
            'type' => empty( $type ) ? 'warning' : $type,
        );
    }

    public function render_form() {
        if( ! empty( $this->html )) {
            return $this->html;
        }
        
        if( $this->multistep ) {
            $this->refresh_steps();
            $current_step = $this->get_current_step_config();
            if( empty( $current_step ) ) {
                error_log( __METHOD__ . ' no current step found' );
                return '(debug) No step to render';
            }
            
            $fields = $current_step['fields'] ?? array();
            $step_title = $current_step['title'] ?? '';
            $step_description = $current_step['description'] ?? '';
        } else {
            $fields = $this->fields;
            $step_title = '';
            $step_description = '';
        }

        if( empty( $fields ) ) {
            error_log( __METHOD__ . ' called with empty fields' );
            return 'Form has no fields defined.';
        }

        $form_id = $this->form_id;
        
        $html = '';
        
        // Add progress bar for multistep forms
        if( $this->multistep ) {
            $html .= $this->render_progress();
        }
        
        // Add step title and description
        if( !empty( $step_title ) ) {
            $html .= '<h2>' . do_not_sanitize( $step_title ) . '</h2>';
        }
        if( !empty( $step_description ) ) {
            $html .= '<p class="lead">' . do_not_sanitize( $step_description ) . '</p>';
        }
        
        // Display general errors
        if (!empty($this->errors)) {
            $html .= '<div class="alert alert-danger" role="alert">';
            $html .= '<ul class="mb-0">';
            foreach ($this->errors as $error) {
                $html .= '<li>' . do_not_sanitize($error) . '</li>';
            }
            $html .= '</ul>';
            $html .= '</div>';
        }
        
        // Start form
        $html .= sprintf(
            '<form id="%s" method="post" action="%s" class="helpers-form">',
            $this->form_id,
            $_SERVER['REQUEST_URI']
        );
        
        // Render fields
        foreach ( $fields as $field_id => $field_config ) {
            $html .= $this->render_field( $field_id, $field_config );
        }
        
        $current_step_slug = $this->get_current_step_slug();
        $current_step_number = $this->get_current_step_number();
        $step_keys = array_keys($this->steps);

        $buttons = array();

        if( $current_step_number >0 ) {
            // Previous button (not on first step)
            $buttons['previous'] = ($this->multistep && $current_step_number > 0) ? sprintf(
                '<button type="button" class="ms-auto btn btn-outline-secondary" onclick="previousStep()">
                    <i class="bi bi-arrow-left"></i> 
                    %s
                </button>',
                _('Previous')
            ) : '';
        }

        $buttons['reset'] = sprintf(
            '<button type="button" class="ms-auto btn btn-outline-danger" onclick="resetWizard()">%s</button>',
            _('Reset')
        );
        $buttons['submit'] = sprintf(
            '<button type="submit" class="btn btn-primary">%s</button>',
            $this->multistep ? _('Next') : _('Submit')
        );

        // Add form buttons
        $html .= '<div class="mt-4">';
        // Reset button
        $html .= sprintf(
            '<input type="hidden" name="form_id" value="%s">'
            . '<input type="hidden" name="step_slug" value="%s">'
            . '<div class="d-flex gap-3 mt-4">%s</div>',
            $this->form_id,
            $current_step_slug,
            join(' ', $buttons),
        );

        $html .= '</div>';
        $html .= '</form>';

        $this->html = $html;
        return $html;
    }

    /**
     * Render individual field based on type with Bootstrap wrapper and conditions
     */
    private function render_field($field_id, $field_config) {
        // Check field conditions first
        if (!$this->should_show_field($field_config)) {
            return '';
        }
        
        // Check if field has errors
        $has_error = isset($this->field_errors[$field_id]);
        $field_error = $has_error ? $this->field_errors[$field_id] : '';
        
        // Use OpenSim_Field class for all field rendering
        $field = new OpenSim_Field($field_id, $field_config);
        
        // Pass error information to field
        if ($has_error) {
            $field->set_error($field_error);
        }
        
        // Wrap field with Bootstrap classes and data attributes
        $wrapper_classes = ['mb-3'];
        if (!empty($field_config['condition'])) {
            $wrapper_classes[] = 'conditional-field';
        }
        if ($has_error) {
            $wrapper_classes[] = 'has-validation';
        }
        
        $html = '<div class="' . implode(' ', $wrapper_classes) . '" data-field="' . do_not_sanitize($field_id) . '">';
        $html .= $field->render();
        
        // Add field-specific error message
        if ($has_error && $field_error) {
            $html .= '<div class="invalid-feedback d-block">' . do_not_sanitize($field_error) . '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Check if field should be shown based on conditions
     */
    private function should_show_field($field_config) {
        if (empty($field_config['condition'])) {
            return true;
        }
        
        $conditions = $field_config['condition'];
        foreach ($conditions as $condition_field => $condition_values) {
            $current_value = $_POST[$condition_field] ?? $_SESSION[$this->form_id][$condition_field] ?? '';
            
            if (is_array($condition_values)) {
                if (!in_array($current_value, $condition_values)) {
                    return false;
                }
            } else {
                if ($current_value !== $condition_values) {
                    return false;
                }
            }
        }
        
        return true;
    }

    /**
     * Get field value with proper fallback
     */
    public function get_field_value($field_id, $field_config) {
        return $_POST[$field_id] ?? $_SESSION[$this->form_id][$field_id] ?? $field_config['default'] ?? '';
    }
    
    /**
     * Get form ID for field access
     */
    public function get_form_id() {
        return $this->form_id;
    }
    
    /**
     * Get the step slug that should be displayed
     */
    private function get_current_step_slug() {
        if (!$this->multistep || empty($this->steps)) {
            return null;
        }
        
        $current_step_slug = $_SESSION[$this->form_id]['current_step'] ?? null;
        if (empty($current_step_slug)) {
            $step_keys = array_keys($this->steps);
            $current_step_slug = $step_keys[0];
            $_SESSION[$this->form_id]['current_step'] = $current_step_slug;
        }
        
        return $current_step_slug;
    }

    /**
     * Get the current step index
     */
    private function get_current_step_number() {
        $current_step_slug = $this->get_current_step_slug();
        if (empty($current_step_slug)) {
            return 0;
        }
        
        $step_keys = array_keys($this->steps);
        $current_index = array_search($current_step_slug, $step_keys);
        
        if ($current_index === false) {
            error_log(__METHOD__ . ' Current step slug not found in steps: ' . $current_step_slug);
            return 0;
        }
        
        return $current_index;
    }

    /**
     * Get current step config for multistep forms
     * Returns the step that should be displayed/processed RIGHT NOW
     * 
     * @return array|null Returns the current step configuration or null if not a multistep form
     */
    private function get_current_step_config() {
        if (!$this->multistep || empty($this->steps)) {
            return null;
        }
        
        // The current step is always what's in session (or first step if nothing)
        $current_step_slug = $this->get_current_step_slug();
        if (empty($current_step_slug)) {
            // get_current_step_slug returns first step by default, so 
            // if it's empty, we have a problem, Huston
            return false;
        }

        $step_config = $this->steps[$current_step_slug] ?? false;
        if (!$step_config) {
            error_log(__METHOD__ . ' No step configuration found for slug: ' . $current_step_slug);
        }        
        return $step_config;
    }

    /**
     * Process step validation and advance if successful
     */
    private function process_step() {
        if (empty($_POST['form_id']) || $_POST['form_id'] !== $this->form_id) {
            return;
        }
        
        // Get the step that was just submitted
        $submitted_step_key = $_POST['step_slug'] ?? null;
        if (empty($submitted_step_key)) {
            error_log(__METHOD__ . ' [ERROR] No step_slug in POST data');
            return;
        }
        
        $submitted_step = $this->steps[$submitted_step_key] ?? null;
        if (!$submitted_step) {
            error_log(__METHOD__ . ' No step configuration found for key: ' . $submitted_step_key);
            return;
        }
        
        error_log(__METHOD__ . ' Processing submitted step: ' . $submitted_step_key);
        
        // Get step callback if defined
        $callback = $submitted_step['callback'] ?? null;
        if (!$callback) {
            error_log(__METHOD__ . ' No callback defined for step ' . $submitted_step_key . ', advancing automatically');
            // No validation needed, just save form data and advance
            $this->save_step_data($submitted_step_key);
            $this->advance_step();
            return;
        }
        
        error_log(__METHOD__ . ' Executing callback: ' . $callback);
        
        // Execute step validation callback with submitted values
        $submitted_values = $_POST;
        
        if (is_callable($callback)) {
            $result = call_user_func($callback, $submitted_values);
        } elseif (is_array($this->callback) && method_exists($this->callback[0], $callback)) {
            $result = call_user_func(array($this->callback[0], $callback), $submitted_values);
        } else {
            error_log(__METHOD__ . ' callback not found: ' . $callback);
            return;
        }

        // Handle validation result
        if (is_array($result) && isset($result['success'])) {
            if ($result['success']) {
                // Validation passed, advance to next step
                error_log(__METHOD__ . ' Validation passed, advancing to next step');
                $this->save_step_data($submitted_step_key);
                $this->advance_step();
            } else {
                // Validation failed, set errors and stay on current step
                if (isset($result['errors'])) {
                    $this->errors = $result['errors'];
                }
                if (isset($result['field_errors'])) {
                    $this->field_errors = $result['field_errors'];
                }
                // Current step remains unchanged - user stays on the step that failed validation
            }
        }
    }
    
    /**
     * Save current step data to session
     */
    private function save_step_data($step_slug) {
        $step_data = $_POST;
        
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION[$this->form_id]['steps'][$step_slug] = $step_data;
    }
    
    /**
     * Advance to next step
     */
    private function advance_step() {
        $step_keys = array_keys($this->steps);
        $current_step_slug = $_SESSION[$this->form_id]['current_step'] ?? $step_keys[0];
        $current_index = array_search($current_step_slug, $step_keys);
        
        if ($current_index !== false && $current_index < count($step_keys) - 1) {
            $next_step_key = $step_keys[$current_index + 1];
            $_SESSION[$this->form_id]['current_step'] = $next_step_key;
            error_log(__METHOD__ . ' [DEBUG] Advanced from ' . $current_step_slug . ' to ' . $next_step_key);
        } else {
            error_log(__METHOD__ . ' [DEBUG] Already at last step or step not found');
        }
    }

    public function process() {
        if( empty( $_POST )) {
            // Fail if no POST data
            error_log(__METHOD__ . ' called without POST data');
            return false;
        }
        $values = $_POST;

        // TODO: validate form_id, make basic sanitization

        if (is_callable($this->callback)) {
            return call_user_func($this->callback, $values);
        } else {
            if (is_array($this->callback)) {
                $callback_name = get_class($this->callback[0]) . '::' . $this->callback[1];
            } else {
                $callback_name = $this->callback;
            }
            error_log( $callback_name . ' is not callable from ' . __METHOD__ );
            return false;
        }
    }



    private function get_step_fields() {
        if( ! isset( $this->next_step_key ) || ! isset( $this->fields[$this->next_step_key] ) ) {
            return array();
        }
        return $this->fields[$this->next_step_key];
    }

    // Get defined fields
    public function get_fields() {
        return $this->fields;
    }

    public function get_form( $form_id ) {
        if( empty( $form_id )) {
            return false;
        }
        return isset( self::$forms[$form_id] ) ? self::$forms[$form_id] : false;
    }

    public function get_forms() {
        return self::$forms ?? false;
    }

    public function get_next_step() {
        if( empty( $this->steps )) {
            return false;
        }
        
        // Get current step from session (this is the step to display)
        $current_step_slug = $this->get_current_step_slug();
        if(!is_numeric($current_step_slug)) {
            // Probably redundant with empty($this->steps)
            return false;
        }

        // TODO: get next step slug based on current step
        error_log(__METHOD__ . ' used but not implemented yet');
        return false;
    }

    /**
     * Use the value of $this->complete as last completed step, get the next step and 
     * build a navigation html.
     */
    private function refresh_steps() {
        if( empty( $this->steps )) {
            return false;
        }

        $steps = $this->steps;

        // TODO: make sure current step is validated before doing that
        $next_step_key = $this->get_next_step();

        // Set progression table
        $progress = array();
        $status = 'completed';
        foreach( $steps as $key => $step ) {
            if( $key == $next_step_key ) {
                $progress[$key] = 'active';
                $status = '';
            } else {
                $progress[$key] = $status;
            }
        }
        $this->progression = $progress;
    }

    /**
     * Build HTML progress bar with numbered steps
     */
    public function render_progress() {
        if( empty( $this->steps )) {
            return '';
        }

        $steps = $this->steps;
        $current_step_slug = $this->get_current_step_slug();
        $step_keys = array_keys($steps);
        $current_index = array_search($current_step_slug, $step_keys);
        
        $html = '<div class="multistep-progress mt-4">';
        $html .= '<div class="step-indicators d-flex align-items-stretch justify-content-between">';

        error_log(__METHOD__ . ' step slug ' . $current_step_slug . ' at index ' . $current_index . ' step keys: ' . print_r($step_keys, true));
        
        foreach( $step_keys as $index => $step_slug ) {
            $step_number = $index + 1;
            $step_title = $steps[$step_slug]['title'] ?? ucfirst($step_slug);
            
            $status_class = '';
            if ($index < $current_index) {
                $status_class = 'completed';
                $number_class = 'bg-success text-white';
            } elseif ($index === $current_index) {
                $status_class = 'active';
                $number_class = 'bg-primary text-white';
            } else {
                $status_class = 'pending';
                $number_class = 'bg-secondary text-white';
            }

            $html .= sprintf(
                '<div class="step-indicator %s" data-step="%s">
                <div class="step-number %s">%d</div>
                <div class="step-title">%s</div>
                </div>
                ',
                $status_class,
                $step_slug,
                $number_class,
                $step_number,
                $step_title,
            );
            
            // Add connector line (except for last step)
            if ($index < count($step_keys) - 1) {
                $html .= '<div class="step-connector pt-1"><hr></div>';
            }
        }
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }

    public function validate_file( $field_id, $file_path, $strict = false ) {
        if( empty( $file_path ) ) {
            if( $strict ) {
                $message = sprintf( _('File %s is required'), $field_id );
                $this->task_error( $field_id, $message, 'danger' );
                return false;
            }
            // if not strict, allow it to be empty
            return true;
        }
        if( ! file_exists( $file_path )) {
            $message = sprintf( _('File %s not found'), $file_path );
            $this->task_error( $field_id, $message, 'danger' );
            return false;
        }
        return true;
    }

    /**
     * Make sure the file is a valid .ini file
     */
    public function is_valid_ini_file( $field_id, $file_path, $strict = false ) {
        if( ! $this->validate_file( $field_id, $file_path, true )) {
            $this->task_error( $field_id, _('File not found'), 'danger' );
            return false;
        }

        // If strict, use parse_ini_file
        if( $strict ) {
            $ini = parse_ini_file( $file_path );
            if( empty( $ini )) {
                $message = sprintf( _('File %s does not comply with .ini standards.'), $file_path );
                $this->task_error( $field_id, $message, 'danger' );
                // throw new Exception( $message );
                return false;
            }
            return true;
        }

        // If not strict, a light check is enough
        //
        // OpenSim uses some non-standard formatting that are not supported by parse_ini_file.
        // Ignore comments and empty lines, then make sure the remaining contains 
        // only key = value pairs or [sections]
        $ini = file_get_contents( $file_path );
        $lines = explode( "\n", $ini );
        $valid = true;

        // Filter out comments and empty lines
        $lines = array_filter( array_map( 'trim', $lines ), function( $line ) {
            return ! empty( $line ) && ! preg_match( '/^\s*;/', $line );
        });

        // Filter out valid lines, leaving only invalid ones
        $valid = array_filter( array_map( function( $line ) {
            return preg_match( '/^\[.*\]$|.*=.*$/', $line ) ? false : $line;
        }, $lines ));

        if( ! empty( $valid )) {
            $message = sprintf( _('File %s is not a valid .ini file'), $file_path );
            // error_log( $message . ', found invalid lines: ' . print_r( $valid, true ) );
            $this->task_error( $field_id, $message, 'danger' );
            return false;
        }
        return true;
    }

    /**
     * Make sure the file is a valid Robust.ini file
     */
    public function is_robust_ini_file( $field_id, $file_path ) {
        if( ! $this->is_valid_ini_file( $field_id, $file_path )) {
            throw new Exception( _('Not a valid ini file') );
            // return false;
        }

        $required_sections = array(
            'DatabaseService',
            'GridInfoService',
            'LoginService',
        );

        // Check if all required sections are present, with array_map (without parse_ini_file)
        $ini = file_get_contents( $file_path );
        $lines = explode( "\n", $ini );
        $sections = array_map( function( $line ) {
            if( preg_match( '/^\[(.*)\]\s*$/', $line, $matches )) {
                return $matches[1];
            }
            return false;
        }, $lines );

        $missing = array_diff( $required_sections, $sections );
        if( ! empty( $missing )) {
            $message = sprintf( _('Not a valid Robust config file.'), $file_path, implode( ', ', $missing ));
            $this->task_error( $field_id, $message, 'danger' );
            $message = sprintf( _('%s is missing required sections: %s'), $file_path, '<ul><li>' . implode( '</li><li>', $missing )  . '</li></ul>' );
            throw new Exception( $message );
        }

        return true;
    }

    public function complete( $step ) {
        $this->completed = $step;
        $_SESSION[$this->form_id]['completed'] = $step;
        $this->refresh_steps();
    }
}
