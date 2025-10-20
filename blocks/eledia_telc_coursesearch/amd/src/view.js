// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Manage the courses view for the overview block.
 *
 * @copyright  2018 Bas Brands <bas@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//import $, { error } from 'jquery';
import $ from 'jquery';
import * as Repository from 'block_eledia_telc_coursesearch/repository';
import * as PagedContentFactory from 'core/paged_content_factory';
import * as PubSub from 'core/pubsub';
import * as CustomEvents from 'core/custom_interaction_events';
import * as Notification from 'core/notification';
import { exception as displayException } from 'core/notification';
import * as Templates from 'core/templates';
import * as CourseEvents from 'core_course/events';
import SELECTORS from 'block_eledia_telc_coursesearch/selectors';
import * as PagedContentEvents from 'core/paged_content_events';
import * as Aria from 'core/aria';
import { debounce } from 'core/utils';
import { setUserPreference } from 'core_user/repository';

const TEMPLATES = {
    COURSES_CARDS: 'block_eledia_telc_coursesearch/view-cards',
    COURSES_LIST: 'block_eledia_telc_coursesearch/view-list',
    COURSES_SUMMARY: 'block_eledia_telc_coursesearch/view-summary',
    NOCOURSES: 'core_course/no-courses'
};

const GROUPINGS = {
    GROUPING_ALLINCLUDINGHIDDEN: 'allincludinghidden',
    GROUPING_ALL: 'all',
    GROUPING_INPROGRESS: 'inprogress',
    GROUPING_FUTURE: 'future',
    GROUPING_PAST: 'past',
    GROUPING_FAVOURITES: 'favourites',
    GROUPING_HIDDEN: 'hidden'
};

const NUMCOURSES_PERPAGE = [12, 24, 48, 96, 0];

let loadedPages = [];

let courseOffset = 0;

let lastPage = 0;

let lastLimit = 0;

let namespace = null;

let selectedCategories = [];
let selectableCategories = [];
let selectedTags = [];
let selectableTags = [];
let customfields = [];
let filteredCustomfields = [];
let selectedCustomfields = [];
let searchTerm = '';
let catSearchTerm = '';
let tagsSearchTerm = '';
let courseInProgress = 'all';
let currentCustomField = 0;

let tagsReverseState = false;
let customReverseState = false;
let catReverseState = false;

/**
 * Whether the summary display has been loaded.
 *
 * If true, this means that courses have been loaded with the summary text.
 * Otherwise, switching to the summary display mode will require course data to be fetched with the summary text.
 *
 * @type {boolean}
 */
let summaryDisplayLoaded = false;

/**
 * Get filter values from DOM.
 *
 * @param {object} root The root element for the courses view.
 * @return {filters} Set filters.
 */
const getFilterValues = root => {
    const courseRegion = root.find(SELECTORS.courseView.region);
    return {
        display: courseRegion.attr('data-display'),
        grouping: courseRegion.attr('data-grouping'),
        sort: courseRegion.attr('data-sort'),
        displaycategories: courseRegion.attr('data-displaycategories'),
        // TODO: Remove
        customfieldname: courseRegion.attr('data-customfieldname'),
        customfieldvalue: courseRegion.attr('data-customfieldvalue'),
    };
};

/**
 * Get filter values from DOM and global variables.
 *
 * @param {object} root The root element for the courses view.
 * @return {filters} Set filters.
 */
const getAllFilterValues = root => {
    const courseRegion = root.find(SELECTORS.courseView.region);
    return {
        display: courseRegion.attr('data-display'),
        grouping: courseRegion.attr('data-grouping'),
        sort: courseRegion.attr('data-sort'),
        displaycategories: courseRegion.attr('data-displaycategories'),
        customfields: customfields,
        selectedCategories: selectedCategories,
    };
};

// We want the paged content controls below the paged content area.
// and the controls should be ignored while data is loading.
const DEFAULT_PAGED_CONTENT_CONFIG = {
    ignoreControlWhileLoading: true,
    controlPlacementBottom: true,
    persistentLimitKey: 'block_eledia_telc_coursesearch_user_paging_preference'
};

/**
 * Get enrolled courses from backend.
 *
 * @param {object} filters The filters for this view.
 * @param {int} limit The number of courses to show.
 * @param {object} searchParams The params.
 * @return {promise} Resolved with an array of courses.
 */
const getMyCourses = (filters, limit, searchParams) => {
    const params = {
        offset: courseOffset,
        limit: limit,
        classification: filters.grouping,
        sort: filters.sort,
        customfieldname: filters.customfieldname,
        customfieldvalue: filters.customfieldvalue,
    };
    if (filters.display === 'summary') {
        params.requiredfields = Repository.SUMMARY_REQUIRED_FIELDS;
        summaryDisplayLoaded = true;
    } else {
        params.requiredfields = Repository.CARDLIST_REQUIRED_FIELDS;
    }
    // return Repository.getEnrolledCoursesByTimeline(searchParams);
    return Repository.getEnrolledCoursesByTimeline(searchParams);
};

/**
 * Search for enrolled courses from backend.
 *
 * @param {object} filters The filters for this view.
 * @param {int} limit The number of courses to show.
 * @param {string} searchValue What does the user want to search within their courses.
 * @return {promise} Resolved with an array of courses.
 */
const getSearchMyCourses = (filters, limit, searchValue) => {
    const params = {
        offset: courseOffset,
        limit: limit,
        classification: 'search',
        sort: filters.sort,
        customfieldname: filters.customfieldname,
        customfieldvalue: filters.customfieldvalue,
        searchvalue: searchValue,
    };
    if (filters.display === 'summary') {
        params.requiredfields = Repository.SUMMARY_REQUIRED_FIELDS;
        summaryDisplayLoaded = true;
    } else {
        params.requiredfields = Repository.CARDLIST_REQUIRED_FIELDS;
        summaryDisplayLoaded = false;
    }
    // return Repository.getEnrolledCoursesByTimeline(params);
    return Repository.getEnrolledCoursesByTimeline(searchValue);
};

/**
 * Search for categories from backend.
 *
 * @return {promise} Resolved with an array of categories.
 */
const getSearchCategories = () => {
    const params = getParams();
    return Repository.getCategories(params);
};

/**
 * Search for tags from backend.
 *
 * @return {promise} Resolved with an array of categories.
 */
const getSearchTags = () => {
    const params = getParams();
    return Repository.getTags(params);
};

/**
 * Search for custom fields from backend.
 *
 * @return {promise} Resolved with an array of categories.
 */
const getSearchCustomfields = () => {
    const params = getParams();
    return Repository.getCustomfields(params);
};

/**
 * Get params for search
 *
 * @param {int} limit
 *
 * @return {Object} The favourite icon container
 */
const getParams = (limit = 0) => {
    const params = {
        criteria: [
            {
                key: "categoryName",
                value: catSearchTerm,
            },
            {
                key: "tagsName",
                value: tagsSearchTerm,
            },
            {
                key: 'name',
                value: searchTerm,
            },
            {
                key: 'selectedCategories',
                categories: selectedCategories,
            },
            {
                key: 'limit',
                value: limit,
            },
            {
                key: 'offset',
                value: courseOffset,
            },
            {
                key: 'progress',
                value: courseInProgress,
            },
            {
                key: 'currentCustomField',
                value: currentCustomField,
            },
            {
                key: 'selectedCustomfields',
                customfields: getCustomFields(),
            },
            {
                key: 'selectedTags',
                tags: selectedTags,
            },
        ],
        addsubcategories: true,
    };
    return params;
};

/**
 * Get params for search
 *
 * @return {Object} The favourite icon container
 */
const getCustomFields = () => {
    if (!selectedCustomfields.length) {
        return [];
    }
    const customFields = selectedCustomfields.map((values, key) => {
        if (!values || values === undefined) {
            return null;
        }
        const customValues = {
            fieldid: key,
            fieldvalues: values.map(val => val.value),
        };
        return customValues;
    }).filter(Boolean);
    return customFields;
};

/**
 * Get the container element for the favourite icon.
 *
 * @param {Object} root The course overview container
 * @param {Number} courseId Course id number
 * @return {Object} The favourite icon container
 */
const getFavouriteIconContainer = (root, courseId) => {
    return root.find(SELECTORS.FAVOURITE_ICON + '[data-course-id="' + courseId + '"]');
};

/**
 * Get the paged content container element.
 *
 * @param {Object} root The course overview container
 * @param {Number} index Rendered page index.
 * @return {Object} The rendered paged container.
 */
