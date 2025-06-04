<?php
/**
 * OpenSimulator Engine Form Field Class
 * 
 * Will be used to handle the different type fields needed by OpenSim_Form class.
 * 
 * This class does not output HTML directly, it returns the HTML code to the caller.
 * 
 * Fields could be defined as:
 *    $my_field = new OpenSim_Field( 'my_field_id', array(
 *       'type' => 'text', // all standard fields (text, integer, select, select2, checkbox, radio, textarea, password, file, hidden, ...)
 *                         // also supports custom pseudo-types like 'credentials', 'db_credentials', 'console_credentials', 'switch'...
 *       'name' => 'my_field_name',
 *      'label' => 'My Field Label',
 *      'default' => 'default value', // optional
 *      'description' => 'This is a description for the field', // optional
 *      'value' => 'current value', // optional, if not set, will use default value 
 *      'validation' => 'callback_function', // optional, function to validate the field value, string for global function or array for class method
 *      'options' => array( // for select fields
 *          'option1' => 'Option 1',
 *          'option2' => 'Option 2',
 *     ),
 *      'attributes' => array( // additional HTML attributes
 *          'class' => 'my-custom-class',
 *          'id' => 'my_field_id',
 *      'required' => true, // boolean
 *      'placeholder' => 'Enter your value here', // for text fields
 *      'error' => 'This field is required', // optional error message
 *      'help' => 'This is a help text for the field', // optional help text
 *  );
 * 
 * @package magicoli/opensim-engine
 */

class OpenSim_Field {
    private $field_id;
    private $field_config;
    private $has_error = false;
    private $error_message = '';

    public function __construct($field_id, $field_config = array()) {
        if(empty($field_id)) {
            throw new InvalidArgumentException('Field ID cannot be empty');
        }
        if(!is_array($field_config)) {
            throw new InvalidArgumentException('Field configuration must be an array');
        }
        
        $this->field_id = $field_id;
        $this->field_config = array_merge(
            array(
                'type' => 'text', // Default type
                'name' => '',
                'label' => '',
                'default' => null,
                'value' => null, // Will be set to default if not provided
                'description' => '',
                'options' => array(), // For select fields
                'attributes' => array(), // Additional HTML attributes
                'required' => false,
                'placeholder' => '',
                'icon' => '',
                'description' => '',
            ),
            $field_config
        );
    }

    /**
     * Set field error for validation display
     */
    public function set_error($error_message) {
        $this->has_error = true;
        $this->error_message = $error_message;
    }
    
    /**
     * Render field based on type
     */
    public function render() {
        $field_type = $this->field_config['type'] ?? 'text';
        
        // Check field conditions first
        if (!$this->should_show_field()) {
            return '';
        }
        
        switch ($field_type) {
            case 'select-nested':
                return $this->render_select_nested();
            case 'select-accordion':
                return $this->render_accordion();
            case 'console_credentials':
                return $this->render_console_credentials();
            case 'db_credentials':
                return $this->render_db_credentials();
            case 'file-ini':
                return $this->render_ini_files();
            default:
                return $this->render_standard();
        }
    }
    
