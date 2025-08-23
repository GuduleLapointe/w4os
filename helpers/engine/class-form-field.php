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

    
    protected $type = 'text';
    protected $label;
    protected $required;
    protected $required_mark;
    protected $value;
    protected $placeholder;
    protected $description;
    protected $options;
    protected $input_classes = array();
    protected $attributes = array();

    public const REQUIRED_MARK =  ' <i class="bi bi-asterisk text-danger"></i> ';
    public const SELECT_ARROW_MARK = ' › ';

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

        $this->type = $this->field_config['type'] ?? 'text';
        $this->label = $this->field_config['label'] ?? '';
        $this->required = $this->field_config['required'] ?? false;
        $this->value = $this->get_field_value();
        $this->placeholder = $this->field_config['placeholder'] ?? null;
        $this->description = $this->field_config['description'] ?? null;
        $this->options = $this->field_config['options'] ?? array();
        $this->required_mark = $this->required ? self::REQUIRED_MARK : '';

        $input_classes = $this->field_config['input_classes'] ?? array();
        $input_classes = is_string($input_classes) ? [ $input_classes ] : $input_classes;
        $this->input_classes = array_merge( $this->get_input_type_classes(), $input_classes );

        $this->set_input_attributes();
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
        $type = $this->field_config['type'] ?? 'text';
        
        // Handle special field types that don't follow standard pattern
        switch ($type) {
            case 'select-nested':
                return $this->render_select_nested();
                
            case 'select-accordion':
                return $this->render_select_accordion();
                
            case 'console_credentials':
                return $this->render_console_credentials();
                
            case 'db_credentials':
                return $this->render_db_credentials();
                
            case 'ini_files':
                return $this->render_ini_files();
                
            case 'field-group':
                return $this->render_group();
                
            default:
                if(!empty($this->field_config['fields'])) {
                    // Type not set to field-group, but has children, so it's a field group
                    return $this->render_group();
                }
                // Use standard rendering for all other types
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
        $required = $this->field_config['required'] ?? false;
        $value = $this->get_field_value();
        $placeholder = $this->field_config['placeholder'] ?? '';
        $description = $this->field_config['description'] ?? '';
        $options = $this->field_config['options'] ?? array();
        $required_mark = $this->required_mark;
        
        $field_container_classes[] = "form-group mb-3";
        $columns = $this->field_config['columns'] ?? null;
        if(is_integer($columns) && $columns > 0 && $columns <= 12) {
            $field_container_classes[] = 'col-auto col-lg-' . $columns;
        }

        $html = sprintf(
            '<div class="%s">',
            implode(' ', $field_container_classes),
        );

        if ($label) {
            $html .= sprintf(
                '<label class="form-label" for="%s">%s%s</label>',
                $this->field_id,
                opensim_sanitize_basic_html($label),
                $this->required_mark,
            );
        }
        
        // // Add Bootstrap classes based on field state
        if ($this->has_error) {
            $this->input_classes[] = 'is-invalid';
        }

        $input_classes = $this->input_classes;
        
        // Render field based on type
        switch ($type) {
            case 'textarea':
                $html .= $this->render_textarea();
                break;
                
            case 'select':
                $html .= $this->render_select();
                break;
                
            case 'select2':
                $html .= $this->render_select2();
                break;
                
            case 'radio':
                $html .= $this->render_radio();
                break;
                
            case 'checkbox':
                $html .= $this->render_checkbox();
                break;
                
            case 'switch':
                $html .= $this->render_switch();
                break;
                
            case 'file':
                $html .= $this->render_file();
                break;
                
            case 'hidden':
                // For hiddent file, we don't want the container, return only field code
                return $this->render_hidden();
                
            case 'color':
                $html .= $this->render_color();
                break;
                
            default:
                // Handle all HTML5 input types: text, email, url, tel, password, number, date, time, datetime-local, month, week, color, range, search
                $html .= $this->render_input();
                break;
        }
        
        // Add description
        if (!empty($description)) {
            $html .= '<div class="form-text">' . opensim_sanitize_html($description) . '</div>';
        }
        
        $html .= '</div>'; // End form-group
        
        return $html;
    }
    
    /**
     * Get CSS classes for input elements
     */
    private function get_input_type_classes() {
        switch ($this->type) {
            case 'checkbox':
            case 'radio':
                return ['form-check-input'];
            case 'select':
            case 'select2':
                return ['form-select'];
            case 'file':
                return ['form-control'];
            case 'range':
                return ['form-range'];
            case 'color':
                return ['form-control-color'];
            default:
                return ['form-control'];
        }
    }
    
    /**
     * Render textarea element
     */
    private function render_textarea() {
        $input_classes = $this->input_classes ?? array();
        $value = $this->value ?? null;
        $placeholder = $this->placeholder ?? null;
        $required = $this->required ?? false;
        $attributes = $this->input_attributes ?? array();

        $rows = $this->field_config['rows'] ?? 3;
        
        $html = sprintf(
            '<textarea id="%1$s" name="%1$s" class="%2$s" rows="%3$d" placeholder="%4$s" %5$s>%6$s</textarea>',
            $this->field_id,
            implode(' ', $input_classes),
            $rows,
            opensim_esc_attr($placeholder),
            implode(' ', $attributes),
            opensim_esc_html($value)
        );
        return $html;
    }
    
    /**
     * Render select element
     */
    private function render_select() {
        $type = $this->type ?? 'select';
        $multiple = $this->field_config['multiple'] ?? false;
        $attributes = $this->input_attributes;
        $value = $this->value ?? null;
        $required = $this->required ?? false;
        $options = $this->options ?? array();

        $html = sprintf(
            '<select id="%s" name="%s" class="%s" %s>',
            $this->field_id,
            $this->field_id . ($multiple ? '[]' : ''),
            implode(' ', $this->input_classes),
            implode($attributes),
        );
        
        $placeholder = self::SELECT_ARROW_MARK . (empty($this->placeholder) ? ( $multiple ? _('Select options') : _('Select an option') ) : $this->placeholder);

        // Add empty option if not required and not multiple
        if (!$required && !$multiple) {
            $html .= sprintf(
                '<option value="">%s</option>',
                $placeholder,
            );
        }

        $selected_values = $multiple && is_array($value) ? $value : array($value);
        
        foreach ($options as $option_value => $option_label) {
            $selected = in_array($option_value, $selected_values) ? ' selected' : '';
            $html .= '<option value="' . opensim_esc_attr($option_value) . '"' . $selected . '>' . opensim_esc_html($option_label) . '</option>';
        }
        
        $html .= '</select>';
        return $html;
    }
    
    /**
     * Render select2 dropdown with search
     */
    private function render_select2() {
        $multiple = $this->field_config['multiple'] ?? false;
        $attributes = $this->input_attributes ?? array();
        $input_classes = $this->input_classes ?? array();
        $options = $this->options ?? array();
        $required = $this->required ?? false;
        $value = $this->value ?? null;

        // Add select2 class
        $input_classes[] = 'form-control select2';

        $input_classes = array_unique($input_classes);

        $html = sprintf(
            '<select id="%s" name="%s" class="%s" %s>',
            $this->field_id,
            $this->field_id . ($multiple ? '[]' : ''),
            implode(' ', $input_classes),
            implode(' ', $attributes),
        );
        
        $placeholder = self::SELECT_ARROW_MARK . (empty($this->placeholder) ? ( $multiple ? _('Select options') : _('Select an option') ) : $this->placeholder);

        // Add empty option if not required and not multiple
        if (!$required && !$multiple) {
            $html .= sprintf(
                '<option value="">%s</option>',
                $placeholder,
            );
        }
        
        // $selected_values = $multiple && is_array($value) ? $value : array($value);
        $selected_values = is_array($value) ? $value : array($value);
        
        foreach ($options as $option_value => $option_label) {
            $selected = in_array($option_value, $selected_values) ? ' selected' : '';
            $html .= '<option value="' . opensim_esc_attr($option_value) . '"' . $selected . '>' . opensim_esc_html($option_label) . '</option>';
        }
        
        $html .= '</select>';
        
        // Add select2 initialization script
        $html .= '<script>
        document.addEventListener("DOMContentLoaded", function() {
            if (typeof jQuery !== "undefined" && jQuery.fn.select2) {
                jQuery("#' . $this->field_id . '").select2({
                    placeholder: "' . $placeholder . '",
                    allowClear: ' . ($required ? 'false' : 'true') . '
                });
            }
        });
        </script>';

        return $html;
    }
    
    /**
     * Render radio buttons
     */
    private function render_radio() {
        $value = $this->value ?? null;
        $options = $this->options ?? array();
        $required = $this->required ?? false;
        $attributes = $this->input_attributes;
        $input_classes = $this->input_classes ?? array();
        $input_classes = ['form-check-input'];
        

        $html = '';
        foreach ($options as $option_value => $option_label) {
            $html .= sprintf(
                '<div class="form-check">
                    <input id="%1$s_%2$s" name="%1$s" class="%3$s" type="radio" value="%4$s" %5$s %6$s>
                    <label class="form-check-label" for="%1$s_%2$s">%7$s</label>
                </div>',
                $this->field_id,
                $option_value,
                implode(' ', $input_classes),
                opensim_esc_attr($option_value),
                ($value === $option_value) ? ' checked' : '',
                implode(' ', $attributes),
                opensim_sanitize_basic_html($option_label)
            );
        }
        return $html;
    }
    
    /**
     * Render checkbox options
     */
    private function render_checkbox() {
        $value = $this->value ?? array();
        $values = is_array($value) ? $value : array($value);
        $options = $this->options ?? array();
        $attributes = $this->input_attributes;

        $html = '';
        
        foreach ($options as $option_value => $option_label) {
            $html .= sprintf(
            '<div class="form-check">
                <input class="form-check-input" type="checkbox" id="%1$s_%2$s" name="%1$s[]" value="%3$s" %4$s %5$s>
                <label class="form-check-label" for="%1$s_%2$s">%6$s</label>
            </div>',
            $this->field_id,
            opensim_esc_attr($option_value),
            opensim_esc_attr($option_value),
            in_array($option_value, $values) ? ' checked' : '',
            implode(' ', $attributes),
            opensim_sanitize_basic_html($option_label)
            );
        }
        return $html;
    }
    
    /**
     * Render switch toggle
     */
    // private function render_switch($value) {
    private function render_switch() {
        $value = $this->value ?? false;
        $attributes = $this->input_attributes;

        $html = sprintf(
            '<div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="%1$s" name="%1$s" value="1" %2$s %3$s>
            </div>',
            $this->field_id,
            $value ? 'checked' : '',
            implode(' ', $attributes)
        );
        return $html;
    }
    
    /**
     * Render file input
     */
    private function render_file() {
        $input_classes = $this->input_classes ?? array();
        $required = $this->required ?? false;
        $multiple = $this->field_config['multiple'] ?? false;
        $attributes = $this->input_attributes;

        $accept = $this->field_config['accept'] ?? '';
        if ($accept) {
            $attributes[] = 'accept="' . opensim_esc_attr($accept) . '"';
        }
        
        $html = sprintf(
            '<div class="input-group">
                <input type="file" class="%s" id="%s" name="%s" %s %s>
                <button type="button" class="btn btn-outline-secondary" onclick="clearFileInput(\'%s\')" title="%s">
                    <i class="bi bi-x"></i>
                </button>
            </div>',
            implode(' ', $input_classes),
            $this->field_id,
            $this->field_id . ($multiple ? '[]' : ''),
            $required ? 'required' : '',
            implode(' ', $attributes),
            $this->field_id,
            _('Clear file selection')
        );
        
        return $html;
    }
    
    /**
     * Render color input with enhanced display
     */
    private function render_color() {
        $input_classes = $input_classes ?? array();
        $value = $value ?? null;
        $placeholder = $placeholder ?? '#000000'; // Default placeholder
        $required = $required ?? false;
        $attributes = $this->input_attributes ?? array();
        $attributes = $this->get_input_attributes();
        $color_value = $value ?: '#000000';
        
        $html = '<div class="input-group">';
        $html .= sprintf(
            '<input type="color" class="form-control form-control-color" id="%1$s" name="%1$s" value="%2$s" %3$s %4$s onchange="updateColorValue(\'%1$s\')">',
            $this->field_id,
            opensim_esc_attr($color_value),
            $required ? 'required' : '',
            implode(' ', $attributes)
        );
        $html .= sprintf(
            '<input type="text" class="form-control" id="%1$s_text" value="%2$s" pattern="^#[0-9A-Fa-f]{6}$" placeholder="#000000" onchange="updateColorPicker(\'%1$s\')">',
            $this->field_id,
            opensim_esc_attr($color_value)
        );
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render standard input element
     */
    private function render_input() {
        
        if ($this->placeholder) {
            $this->input_attributes[] = 'placeholder="' . opensim_esc_attr($this->placeholder) . '"';
        }
        
        $html = sprintf(
            '<input id="%1$s" name="%1$s" type="%2$s" class="%3$s" value="%4$s" %5$s>',
            $this->field_id,
            $this->type,
            implode(' ', $this->input_classes),
            $this->value,
            $this->get_input_attributes(),
        );

        return $html;
    }

    /**
     * Render hidden field
     */
    private function render_hidden() {
        $html = sprintf(
            '<input type="hidden" id="%s" name="%s" value="%s">',
            $this->field_id,
            $this->field_id,
            opensim_esc_attr($this->value)
        );
        return $html;
    }
    
    /**
     * Get common attributes for form elements
     */
    private function set_input_attributes() {
        $attributes = $this->input_attributes ?? array();

        if (!empty($this->field_config['readonly'])) {
            $attributes[] = 'readonly';
        }
        if (!empty($this->field_config['disabled'])) {
            $attributes[] = 'disabled';
        }
        if ($this->field_config['multiple'] ?? false) {
            $attributes[] = 'multiple';
        }
        if (!empty($this->field_config['autofocus'])) {
            $attributes[] = 'autofocus';
        }
        if (!empty($this->field_config['required'])) {
            $attributes[] = 'required';
        }
        if (!empty($this->field_config['disabled'])) {
            $attributes[] = 'disabled';
        }

        // Add type-specific attributes
        switch ($this->type) {
            case 'number':
            case 'range':
                $options = $this->options ?? array();
                if (isset($options['min'])) {
                    $attributes['min'] = 'min="' . $options['min'] . '"';
                }
                if (isset($options['max'])) {
                    $attributes['max'] = 'max="' . $options['max'] . '"';
                }
                if (isset($options['step'])) {
                    $attributes['step'] = 'step="' . $options['step'] . '"';
                }
                break;
                
            case 'text':
            case 'email':
            case 'url':
            case 'tel':
            case 'search':
            case 'password':
                $maxlength = $this->field_config['maxlength'] ?? null;
                $minlength = $this->field_config['minlength'] ?? null;
                $pattern = $this->field_config['pattern'] ?? null;
                if ($maxlength) {
                    $attributes['maxlength'] = 'maxlength="' . $maxlength . '"';
                }
                if ($minlength) {
                    $attributes['minlength'] = 'minlength="' . $minlength . '"';
                }
                if ($pattern) {
                    $attributes['pattern'] = 'pattern="' . opensim_esc_attr($pattern) . '"';
                }
                break;
        }
        
        if(is_array($attributes)) {
            $this->input_attributes = $attributes;
            return implode(' ', $attributes);
        }

        error_log('[ERROR] input_attributes is not an array: ' . print_r($attributes));
    }

    private function get_input_attributes() {
        return implode(' ', $this->input_attributes);
    }
    
    /**
     * Render field group with nested fields
     */
    private function render_group() {
        $label = $this->field_config['label'] ?? '';
        $description = $this->field_config['description'] ?? '';
        $fields = $this->field_config['fields'] ?? array();
        
        $html = '<fieldset class="field-group mb-4 card px-4 py-2">';
        if ($label) {
            $html .= sprintf(
                '<legend class="field-group-legend h5 ps-2 mb-0">%s</legend>',
                opensim_sanitize_basic_html($label)
            );
        }
        if ($description) {
            $html .= sprintf(
                '<div class="field-group-description text-muted mb-3">%s</div>',
                opensim_sanitize_html($description)
            );
        }
        
        $html .= '<div class="row">';
        // Render nested fields
        foreach ($fields as $nested_field_id => $nested_field_config) {
            $nested_field = new OpenSim_Field($nested_field_id, $nested_field_config);
            $html .= $nested_field->render();
        }
        $html .= '</div>';        
        $html .= '</fieldset>';
        return $html;
    }
    
    /**
     * Render select field with accordion-style items for some options
     */
    private function render_select_nested() {
        $label = $this->field_config['label'] ?? '';
        $options = $this->field_config['options'] ?? array();
        $required = $this->field_config['required'] ?? false;
        $value = $this->get_field_value();
        
        $required_mark = $this->required_mark;

        $html = '<div class="config-choice mb-4">';
        if ($label) {
            $html .= sprintf(
            '<h5 class="mb-3">%s%s</h5>',
            opensim_sanitize_basic_html($label),
            $required_mark
            );
        }

        // Hidden input to store the selected value
        $html .= sprintf(
            '<input type="hidden" name="%1$s" id="%1$s" value="%2$s"%3$s>',
            $this->field_id,
            opensim_esc_attr($value),
            $required ? ' required' : ''
        );
        
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
            
            $html .= sprintf(
                '<div class="%s" onclick="selectChoice(\'%s\', \'%s\')" style="cursor: pointer;">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                %s
                                <span class="fw-semibold">%s</span>
                            </div>
                        </div>
                    </div>',
                $card_classes,
                $this->field_id,
                $option_value,
                self::render_icon($icon),
                opensim_sanitize_basic_html($option_label)
            );
            
            // Render sub-fields if they exist
            if (!empty($sub_fields || ! empty( $option_description))) {
                $display_class = $is_selected ? '' : 'd-none';
                $html .= sprintf(
                    '<div class="choice-sub-fields border-top border-primary bg-light px-4 p-4  %s" id="%s_%s_fields">',
                    $display_class,
                    $this->field_id,
                    $option_value
                );

                // Show option description
                if ($option_description) {
                    // $desc_margin= empty($sub_fields) ? 'mb-O' : 'mb-3';
                    $html .= sprintf(
                        '<div class="%s text-muted">%s</div>',
                        $desc_margin ?? '',
                        opensim_sanitize_basic_html($option_description)
                    );
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
        $html .= '</div>';
        
        return $html;
    }
    
    private function render_icon($icon) {
        if (empty($icon)) {
            return '';
        }
        if( strpos($icon, 'bi-') === 0) {
            $icon = '<i class="' . opensim_filter_key($icon) . '"></i>'; 
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
        $required = $this->field_config['required'] ?? false;
        $value = $this->get_field_value();
        
        $required_mark = $this->required_mark;
        
        $html = '<div class="connection-methods mb-4">';
        if ($label) {
            $html .= sprintf(
                '<h5 class="mb-3">%s%s</h5>',
                opensim_sanitize_basic_html($label),
                $required_mark
            );
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
            
            $html .= sprintf(
                '<div class="method-accordion mb-3">
                    <div class="%s" onclick="selectMethod(\'%s\')" style="cursor: pointer;">
                        <div class="d-flex align-items-center">
                            <input type="radio" name="%s" value="%s" %s%s class="me-3">
                            <span class="method-title fw-semibold">%s</span>
                        </div>
                        %s
                    </div>',
                $header_classes,
                $option_value,
                $this->field_id,
                opensim_esc_attr($option_value),
                $checked,
                ($required ? ' required' : ''),
                opensim_sanitize_basic_html($option_label),
                self::render_icon($option_icon ?? '')
            );
            
            $html .= sprintf(
                '<div class="%s" id="%s-body">',
                $body_classes,
                $option_value
            );
            if ($option_description) {
                $html .= sprintf(
                    '<p class="text-muted mb-3">%s</p>',
                    opensim_sanitize_basic_html($option_description)
                );
            }
            
            // Render sub-fields for this accordion item
            foreach ($option_fields as $sub_field_id => $sub_field_config) {
                $sub_field = new OpenSim_Field($sub_field_id, $sub_field_config);
                $html .= $sub_field->render();
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
        
        $html = '<div class="credentials-section my-4">';
        $html .= '<h6>' . opensim_sanitize_basic_html($label) . '</h6>';
        
        if ($description) {
            $html .= '<p class="text-muted small">' . opensim_sanitize_html($description) . '</p>';
        }
        
        // Console fields in rows
        $html .= sprintf(
            '<div class="row">
            <div class="col-md-6">%s</div>
            <div class="col-md-2">%s</div>
            <div class="col-md-6">%s</div>
            <div class="col-md-6">%s</div>
            </div>
            </div>',
            $this->render_inline_field('console_host', _('Host'), 'text', $defaults['host'] ?? 'localhost', true),
            $this->render_inline_field('console_port', _('Port'), 'number', $defaults['port'] ?? '8404', true),
            $this->render_inline_field('console_user', _('Username'), 'text', $defaults['user'] ?? 'admin', true),
            $this->render_inline_field('console_pass', _('Password'), 'password', $defaults['pass'] ?? '', true)
        );
        
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
        
        $html = '<div class="credentials-section my-4">';
        $html .= '<h6>' . opensim_sanitize_basic_html($label) . '</h6>';
        
        if ($description) {
            $html .= '<p class="text-muted small">' . opensim_sanitize_html($description) . '</p>';
        }
        
        // Add "Use default" checkbox for non-main databases
        if (!$is_main_db) {
            $use_default_checked = $use_default ? 'checked' : '';
            $fields_style = $use_default ? 'style="display: none;"' : '';
            
            $html .= sprintf(
                '<div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="%1$s_use_default" name="%1$s_use_default" value="1" %2$s onchange="toggleDbCredentials(\'%1$s\')">
                    <label class="form-check-label" for="%1$s_use_default">%3$s</label>
                </div>
                <div class="db-credentials-fields" id="%1$s_fields" %4$s>',
                $this->field_id,
                $use_default_checked,
                _('Use default (same as main database)'),
                $fields_style
            );
        }
        
        // Database fields in rows
        $html .= sprintf(
            '<div class="row">
                <div class="col-md-4">%s</div>
                <div class="col-md-2">%s</div>
                <div class="col-md-6">%s</div>
                <div class="col-md-6">%s</div>
                <div class="col-md-6">%s</div>
            </div>',
            $this->render_inline_field('db_host', _('Host name'), 'text', $defaults['host'] ?? '', true),
            $this->render_inline_field('db_port', _('Port'), 'number', $defaults['port'] ?? '3306', true),
            $this->render_inline_field('db_name', _('Database name'), 'text', $defaults['name'] ?? '', true),
            $this->render_inline_field('db_user', _('Username'), 'text', $defaults['user'] ?? '', true),
            $this->render_inline_field('db_pass', _('Password'), 'password', $defaults['pass'] ?? '', true)
        );
        
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
        $html .= '<h6>' . opensim_sanitize_basic_html($label) . '</h6>';
        
        if ($description) {
            $html .= '<p class="text-muted small">' . opensim_sanitize_html($description) . '</p>';
        }
        
        // Only Robust.HG.ini for grid configuration, OpenSim.ini comes later for regions
        $html .= sprintf(
            '<fieldset class="input-group mutual-exclusive">
                <label class="input-group-text" for="%1$s[path]">%2$s</label>
                <input type="text" class="form-control" id="%1$s[path]" name="%1$s[path]" value="%3$s" placeholder="%4$s" %5$s onload="%6$s" oninput="%6$s" onchange="%6$s">
                <label class="input-group-text bg-transparent border-0" for="%1$s[upload]">%7$s</label>
                <input type="file" class="form-control" id="%1$s[upload]" name="%1$s[upload]" accept=".ini" %7$s onchange="%6$s">
                <button type="button" class="btn btn-outline-secondary" onclick="clearInputField(\'%1$s[upload]\')">
                    <i class="bi bi-x"></i>
                </button>
            </fieldset>',
            $field_id,
            _('On server'),
            $this->get_field_value()['path'] ?? '',
            _('e.g. /opt/opensim/bin/Robust.HG.ini'),
            $required,
            'toggleMutualExclusive(this)',
            _('Or upload'),
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
        $accept_attr = $accept ? 'accept="' . opensim_esc_attr($accept) . '"' : '';
        $required_mark = $this->required_mark;
        
        $html = sprintf(
            '<div class="form-group mb-2">
            <label class="form-label" for="%1$s">%2$s%3$s</label>
            <input type="%4$s" class="form-control" id="%1$s" name="%1$s" value="%5$s" %6$s %7$s %8$s>
            </div>',
            $name,
            opensim_sanitize_basic_html($label),
            $required_mark,
            $type,
            opensim_esc_attr($value),
            $required_attr,
            $readonly_attr,
            $accept_attr
        );
        
        return $html;
    }
    
    /**
     * Get field value with proper fallbacks
     */
    private function get_field_value() {
        // Check if value is explicitly set in config
        if (isset($this->field_config['value'])) {
            return $this->field_config['value'];
        }
        
        // Check POST data
        if (isset($_POST[$this->field_id])) {
            return $_POST[$this->field_id];
        }
        
        // Check session data
        if (isset($_SESSION['opensim_install_wizard'][$this->field_id])) {
            return $_SESSION['opensim_install_wizard'][$this->field_id];
        }
        
        // Use default value
        return $this->field_config['default'] ?? null;
    }
}
