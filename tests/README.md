# Unit Tests for block_eledia_coursesearch

This directory contains unit tests for the eledia_coursesearch block plugin.

## Test Files

### externallib_test.php
Contains unit tests for all web service functions used by the JavaScript frontend. Tests include:

#### Web Service Function Tests:
- **test_get_data()** - Tests the basic get_data web service that retrieves enrolled courses
- **test_get_courseview()** - Tests course view retrieval with various filters
- **test_get_available_categories()** - Tests category filtering functionality
- **test_get_available_tags()** - Tests tag filtering functionality
- **test_get_customfields()** - Tests custom field retrieval
- **test_get_customfield_available_options()** - Tests custom field option retrieval
- **test_get_enrolled_courses_by_timeline_classification()** - Tests course filtering by timeline (past, in progress, future)

#### Search and Filter Tests:
- **test_get_courseview_with_search()** - Tests search functionality in course view
- **test_get_courseview_with_category_filter()** - Tests category-based filtering

#### Helper Function Tests:
- **test_remap_searchdata()** - Tests search data remapping
- **test_zero_response()** - Tests empty response helper
- **test_filterparams()** - Tests parameter filtering
- **test_get_multiselect_customfields()** - Tests multiselect custom field retrieval
- **test_select_translation()** - Tests translation selection helper

### privacy/provider_test.php
Contains tests for GDPR privacy API compliance:
- User preference export
- Hidden course preferences
- User data handling

## Running Tests

To run all unit tests for this block:

```bash
cd /path/to/moodle
php admin/tool/phpunit/cli/util.php --buildcomponentconfigs
vendor/bin/phpunit --testsuite block_eledia_coursesearch_testsuite
```

To run a specific test class:

```bash
vendor/bin/phpunit blocks/eledia_coursesearch/tests/externallib_test.php
```

To run a specific test method:

```bash
vendor/bin/phpunit --filter test_get_courseview blocks/eledia_coursesearch/tests/externallib_test.php
```

## Behat Tests

Behat (acceptance) tests are located in the `behat/` subdirectory and test the block's functionality from a user interface perspective.

## Test Coverage

The unit tests focus on:
1. **Web service functions** - All AJAX endpoints called by the JavaScript frontend
2. **Data filtering** - Category, tag, and custom field filtering
3. **Search functionality** - Course search across multiple fields
4. **Timeline classification** - Past, present, and future course filtering
5. **Helper functions** - Internal utility functions used throughout the plugin

## Adding New Tests

When adding new web service functions or modifying existing ones:
1. Add corresponding test methods to `externallib_test.php`
2. Ensure test data is properly set up using the data generator
3. Test both success cases and edge cases
4. Always call `$this->resetAfterTest(true)` or use `setUp()` method
5. Use meaningful assertion messages for better debugging

## Notes

- All tests extend `\advanced_testcase` which provides access to Moodle's test data generators
- Tests use the data generator to create test courses, users, categories, and custom fields
- Privacy tests extend `\core_privacy\tests\provider_testcase` for GDPR compliance testing