    /**
     * Check if field should be shown based on conditions
     */
    private function should_show_field() {
        if (isset($this->field_config['enable']) && $this->field_config['enable'] === false) {
            return false;
        }

        if (empty($this->field_config['condition'])) {
            return true;
        }
        
        $conditions = $this->field_config['condition'];
        foreach ($conditions as $condition_field => $condition_values) {
            $current_value = $_POST[$condition_field] ?? $_SESSION['opensim_install_wizard'][$condition_field] ?? '';
            
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
     * Render standard form field
     */
    private function render_standard() {
        $type = $this->field_config['type'] ?? 'text';
        $label = $this->field_config['label'] ?? '';
        $required = !empty($this->field_config['required']);
        $value = $this->get_field_value();
        $placeholder = $this->field_config['placeholder'] ?? '';
        $description = $this->field_config['description'] ?? 'DEBUG description';
        $required_mark = $required ? '<span class="text-danger">*</span>' : '';
        
        $html = '<div class="form-group mb-3">';
        if ($label) {
            $html .= '<label class="form-label" for="' . $this->field_id . '">' . do_not_sanitize($label) . $required_mark . '</label>';
        }
        
        // Add Bootstrap classes based on field state
        $input_classes = ['form-control'];
        if ($this->has_error) {
            $input_classes[] = 'is-invalid';
        }
        
        $html .= '<input type="' . $type . '" class="' . implode(' ', $input_classes) . '" id="' . $this->field_id . '" name="' . $this->field_id . '" ';
        $html .= 'value="' . do_not_sanitize($value) . '" ';
        if ($placeholder) {
            $html .= 'placeholder="' . do_not_sanitize($placeholder) . '" ';
        }
        if ($required) {
            $html .= 'required ';
        }
        $html .= '>';
        
        if (!empty($this->field_config['description'])) {
            $html .= '<div class="form-text">' . $this->field_config['description'] . '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render select field with accordion-style items for some options
     */
    private function render_select_nested() {
        $label = $this->field_config['label'] ?? '';
        $options = $this->field_config['options'] ?? array();
        $required = !empty($this->field_config['required']);
        $value = $this->get_field_value();
        
        $required_mark = $required ? '<span class="text-danger">*</span>' : '';
        $html = '<div class="config-choice mb-4">';
        if ($label) {
            $html .= '<h5 class="mb-3">' . do_not_sanitize($label) . $required_mark . '</h5>';
        }
        
        // Hidden input to store the selected value
        $html .= '<input type="hidden" name="' . $this->field_id . '" id="' . $this->field_id . '" value="' . do_not_sanitize($value) . '"' . ($required ? ' required' : '') . '>';
        
        foreach ($options as $option_value => $option_config) {
            if(isset($option_config['enable']) && $option_config['enable'] === false) {
                continue; // Skip disabled options
            }
            // Handle both string and array option configs
            if (is_array($option_config)) {
                $option_label = $option_config['label'] ?? $option_value;
                $option_description = $option_config['description'] ?? 'DEBUG description array';
                $sub_fields = $option_config['fields'] ?? array();
                $icon = $option_config['icon'] ?? '';
            } else {
                $option_label = $option_config;
                $option_description = 'debug description string';
                $sub_fields = array();
                $icon = '';
            }
            
            $is_selected = ($value === $option_value);
            $card_classes = 'card mb-3 choice-option';
            if ($is_selected) {
                $card_classes .= ' border-primary bg-primary bg-opacity-10';
            } else {
                $card_classes .= ' border-secondary';
            }
            
            $html .= '<div class="' . $card_classes . '" onclick="selectChoice(\'' . $this->field_id . '\', \'' . $option_value . '\')" style="cursor: pointer;">';
            $html .= '<div class="card-body">';
            $html .= '<div class="d-flex align-items-center justify-content-between">';
            $html .= '<div class="d-flex align-items-center">';
            $html .= self::render_icon($icon);
            $html .= '<span class="fw-semibold">' . do_not_sanitize($option_label) . '</span>';
            $html .= '</div>';
            $html .= '</div>';
            
            
            $html .= '</div>';
            
            // Render sub-fields if they exist
            if (!empty($sub_fields || ! empty( $option_description))) {
                $display_class = $is_selected ? '' : 'd-none';
                $html .= '<div class="choice-sub-fields border-top border-primary bg-light p-3 ' . $display_class . '" id="' . $this->field_id . '_' . $option_value . '_fields">';

                // Show option description
                if ($option_description) {
                    $desc_margin= empty($sub_fields) ? 'mb-O' : 'mb-3';
                    $html .= '<div class="' . $desc_margin . ' text-muted">' . $option_description . '</div>';
                }

                foreach ($sub_fields as $sub_field_id => $sub_field_config) {
                    $field = new OpenSim_Field($sub_field_id, $sub_field_config);
                    $html .= $field->render();
                }
                $html .= '</div>';
            }
            
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    private function render_icon($icon) {
        if (empty($icon)) {
            return '';
        }
        if( strpos($icon, 'bi-') === 0) {
            $icon = '<i class="' . sanitize_id($icon) . '"></i>'; 
        }
        $icon_html = ' <span class="method-icon fs-4 p-1"> ' . $icon . '</span> ';
        return $icon_html;
    }
        
    /**
     * Render accordion field (select-accordion type)
     */
    private function render_accordion() {
        $options = $this->field_config['options'] ?? array();
        $label = $this->field_config['label'] ?? '';
        $required = !empty($this->field_config['required']);
        $value = $this->get_field_value();
        
        $required_mark = $required ? '<span class="text-danger">*</span>' : '';
        
        $html = '<div class="connection-methods mb-4">';
        if ($label) {
            $html .= '<h5 class="mb-3">' . do_not_sanitize($label) . $required_mark . '</h5>';
        }
        
        foreach ($options as $option_value => $option_config) {
            $option_label = $option_config['label'] ?? $option_value;
            $option_description = $option_config['description'] ?? '';
            $option_fields = $option_config['fields'] ?? array();
            $option_icon = self::render_icon($option_config['icon'] ?? '');
            
            // Determine if this option should be active
            $is_active = ($value === $option_value);
            $checked = $is_active ? 'checked' : '';
            
            $header_classes = 'method-header p-3 border rounded-top d-flex align-items-center justify-content-between';
            $body_classes = 'method-body p-3 border border-top-0 rounded-bottom';
            
            if ($is_active) {
                $header_classes .= ' bg-primary bg-opacity-10 border-primary';
                $body_classes .= ' border-primary';
            } else {
                $header_classes .= ' bg-light border-secondary';
                $body_classes .= ' border-secondary d-none';
            }
            
            $html .= '<div class="method-accordion mb-3">';
            $html .= '<div class="' . $header_classes . '" onclick="selectMethod(\'' . $option_value . '\')" style="cursor: pointer;">';
            $html .= '<div class="d-flex align-items-center">';
            $html .= '<input type="radio" name="' . $this->field_id . '" value="' . $option_value . '" ' . $checked . ($required ? ' required' : '') . ' class="me-3">';
            $html .= '<span class="method-title fw-semibold">' . do_not_sanitize($option_label) . '</span>';
            $html .= '</div>';
            $html .= self::render_icon($option_icon ?? '');
            $html .= '</div>';
            
            $html .= '<div class="' . $body_classes . '" id="' . $option_value . '-body">';
            if ($option_description) {
                $html .= '<p class="text-muted mb-3">' . do_not_sanitize($option_description) . '</p>';
            }
            
            // Render option fields
            if (!empty($option_fields)) {
                foreach ($option_fields as $option_field_id => $option_field_config) {
                    $field = new OpenSim_Field($option_field_id, $option_field_config);
                    $html .= $field->render();
                }
            }
            
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render console credentials fields
     */
    private function render_console_credentials() {
        $label = $this->field_config['label'] ?? _('Console credentials');
        $defaults = $this->field_config['default'] ?? array();
        $description = $this->field_config['description'] ?? '';
        
        $html = '<div class="credentials-section">';
        $html .= '<h6>' . do_not_sanitize($label) . '</h6>';
        
        if ($description) {
            $html .= '<p class="text-muted small">' . $description . '</p>';
        }
        
        // Console fields in rows
        $html .= '<div class="row">';
        $html .= '<div class="col-md-6">';
        $html .= $this->render_inline_field('console_host', _('Host'), 'text', $defaults['host'] ?? 'localhost', true);
        $html .= '</div>';
        $html .= '<div class="col-md-6">';
        $html .= $this->render_inline_field('console_port', _('Port'), 'number', $defaults['port'] ?? '8404', true);
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '<div class="row">';
        $html .= '<div class="col-md-6">';
        $html .= $this->render_inline_field('console_user', _('Username'), 'text', $defaults['user'] ?? 'admin', true);
        $html .= '</div>';
        $html .= '<div class="col-md-6">';
        $html .= $this->render_inline_field('console_pass', _('Password'), 'password', $defaults['pass'] ?? '', true);
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render database credentials fields
     */
    private function render_db_credentials() {
        $label = $this->field_config['label'] ?? _('Database credentials');
        $defaults = $this->field_config['default'] ?? array();
        $description = $this->field_config['description'] ?? '';
        $use_default = $this->field_config['use_default'] ?? false;
        $is_main_db = strpos($this->field_id, 'robust.DatabaseService') !== false;
        
        $html = '<div class="credentials-section">';
        $html .= '<h6>' . do_not_sanitize($label) . '</h6>';
        
        if ($description) {
            $html .= '<p class="text-muted small">' . $description . '</p>';
        }
        
        // Add "Use default" checkbox for non-main databases
        if (!$is_main_db) {
            $use_default_checked = $use_default ? 'checked' : '';
            $fields_style = $use_default ? 'style="display: none;"' : '';
            
            $html .= '<div class="form-check mb-3">';
            $html .= '<input class="form-check-input" type="checkbox" id="' . $this->field_id . '_use_default" ';
            $html .= 'name="' . $this->field_id . '_use_default" value="1" ' . $use_default_checked . ' ';
            $html .= 'onchange="toggleDbCredentials(\'' . $this->field_id . '\')">';
            $html .= '<label class="form-check-label" for="' . $this->field_id . '_use_default">';
            $html .= _('Use default (same as main database)');
            $html .= '</label>';
            $html .= '</div>';
            
            $html .= '<div class="db-credentials-fields" id="' . $this->field_id . '_fields" ' . $fields_style . '>';
        }
        
        // Database fields in rows
        $html .= '<div class="row">';
        $html .= '<div class="col-md-6">';
        $html .= $this->render_inline_field('db_host', _('Host'), 'text', $defaults['host'] ?? '', true);
        $html .= '</div>';
        $html .= '<div class="col-md-6">';
        $html .= $this->render_inline_field('db_port', _('Port'), 'number', $defaults['port'] ?? '3306', true);
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '<div class="row">';
        $html .= '<div class="col-md-6">';
        $html .= $this->render_inline_field('db_name', _('Database'), 'text', $defaults['name'] ?? '', true);
        $html .= '</div>';
        $html .= '<div class="col-md-6">';
        $html .= $this->render_inline_field('db_user', _('Username'), 'text', $defaults['user'] ?? '', true);
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '<div class="row">';
        $html .= '<div class="col-md-6">';
        $html .= $this->render_inline_field('db_pass', _('Password'), 'password', $defaults['pass'] ?? '', true);
        $html .= '</div>';
        $html .= '</div>';
        
        if (!$is_main_db) {
            $html .= '</div>'; // Close db-credentials-fields
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render .ini files upload fields
     */
    private function render_ini_files() {
        $field_id = $this->field_id;
        $label = $this->field_config['label'] ?? _('.ini files');
        $description = $this->field_config['description'] ?? '';
        $required = $this->field_config['required'] ?? false ? 'required' : '';
        
        $html = '<div class="ini-files-section">';
        $html .= '<h6>' . do_not_sanitize($label) . '</h6>';
        
        if ($description) {
            $html .= '<p class="text-muted small">' . $description . '</p>';
        }
        
        // Only Robust.HG.ini for grid configuration, OpenSim.ini comes later for regions
        $html .= sprintf(
            '<fieldset class="input-group">
                <label class="input-group-text" for="%1$s[path]">%2$s</label>
                <input type="text" class="form-control" id="%1$s[path]" name="%1$s[path]" value="%3$s" placeholder="%4$s" %5$s oninput="toggleMutualExclusive(this)" onchange="toggleMutualExclusive(this)">
                <label class="input-group-text bg-transparent border-0" for="%1$s[upload]">%6$s</label>
                <input type="file" class="form-control" id="%1$s[upload]" name="%1$s[upload]" accept=".ini" %7$s onchange="toggleMutualExclusive(this)">
                <button type="button" class="btn btn-outline-secondary" onclick="clearInputField(\'%1$s[upload]\')">
                    <i class="bi bi-x"></i>
                </button>
            </fieldset>',
            $field_id,
            _('On server'),
            $this->get_field_value()['path'] ?? '',
            _('e.g. /opt/opensim/bin/Robust.HG.ini'),
            $required,
            _('Or upload'),
            $required
        );
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render inline form field
     */
    private function render_inline_field($name, $label, $type, $value = '', $required = false, $readonly = false, $accept = '') {
        $required_attr = $required ? 'required' : '';
        $readonly_attr = $readonly ? 'readonly' : '';
        $accept_attr = $accept ? 'accept="' . do_not_sanitize($accept) . '"' : '';
        $required_mark = $required ? '<span class="text-danger">*</span>' : '';
        
        $html = '<div class="form-group mb-2">';
        $html .= '<label class="form-label" for="' . $name . '">' . do_not_sanitize($label) . $required_mark . '</label>';
        $html .= '<input type="' . $type . '" class="form-control" id="' . $name . '" name="' . $name . '" ';
        $html .= 'value="' . do_not_sanitize($value) . '" ' . $required_attr . ' ' . $readonly_attr . ' ' . $accept_attr . '>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Get field value with proper fallback
     */
    private function get_field_value() {
        return $_POST[$this->field_id] ?? $this->field_config['default'] ?? '';
    }
    
    /**
     * Check if a method should be active based on available credentials
     */
    private function is_method_active($method_key, $method_fields) {
        // Check against default value and current value
        $current_value = $this->get_field_value();
        if ($current_value === $method_key) {
            return true;
        }
        
        // Check if this is the default value
        $default_value = $this->field_config['default'] ?? '';
        return ($default_value === $method_key);
    }
}
