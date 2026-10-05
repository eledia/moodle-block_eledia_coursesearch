# Changelog

All notable changes to the eLeDia Course Search block will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

- Make selected filter pills keyboard selectable and provide aria labels
- Fix duplicate course details modals when using boost union theme on the site home in listing view and clicking on a details button on page >= 3
- Declare missing boost union fields in get_courseview() external function


## [2.0] - 2026-09-01

- Add support for displaying course cards grid and list layouts using the Boost Union Moodle theme if available
- Add "Inline within filter input fields" display mode for selected filter options, showing active selections as removable pills directly inside the input fields
- Improve design of filter input fields and dropdowns and fix UX issues
- Allow courses to be excluded from search results via a custom course field / checkbox (`block_eledia_coursesearch_visible`)
- Fix pagination of search results
- Replaced text button for switching between card and list mode with bootstrap icon button group
- Move collapsible custom field filters above the action buttons to group all filter options together
- Replace custom translation system with Moodle's built-in multilang filter
- Align course search bar placeholder with the input field label
- Improve course freetext search bar and remove dependency on core component
- Add link to clear all selected search filters at once
- Display cards grid layout by default, regardless of the clients device type since it is now responsive
- Add Bootstrap 5 compatibility for newer Moodle releases
- Fix course category filter dropdown and selectors for customfield dropdowns
- Unify spacing between different filter input fields
- Align German translations for course timeframe with Moodle core translations


## [1.0] - 2026-01-29

### Initial Release

#### Added
- Advanced course search with multiple filter criteria
- Support for filtering by:
  - Course categories (with hierarchical display)
  - Course tags
  - Custom fields (text, select, multiselect, checkbox, date)
- Three view modes:
  - Cards view (with course images)
  - List view (compact)
  - Summary view (detailed)
- Progress tracking integration
  - Visual progress bars
  - Percentage completion display
- Favorite courses management
  - Mark/unmark courses as favorites
  - Filter to show only favorites
- Search functionality:
  - Full-text course search
  - Auto-complete suggestions for categories, tags, and custom fields
- Responsive design:
  - Mobile-friendly interface
  - Adaptive layouts for all screen sizes
- Accessibility features:
  - WCAG 2.1 AA compliant
  - Keyboard navigation support
  - Screen reader friendly
  - ARIA labels and landmarks
- Multi-language support:
  - English (en)
  - German (de)
  - Translation support for custom field names
- Privacy API implementation:
  - Full GDPR compliance
  - User data export
  - User data deletion
- Comprehensive documentation:
  - Installation guides (EN/DE)
  - User instructions (EN/DE)
  - Developer documentation
- PHPUnit tests (30 tests, 100% passing)
- Behat tests for end-to-end testing
- GitHub Actions CI/CD integration

#### Technical Features
- Compatible with Moodle 4.5+
- PHP 8.1, 8.2, and 8.3 support
- PostgreSQL and MariaDB support
- AMD/JavaScript modules for dynamic functionality
- Mustache templates for rendering
- External API for AJAX operations
- Proper capability checks and security
- Cache-friendly implementation

### Notes
- Derived from Moodle core myoverview block
- Approximately 50% new code, including complete search backend
- No database tables required (uses core Moodle tables)
- Recommended for Dashboard placement
- Custom fields must be set to "Visible to everyone" to appear in filters
