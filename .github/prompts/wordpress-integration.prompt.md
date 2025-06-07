# WordPress Integration Rules

When working on files in the `wordpress/` directory:

- Use WordPress functions and hooks appropriately
- Class names: `W4OS_*` for main classes, `w4os_*` for functions
- Handle WordPress-specific features (admin pages, settings API, etc.)
- Convert between WordPress data and engine data contracts
- Use WordPress coding standards

## Data Flow Patterns
```php
// WordPress collects data
$wp_options = get_option('w4os_settings');

// Convert to generic contract
$engine_data = array(
    'values' => $migration_data,
    'return_url' => admin_url('admin.php?page=w4os'),
    'timestamp' => time()
);

// Pass to engine via session
$_SESSION['wizard_data'] = $engine_data;
```
