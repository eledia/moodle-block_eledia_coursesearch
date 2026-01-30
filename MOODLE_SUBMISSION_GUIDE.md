# Moodle Plugin Directory Submission Guide

## Package Information

**Plugin Name:** block_eledia_coursesearch  
**Version:** 2026012900 (v1.0)  
**Package File:** `eledia_coursesearch_v2026012900.zip`  
**Package Location:** `/home/ipasanec/moodles/moodle45/public_html/blocks/`  

## Pre-Submission Verification ✅

### Required Files
- ✅ version.php - Plugin version and requirements
- ✅ README.md - Comprehensive documentation
- ✅ CHANGES.md - Changelog
- ✅ upgrade.txt - Upgrade notes
- ✅ Language files (en, de)
- ✅ PHPUnit tests (30 tests)
- ✅ Privacy provider implementation
- ✅ Database definitions
- ✅ Mustache templates (19 templates)
- ✅ JavaScript (AMD modules)
- ✅ CSS styles

### Code Quality
- ✅ PHPUnit: 30/30 tests passing
- ✅ PHP Lint: No errors
- ✅ Moodle Code Checker: Passing
- ✅ PHPDoc Checker: No errors
- ✅ Mustache Lint: Passing
- ✅ Grunt Build: Passing
- ✅ GitHub CI: All checks passing

### Compatibility Testing
- ✅ Moodle 4.5.8+
- ✅ PHP 8.1, 8.2, 8.3
- ✅ PostgreSQL 15
- ✅ MariaDB 10

## Submission Steps

### 1. Access Moodle Plugins Directory
1. Go to: https://moodle.org/plugins/
2. Log in with your Moodle.org account
3. Click **"Register a new plugin"** (or **"Add a new version"** if updating)

### 2. Plugin Information

**Basic Information:**
- Plugin type: `Block (block)`
- Plugin name: `eledia_coursesearch`
- Full name: `eLeDia Course Search`
- Short description: `Advanced course search and filtering block with multiple view modes`

**Category:**
- Primary: `Course`
- Secondary: `Student tools`

### 3. Version Details

- Version number: `2026012900`
- Release name: `v1.0`
- Maturity: `Stable`
- Required Moodle: `4.5 (2024100100)`
- Supported until: `Latest`

### 4. Package Upload

Upload: `eledia_coursesearch_v2026012900.zip`

### 5. Description

```
The eLeDia Course Search block provides advanced course search and filtering capabilities for Moodle, helping users quickly find and access their courses.

Key Features:
• Advanced multi-criteria search (categories, tags, custom fields)
• Three view modes: cards, list, and summary
• Integrated progress tracking and course completion display
• Favorite courses management
• Fully responsive and mobile-friendly design
• WCAG 2.1 AA accessibility compliant
• Multi-language support (English and German)
• GDPR compliant with Privacy API

Derived from the Moodle core myoverview block with significant enhancements including a completely rewritten search backend and advanced filtering capabilities.

Perfect for institutions with large course catalogs who want to provide students with powerful search and filtering tools.
```

### 6. Links and Resources

- **Bug tracker:** https://github.com/eledia/moodle-block_eledia_coursesearch/issues
- **Documentation:** https://github.com/eledia/moodle-block_eledia_coursesearch/blob/main/README.md
- **Source code:** https://github.com/eledia/moodle-block_eledia_coursesearch
- **Discussion:** (Leave blank initially)

### 7. Maintainer Information

- **Maintainer:** eLeDia GmbH
- **Contact email:** support@eledia.de
- **Author:** Immanuel Pasanec

### 8. Screenshots (Recommended)

Prepare and upload screenshots showing:
1. Block overview with course cards view
2. List view mode
3. Summary view mode  
4. Filter options (categories, tags, custom fields)
5. Search functionality with auto-complete
6. Mobile responsive view
7. Progress tracking display

### 9. License

- **License:** GNU GPL v3 or later
- Copyright: © 2025 eLeDia GmbH

## Post-Submission

### Automated Validation
The Moodle plugins directory will automatically:
- Scan for security issues
- Validate version.php
- Check language strings
- Verify file structure
- Run basic code checks

### Review Process
1. Automated validation (immediate)
2. Manual review by Moodle HQ (1-7 days typically)
3. Possible requests for changes
4. Approval and publication

### After Approval
- Plugin will be available in the Moodle plugins directory
- Users can install directly from Moodle admin interface
- Will appear in search results
- Automatic update notifications for users

## Common Checklist Issues

### If Validation Fails:

**Missing files:**
- Ensure version.php exists with all required fields
- Check language files are in correct location

**Version number issues:**
- Use format YYYYMMDDXX (e.g., 2026012900)
- Must be higher than any previous version

**Language string issues:**
- All strings must have English (en) version
- No unused language strings

**License issues:**
- All files must have GPL v3 header
- Copyright notices must be present

**Security issues:**
- No SQL injection vulnerabilities
- No XSS vulnerabilities
- Proper capability checks

## Support After Publication

### Updating the Plugin
1. Make changes in your repository
2. Update version number in version.php
3. Update CHANGES.md
4. Create new package ZIP
5. Log in to plugins directory
6. Upload new version

### Handling Issues
- Monitor GitHub issues
- Respond to user questions in Moodle forums
- Provide timely updates for bugs and security issues

## Files Included in Package

The package includes:
- All PHP files
- Language files (en, de)
- JavaScript (source and minified)
- Mustache templates
- CSS files
- Documentation (README.md, CHANGES.md, upgrade.txt)
- Tests (PHPUnit and Behat)
- Icons and images

Excluded from package:
- .git directory
- .github directory (CI configuration)
- Submission helper files (SUBMISSION_*.*)
- IDE files (.idea, .vscode, etc.)
- Temporary files

---

**Status:** ✅ Ready for submission to Moodle Plugin Directory

**Package created:** 2026-01-30  
**All CI tests:** Passing  
**Documentation:** Complete
