<?php
/**
 * WordPress Installation Wizard Integration
 * 
 * Integrates the Engine Installation_Wizard with WordPress admin interface
 */

class W4OS3_Installation_Wizard {
    private $wizard;
    
    public function __construct() {
        $this->wizard = new Installation_Wizard();
        
        // Add admin menu hook
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Handle AJAX requests
        add_action('wp_ajax_w4os_installation_step', array($this, 'handle_ajax_step'));
    }
    
    /**
     * Add installation wizard to WordPress admin menu
     */
    public function add_admin_menu() {
        // Only show if not configured or if explicitly requested
        if (!Engine_Settings::configured() || isset($_GET['force_wizard'])) {
            add_submenu_page(
                'w4os',
                'Installation Wizard',
                'Installation Wizard',
                'manage_options',
                'w4os_installation_wizard',
                array($this, 'render_admin_page')
            );
        }
    }
    
    /**
     * Render the WordPress admin page
     */
    public function render_admin_page() {
        // Process form if submitted
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            $this->process_form();
        }
        
        $current_step = $this->wizard->get_current_step();
        
        if (!$current_step) {
            $this->render_completion_page();
            return;
        }
        
        $this->render_wizard_page($current_step);
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
    
    /**
     * Render the wizard page in WordPress admin
     */
    private function render_wizard_page($step) {
        $wizard_data = $this->wizard->get_wizard_data();
        $progress = $this->wizard->get_progress();
        ?>
        
        <div class="wrap">
            <h1>OpenSimulator Installation Wizard</h1>
            
            <?php settings_errors('w4os_wizard'); ?>
            
            <div class="card">
                <div class="card-header">
                    <h2><?php echo esc_html($step['title']); ?></h2>
                    <div style="background: #f0f0f0; height: 10px; border-radius: 5px; margin: 10px 0;">
                        <div style="background: #0073aa; height: 100%; width: <?php echo $progress; ?>%; border-radius: 5px;"></div>
                    </div>
                    <p><small>Step <?php echo $step['number']; ?> of <?php echo $step['total']; ?></small></p>
                </div>
                
                <div style="padding: 20px;">
                    <?php if (!empty($step['description'])): ?>
                        <p><?php echo esc_html($step['description']); ?></p>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <?php wp_nonce_field('w4os_installation_wizard'); ?>
                        <input type="hidden" name="action" value="next_step">
                        
                        <table class="form-table" role="presentation">
                            <?php $this->render_step_fields($step, $wizard_data); ?>
                        </table>
                        
                        <div style="display: flex; justify-content: space-between; margin-top: 20px;">
                            <div>
                                <?php if ($step['number'] > 1): ?>
                                    <button type="submit" name="action" value="previous_step" class="button">
                                        ← Previous
                                    </button>
                                <?php endif; ?>
                            </div>
                            
                            <div>
                                <button type="submit" name="action" value="reset" class="button" 
                                        onclick="return confirm('Are you sure you want to reset the wizard?')">
                                    Reset
                                </button>
                                
                                <button type="submit" class="button button-primary">
                                    <?php if ($step['number'] < $step['total']): ?>
                                        Next →
                                    <?php else: ?>
                                        Complete Installation
                                    <?php endif; ?>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <?php
    }
    
    /**
     * Render form fields for WordPress admin
     */
    private function render_step_fields($step, $wizard_data) {
        if (empty($step['fields'])) {
            return;
        }
        
        foreach ($step['fields'] as $field_key => $field_config) {
            $value = $wizard_data[$field_key] ?? ($field_config['default'] ?? '');
            $required = !empty($field_config['required']) ? 'required' : '';
            
            echo '<tr>';
            echo '<th scope="row">';
            echo '<label for="' . $field_key . '">' . esc_html($field_config['label']);
            if (!empty($field_config['required'])) {
                echo ' <span style="color: red;">*</span>';
            }
            echo '</label>';
            echo '</th>';
            echo '<td>';
            
            switch ($field_config['type']) {
                case 'text':
                    $placeholder = !empty($field_config['placeholder']) ? 'placeholder="' . esc_attr($field_config['placeholder']) . '"' : '';
                    echo '<input type="text" class="regular-text" id="' . $field_key . '" name="' . $field_key . '" value="' . esc_attr($value) . '" ' . $placeholder . ' ' . $required . '>';
                    break;
                    
                case 'password':
                    echo '<input type="password" class="regular-text" id="' . $field_key . '" name="' . $field_key . '" value="' . esc_attr($value) . '" ' . $required . '>';
                    break;
                    
                case 'number':
                    echo '<input type="number" class="small-text" id="' . $field_key . '" name="' . $field_key . '" value="' . esc_attr($value) . '" ' . $required . '>';
                    break;
                    
                case 'radio':
                    if (!empty($field_config['options'])) {
                        foreach ($field_config['options'] as $option_key => $option_label) {
                            $checked = ($value === $option_key) ? 'checked' : '';
                            echo '<label>';
                            echo '<input type="radio" name="' . $field_key . '" value="' . $option_key . '" ' . $checked . ' ' . $required . '> ';
                            echo esc_html($option_label);
                            echo '</label><br>';
                        }
                    }
                    break;
                    
                case 'file':
                    $accept = !empty($field_config['accept']) ? 'accept="' . esc_attr($field_config['accept']) . '"' : '';
                    echo '<input type="file" id="' . $field_key . '" name="' . $field_key . '" ' . $accept . ' ' . $required . '>';
                    break;
            }
            
            if (!empty($field_config['help'])) {
                echo '<p class="description">' . esc_html($field_config['help']) . '</p>';
            }
            
            echo '</td>';
            echo '</tr>';
        }
    }
    
    /**
     * Render completion page
     */
    private function render_completion_page() {
        ?>
        <div class="wrap">
            <h1>Installation Complete!</h1>
            
            <div class="notice notice-success">
                <p><strong>Congratulations!</strong> Your OpenSimulator helpers have been successfully configured.</p>
            </div>
            
            <div class="card">
                <div style="padding: 20px; text-align: center;">
                    <h2>✓ Installation Complete</h2>
                    <p>Your OpenSimulator configuration has been saved and is ready to use.</p>
                    
                    <p>
                        <a href="<?php echo admin_url('admin.php?page=w4os_settings'); ?>" class="button button-primary">
                            Go to Settings
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=w4os_installation_wizard&force_wizard=1'); ?>" class="button">
                            Run Wizard Again
                        </a>
                    </p>
                </div>
            </div>
        </div>
        <?php
    }
}

// Initialize if in WordPress admin
if (is_admin()) {
    new W4OS3_Installation_Wizard();
}