const getPagedContentContainer = (root, index) => {
    return root.find('[data-region="paged-content-page"][data-page="' + index + '"]');
};

/**
 * Get the course id from a favourite element.
 *
 * @param {Object} root The favourite icon container element.
 * @return {Number} Course id.
 */
const getCourseId = root => {
    return root.attr('data-course-id');
};

/**
 * Hide the favourite icon.
 *
 * @param {Object} root The favourite icon container element.
 * @param {Number} courseId Course id number.
 */
const hideFavouriteIcon = (root, courseId) => {
    const iconContainer = getFavouriteIconContainer(root, courseId);

    const isFavouriteIcon = iconContainer.find(SELECTORS.ICON_IS_FAVOURITE);
    isFavouriteIcon.addClass('hidden');
    Aria.hide(isFavouriteIcon);

    const notFavourteIcon = iconContainer.find(SELECTORS.ICON_NOT_FAVOURITE);
    notFavourteIcon.removeClass('hidden');
    Aria.unhide(notFavourteIcon);
};

/**
 * Show the favourite icon.
 *
 * @param {Object} root The course overview container.
 * @param {Number} courseId Course id number.
 */
const showFavouriteIcon = (root, courseId) => {
    const iconContainer = getFavouriteIconContainer(root, courseId);

    const isFavouriteIcon = iconContainer.find(SELECTORS.ICON_IS_FAVOURITE);
    isFavouriteIcon.removeClass('hidden');
    Aria.unhide(isFavouriteIcon);

    const notFavourteIcon = iconContainer.find(SELECTORS.ICON_NOT_FAVOURITE);
    notFavourteIcon.addClass('hidden');
    Aria.hide(notFavourteIcon);
};

/**
 * Get the action menu item
 *
 * @param {Object} root The course overview container
 * @param {Number} courseId Course id.
 * @return {Object} The add to favourite menu item.
 */
const getAddFavouriteMenuItem = (root, courseId) => {
    return root.find('[data-action="add-favourite"][data-course-id="' + courseId + '"]');
};

/**
 * Get the action menu item
 *
 * @param {Object} root The course overview container
 * @param {Number} courseId Course id.
 * @return {Object} The remove from favourites menu item.
 */
const getRemoveFavouriteMenuItem = (root, courseId) => {
    return root.find('[data-action="remove-favourite"][data-course-id="' + courseId + '"]');
};

/**
 * Add course to favourites
 *
 * @param {Object} root The course overview container
 * @param {Number} courseId Course id number
 */
const addToFavourites = (root, courseId) => {
    const removeAction = getRemoveFavouriteMenuItem(root, courseId);
    const addAction = getAddFavouriteMenuItem(root, courseId);

    setCourseFavouriteState(courseId, true).then(success => {
        if (success) {
            PubSub.publish(CourseEvents.favourited, courseId);
            removeAction.removeClass('hidden');
            addAction.addClass('hidden');
            showFavouriteIcon(root, courseId);
        } else {
            Notification.alert('Starring course failed', 'Could not change favourite state');
        }
        return;
    }).catch(Notification.exception);
};

/**
 * Remove course from favourites
 *
 * @param {Object} root The course overview container
 * @param {Number} courseId Course id number
 */
const removeFromFavourites = (root, courseId) => {
    const removeAction = getRemoveFavouriteMenuItem(root, courseId);
    const addAction = getAddFavouriteMenuItem(root, courseId);

    setCourseFavouriteState(courseId, false).then(success => {
        if (success) {
            PubSub.publish(CourseEvents.unfavorited, courseId);
            removeAction.addClass('hidden');
            addAction.removeClass('hidden');
            hideFavouriteIcon(root, courseId);
        } else {
            Notification.alert('Starring course failed', 'Could not change favourite state');
        }
        return;
    }).catch(Notification.exception);
};

/**
 * Get the action menu item
 *
 * @param {Object} root The course overview container
 * @param {Number} courseId Course id.
 * @return {Object} The hide course menu item.
 */
const getHideCourseMenuItem = (root, courseId) => {
    return root.find('[data-action="hide-course"][data-course-id="' + courseId + '"]');
};

/**
 * Get the action menu item
 *
 * @param {Object} root The course overview container
 * @param {Number} courseId Course id.
 * @return {Object} The show course menu item.
 */
const getShowCourseMenuItem = (root, courseId) => {
    return root.find('[data-action="show-course"][data-course-id="' + courseId + '"]');
};

/**
 * Hide course
 *
 * @param {Object} root The course overview container
 * @param {Number} courseId Course id number
 */
const hideCourse = (root, courseId) => {
    const hideAction = getHideCourseMenuItem(root, courseId);
    const showAction = getShowCourseMenuItem(root, courseId);
    const filters = getFilterValues(root);

    setCourseHiddenState(courseId, true);

    // Remove the course from this view as it is now hidden and thus not covered by this view anymore.
    // Do only if we are not in "All (including archived)" view mode where really all courses are shown.
    if (filters.grouping !== GROUPINGS.GROUPING_ALLINCLUDINGHIDDEN) {
        hideElement(root, courseId);
    }

    hideAction.addClass('hidden');
    showAction.removeClass('hidden');
};

/**
 * Show course
 *
 * @param {Object} root The course overview container
 * @param {Number} courseId Course id number
 */
const showCourse = (root, courseId) => {
    const hideAction = getHideCourseMenuItem(root, courseId);
    const showAction = getShowCourseMenuItem(root, courseId);
    const filters = getFilterValues(root);

    setCourseHiddenState(courseId, null);

    // Remove the course from this view as it is now shown again and thus not covered by this view anymore.
    // Do only if we are not in "All (including archived)" view mode where really all courses are shown.
    if (filters.grouping !== GROUPINGS.GROUPING_ALLINCLUDINGHIDDEN) {
        hideElement(root, courseId);
    }

    hideAction.removeClass('hidden');
    showAction.addClass('hidden');
};

/**
 * Set the courses hidden status and push to repository
 *
 * @param {Number} courseId Course id to favourite.
 * @param {Boolean} status new hidden status.
 * @return {Promise} Repository promise.
 */
const setCourseHiddenState = (courseId, status) => {

    // If the given status is not hidden, the preference has to be deleted with a null value.
    if (status === false) {
        status = null;
    }

    return setUserPreference(`block_eledia_telc_coursesearch_hidden_course_${courseId}`, status)
        .catch(Notification.exception);
};

/**
 * Reset the loadedPages dataset to take into account the hidden element
 *
 * @param {Object} root The course overview container
 * @param {Number} id The course id number
 */
const hideElement = (root, id) => {
    const pagingBar = root.find('[data-region="paging-bar"]');
    const jumpto = parseInt(pagingBar.attr('data-active-page-number'));

    // Get a reduced dataset for the current page.
    const courseList = loadedPages[jumpto];
    let reducedCourse = courseList.courses.reduce((accumulator, current) => {
        if (+id !== +current.id) {
            accumulator.push(current);
        }
        return accumulator;
    }, []);

    // Get the next page's data if loaded and pop the first element from it.
    if (typeof (loadedPages[jumpto + 1]) !== 'undefined') {
        const newElement = loadedPages[jumpto + 1].courses.slice(0, 1);

        // Adjust the dataset for the reset of the pages that are loaded.
        loadedPages.forEach((courseList, index) => {
            if (index > jumpto) {
                let popElement = [];
                if (typeof (loadedPages[index + 1]) !== 'undefined') {
                    popElement = loadedPages[index + 1].courses.slice(0, 1);
                }
                loadedPages[index].courses = [...loadedPages[index].courses.slice(1), ...popElement];
            }
        });

        reducedCourse = [...reducedCourse, ...newElement];
    }

    // Check if the next page is the last page and if it still has data associated to it.
    if (lastPage === jumpto + 1 && loadedPages[jumpto + 1].courses.length === 0) {
        const pagedContentContainer = root.find('[data-region="paged-content-container"]');
        PagedContentFactory.resetLastPageNumber($(pagedContentContainer).attr('id'), jumpto);
    }

    loadedPages[jumpto].courses = reducedCourse;

    // Reduce the course offset.
    courseOffset--;

    // Render the paged content for the current.
    const pagedContentPage = getPagedContentContainer(root, jumpto);
    renderCourses(root, loadedPages[jumpto]).then((html, js) => {
        return Templates.replaceNodeContents(pagedContentPage, html, js);
    }).catch(Notification.exception);

    // Delete subsequent pages in order to trigger the callback.
    loadedPages.forEach((courseList, index) => {
        if (index > jumpto) {
            const page = getPagedContentContainer(root, index);
            page.remove();
        }
    });
};

