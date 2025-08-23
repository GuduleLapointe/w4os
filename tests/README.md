# W4OS Plugin Testing

This directory contains tests for the W4OS - OpenSimulator Web Interface plugin using a simple PHP test runner approach.

## Running Tests

To run all tests:

```bash
cd /path/to/w4os-dev
php tests/run-tests.php
```

## Test Approach

- **No PHPUnit required** - Uses plain PHP with a simple test framework
- **Tests against live WordPress** - Uses your actual WordPress installation instead of a separate test environment
- **Environment-aware** - Tests with all your plugins, configuration, and OpenSim setup intact

## Critical Features to Test

Based on the essential functionality of W4OS, these are the priority features that should be tested:

### Basics
- [x] **Basic Environment Tests** (implemented in `run-tests.php`):
    - WordPress functions are loaded
    - Database connectivity works
    - W4OS plugin is loaded and active
    - Site configuration is accessible
- [ ] Check proper OpenSimulator settings detection and DB connection

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

To add new tests, edit `run-tests.php` and add test methods using the SimpleTest class:

```php
// Example test
$test->assert_true( your_condition_here(), 'Description of what you're testing' );
$test->assert_equals( expected_value, actual_value, 'Test description' );
$test->assert_not_empty( some_value, 'Test description' );
```

## Benefits

- **Fast setup** - No complex testing framework installation
- **Real environment** - Tests actual plugin behavior in your WordPress setup
- **Clear output** - Simple pass/fail indicators with ✓/✗ symbols
- **Easy debugging** - Plain PHP, easy to modify and extend
- **No separate database** - Tests against your actual WordPress/OpenSim data
