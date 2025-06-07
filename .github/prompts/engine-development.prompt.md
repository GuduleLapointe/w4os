# Engine Development Rules

When working on files in the `engine/` directory:

- Use only generic PHP - no WordPress functions
- Class names: `Engine_*`, `Installation_*`, etc.
- No `w4os` references in variable names or constants
- Settings should work with .ini files, not WordPress options
- Use dependency injection for external data
- All methods should work standalone without framework context

## Example Patterns
```php
// Good - Generic
Engine_Settings::get('database_host')
$wizard_data = $_SESSION['wizard_data']

// Bad - WordPress specific  
get_option('w4os_database_host')
$wp_data = $_SESSION['w4os_wizard_data']
```
