<?php
/**
 * TODO
 * 
 * Form class for OpenSimulator Helpers
 * 
 * Handles form rendering and global processing. Forms definition are passed by calling class.
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

require_once( dirname(__DIR__) . '/classes/init.php' );

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
            $fields = $this->get_step_fields() ?? array();
            if( ! is_array( $fields )) {
                error_log( __METHOD__ . ' wrong field format ' );
                return false;
            }
        } else {
            $fields = $this->fields;
        }

        $form_id = $this->form_id;

        // Update Reset button to remain a submit button and bypass validation
        $reset_button = ( empty($_SESSION[$form_id])) ? '' : sprintf(
            '<button type="submit" name="reset" formnovalidate class="btn btn-secondary bg-black-50 mx-2">%s</button>',
            _( 'Reset Form' )
        );
        
        if( empty( $fields ) && empty( $reset_button ) ) {
            error_log( __METHOD__ . ' called with empty fields' );
            return false;
        }
        
        $html = '';
        $fields = empty($fields) ? array() : $fields;
        foreach ( $fields as $field => $data ) {
            $add_class = '';
            $add_attrs = '';
            if( ! empty( $this->errors[$field] ) ) {
                $field_error = $this->errors[$field];
                $data['help'] = OpenSim::error_html( $field_error, 'warning' ) . ( $data['help'] ?? '' );
                if( $field_error['type'] == 'danger' ) {
                    $add_class .= ' is-invalid';
                }
            }
            if( $data['type'] == 'plaintext' ) {
                $data['type'] = 'text';
                $add_class .= ' form-control-plaintext';
            }
            if( isset( $data['readonly'] ) && $data['readonly'] ) {
                $add_class .= ' text-muted';
                $add_attrs .= ' readonly';
            }
            $add_attrs .= isset( $data['disabled'] ) && $data['disabled'] ? ' disabled' : '';
            $add_attrs .= isset( $data['required'] ) && $data['required'] ? ' required' : '';
            // $placeholder = isset( $data['placeholder'] ) ? $data['placeholder'] : '';

            $html .= sprintf(
            '<div class="form-group py-1">
                <label for="%s">%s</label>
                <input type="%s" name="%s" class="form-control %s" value="%s" placeholder="%s" %s>
                <small class="form-text text-muted">%s</small>
            </div>',
            $field,
            $data['label'],
            $data['type'],
            $field,
            $add_attrs . $add_class,
            $_POST[$field] ?? $data['value'] ?? $data['default'] ?? '',
            $data['placeholder'] ?? '',
            $add_attrs,
            $data['help'] ?? ''
            );
        }

        $submit = empty( $html ) ? '' : sprintf(
            '<button type="submit" class="btn btn-primary">%s</button>',
            _('Submit')
        );

        $buttons = sprintf(
            '<input type="hidden" name="form_id" value="%s">'
            . '<input type="hidden" name="step_key" value="%s">'
            . '<div class="form-group text-end">%s</div>',
            $this->form_id,
            $this->next_step_key ?? '',
            $reset_button . $submit
        );
        $html = sprintf(
            '<form id="%s" method="post" action="%s" class="py-4">%s</form>',
            $this->form_id,
            $_SERVER['PHP_SELF'],
            $html . $buttons
        );

        OpenSim::enqueue_script( 'form', 'js/form.js' );
        OpenSim::enqueue_style( 'form', 'css/form.css' );

        $this->html = $html;
        return $html;
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
        $steps = $this->steps;
        $current_step = array_search($this->completed, array_keys($steps));
        if( empty( $this->completed ) ) {
            $next_step_key = key($steps);
            $next_step_label = $steps[$next_step_key];
        } else {
            $next_step_key = array_keys($steps)[$current_step + 1] ?? null;
            if( empty($steps[$next_step_key])) {
                $next_step_key='completed';
                $next_step_label = _('Completed');
            } else {
                $next_step_label = $steps[$next_step_key] ?? null;
            }
        }
        // $this->next_step = $next_step_label;
        $this->next_step_key = $next_step_key;
        $this->tasks = $this->steps[$next_step_key]['tasks'] ?? false;
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
     * Build HTML progress bar with bootstrap classes
     */
    public function render_progress() {
        $this->refresh_steps();

        if( empty( $this->steps )) {
            return false;
        }

        $steps = $this->steps;
        $progress = $this->progression;

        $status = 'completed';
        $html = '<ul class="nav nav-tabs nav-fill">';
        foreach( $steps as $key => $step ) {
            $status = $progress[$key] ?? 'disabled';
            $label = $steps[$key]['label'] ?? $key;
            $style = '';
            switch( $status ) {
                case 'completed':
                    $status .= ' text-success';
                    $label .= ' &#10003;';
                    // $style = 'style="color:green"';
                    break;
                case 'active':
                    $status = 'active bg-secondary';
                    $style = 'style="font-weight:bold"';

                    break;
            }
            $status = empty($status) ? 'disabled' : $status;
            // if( $key == $next_step_key ) {
            //     $progress[$key] = 'active';
            //     $status = '';
            // } else {
            //     $progress[$key] = $status;
            // }
            // '<div class="progress-bar progress-bar-striped progress-bar-animated bg-%s" role="progressbar" style="width: 20%%" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100">%s</div>',
            $html .= sprintf( '<li class="nav-item">
                <a class="nav-link %s" aria-current="page" href="#" %s>%s</a>
                </li>',
                $status,
                $style,
                $label,
            );
        }
        $html .= '</ul>';
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