/**
 * Set the courses favourite status and push to repository
 *
 * @param {Number} courseId Course id to favourite.
 * @param {boolean} status new favourite status.
 * @return {Promise} Repository promise.
 */
const setCourseFavouriteState = (courseId, status) => {

    return Repository.setFavouriteCourses({
        courses: [
            {
                'id': courseId,
                'favourite': status
            }
        ]
    }).then(result => {
        if (result.warnings.length === 0) {
            loadedPages.forEach(courseList => {
                courseList.courses.forEach((course, index) => {
                    if (course.id == courseId) {
                        courseList.courses[index].isfavourite = status;
                    }
                });
            });
            return true;
        } else {
            return false;
        }
    }).catch(Notification.exception);
};

/**
 * Given there are no courses to render provide the rendered template.
 *
 * @param {object} root The root element for the courses view.
 * @return {promise} jQuery promise resolved after rendering is complete.
 */
const noCoursesRender = root => {
    const nocoursesimg = root.find(SELECTORS.courseView.region).attr('data-nocoursesimg');
    const newcourseurl = root.find(SELECTORS.courseView.region).attr('data-newcourseurl');
    return Templates.render(TEMPLATES.NOCOURSES, {
        nocoursesimg: nocoursesimg,
        newcourseurl: newcourseurl
    });
};

/**
 * Render the dashboard courses.
 *
 * @param {object} root The root element for the courses view.
 * @param {array} coursesData containing array of returned courses.
 * @return {promise} jQuery promise resolved after rendering is complete.
 */
const renderCourses = (root, coursesData) => {

    const filters = getFilterValues(root);

    let currentTemplate = '';
    if (filters.display === 'card') {
        currentTemplate = TEMPLATES.COURSES_CARDS;
    } else if (filters.display === 'list') {
        currentTemplate = TEMPLATES.COURSES_LIST;
    } else {
        currentTemplate = TEMPLATES.COURSES_SUMMARY;
    }

    if (!coursesData) {
        return noCoursesRender(root);
    } else {
        // Sometimes we get weird objects coming after a failed search, cast to ensure typing functions.
        if (Array.isArray(coursesData.courses) === false) {
            coursesData.courses = Object.values(coursesData.courses);
        }
        // Whether the course category should be displayed in the course item.
        coursesData.courses = coursesData.courses.map(course => {
            course.showcoursecategory = filters.displaycategories === 'on';
            return course;
        });
        if (coursesData.courses.length) {
            return Templates.render(currentTemplate, {
                courses: coursesData.courses,
            });
        } else {
            return noCoursesRender(root);
        }
    }
};

/**
 * Render the categories in search dropdown.
 *
 * @param {string} dropdownContainer The categories element for the courses view.
 * @param {string} dropdown The categories element for the courses view.
 * @param {array} categoriesData containing array of returned categories.
 * @param {array} selectionsData containing array of selected categories.
 * @param {object} page The page object.
 * @return {promise} jQuery promise resolved after rendering is complete.
 */
const renderCategories = (dropdownContainer, dropdown, categoriesData, selectionsData, page) => {

    // const filters = getFilterValues(categories);

    const template = 'block_eledia_telc_coursesearch/nav-category-dropdown';

    // NOTE: Render function for mustache.
    window.console.log('render categoriesData');
    return Templates.renderForPromise(template, {
        categories: categoriesData,
        catselections: selectionsData,
    }).then(({ html, js }) => {
        window.console.log('replaceNodeContents');
        window.console.log(html);
        window.console.log(js);
        const renderResult = Templates.replaceNodeContents(dropdownContainer, html, js);
        const catDropdowns = page.querySelectorAll(dropdown);
        catDropdowns.forEach(cdd => { cdd.style.display = 'block'; });
        return renderResult;
    }).catch(error => displayException(error));
};

/**
 * Render the categories in search dropdown.
 *
 * @param {string} dropdownContainer The categories element for the courses view.
 * @param {string} dropdown The categories element for the courses view.
 * @param {array} tagsData containing array of returned categories.
 * @param {array} selectionsData containing array of selected categories.
 * @param {object} page The page object.
 * @return {promise} jQuery promise resolved after rendering is complete.
 */
const renderTags = (dropdownContainer, dropdown, tagsData, selectionsData, page) => {

    const template = 'block_eledia_telc_coursesearch/nav-tags-dropdown';

    // NOTE: Render function for mustache.
    return Templates.renderForPromise(template, {
        tags: tagsData,
        tagsselections: selectionsData,
    }).then(({ html, js }) => {
        const renderResult = Templates.replaceNodeContents(dropdownContainer, html, js);
        const catDropdowns = page.querySelectorAll(dropdown);
        catDropdowns.forEach(cdd => { cdd.style.display = 'block'; });
        return renderResult;
    }).catch(error => displayException(error));
};

/**
 * Render the categories in search dropdown.
 *
 * @param {string} dropdownDiv The categories element for the courses view.
 * @param {string} dropdown The categories element for the courses view.
 * @param {array} customfieldsData containing array of returned categories.
 * @param {array} selectionsData containing array of selected categories.
 * @param {object} page The page object.
 * @return {promise} jQuery promise resolved after rendering is complete.
 */
const renderCustomfields = (dropdownDiv, dropdown, customfieldsData, selectionsData, page) => { // eslint-disable-line

    const template = 'block_eledia_telc_coursesearch/nav-customfield-dropdown';
    const dropdownObject = document.querySelector(dropdownDiv);
    window.console.log('dropdownDiv:', dropdownDiv);
    window.console.log('dropdownObject:', dropdownObject);
    // NOTE: Render function for mustache.
    return Templates.renderForPromise(template, {
        customvalues: customfieldsData,
        customselections: selectionsData,
        customfieldid: currentCustomField,
        description: dropdownObject.dataset.description,
    }).then(({ html, js }) => {
        window.console.log('dropdownDiv');
        window.console.log(dropdownDiv);
        const renderResult = Templates.replaceNodeContents(dropdownDiv, html, js);
        window.console.log('renderResult');
        window.console.log(renderResult);
        const cuDropdowns = page.querySelectorAll(dropdown);
        window.console.log(dropdown);
        window.console.log(cuDropdowns);
        cuDropdowns.forEach(cuDropdown => { cuDropdown.style.display = 'block'; });
        window.console.log('renderCustomfields wnd');
        return renderResult;
    }).catch(error => displayException(error));
};

/**
 * Return the callback to be passed to the subscribe event
 *
 * @param {object} root The root element for the courses view
 * @return {function} Partially applied function that'll execute when passed a limit
 */
const setLimit = root => {
    // @param {Number} limit The paged limit that is passed through the event.
    return limit => root.find(SELECTORS.courseView.region).attr('data-paging', limit);
};

/**
 * Intialise the paged list and cards views on page load.
 * Returns an array of paged contents that we would like to handle here
 *
 * @param {object} root The root element for the courses view
 * @param {string} namespace The namespace for all the events attached
 */
const registerPagedEventHandlers = (root, namespace) => {
    const event = namespace + PagedContentEvents.SET_ITEMS_PER_PAGE_LIMIT;
    PubSub.subscribe(event, setLimit(root));
};

/**
 * Figure out how many items are going to be allowed to be rendered in the block.
 *
 * @param  {Number} pagingLimit How many courses to display
 * @param  {Object} root The course overview container
 * @return {Number[]} How many courses will be rendered
 */
const itemsPerPageFunc = (pagingLimit, root) => {
    let itemsPerPage = NUMCOURSES_PERPAGE.map(value => {
        let active = false;
        if (value === pagingLimit) {
            active = true;
        }

        return {
            value: value,
            active: active
        };
    });

    // Filter out all pagination options which are too large for the amount of courses user is enrolled in.
    const totalCourseCount = parseInt(root.find(SELECTORS.courseView.region).attr('data-totalcoursecount'), 10);
    return itemsPerPage.filter(pagingOption => {
        if (pagingOption.value === 0 && totalCourseCount > 100) {
            // To minimise performance issues, do not show the "All" option
            // if the user is enrolled in more than 100 courses.
            return false;
        }
        return pagingOption.value < totalCourseCount;
    });
};

