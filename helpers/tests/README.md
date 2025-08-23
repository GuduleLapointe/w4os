# OpenSim Helpers Test Suite

This directory contains PHPUnit tests for the OpenSim Helpers scripts.

## Setup

1. Install PHPUnit dependencies:
```bash
composer install --dev
```

2. Configure your OpenSim settings using `Engine_Settings` before running tests.

3. Ensure your web server can access the helpers scripts at the configured base URL.

## Running Tests

Run all tests with clean summary:
```bash
composer test
```

Run with PHPUnit directly (verbose):
```bash
composer run test-phpunit
```

Run specific test class:
```bash
vendor/bin/phpunit tests/QueryTest.php
```

## Test Execution Order

1. **Prerequisites Test**: Runs first and validates:
   - Robust database connectivity
   - Grid online status (get_grid_info endpoint)
   - Helpers accessibility
   
   If prerequisites fail, all other tests are skipped.

2. **Main Test Suite**: Runs only if prerequisites pass

## Test Output

The custom test runner provides:
- Real-time progress indicators (., F, S)
- Clean summary with one line per test
- Pass/Fail/Skip status with symbols (✅/❌/⚠️)
- Brief error messages for failed tests
- Final statistics

Example output:
```
✅ Prerequisites::Database Connectivity     PASS
✅ QueryTest::testPlacesQueryWithValidRequest PASS
❌ CurrencyTest::testCurrencyQuoteRequest    FAIL
⚠️  GuideTest::testGuideAccessibility        SKIP

Total: 25, Passed: 20, Failed: 2, Skipped: 3
```

## Test Coverage

- **QueryTest**: Search functionality (query.php)
- **CurrencyTest**: Virtual currency operations (currency.php) 
- **OfflineTest**: Offline messaging (offline.php)
- **RegisterTest**: Search registration (register.php)
- **LandtoolTest**: Land management (landtool.php)
- **GuideTest**: Destination guide (guide.php)
- **ParserTest**: Data parsing (parser.php, eventsparser.php)
- **SplashTest**: Basic configuration (splash.php)

## Configuration Requirements

Tests may be skipped if required services are not configured:

- **Database tests**: Require `robust.DatabaseService.ConnectionString`
- **Search tests**: Require search database configuration
- **Events tests**: Require `engine.Search.HypeventsUrl`
- **Currency tests**: Require currency system configuration

## Test Environment

Tests use the `OpenSimHelpersTestCase` base class which provides:

- HTTP request helpers for testing scripts
- XMLRPC request/response handling
- UUID generation for test data
- Configuration validation
- Response assertion helpers

Tests are designed to be non-destructive and use test data where possible.
