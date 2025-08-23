# OpenSim Engine Development Rules

Although this library is primarily designed to be used with W4OS WordPress plugin 
and the OpenSim Helpers project, it can also be used independently. Therefore, it 
is important to follow the rules below to ensure that the code remains generic 
and does not depend on WordPress or the Helpers library.

**The Engine is a pure library responsible for data manipulation and storage only.**
It should never accept HTTP input nor provide HTTP output. All requests must be made 
by calling internal methods and functions, and all responses must be returned by 
those methods and functions. It is not responsible for user interactions.

When working on files in the `engine/` directory:

- Use only generic PHP - no WordPress functions, no Helpers functions
- No `w4os`, `wordpress` or `helpers` references in variable names or constants
- Class names: `Engine_*`, `OpenSim_*`
- Settings use only `Engine_Settings` class
- Pass data as parameters instead of accessing globals directly
- All methods should work standalone without the need of usual parents like w4os plugin or Helpers

## Example Patterns
```php
// Good - Generic
Engine_Settings::get('database_host')
$wizard_data = $_SESSION['opensim_engine']['received_data']

// Good - Pass data explicitly
function process_avatar($avatar_data, $db_config) {
    // Work with provided data
}

// Bad - WordPress specific  
get_option('w4os_database_host')
$wp_data = $_SESSION['w4os_wizard_data']

// Bad - Hidden dependencies
function process_avatar() {
    global $wpdb;
    $config = get_option('w4os_config');
    // Accessing globals makes code unpredictable
}

// Bad - Direct HTTP handling (Engine should NOT do this)
function handle_avatar_request() {
    $avatar_data = $_POST['avatar'];
    echo json_encode($result);
    // Engine should never handle HTTP directly
}

// Good - Pure data processing (Engine SHOULD do this)
function process_avatar_data($avatar_data) {
    // Process and return data only
    return $processed_data;
}
```

## Engine vs Helpers Responsibility

- **Engine:** Data processing, database operations, OpenSim protocol
- **Helpers:** HTTP handling, form processing, HTML output, user interface