/**
 * Mutates and controls the loadedPages array and handles the bootstrapping.
 *
 * @param {Array|Object} coursesData Array of all of the courses to start building the page from
 * @param {Number} currentPage What page are we currently on?
 * @param {Object} pageData Any current page information
 * @param {Object} actions Paged content helper
 * @param {null|boolean} activeSearch Are we currently actively searching and building up search results?
 */
const pageBuilder = (coursesData, currentPage, pageData, actions, activeSearch = null) => {
    // If the courseData comes in an object then get the value otherwise it is a pure array.
    let courses = coursesData.courses ? coursesData.courses : coursesData;
    let nextPageStart = 0;
    let pageCourses = [];

    // If current page's data is loaded make sure we max it to page limit.
    if (typeof (loadedPages[currentPage]) !== 'undefined') {
        pageCourses = loadedPages[currentPage].courses;
        const currentPageLength = pageCourses.length;
        if (currentPageLength < pageData.limit) {
            nextPageStart = pageData.limit - currentPageLength;
            pageCourses = { ...loadedPages[currentPage].courses, ...courses.slice(0, nextPageStart) };
        }
    } else {
        // When the page limit is zero, there is only one page of courses, no start for next page.
        nextPageStart = pageData.limit || false;
        pageCourses = (pageData.limit > 0) ? courses.slice(0, pageData.limit) : courses;
    }

    // Finished setting up the current page.
    loadedPages[currentPage] = {
        courses: pageCourses
    };

    // Set up the next page (if there is more than one page).
    const remainingCourses = nextPageStart !== false ? courses.slice(nextPageStart, courses.length) : [];
    if (remainingCourses.length) {
        loadedPages[currentPage + 1] = {
            courses: remainingCourses
        };
    }

    // Set the last page to either the current or next page.
    if (loadedPages[currentPage].courses.length < pageData.limit || !remainingCourses.length) {
        lastPage = currentPage;
        if (activeSearch === null) {
            actions.allItemsLoaded(currentPage);
        }
    } else if (typeof (loadedPages[currentPage + 1]) !== 'undefined'
        && loadedPages[currentPage + 1].courses.length < pageData.limit) {
        lastPage = currentPage + 1;
    }

    courseOffset = coursesData.nextoffset;
};

/**
 * In cases when switching between regular rendering and search rendering we need to reset some variables.
 */
const resetGlobals = () => {
    courseOffset = 0;
    loadedPages = [];
    lastPage = 0;
    lastLimit = 0;
};

/**
 * The default functionality of fetching paginated courses without special handling.
 *
 * @return {function(Object, Object, Object, Object, Object, Promise, Number, Object): void}
 */
const standardFunctionalityCurry = () => {
    resetGlobals();
    return (filters, currentPage, pageData, actions, root, promises, limit, searchParams) => {
        const pagePromise = getMyCourses(
            filters,
            limit,
            searchParams
        ).then(coursesData => {
            pageBuilder(coursesData, currentPage, pageData, actions);
            return renderCourses(root, loadedPages[currentPage]);
        }).catch(Notification.exception);

        promises.push(pagePromise);
    };
};

/**
 * Initialize the searching functionality so we can call it when required.
 *
 * @return {function(Object, Number, Object, Object, Object, Promise, Number, String): void}
 */
const searchFunctionalityCurry = () => {
    resetGlobals();
    return (filters, currentPage, pageData, actions, root, promises, limit, inputValue) => {
        const searchingPromise = getSearchMyCourses(
            filters,
            limit,
            inputValue
        ).then(coursesData => {
            const searchTerm = document.querySelector('.block-eledia_telc_coursesearch [data-action="search"]').value;
            window.console.log('searchTerm');
            window.console.log(searchTerm);
            if (searchTerm.trim() !== '') {
                window.console.log('coursesData');
                window.console.log(coursesData);
                coursesData.courses.forEach(c => {
                    const word = searchTerm.trim();
                    const summary = c.summary;
                    const fullname = c.fullname;
                    const escapedWord = word.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                    const regex = new RegExp(`(${escapedWord})`, 'gi');
                    c.summary = summary.replace(regex, '<mark>$1</mark>');
                    c.fullname = fullname.replace(regex, '<mark>$1</mark>');
                });
            }

            pageBuilder(coursesData, currentPage, pageData, actions);
            return renderCourses(root, loadedPages[currentPage]);
        }).catch(Notification.exception);

        promises.push(searchingPromise);
    };
};

/**
 * Initialize the categoy searching functionality so we can call it when required.
 *
 * @return {function(Object): void}
 */
const catSearchFunctionality = () => {
    return (dropdownContainer, dropdown, dropdownHelper, page, selectedCategories) => {
        const searchingPromise = getSearchCategories().then(categoriesData => {
            // NOTE: Here it goes.
            selectableCategories = categoriesData;
            selectedCategories.forEach((selected) => {
                const categoryIndex = selectableCategories.findIndex(item => item.id == selected.id);
                if (categoryIndex !== -1) {
                    selectableCategories.splice(categoryIndex, 1);
                }
            });
            //return renderCategories(dropdownContainer, dropdown, categoriesData, page);
            const dropdowns = dropdownHelper('categories', dropdownContainer, true);
            return dropdowns.forEach(ddc => {
                return renderCategories(ddc, dropdown, selectableCategories, selectedCategories, page);
            });
            //pageBuilder(categoriesData, actions);
            //return renderCategories(root, loadedPages[currentPage]);
        }).catch(Notification.exception);

        // promises.push(searchingPromise);
        // window.console.log(searchingPromise);
        return searchingPromise;
    };
};

/**
 * Initialize the tags searching functionality so we can call it when required.
 *
 * @return {function(Object): void}
 */
const tagsSearchFunctionality = () => {
    return (dropdownContainer, dropdown, dropdownHelper, page, selectedTags) => {
        const searchingPromise = getSearchTags().then(tagsData => {
            // NOTE: Here it goes.
            selectableTags = tagsData;
            selectedTags.forEach((selected) => {
                const tagsIndex = selectableTags.findIndex(item => item.id == selected.id);
                if (tagsIndex !== -1) {
                    selectableTags.splice(tagsIndex, 1);
                }
            });
            //return renderCategories(dropdownContainer, dropdown, categoriesData, page);
            const dropdowns = dropdownHelper('tags', dropdownContainer, true);
            return dropdowns.forEach(ddc => {
                return renderTags(ddc, dropdown, selectableTags, selectedTags, page);
            });
            //pageBuilder(categoriesData, actions);
            //return renderCategories(root, loadedPages[currentPage]);
        }).catch(Notification.exception);

        // promises.push(searchingPromise);
        // window.console.log(searchingPromise);
        return searchingPromise;
    };
};

/**
 * Initialize the custom field searching functionality so we can call it when required.
 *
 * @return {function(Object): void}
 */
const customfieldSearchFunctionality = () => {
    return (dropdownDiv, dropdown, dropdownHelper, page, searchterm) => {
        const searchingPromise = getSearchCustomfields().then(customfieldsData => {
            const noneOptionIndex = customfieldsData.findIndex(option => option.value === -1);
            // TODO: Fields may have different methods of signaling 'select none' options. -1 might be a legit value.
            if (noneOptionIndex !== -1) {
                customfieldsData.splice(noneOptionIndex, 1);
            }
            customfields[currentCustomField] = customfieldsData;
            if (selectedCustomfields[currentCustomField] === undefined) {
                selectedCustomfields[currentCustomField] = [];
            }
            selectedCustomfields[currentCustomField]?.forEach((selected) => {
                const customfieldIndex = customfields[currentCustomField].findIndex(item => item.value === selected.value);
                if (customfieldIndex !== -1) {
                    customfields[currentCustomField].splice(customfieldIndex, 1);
                }
            });
            filteredCustomfields[currentCustomField] = customfields[currentCustomField];
            if (searchterm && searchterm.trim() !== '') {
                filteredCustomfields[currentCustomField] = filteredCustomfields[currentCustomField].filter(
                    item => item.name.toLowerCase().includes(searchterm.trim().toLowerCase()));
            }
            const dropdowns = dropdownHelper('customfields', dropdownDiv, true);
            return dropdowns.forEach(ddiv => {

                return renderCustomfields(ddiv,
                    dropdown,
                    filteredCustomfields[currentCustomField],
                    selectedCustomfields[currentCustomField],
                    page);
            });
        }).catch(Notification.exception);

        return searchingPromise;
    };
};

/**
 * Initialise the courses list and cards views on page load.
 *
 * @param {object} root The root element for the courses view.
 * @param {function} promiseFunction How do we fetch the courses and what do we do with them?
 * @param {null | string} inputValue What to search for
 * @param {object} params The params
 */
