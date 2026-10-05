# eLeDia Course Search Block

[![Moodle Plugin CI](https://github.com/eledia/moodle-block_eledia_coursesearch/workflows/Moodle%20Plugin%20CI/badge.svg)](https://github.com/eledia/moodle-block_eledia_coursesearch/actions)

An advanced course search and filtering block for Moodle that provides powerful search capabilities with multiple filter criteria and view modes.

## Features

* **Advanced Search**: Search courses by name, category, tags, and custom fields
* **Multiple View Modes**: Choose between cards, list, or summary view
* **Progress Tracking**: Integrated course progress display
* **Favorites Management**: Mark and filter favorite courses
* **Responsive Design**: Fully responsive and mobile-friendly
* **Boost Union Integration**: Optional Boost Union course cards, lists, and details dialogs
* **Public Catalogue**: Can be placed on the site home using Moodle's automatic guest login
* **Accessibility**: WCAG 2.1 AA compliant
* **Multi-language**: English and German support included
* **Privacy API**: Full GDPR compliance

## Requirements

* Moodle 4.5 or higher
* PHP 8.1, 8.2, or 8.3
* PostgreSQL 15+ or MariaDB 10+

## Installation

### Via uploaded ZIP file

1. Download the plugin from the [Moodle plugins directory](https://moodle.org/plugins/block_eledia_coursesearch) or [GitHub](https://github.com/eledia/moodle-block_eledia_coursesearch)
2. Log in to your Moodle site as an administrator
3. Go to *Site administration* > *Plugins* > *Install plugins*
4. Upload the ZIP file and click *Install plugin from the ZIP file*
5. Follow the on-screen instructions

### Via Git

```bash
cd /path/to/moodle/blocks
git clone https://github.com/eledia/moodle-block_eledia_coursesearch.git eledia_coursesearch
cd eledia_coursesearch
```

Then visit your Moodle site's notification page to complete the installation.

## Configuration

### Adding the Block to the Dashboard

1. Go to *Site administration* > *Appearance* > *Default Dashboard page*
2. Turn editing on
3. Click *Add a block*
4. Select *eLeDia Course Search*
5. Turn editing off
6. Click *Reset Dashboard for all users* to make it available to everyone

### Adding the Block to the Site Home

The block can also provide a public course catalogue on the site home. Public
access uses a Moodle guest session; it is not sessionless anonymous access.

1. Ensure that Moodle's guest account exists and that the guest role has the
   intended `moodle/category:viewcourselist` permissions.
2. Enable *Auto-login guests* under *Site administration* > *Users* >
   *Permissions* > *User policies*.
3. Open the site home, turn editing on, and add *eLeDia Course Search*.

The automatic guest-login setting applies to the whole Moodle site. Without
it, an anonymous visitor may be redirected to the login page when the block
finds at least one catalogue-visible course.

### Custom Fields Configuration

For custom fields to appear as filter options:

1. Go to *Site administration* > *Courses* > *Course custom fields*
2. Create or edit a custom field
3. Set *Visible to* to **Everyone** in the field settings

Use Moodle's built-in multilang filter if custom-field names or descriptions
need translations.

## Documentation

Comprehensive documentation is available in multiple languages:

### English
* [Installation and Setup Guide](docs/en/installation_and_setup.md)
* [User Instructions](docs/en/user_instructions.md)
* [Developer Documentation](docs/development/developer_documentation.md)

### German
* [Installations- und Einrichtungshinweise](docs/de/installation_and_setup.md)
* [Anwenderinformationen](docs/de/user_instructions.md)

## Support

* **Bug reports**: [GitHub Issues](https://github.com/eledia/moodle-block_eledia_coursesearch/issues)
* **Email**: support@eledia.de
* **Documentation**: See the `docs/` folder

## Credits

* **Copyright**: © 2025 eLeDia GmbH
* **Author**: Immanuel Pasanec
* **Made possible by**: TU Ilmenau
* **Derived from**: Moodle core myoverview block

## License

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program. If not, see <https://www.gnu.org/licenses/>.
