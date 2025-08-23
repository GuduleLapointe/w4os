# OpenSim Helpers Development Rules

This library is primarily designed to be used independantly.

Although it is also used by W4OS WordPress plugin, it is important to follow
the rules below to ensure that the code remains generic and does not depend 
on WordPress or W4OS to function.

The Helpers layer handles external requests and responses output (including HTML, JSON, XML, XMLRPC, txt...).
All data manipulation, validation, storage, db connections, are made by the Engine layer.

The webui provided by he Helpers is strictly minimal, generic and light. It is limited to 
the output required by viewers requests (destination guide, user profile, ...) and 
a very few necessary pages (installation wizard). Complex web integration must be 
done via a separate project (e.g. w4os WordPress plugin) using the Helpers as on Engine
libraries.

All html output must include boostrap styling. Javascript and CSS must be strictly
limited to what cannot be achieved with standard HTML5 features or boostrap.

When working on files in this `helpers/` directory:

- Use only generic PHP - no WordPress functions
- Declare only classes, objects, functions etc. specific to helpers.
- Generic classes, object,s functions and properties must be added in Engine subfolder engine/
- No `w4os` or `wordpress` references in variable names or constants
- Class names: `Helpers_*`, `OpenSim_Helpers_*`
- Settings use only `Engine_Settings` class from engine/
- Pass data as parameters instead of accessing globals directly
- All methods should work standalone without the need of usual parents like w4os plugin or Helpers

## Example Patterns
```php
// Good - Generic
Engine_Settings::get('database_host')
$wizard_data = $_SESSION['opensim_helpers']['received_data']

// Good - Pass data explicitly (dependency injection)
function process_user($user_data, $settings) {
    // Work with provided data
}

// Bad - WordPress specific  
get_option('w4os_database_host')
$wp_data = $_SESSION['w4os_wizard_data']

// Bad - Access globals directly
function process_user() {
    global $wpdb;
    $settings = get_option('w4os_settings');
    // Hidden dependencies
}
```