const initializePagedContent = (root, promiseFunction, inputValue = null, params) => {// eslint-disable-line
    const pagingLimit = parseInt(root.find(SELECTORS.courseView.region).attr('data-paging'), 10);
    let itemsPerPage = itemsPerPageFunc(pagingLimit, root);

    const config = { ...{}, ...DEFAULT_PAGED_CONTENT_CONFIG };
    config.eventNamespace = namespace;

    const pagedContentPromise = PagedContentFactory.createWithLimit(
        itemsPerPage,
        (pagesData, actions) => {
            let promises = [];
            pagesData.forEach(pageData => {
                const currentPage = pageData.pageNumber;
                let limit = (pageData.limit > 0) ? pageData.limit : 0;

                // Reset local variables if limits have changed.
                if (+lastLimit !== +limit) {
                    loadedPages = [];
                    courseOffset = 0;
                    lastPage = 0;
                }

                if (lastPage === currentPage) {
                    // If we are on the last page and have it's data then load it from cache.
                    actions.allItemsLoaded(lastPage);
                    promises.push(renderCourses(root, loadedPages[currentPage]));
                    return;
                }

                lastLimit = limit;

                // Get 2 pages worth of data as we will need it for the hidden functionality.
                if (typeof (loadedPages[currentPage + 1]) === 'undefined') {
                    if (typeof (loadedPages[currentPage]) === 'undefined') {
                        limit *= 2;
                    }
                }

                // Get the current applied filters.
                const filters = getFilterValues(root);

                // TODO: exchange with original function.
                window.console.log(getAllFilterValues(root));

                // Call the curried function that'll handle the course promise and any manipulation of it.
                promiseFunction(filters, currentPage, pageData, actions, root, promises, limit, params);
            });
            return promises;
        },
        config
    );

    pagedContentPromise.then((html, js) => {
        registerPagedEventHandlers(root, namespace);
        window.console.log('html');
        window.console.log(html);
        return Templates.replaceNodeContents(root.find(SELECTORS.courseView.region), html, js);
    }).catch(Notification.exception);
};

/**
 * Initialise the list of categories in the search dropdown.
 *
 * @param {string} dropdownContainer The dropdown container element.
 * @param {string} dropdown The dropdown element for the search results.
 * @param {function} promiseFunction How do we fetch the categories and what do we do with them?
 * @param {function} dropdownHelper
 * @param {object} page The page object.
 * @param {object} selectedCategories
 */
const initializeCategorySearchContent = (dropdownContainer,
    dropdown,
    promiseFunction,
    dropdownHelper,
    page,
    selectedCategories) => {// eslint-disable-line
    const categories = promiseFunction(dropdownContainer,
        dropdown,
        dropdownHelper,
        page,
        selectedCategories);
    window.console.log(categories);
};

/**
 * Initialise the list of tags in the search dropdown.
 *
 * @param {string} dropdownContainer The dropdown container element.
 * @param {string} dropdown The dropdown element for the search results.
 * @param {function} promiseFunction How do we fetch the categories and what do we do with them?
 * @param {function} dropdownHelper
 * @param {object} page The page object.
 * @param {object} selectedTags
 */
const initializeTagsSearchContent = (dropdownContainer,
    dropdown,
    promiseFunction,
    dropdownHelper,
    page,
    selectedTags) => {// eslint-disable-line
    const categories = promiseFunction(dropdownContainer,
        dropdown,
        dropdownHelper,
        page,
        selectedTags);
    window.console.log(categories);
};

/**
 * Initialise the list of categories in the search dropdown.
 * TODO: Implement.
 *
 * @param {string} dropdownContainer The dropdown container element.
 * @param {string} dropdown The dropdown element for the search results.
 * @param {function} promiseFunction How do we fetch the categories and what do we do with them?
 * @param {object} dropdownHelper
 * @param {object} page The page object.
 * @param {string} searchterm The current searchterm.
 */
const initializeCustomfieldSearchContent = (dropdownContainer,
    dropdown,
    promiseFunction,
    dropdownHelper,
    page,
    searchterm) => {// eslint-disable-line
    const $customfields = promiseFunction(dropdownContainer,
        dropdown,
        dropdownHelper,
        page,
        searchterm);
    window.console.log($customfields);
};

/**
 * Listen to, and handle events for the eledia_telc_coursesearch block.
 *
 * @param {Object} root The eledia_telc_coursesearch block container element.
 * @param {HTMLElement} page The whole HTMLElement for our block.
 */
