// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Javascript to initialise the selectors for the eledia_coursesearch block.
 *
 * @package
 * @copyright 2025 eLeDia GmbH
 * @author Immanuel Pasanec <support@eledia.de>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

export default {
    courseView: {
        region: '[data-region="courses-view"]',
        regionContent: '[data-region="course-view-content"]',
        elediaButton: 'eledia-courseseach-viewbtn',
        courseSummary: 'summary'
    },
    FILTERS: '[data-region="filter"]',
    FILTER_OPTION: '[data-filter]',
    FILTER_GROUPING: '[data-filter="grouping"]',
    DISPLAY_OPTION: '[data-display-option]',
    ACTION_HIDE_COURSE: '[data-action="hide-course"]',
    ACTION_SHOW_COURSE: '[data-action="show-course"]',
    ACTION_ADD_FAVOURITE: '[data-action="add-favourite"]',
    ACTION_REMOVE_FAVOURITE: '[data-action="remove-favourite"]',
    FAVOURITE_ICON: '[data-region="favourite-icon"]',
    ICON_IS_FAVOURITE: '[data-region="is-favourite"]',
    ICON_NOT_FAVOURITE: '[data-region="not-favourite"]',
    region: {
        selectBlock: '[data-region="eledia_coursesearch"]',
        searchInput: '[data-action="search"]',
        clearIcon: '[data-action="clearsearch"]',
    },
    cat: {
        prevent: '.catprevent',
        clearIcon: '[data-action="clearcatsearch"]',
        input: '[data-action="catsearch"]',
        search: '.categorysearch',
        dropdown: '.dropdown-categories',
        dropdownDiv: '.category-droprown-container',
        selectableItem: 'category-selectable',
        selectedItem: 'category-selected',
        noItem: 'category-no',
    },
    tags: {
        prevent: '.tagsprevent',
        clearIcon: '[data-action="cleartagssearch"]',
        input: '[data-action="tagssearch"]',
        search: '.tagssearch',
        dropdown: '.dropdown-tags',
        dropdownDiv: '.tags-droprown-container',
        selectableItem: 'tags-selectable',
        selectedItem: 'tags-selected',
        noItem: 'tags-no',
    },
    customfields: {
        clearIcon: '[data-action="clearcustomsearch"]',
        input: '[data-action="customsearch"]',
        searchfield: '.customsearch-',
        dropdown: '.dropdown-customfields-',
        dropdownAll: '.dropdown-menu-customfields',
        dropdownDiv: '.customfield-dropdown-container-',
        selectableItem: 'customfield-selectable',
        selectedItem: 'customfield-selected',
    },
};
