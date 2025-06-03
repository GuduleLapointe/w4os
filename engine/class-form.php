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
 * - get_values()   return an array of field_id => value pairs
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
    private $errors;
    private $html;
    private $completed;
    private $multistep;
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

        $this->completed = $_SESSION[$this->form_id]['completed'] ?? $this->completed;
        self::$forms[$this->form_id] = $this;
        // $this->refresh_steps();
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
        $this->get_next_step();
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
            $current_step = $this->get_current_step();
            if( empty( $current_step ) ) {
                error_log( __METHOD__ . ' no current step found' );
                return 'No step to render';
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
            $html .= '<h2>' . htmlspecialchars( $step_title ) . '</h2>';
        }
        if( !empty( $step_description ) ) {
            $html .= '<p class="lead">' . htmlspecialchars( $step_description ) . '</p>';
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

        // Add form buttons
        $reset_button = ( empty($_SESSION[$form_id])) ? '' : sprintf(
            '<button type="submit" name="reset" formnovalidate class="btn btn-secondary mx-2">%s</button>',
            _( 'Reset' )
        );
        
        $submit_button = sprintf(
            '<button type="submit" class="btn btn-primary">%s</button>',
            $this->multistep ? _('Next') : _('Submit')
        );

        $buttons = sprintf(
            '<input type="hidden" name="form_id" value="%s">'
            . '<input type="hidden" name="step_key" value="%s">'
            . '<div class="form-actions mt-4 text-end">%s%s</div>',
            $this->form_id,
            $this->next_step_key ?? '',
            $reset_button,
            $submit_button
        );
        
        $html .= $buttons;
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
        
        // Use OpenSim_Field class for all field rendering
        $field = new OpenSim_Field($field_id, $field_config);
        
        // Wrap field with Bootstrap classes and data attributes
        $wrapper_classes = ['mb-3'];
        if (!empty($field_config['condition'])) {
            $wrapper_classes[] = 'conditional-field';
        }
        
        $html = '<div class="' . implode(' ', $wrapper_classes) . '" data-field="' . htmlspecialchars($field_id) . '">';
        $html .= $field->render();
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
     * Get current step for multistep forms
     */
    private function get_current_step() {
        if (!$this->multistep || empty($this->steps)) {
            return null;
        }
        
        $step_key = $this->get_next_step();
        return $this->steps[$step_key] ?? null;
    }

    public function process() {
        if( empty( $_POST )) {
            // Only init values if form is not submitted
            return $this->get_values();
        }
        if (is_callable($this->callback)) {
            return call_user_func($this->callback, $this->get_values());
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

    /**
     * Get values from fields definition and post.
     * 
     * TODO: make sure values are not replaced with post values before this step,
     * although it doesn't hurt with the current usage, it might be useful to
     * compare old and new value in the process() method called later.
     */
    public function get_values() {
        $form_id = $this->form_id;
        $values = array();
        if( $this->multistep ) {
            $fields = $this->get_step_fields() ?? array();
            if( ! is_array( $fields )) {
                error_log( __METHOD__ . ' wrong field format ' );
                return false;
            }
        } else {
            $fields = $this->fields;
        }
        // error_log( 'Fields: ' . print_r( $fields, true ) );
        foreach( $fields as $key => $field ) {
            $values[$key] = $_POST[$key] ?? $_SESSION[$form_id][$key] ?? $field['value'] ?? null;
        }
        return $values;
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
        
        // Get current step from POST or session
        $current_step_key = $_POST['step_key'] ?? $_SESSION[$this->form_id]['current_step'] ?? null;
        
        // If no current step, start with first step
        if (empty($current_step_key)) {
            $step_keys = array_keys($this->steps);
            $this->next_step_key = $step_keys[0];
            return $this->next_step_key;
        }
        
        // If form was submitted, move to next step
        if (!empty($_POST['form_id']) && $_POST['form_id'] === $this->form_id) {
            $step_keys = array_keys($this->steps);
            $current_index = array_search($current_step_key, $step_keys);
            
            if ($current_index !== false && $current_index < count($step_keys) - 1) {
                $this->next_step_key = $step_keys[$current_index + 1];
            } else {
                // Last step or step not found
                $this->next_step_key = $current_step_key;
            }
        } else {
            // Not submitted, return current step
            $this->next_step_key = $current_step_key;
        }
        
        return $this->next_step_key;
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
        if( ! empty($_POST['form_id']) ) {
            $form_id = $_POST['form_id'];
            $form = self::$forms[$form_id];
            if( $form ) {
                $form->process();
            } else {
                error_log( __METHOD__ . ' Form ' . $form_id . ' is not registered' );
                return false;
            }
        }
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
        $current_step_key = $this->get_next_step();
        $step_keys = array_keys($steps);
        $current_index = array_search($current_step_key, $step_keys);
        
        $html = '<div class="multistep-progress mb-4">';
        $html .= '<div class="step-indicators">';
        
        foreach( $step_keys as $index => $step_key ) {
            $step_number = $index + 1;
            $step_title = $steps[$step_key]['title'] ?? ucfirst($step_key);
            
            $status_class = '';
            if ($index < $current_index) {
                $status_class = 'completed';
            } elseif ($index === $current_index) {
                $status_class = 'active';
            } else {
                $status_class = 'pending';
            }
            
            $html .= '<div class="step-indicator ' . $status_class . '">';
            $html .= '<div class="step-number">' . $step_number . '</div>';
            $html .= '<div class="step-title">' . htmlspecialchars($step_title) . '</div>';
            $html .= '</div>';
            
            // Add connector line (except for last step)
            if ($index < count($step_keys) - 1) {
                $html .= '<div class="step-connector"></div>';
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
            error_log( $message . ', found invalid lines: ' . print_r( $valid, true ) );
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
