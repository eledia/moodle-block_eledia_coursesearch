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
 * Manage the timeline view navigation for the overview block.
 *
 * @copyright  2018 Bas Brands <bas@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';
import * as CustomEvents from 'core/custom_interaction_events';
import Notification from 'core/notification';
import { setUserPreference } from 'core_user/repository';
import * as View from 'block_eledia_telc_coursesearch/view';
import SELECTORS from 'block_eledia_telc_coursesearch/selectors';

/**
 * Update the user preference for the block.
 *
 * @param {String} filter The type of filter: display/sort/grouping.
 * @param {String} value The current preferred value.
 * @return {Promise}
 */
// eslint-disable-next-line no-unused-vars
const updatePreferences = (filter, value) => {
    let type = null;
    if (filter === 'display') {
        type = 'block_eledia_telc_coursesearch_user_view_preference';
    } else if (filter === 'sort') {
        type = 'block_eledia_telc_coursesearch_user_sort_preference';
    } else if (filter === 'customfieldvalue') {
        type = 'block_eledia_telc_coursesearch_user_grouping_customfieldvalue_preference';
    } else {
        type = 'block_eledia_telc_coursesearch_user_grouping_preference';
    }

    return setUserPreference(type, value)
        .catch(Notification.exception);
};

/**
 * Event listener for the Display filter (cards, list).
 *
 * @param {object} root The root element for the overview block
 */
const registerSelector = root => {

    const Selector = root.find(SELECTORS.FILTERS);

    CustomEvents.define(Selector, [CustomEvents.events.activate]);
    Selector.on(
        CustomEvents.events.activate,
        SELECTORS.FILTER_OPTION,
        (e, data) => {
            // NOTE: It might be possible to repurpose this without much change for custom fields.
            const option = $(e.target);

            if (option.hasClass('active')) {
                // If it's already active then we don't need to do anything.
                return;
            }

            const filter = option.attr('data-filter');
            // eslint-disable-next-line no-unused-vars
            const pref = option.attr('data-pref');
            const customfieldvalue = option.attr('data-customfieldvalue');

            root.find(SELECTORS.courseView.region).attr('data-' + filter, option.attr('data-value'));

            if (customfieldvalue) {
                root.find(SELECTORS.courseView.region).attr('data-customfieldvalue', customfieldvalue);
            }

            // Reset the views.

            // Check if the user is currently in a searching state, if so we'll reset it.
            // NOTE: This empties the search when something in the "All" dropdown is being selected.
            const page = document.querySelector(SELECTORS.region.selectBlock);
            const input = page.querySelector(SELECTORS.region.searchInput);
            if (input.value !== '') {
                const clearIcon = page.querySelector(SELECTORS.region.clearIcon);
                input.value = '';
                // Triggers the init so wont need to call it again.
                View.clearSearch(clearIcon, root);
            } else {
                View.init(root);
            }

            data.originalEvent.preventDefault();
        }
    );

    Selector.on(
        CustomEvents.events.activate,
        SELECTORS.DISPLAY_OPTION,
        (e, data) => {
            const option = $(e.target);

            if (option.hasClass('active')) {
                return;
            }

            // eslint-disable-next-line no-unused-vars
            const filter = option.attr('data-display-option');
            // eslint-disable-next-line no-unused-vars
            const pref = option.attr('data-pref');

            root.find(SELECTORS.courseView.region).attr('data-display', option.attr('data-value'));
            const elediaButtons = document.getElementsByClassName(SELECTORS.courseView.elediaButton);
            elediaButtons.forEach(e => {
                if (e.classList.contains('d-none')) {
                    e.classList.remove('d-none');
                } else {
                    e.classList.add('d-none');
                }
            });
            View.reset(root);
            data.originalEvent.preventDefault();
        }
    );
};

/**
 * Initialise the timeline view navigation by adding event listeners to
 * the navigation elements.
 *
 * @param {object} root The root element for the eledia_telc_coursesearch block
 */
export const init = root => {
    root = $(root);
    registerSelector(root);
};