const registerEventListeners = (root, page) => {

    CustomEvents.define(root, [
        CustomEvents.events.activate
    ]);

    root.on(CustomEvents.events.activate, SELECTORS.ACTION_ADD_FAVOURITE, (e, data) => {
        const favourite = $(e.target).closest(SELECTORS.ACTION_ADD_FAVOURITE);
        const courseId = getCourseId(favourite);
        addToFavourites(root, courseId);
        data.originalEvent.preventDefault();
    });

    root.on(CustomEvents.events.activate, SELECTORS.ACTION_REMOVE_FAVOURITE, (e, data) => {
        const favourite = $(e.target).closest(SELECTORS.ACTION_REMOVE_FAVOURITE);
        const courseId = getCourseId(favourite);
        removeFromFavourites(root, courseId);
        data.originalEvent.preventDefault();
    });

    root.on(CustomEvents.events.activate, SELECTORS.FAVOURITE_ICON, (e, data) => {
        data.originalEvent.preventDefault();
    });

    root.on(CustomEvents.events.activate, SELECTORS.ACTION_HIDE_COURSE, (e, data) => {
        const target = $(e.target).closest(SELECTORS.ACTION_HIDE_COURSE);
        const courseId = getCourseId(target);
        hideCourse(root, courseId);
        data.originalEvent.preventDefault();
    });

    root.on(CustomEvents.events.activate, SELECTORS.ACTION_SHOW_COURSE, (e, data) => {
        const target = $(e.target).closest(SELECTORS.ACTION_SHOW_COURSE);
        const courseId = getCourseId(target);
        showCourse(root, courseId);
        data.originalEvent.preventDefault();
    });

    // Searching functionality event handlers.
    const input = page.querySelector(SELECTORS.region.searchInput);
    const clearIcons = page.querySelectorAll(SELECTORS.region.clearIcon);
    const catinputs = page.querySelectorAll(SELECTORS.cat.input);
    const tagsinputs = page.querySelectorAll(SELECTORS.tags.input);
    const customInputs = page.querySelectorAll(SELECTORS.customfields.input);
    const clearCatIcons = page.querySelectorAll(SELECTORS.cat.clearIcon);
    const clearTagsIcons = page.querySelectorAll(SELECTORS.tags.clearIcon);
    const clearCustomfieldIcons = page.querySelectorAll(SELECTORS.customfields.clearIcon);
    const customClass = SELECTORS.customfields.searchfield;
    const catSelectable = SELECTORS.cat.selectableItem;
    const catSelected = SELECTORS.cat.selectedItem;
    const tagsSelectable = SELECTORS.tags.selectableItem;
    const tagsSelected = SELECTORS.tags.selectedItem;
    const customfieldSelectable = SELECTORS.customfields.selectableItem;
    const customfieldSelected = SELECTORS.customfields.selectedItem;
    const groupingFilter = page.querySelectorAll(SELECTORS.FILTER_GROUPING);

    clearIcons.forEach(icon => {
        icon.addEventListener('click', () => {
            input.value = '';
            searchTerm = '';
            input.focus();
            clearIcons.forEach(ci => {
                clearSearch(ci, root);
            });
        });
    });

    clearCatIcons.forEach(icon => {
        icon.addEventListener('click', () => {
            window.console.log('CLICKED clearCatIcon');
            catSearchTerm = '';
            catinputs.forEach(input => {
                input.value = '';
            });
            catinput().focus();
            clearCatIcons.forEach(i => {
                clearCatSearch(i);
            });
            initializeCategorySearchContent(
                SELECTORS.cat.dropdownDiv,
                SELECTORS.cat.dropdown,
                catSearchFunctionality(),
                dropdownHelper,
                page,
                selectedCategories);
        });
    });

    clearTagsIcons.forEach(icon => {
        icon.addEventListener('click', () => {
            window.console.log('CLICKED clearCatIcon');
            tagsSearchTerm = '';
            tagsinputs.forEach(input => {
                input.value = '';
            });
            tagsinput().focus();
            clearTagsIcons.forEach(i => {
                clearCatSearch(i);
            });
            initializeTagsSearchContent(
                SELECTORS.tags.dropdownDiv,
                SELECTORS.tags.dropdown,
                tagsSearchFunctionality(),
                dropdownHelper,
                page,
                selectedTags);
        });
    });

    clearCustomfieldIcons.forEach(icon => {
        currentCustomField = icon.dataset.customfieldid;
        const customfieldInputs = page.querySelectorAll(customClass + currentCustomField);
        icon.addEventListener('click', () => {
            window.console.log('CLICKED clearCustomfieldIcon');
            customfieldInputs.forEach(input => {
                input.value = '';
            });
            customfieldInput(customClass + currentCustomField).focus();
            clearCustomfieldSingleIconSearch(icon);
            initializeCustomfieldSearchContent(
                SELECTORS.customfields.dropdownDiv + currentCustomField,
                SELECTORS.customfields.dropdown + currentCustomField,
                customfieldSearchFunctionality(),
                dropdownHelper,
                page,
                '');
        });
    });

    input.addEventListener('input', debounce(() => {
        if (input.value === '') {
            searchTerm = '';
            clearIcons.forEach(icon => {
                clearSearch(icon, root);
            });
        } else {
            clearIcons.forEach(icon => {
                activeSearch(icon);
            });
            searchTerm = input.value.trim();
            initializePagedContent(root, searchFunctionalityCurry(), input.value.trim(), getParams());
        }
    }, 1000));

    // Flow:
    // eventListener
    // initializeCustomfieldSearchContent()
    // customfieldSearchFunctionality()
    // renderCustomfields()
    customInputs.forEach(i => {
        i.addEventListener('click', (e) => {
            currentCustomField = e.target.dataset.customfieldid;
            const currentSearchterm = e.target.value.toLowerCase();
            initializeCustomfieldSearchContent(
                SELECTORS.customfields.dropdownDiv + currentCustomField,
                SELECTORS.customfields.dropdown + currentCustomField,
                customfieldSearchFunctionality(),
                dropdownHelper,
                page,
                currentSearchterm);
        });
        i.addEventListener('input', debounce((e) => {
            currentCustomField = e.target.dataset.customfieldid;
            const currentSearchterm = e.target.value.toLowerCase();
            if (currentSearchterm === '') {
                clearCustomfieldSearch(clearCustomfieldIcons);
                manageCustomfielddropdownItems(
                    e,
                    customfieldSelected,
                    customfieldSelectable,
                    SELECTORS.customfields.dropdownDiv + currentCustomField,
                    SELECTORS.customfields.dropdown + currentCustomField,
                    dropdownHelper,
                    page);
            } else {
                filteredCustomfields[currentCustomField] = filteredCustomfields[currentCustomField].filter(
                    item => item.name.toLowerCase().includes(currentSearchterm.toLowerCase().trim()));
                activeCustomfieldSearch(clearCustomfieldIcons);
                manageCustomfielddropdownItems(
                    e,
                    customfieldSelected,
                    customfieldSelectable,
                    SELECTORS.customfields.dropdownDiv + currentCustomField,
                    SELECTORS.customfields.dropdown + currentCustomField,
                    dropdownHelper,
                    page);
            }
        }, 1000));
    });

    // Initialize category search dropdown on first click.
    catinputs.forEach(ci => {
        ci.addEventListener('click', () => {
            initializeCategorySearchContent(
                SELECTORS.cat.dropdownDiv,
                SELECTORS.cat.dropdown,
                catSearchFunctionality(),
                dropdownHelper,
                page,
                selectedCategories);
        });
    });

    catinputs.forEach(catinput => {
        catinput.addEventListener('input', debounce(() => {
            if (catinput.value === '') {
                clearCatIcons.forEach(icon => {
                    clearCatSearch(icon);
                });
                copyCatValues('');
                catSearchTerm = '';
                initializeCategorySearchContent(
                    SELECTORS.cat.dropdownDiv,
                    SELECTORS.cat.dropdown,
                    catSearchFunctionality(),
                    dropdownHelper,
                    page,
                    selectedCategories);
            } else {
                window.console.log('catinput.value');
                window.console.log(catinput.value);
                clearCatIcons.forEach(icon => {
                    activeSearch(icon);
                });
                catSearchTerm = catinput.value.trim();
                copyCatValues(catinput.value);
                initializeCategorySearchContent(
                    SELECTORS.cat.dropdownDiv,
                    SELECTORS.cat.dropdown,
                    catSearchFunctionality(),
                    dropdownHelper,
                    page,
                    selectedCategories);
            }
        }, 1000));
    });

    /**
     * Copies the given value to all category input fields.
     *
     * @param {string} value The value to set for all category input fields.
     */
    function copyCatValues(value) {
        catinputs.forEach(ci => {
            ci.value = value;
        });
    }

    // Initialize tags search dropdown on first click.
    tagsinputs.forEach(tagsinput => {
        tagsinput.addEventListener('click', () => {
            initializeTagsSearchContent(
                SELECTORS.tags.dropdownDiv,
                SELECTORS.tags.dropdown,
                tagsSearchFunctionality(),
                dropdownHelper,
                page,
                selectedTags);
        });
    });

    tagsinputs.forEach(tagsinput => {
        tagsinput.addEventListener('input', debounce(() => {
            if (tagsinput.value === '') {
                clearTagsIcons.forEach(icon => {
                    clearCatSearch(icon);
                });
                tagsSearchTerm = '';
                copyTagsValues('');
                initializeTagsSearchContent(
                    SELECTORS.tags.dropdownDiv,
                    SELECTORS.tags.dropdown,
                    tagsSearchFunctionality(),
                    dropdownHelper,
                    page,
                    selectedTags);
            } else {
                clearTagsIcons.forEach(icon => {
                    activeSearch(icon);
                });
                tagsSearchTerm = tagsinput.value.trim();
                copyTagsValues(tagsinput.value);
                initializeTagsSearchContent(
                    SELECTORS.tags.dropdownDiv,
                    SELECTORS.tags.dropdown,
                    tagsSearchFunctionality(),
                    dropdownHelper,
                    page,
                    selectedTags);
            }
        }, 1000));
    });



    /**
     * Copies the given value to all tags input fields.
     *
     * @param {string} value The value to set for all tags input fields.
     */
    function copyTagsValues(value) {
        tagsinputs.forEach(ti => {
            ti.value = value;
        });
    }


    document.body.addEventListener('click', manageCategorydropdownCollapse);
    document.body.addEventListener('click', manageTagsdropdownCollapse);
    document.body.addEventListener('click', manageCustomfielddropdownCollapse);

    document.body.addEventListener('click', (e) => {
        if (e.target.classList.contains(catSelected) || e.target.classList.contains(catSelectable)) {
            e.preventDefault();
            manageCategorydropdownItems(
                e,
                catSelected,
                catSelectable,
                SELECTORS.cat.dropdownDiv,
                SELECTORS.cat.dropdown,
                dropdownHelper,
                page);
            initializePagedContent(root, searchFunctionalityCurry(), input.value.trim(), getParams());
        }
    });

    document.body.addEventListener('click', (e) => {
        if (e.target.classList.contains(tagsSelected) || e.target.classList.contains(tagsSelectable)) {
            e.preventDefault();
            manageTagsdropdownItems(
                e,
                tagsSelected,
                tagsSelectable,
                SELECTORS.tags.dropdownDiv,
                SELECTORS.tags.dropdown,
                dropdownHelper,
                page);
            initializePagedContent(root, searchFunctionalityCurry(), input.value.trim(), getParams());
        }
    });

    document.body.addEventListener('click', (e) => {
        if (e.target.classList.contains(customfieldSelected) || e.target.classList.contains(customfieldSelectable)) {
            e.preventDefault();
            const currentId = e.target.dataset.customfieldid;
            manageCustomfielddropdownItems(
                e,
                customfieldSelected,
                customfieldSelectable,
                SELECTORS.customfields.dropdownDiv + currentId,
                SELECTORS.customfields.dropdown + currentId,
                dropdownHelper,
                page);
            // TODO: Initialize search on *every* change.
            initializePagedContent(root, searchFunctionalityCurry(), input.value.trim(), getParams());
        }
    });

    document.body.addEventListener('click', (e) => {
        const expandLink = e.target;
        if (expandLink.classList.contains('eledia-telc-expandsummary')) {
            e.preventDefault();
            const summary = e.target.previousElementSibling;
            expandLink.classList.add('d-none');
            summary.classList.remove('summary-fadeout');
        }
    });

    document.body.addEventListener('click', (e) => {
        const collapseLink = e.target;
        if (collapseLink.classList.contains('eledia-telc-collapsesummary')) {
            e.preventDefault();
            const summary = e.target.parentElement;
            const expandLink = summary.nextElementSibling;
            expandLink.classList.remove('d-none');
            summary.classList.add('summary-fadeout');
        }
    });

    groupingFilter.forEach(filter => {
        const filterType = filter.dataset.value;
        filter.addEventListener('click', () => {
            courseInProgress = filterType;
        });
    });

};
/*
 * @param {string} dataSource
 * @param {string} selector
 * @param {boolean} all
 */
