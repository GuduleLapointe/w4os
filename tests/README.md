# W4OS Plugin Testing

This directory contains tests for the W4OS - OpenSimulator Web Interface plugin using a simple PHP test runner approach.

## Running Tests

To run all tests:

```bash
./tests/run-tests.php
```

To run individual test groups:

```bash
php ./tests/test-00-dependencies-required.php  # System dependencies (PHP extensions) - REQUIRED
php ./tests/test-01-environment-required.php   # WordPress environment tests - REQUIRED
php ./tests/test-02-opensim-required.php       # OpenSimulator environment tests - REQUIRED
php ./tests/test-profile.php                   # Avatar profile functionality (optional)
```

## Test Structure

- **`bootstrap.php`** - Loads WordPress and provides SimpleTest framework
- **`run-tests.php`** - Main test runner that executes all test-*.php files in order with requirement checking
- **`test-00-dependencies-required.php`** - System dependency tests (PHP extensions, server requirements) - REQUIRED
- **`test-01-environment-required.php`** - WordPress environment tests (database, plugins, etc.) - REQUIRED  
- **`test-02-opensim-required.php`** - OpenSimulator environment tests (databases, console, services) - REQUIRED
- **`test-profile.php`** - Avatar profile functionality tests (optional)
- **`test-themes.sh`** - Theme compatibility testing script

*Tests with "-required" suffix must pass for subsequent tests to run. Optional tests like `test-profile.php` will run regardless of other test outcomes.*

## Test Approach

- **No PHPUnit required** - Uses plain PHP with a simple test framework
- **Tests against live WordPress** - Uses your actual WordPress installation instead of a separate test environment
- **Environment-aware** - Tests with all your plugins, configuration, and OpenSim setup intact
- **Requirement-based execution** - Tests with "-required" suffix must pass before subsequent tests run
- **Comprehensive dependency checking** - Validates PHP extensions and system requirements

## Critical Features to Test

Based on the essential functionality of W4OS, these are the priority features that should be tested:

### System Requirements
- [x] **System Dependencies Tests** (implemented in `test-00-dependencies-required.php`):
    - Required PHP extensions (curl, json, simplexml, openssl, PDO, mysqli)
    - Recommended PHP extensions (xmlrpc, imagick, mbstring, intl, gd)
    - PHP version requirements
    - Server configuration requirements

### Basics
- [x] **WordPress Environment Tests** (implemented in `test-01-environment-required.php`):
    - WordPress functions are loaded
    - Database connectivity works
    - W4OS plugin is loaded and active
    - Site configuration is accessible
- [x] **OpenSimulator Environment Tests** (implemented in `test-02-opensim-required.php`):
    - OpenSimulator settings detection and parsing
    - Database connectivity for all configured services
    - Console connectivity (if enabled)

### Avatar & Profile Features  
- [x] **Avatar Profile Tests** (implemented in `test-profile.php`):
    - Avatar profile display functionality
    - Profile data retrieval and formatting
    - Integration with WordPress user system

### Authentication & User Management
- [ ] Login with current WordPress user credentials
- [ ] Login with current OpenSimulator avatar credentials  
- [ ] Register a user with OpenSimulator avatar
- [ ] User profile synchronization between WordPress and OpenSim
- [ ] User change password from wp (synced with OpenSimulator)

### Blocks & Shortcodes
- [ ] Grid info display (`[w4os-grid-info]`)
- [ ] Grid status display (`[w4os-grid-status]`) 
- [ ] Avatar profile display (`[w4os-profile]`)
- [ ] Popular places display
- [ ] Web search functionality

### Helper Services
- [ ] Search places functionality
- [ ] Search events functionality  
- [ ] Search classifieds functionality
- [ ] Search land for sale functionality
- [ ] Destination guide functionality

### Core Integration
- [ ] OpenSimulator database connectivity
- [ ] OpenSimulator console connectivity
- [ ] Asset server integration
- [ ] Grid services communication

## Adding New Tests

To add new test groups, create a new `test-*.php` file following the numbering convention:

- **00**: System requirements and dependencies (use `-required` suffix for critical tests)
- **01**: WordPress environment and plugin setup (use `-required` suffix for critical tests)
- **02**: OpenSimulator connectivity and configuration (use `-required` suffix for critical tests)
- **03+**: Feature-specific tests (optional, like `test-profile.php`)

```php
<?php
/**
 * Your Test Group Name
 * Description of what this test group covers
 * 
 * Usage: php ./tests/test-##-yourname.php
 */

// Load bootstrap
require_once __DIR__ . '/bootstrap.php';

echo "Testing your functionality...\n";

// Your tests here
$test->assert_true( your_condition_here(), 'Description of what you're testing' );
$test->assert_equals( expected_value, actual_value, 'Test description' );
$test->assert_not_empty( some_value, 'Test description' );

// Show summary
$test->summary();
```

**Important**: Tests with the `-required` suffix in their filename are considered critical. If any required test fails, the test runner will stop executing subsequent tests to prevent cascading failures. Optional tests (like `test-profile.php`) will always run regardless of other test outcomes.

## Benefits

- **Fast setup** - No complex testing framework installation
- **Real environment** - Tests actual plugin behavior in your WordPress setup
- **Clear output** - Simple pass/fail indicators with ✓/✗ symbols
- **Easy debugging** - Plain PHP, easy to modify and extend
- **No separate database** - Tests against your actual WordPress/OpenSim data
- **Smart execution** - Requirement-based testing prevents running tests on broken systems
- **Comprehensive validation** - Checks system dependencies before functional testing
