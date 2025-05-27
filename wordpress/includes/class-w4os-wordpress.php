<?php
/**
 * Main WordPress Integration Class
 * 
 * Manages WordPress-specific functionality and acts as a bridge
 * between WordPress and the engine.
 */

class W4OS_WordPress
{
    private static $instance = null;
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct()
    {
        $this->init();
    }
    
    private function init()
    {
        // WordPress hooks
        add_action('init', [$this, 'wordpress_init']);
        add_action('admin_init', [$this, 'admin_init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_public_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        
        // Plugin lifecycle hooks
        register_activation_hook(W4OS_PLUGIN_DIR . 'w4os.php', [$this, 'activate']);
        register_deactivation_hook(W4OS_PLUGIN_DIR . 'w4os.php', [$this, 'deactivate']);
    }
    
    public function wordpress_init()
    {
        // WordPress initialization code
        load_plugin_textdomain('w4os', false, dirname(plugin_basename(W4OS_PLUGIN_DIR . 'w4os.php')) . '/languages/');
    }
    
    public function admin_init()
    {
        // Admin initialization code
    }
    
    public function enqueue_public_scripts()
    {
        // Public scripts and styles
    }
    
    public function enqueue_admin_scripts()
    {
        // Admin scripts and styles
    }
    
    public function activate()
    {
        // Plugin activation
        flush_rewrite_rules();
    }
    
    public function deactivate()
    {
        // Plugin deactivation
        flush_rewrite_rules();
    }
}