const dropdownHelper = (dataSource, selector, all = false) => {

    let data;
    let parts;
    let index;
    let reverseState;
    const collapse = '[data-region="filter"][role="search"] > .row > .customfields-collapse > .collapse-filter ';
    const noCollapse = '[data-region="filter"][role="search"] > .row > .collapse-filter ';
    switch (dataSource) {
        case 'categories':
            reverseState = catReverseState;
            data = selectedCategories;
            break;
        case 'tags':
            reverseState = tagsReverseState;
            data = selectedTags;
            break;
        case 'customfields':
            if (all) {
                break;
            }
            if (document.querySelectorAll(selector).length === 1) {
                return selector;
            }
            reverseState = customReverseState;
            parts = selector.split('-');
            index = parseInt(parts[parts.length - 1]);
            data = selectedCustomfields[index] || [];
            break;
        default:
            throw new Error('Invalid data source "' + dataSource + '" for dropdownHelper');
    }
    if (all) {
        if (document.querySelectorAll(selector).length === 1) {
            return [selector, selector];
        }
        return [collapse + selector, noCollapse + selector];
    }
    if (document.querySelectorAll(selector).length === 1) {
        return selector;
    }
    if (data.length === 0) {
        return reverseState ? noCollapse + selector : collapse + selector;
    }
    return reverseState ? collapse + selector : noCollapse + selector;
};

/**
 * Eventlistener helper to return the correct catinput element.
 *
 */
export const catinput = () => {
    const catinputs = document.querySelectorAll(SELECTORS.cat.input);
    return selectedCategories.length === 0 && !catReverseState ? catinputs[1] : catinputs[0];
};

/**
 * Eventlistener helper to return the correct tagsinput element.
 *
 */
export const tagsinput = () => {
    const tagsinputs = document.querySelectorAll(SELECTORS.tags.input);
    return selectedTags.length === 0 ? tagsinputs[1] : tagsinputs[0];
};

/**
 * Eventlistener helper to return the correct customfield input element.
 * @param {String} customfieldClass
 */
export const customfieldInput = (customfieldClass) => {
    if (document.querySelectorAll(customfieldClass).length === 1) {
        const customfieldInput = document.querySelector(customfieldClass);
        return customfieldInput;
    }
    const customfieldInputs = document.querySelectorAll(customfieldClass);
    return selectedCustomfields[currentCustomField].length === 0 ? customfieldInputs[1] : customfieldInputs[0];
};

/**
 * Reset the search icon and trigger the init for the block.
 *
 * @param {HTMLElement} clearIcon Our closing icon to manipulate.
 * @param {Object} root The eledia_telc_coursesearch block container element.
 */
export const clearSearch = (clearIcon, root) => {
    clearIcon.classList.add('d-none');
    init(root);
};

/**
 * Reset the search icon and trigger the init for the category search.
 *
 * @param {HTMLElement} clearCatIcon Our closing icon to manipulate.
 */
export const clearCatSearch = (clearCatIcon) => {
    clearCatIcon.classList.add('d-none');
};

/**
 * Reset the search icon and trigger the init for the category search.
 *
 * @param {HTMLElement} clearCustomfieldIcons Our closing icon to manipulate.
 */
export const clearCustomfieldSearch = (clearCustomfieldIcons) => {
    clearCustomfieldIcons.forEach(icon => {
        if (icon.dataset.customfieldid === currentCustomField) {
            icon.classList.add('d-none');
        }
    });
};

/**
 * Reset the search icon and trigger the init for the category search.
 *
 * @param {HTMLElement} icon Our closing icon to manipulate.
 */
export const clearCustomfieldSingleIconSearch = icon => { icon.classList.add('d-none'); };

/**
 * Change the searching icon to its' active state. *
 *
 * @param {HTMLElement} clearCustomfieldIcons Our closing icon to manipulate.
 */
export const activeCustomfieldSearch = (clearCustomfieldIcons) => {
    clearCustomfieldIcons.forEach(icon => {
        if (icon.dataset.customfieldid === currentCustomField) {
            icon.classList.remove('d-none');
        }
    });
};

/**
 * Change the searching icon to its' active state.
 *
 * @param {HTMLElement} clearIcon Our closing icon to manipulate.
 */
const activeSearch = (clearIcon) => {
    clearIcon.classList.remove('d-none');
};

/**
 * Hide category dropdown if clicked outside category search.
 *
 * @param {PointerEvent} e a click.
 */
const manageCategorydropdownCollapse = (e) => {
    const page = document.querySelector(SELECTORS.region.selectBlock);

    const catDropdowns = page.querySelectorAll(SELECTORS.cat.dropdown);
    const catInputs = page.querySelectorAll(SELECTORS.cat.input);

    // We don't want to close the dropdown if clicking inside it or the input field.
    for (const dropdown of catDropdowns) {
        if (dropdown.contains(e.target)) {
            return;
        }
    }
    for (const input of catInputs) {
        if (input.contains(e.target)) {
            return;
        }
    }
    catReverseState = false;

    if (e.target.classList.contains('catprevent') && !e.target.classList.contains('fa-xmark')) {
        catDropdowns.forEach(dropdown => {
            dropdown.style.display = 'block';
        });
        return;
    }

    catDropdowns.forEach(dropdown => {
        dropdown.style.display = 'none';
    });
    const catSelectElements = document.querySelectorAll('.collapse-filter-category');
    if (selectedCategories.length === 0) {
        catSelectElements.forEach(e => {
            if (!e.classList.contains('collapse-enabled')) {
                e.classList.add('collapse-enabled');
            }
        });
        return;
    }
    catSelectElements.forEach(e => {
        if (e.classList.contains('collapse-enabled')) {
            e.classList.remove('collapse-enabled');
        }
    });

};

/**
 * Hide tags dropdown if clicked outside category search.
 *
 * @param {PointerEvent} e a click.
 */
const manageTagsdropdownCollapse = (e) => {
    const page = document.querySelector(SELECTORS.region.selectBlock);
    const tagsDropdowns = page.querySelectorAll(SELECTORS.tags.dropdown);
    const tagsInputs = page.querySelectorAll(SELECTORS.tags.input);
    // if (!e.target.classList.contains('tagsprevent') && !e.target.classList.contains('fa-xmark')) {
    //     tagsDropdown.style.display = 'none';
    // } else if (e.target.classList.contains('tagsprevent') && !e.target.classList.contains('fa-xmark')) {
    //     tagsDropdown.style.display = 'block';
    // }

    for (const dropdown of tagsDropdowns) {
        if (dropdown.contains(e.target)) {
            return;
        }
    }
    for (const input of tagsInputs) {
        if (input.contains(e.target)) {
            return;
        }
    }
    tagsReverseState = false;

    if (e.target.classList.contains('tagsprevent') && !e.target.classList.contains('fa-xmark')) {
        tagsDropdowns.forEach(dropdown => {
            dropdown.style.display = 'block';
        });
        return;
    }

    tagsDropdowns.forEach(dropdown => {
        dropdown.style.display = 'none';
    });
    const tagsSelectElements = document.querySelectorAll('.collapse-filter-tags');
    if (selectedTags.length === 0) {
        tagsSelectElements.forEach(e => {
            if (!e.classList.contains('collapse-enabled')) {
                e.classList.add('collapse-enabled');
            }
        });
        return;
    }
    tagsSelectElements.forEach(e => {
        if (e.classList.contains('collapse-enabled')) {
            e.classList.remove('collapse-enabled');
        }
    });
};

/**
 * TODO: complete
 * Hide category dropdown if clicked outside category search.
 *
 * @param {PointerEvent} e a click.
 * @param {string} selected
 * @param {string} selectable
 * @param {string} dropdownDiv
 * @param {string} dropdown
 * @param {object} dropdownHelper
 * @param {object} page
 **/
