# Changelog

All notable changes to the eLeDia Course Search block will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
