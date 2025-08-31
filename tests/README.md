# W4OS Plugin Testing

This directory contains tests for the W4OS - OpenSimulator Web Interface plugin using a simple PHP test runner approach.

## Running Tests

To run all tests:

```bash
cd /path/to/w4os-dev/tests
php run-tests.php
```

To run individual test groups:

```bash
cd /path/to/w4os-dev/tests
php test-00-environment.php  # WordPress environment tests
php test-01-opensim.php      # OpenSimulator environment tests
```

## Test Structure

- **`bootstrap.php`** - Loads WordPress and provides SimpleTest framework
- **`run-tests.php`** - Main test runner that executes all test-*.php files in order
- **`test-00-environment.php`** - WordPress environment tests (database, plugins, etc.)
- **`test-01-opensim.php`** - OpenSimulator environment tests (databases, console, services)

*Tests are numbered to ensure environment tests run first, followed by OpenSim tests, before any additional feature tests.*

## Test Approach

- **No PHPUnit required** - Uses plain PHP with a simple test framework
- **Tests against live WordPress** - Uses your actual WordPress installation instead of a separate test environment
- **Environment-aware** - Tests with all your plugins, configuration, and OpenSim setup intact

## Critical Features to Test

Based on the essential functionality of W4OS, these are the priority features that should be tested:

### Basics
- [x] **WordPress Environment Tests** (implemented in `test-environment.php`):
    - WordPress functions are loaded
    - Database connectivity works
    - W4OS plugin is loaded and active
    - Site configuration is accessible
- [x] **OpenSimulator Environment Tests** (implemented in `test-opensim.php`):
    - OpenSimulator settings detection and parsing
    - Database connectivity for all configured services
    - Console connectivity (if enabled)

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

To add new test groups, create a new `test-*.php` file:

```php
<?php
/**
 * Your Test Group Name
 * Description of what this test group covers
 * 
 * Usage: php test-yourname.php
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

## Benefits

- **Fast setup** - No complex testing framework installation
- **Real environment** - Tests actual plugin behavior in your WordPress setup
- **Clear output** - Simple pass/fail indicators with ✓/✗ symbols
- **Easy debugging** - Plain PHP, easy to modify and extend
- **No separate database** - Tests against your actual WordPress/OpenSim data