const manageCategorydropdownItems = (e, selected, selectable, dropdownDiv, dropdown, dropdownHelper, page) => {// eslint-disable-line
    const template = 'block_eledia_telc_coursesearch/nav-category-dropdown';
    const categoryId = e.target.dataset.catId;
    dropdownDiv = dropdownHelper('categories', dropdownDiv);
    dropdown = dropdownHelper('categories', dropdown);
    const selectedLength = selectedCategories.length;
    if (e.target.classList.contains(selectable)) {
        const categoryIndex = selectableCategories.findIndex(value => value.id == categoryId);
        selectedCategories.push(selectableCategories.splice(categoryIndex, 1)[0]);
        if ((!selectedLength && selectedCategories.length > 0)) {
            window.console.log('reversed1');
            catReverseState = !catReverseState;
        }
    } else {
        const categoryIndex = selectedCategories.findIndex(value => value.id == categoryId);
        selectableCategories.push(selectedCategories.splice(categoryIndex, 1)[0]);
        if ((selectedLength && selectedCategories.length === 0)) {
            window.console.log('reversed2');
            catReverseState = !catReverseState;
        }
    }
    return Templates.renderForPromise(template, {
        categories: selectableCategories,
        catselections: selectedCategories,
    }).then(({ html, js }) => {
        const renderResult = Templates.replaceNodeContents(dropdownDiv, html, js);
        const catDropdown = page.querySelector(dropdown);
        catDropdown.style.display = 'block';
        return renderResult;
    }).catch(error => displayException(error));
};

/**
 * Hide tags dropdown if clicked outside category search.
 *
 * @param {PointerEvent} e a click.
 * @param {string} selected
 * @param {string} selectable
 * @param {string} dropdownDiv
 * @param {string} dropdown
 * @param {object} dropdownHelper
 * @param {object} page
 **/
const manageTagsdropdownItems = (e, selected, selectable, dropdownDiv, dropdown, dropdownHelper, page) => {// eslint-disable-line
    const template = 'block_eledia_telc_coursesearch/nav-tags-dropdown';
    const tagsId = e.target.dataset.tagsId;
    dropdownDiv = dropdownHelper('tags', dropdownDiv);
    dropdown = dropdownHelper('tags', dropdown);
    if (e.target.classList.contains(selectable)) {
        const tagsIndex = selectableTags.findIndex(value => value.id == tagsId);
        selectedTags.push(selectableTags.splice(tagsIndex, 1)[0]);
    } else {
        const tagsIndex = selectedTags.findIndex(value => value.id == tagsId);
        selectableTags.push(selectedTags.splice(tagsIndex, 1)[0]);
    }
    return Templates.renderForPromise(template, {
        tags: selectableTags,
        tagsselections: selectedTags,
    }).then(({ html, js }) => {
        const renderResult = Templates.replaceNodeContents(dropdownDiv, html, js);
        const tagsDropdown = page.querySelector(dropdown);
        tagsDropdown.style.display = 'block';
        return renderResult;
    }).catch(error => displayException(error));
};

/**
 * Hide customfield dropdown if clicked outside customfield search.
 *
 */
function manageCustomfielddropdownCollapse() {
    const page = document.querySelector(SELECTORS.region.selectBlock);
    const customfieldDropdowns = page.querySelectorAll(SELECTORS.customfields.dropdownAll);
    customfieldDropdowns.forEach(dropdown => {
        if (!dropdown.classList.contains(SELECTORS.customfields.dropdown + currentCustomField)) {
            dropdown.style.display = 'none';
            customReverseState = false;
        } else {
            dropdown.style.display = 'block';
        }
    });
    customfields.forEach((v, i) => {
        if (i !== currentCustomField) {
            const customFields = document.getElementsByClassName('collapse-cfid-' + i);
            const collapseDisabled = (selectedCustomfields[i] !== undefined && selectedCustomfields[i].length);
            Array.from(customFields).forEach(cf => {
                if (collapseDisabled) {
                    cf.classList.remove('collapse-enabled');
                } else if (!cf.classList.contains('collapse-enabled')) {
                    cf.classList.add('collapse-enabled');
                }
            });
        }
    });
}

/**
 * Hide customfield dropdown if clicked outside customfield search.
 *
 * @param {PointerEvent} e a click.
 * @param {string} selected
 * @param {string} selectable
 * @param {string} dropdownDiv
 * @param {string} dropdown
 * @param {object} dropdownHelper
 * @param {object} page
 **/
const manageCustomfielddropdownItems = (e, selected, selectable, dropdownDiv, dropdown, dropdownHelper, page) => {
    // const template = 'block_eledia_telc_coursesearch/nav-customfield-dropdown';
    const customfieldValue = e.target.dataset.selectvalue;
    const customfieldName = e.target.dataset.selectname;
    const customfieldId = e.target.dataset.customfieldid;
    window.console.log('manageCustomfielddropdownItems');
    window.console.log(e);
    window.console.log(customfieldId);
    if (e.target.classList.contains(selectable)) {
        const customfieldIndex = filteredCustomfields[customfieldId].findIndex(item => item.value == customfieldValue);
        selectedCustomfields[customfieldId].push(filteredCustomfields[customfieldId].splice(customfieldIndex, 1)[0]);
        selectedCustomfields[customfieldId].sort((a, b) => {
            return ('' + a.name).localeCompare(b.name);
        });
        //filteredCustomfields[customfieldId].splice(
        //    filteredCustomfields[customfieldId].findIndex(item => item.value == customfieldValue),
        //    1);
        window.console.log('selectable');
        window.console.log(selectedCustomfields);
    } else if (e.target.classList.contains(selected)) {
        const customfieldIndex = selectedCustomfields[customfieldId].findIndex(item => item.value == customfieldValue);
        const interchangedValue = selectedCustomfields[customfieldId].splice(customfieldIndex, 1)[0];
        // customfields[customfieldId].push(interchangedValue);
        const searchField = page.querySelector(".customsearch-" + customfieldId);
        window.console.log('searchField');
        window.console.log(searchField);
        if (searchField.value === '' || customfieldName.toLowerCase().includes(searchField.value.trim().toLowerCase())) {
            filteredCustomfields[customfieldId].push(interchangedValue);
        }
        filteredCustomfields[customfieldId].sort((a, b) => {
            return ('' + a.name).localeCompare(b.name);
        });
        window.console.log('selected');
        window.console.log(selectedCustomfields);
    }
    const dropdowns = dropdownHelper('customfields', dropdownDiv, true);
    return dropdowns.forEach(ddiv => {

        return renderCustomfields(ddiv,
            dropdown,
            filteredCustomfields[customfieldId],
            selectedCustomfields[customfieldId],
            page);
    });
};

/**
 * Intialise the courses list and cards views on page load.
 *
 * @param {object} root The root element for the courses view.
 */
export const init = root => {
    // TODO: include course categories custom fields.
    root = $(root);
    loadedPages = [];
    lastPage = 0;
    courseOffset = 0;

    if (!root.attr('data-init')) {
        const page = document.querySelector(SELECTORS.region.selectBlock);
        registerEventListeners(root, page);
        namespace = "block_eledia_telc_coursesearch_" + root.attr('id') + "_" + Math.random();
        root.attr('data-init', true);
    }

    initializePagedContent(root, standardFunctionalityCurry(), null, getParams());
};

/**
 * Reset the courses views to their original
 * state on first page load.courseOffset
 *
 * This is called when configuration has changed for the event lists
 * to cause them to reload their data.
 *
 * @param {Object} root The root element for the timeline view.
 */
export const reset = root => {
    // TODO: Include categories and custom fields. May be included automatically.
    if (loadedPages.length > 0) {
        const filters = getFilterValues(root);
        // If the display mode is changed to 'summary' but the summary display has not been loaded yet,
        // we need to re-fetch the courses to include the course summary text.
        if (filters.display === 'summary' && !summaryDisplayLoaded) {
            const page = document.querySelector(SELECTORS.region.selectBlock);
            const input = page.querySelector(SELECTORS.region.searchInput);
            if (input.value !== '') {
                initializePagedContent(root, searchFunctionalityCurry(), input.value.trim());
            } else {
                initializePagedContent(root, standardFunctionalityCurry());
            }
        } else {
            loadedPages.forEach((courseList, index) => {
                let pagedContentPage = getPagedContentContainer(root, index);
                renderCourses(root, courseList).then((html, js) => {
                    return Templates.replaceNodeContents(pagedContentPage, html, js);
                }).catch(Notification.exception);
            });
        }
    } else {
        init(root);
    }
};
