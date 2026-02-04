<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * External library class for eledia_coursesearch block
 *
 * @package block_eledia_coursesearch
 * @copyright 2025 eLeDia GmbH (made possible by TU Ilmenau)
 * @author Immanuel Pasanec <support@eledia.de>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_eledia_coursesearch;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use core_external\external_value;
use core_course_category;
use core_course_external;
use coursecat_helper;
use context_system;
use core_course\external\course_summary_exporter;

require_once($CFG->dirroot . '/course/externallib.php');
require_once($CFG->dirroot . '/course/renderer.php');

/**
 * External library class
 *
 * @package block_eledia_coursesearch
 * @copyright 2025 eLeDia GmbH (made possible by TU Ilmenau)
 * @author Immanuel Pasanec <support@eledia.de>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class externallib extends external_api {
    /**
     * Define input parameters
     *
     * @return external_function_parameters
     */
    public static function get_data_parameters() {
        return new external_function_parameters([]);
    }

    /**
     * Webservice logic to return courses for the logged-in user
     *
     * @return array
     */
    public static function get_data() {
        global $USER, $DB;

        // Ensure the user is logged in.
        $context = \context_system::instance();
        self::validate_context($context);

        // Get the user's enrolled courses.
        $sql = "SELECT c.id, c.fullname, c.shortname
                  FROM {course} c
                  JOIN {enrol} e ON e.courseid = c.id
                  JOIN {user_enrolments} ue ON ue.enrolid = e.id
                 WHERE ue.userid = :userid AND c.visible = 1";
        $params = ['userid' => $USER->id];
        $courses = $DB->get_records_sql($sql, $params);

        // Format the data to return.
        $result = [];
        foreach ($courses as $course) {
            $result[] = [
                'id' => $course->id,
                'fullname' => $course->fullname,
                'shortname' => $course->shortname,
            ];
        }

        return $result;
    }

    /**
     * Define the output structure for the webservice
     *
     * @return external_multiple_structure
     */
    public static function get_data_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Course ID'),
                'fullname' => new external_value(PARAM_TEXT, 'Full name of the course'),
                'shortname' => new external_value(PARAM_TEXT, 'Short name of the course'),
            ])
        );
    }

    /**
     * Extract subset by strings
     *
     * @param array $searchstrings Search strings
     * @param array $result Result array
     * @param int $allowedrecursions Maximum allowed recursions
     * @return array
     */
    private static function extract_subset_by_strings(array $searchstrings, array $result, int $allowedrecursions = 20) {
        if (empty($searchstrings) || $allowedrecursions < count($searchstrings)) {
            return $result;
        }
        $currentsearchstr = array_shift($searchstrings);
        $result['courses'] = array_map(function ($course) use ($currentsearchstr) {
            $course = (array)$course;
            $matchfound = array_reduce(array_keys($course), function ($carry, $key) use ($course, $currentsearchstr) {
                return $carry || (is_string($course[$key]) && preg_match("/" . $currentsearchstr . "/i", $course[$key]));
            }, false);
            return $matchfound ? $course : null;
        }, $result['courses']);

        $result['courses'] = array_filter($result['courses']);

        return self::extract_subset_by_strings($searchstrings, $result, $allowedrecursions);
    }

    /**
     * Get enrolled courses by timeline classification parameters
     *
     * @return external_function_parameters
     */
    public static function get_enrolled_courses_by_timeline_classification_parameters() {
        return new external_function_parameters(
            [
                'classification' => new external_value(PARAM_ALPHA, 'future, inprogress, or past'),
                'limit' => new external_value(PARAM_INT, 'Result set limit', VALUE_DEFAULT, 0),
                'offset' => new external_value(PARAM_INT, 'Result set offset', VALUE_DEFAULT, 0),
                'sort' => new external_value(PARAM_TEXT, 'Sort string', VALUE_DEFAULT, null),
                'customfieldname' => new external_value(
                    PARAM_ALPHANUMEXT,
                    'Used when classification = customfield',
                    VALUE_DEFAULT,
                    null
                ),
                'customfieldvalue' => new external_value(
                    PARAM_RAW,
                    'Used when classification = customfield',
                    VALUE_DEFAULT,
                    null
                ),
                'searchvalue' => new external_value(
                    PARAM_RAW,
                    'The value a user wishes to search against',
                    VALUE_DEFAULT,
                    null
                ),
                'requiredfields' => new \core_external\external_multiple_structure(
                    new external_value(PARAM_ALPHANUMEXT, 'Field name to be included from the results', VALUE_DEFAULT),
                    'Array of the only field names that need to be returned. If empty, all fields will be returned.',
                    VALUE_DEFAULT,
                    []
                ),
            ]
        );
    }

    /**
     * Get enrolled courses by timeline classification
     *
     * @param string $classification Classification
     * @param int $limit Limit
     * @param int $offset Offset
     * @param string|null $sort Sort order
     * @param string|null $customfieldname Custom field name
     * @param string|null $customfieldvalue Custom field value
     * @param string|null $searchvalue Search value
     * @param array $requiredfields Required fields
     * @return array
     */
    public static function get_enrolled_courses_by_timeline_classification(
        string $classification,
        int $limit = 0,
        int $offset = 0,
        ?string $sort = null,
        ?string $customfieldname = null,
        ?string $customfieldvalue = null,
        ?string $searchvalue = null,
        array $requiredfields = []
    ) {
        $rawcoursedata = self::get_enrolled_courses_by_timeline_classification_raw(
            $classification,
            $limit,
            $offset,
            $sort,
            $customfieldname,
            $customfieldvalue,
            '', // Searchvalue.
            $requiredfields
        );

        return self::extract_subset_by_strings([ $searchvalue ], $rawcoursedata);
    }

    /**
     * Get enrolled courses by timeline classification raw
     *
     * @param string $classification Classification
     * @param int $limit Limit
     * @param int $offset Offset
     * @param string|null $sort Sort order
     * @param string|null $customfieldname Custom field name
     * @param string|null $customfieldvalue Custom field value
     * @param string|null $searchvalue Search value
     * @param array $requiredfields Required fields
     * @return array
     */
    private static function get_enrolled_courses_by_timeline_classification_raw(
        string $classification,
        int $limit = 0,
        int $offset = 0,
        ?string $sort = null,
        ?string $customfieldname = null,
        ?string $customfieldvalue = null,
        ?string $searchvalue = null,
        array $requiredfields = []
    ) {
        global $CFG, $PAGE, $USER;
        require_once($CFG->dirroot . '/course/lib.php');

        $params = self::validate_parameters(
            self::get_enrolled_courses_by_timeline_classification_parameters(),
            [
                'classification' => $classification,
                'limit' => $limit,
                'offset' => $offset,
                'sort' => $sort,
                'customfieldvalue' => $customfieldvalue,
                'searchvalue' => $searchvalue,
                'requiredfields' => $requiredfields,
            ]
        );

        $classification = $params['classification'];
        $limit = $params['limit'];
        $offset = $params['offset'];
        $sort = $params['sort'];
        $customfieldvalue = $params['customfieldvalue'];
        $searchvalue = clean_param($params['searchvalue'], PARAM_TEXT);
        $requiredfields = $params['requiredfields'];

        switch ($classification) {
            case COURSE_TIMELINE_ALLINCLUDINGHIDDEN:
                break;
            case COURSE_TIMELINE_ALL:
                break;
            case COURSE_TIMELINE_PAST:
                break;
            case COURSE_TIMELINE_INPROGRESS:
                break;
            case COURSE_TIMELINE_FUTURE:
                break;
            case COURSE_FAVOURITES:
                break;
            case COURSE_TIMELINE_HIDDEN:
                break;
            case COURSE_TIMELINE_SEARCH:
                break;
            case COURSE_CUSTOMFIELD:
                break;
            default:
                throw new invalid_parameter_exception('Invalid classification');
        }

        self::validate_context(\context_user::instance($USER->id));
        $exporterfields = array_keys(course_summary_exporter::define_properties());
        // Get the required properties from the exporter fields based on the required fields.
        $requiredproperties = array_intersect($exporterfields, $requiredfields);
        // If the resulting required properties is empty, fall back to the exporter fields.
        if (empty($requiredproperties)) {
            $requiredproperties = $exporterfields;
        }

        $fields = join(',', $requiredproperties);
        $hiddencourses = get_hidden_courses_on_timeline();

        // If the timeline requires really all courses, get really all courses.
        if ($classification == COURSE_TIMELINE_ALLINCLUDINGHIDDEN) {
            $courses = course_get_enrolled_courses_for_logged_in_user(0, $offset, $sort, $fields, COURSE_DB_QUERY_LIMIT);

            // Otherwise if the timeline requires the hidden courses then restrict the result to only $hiddencourses.
        } else if ($classification == COURSE_TIMELINE_HIDDEN) {
            $courses = course_get_enrolled_courses_for_logged_in_user(
                0,
                $offset,
                $sort,
                $fields,
                COURSE_DB_QUERY_LIMIT,
                $hiddencourses
            );

            // Otherwise get the requested courses and exclude the hidden courses.
        } else if ($classification == COURSE_TIMELINE_SEARCH) {
            // Prepare the search API options.
            $searchcriteria['search'] = $searchvalue;
            $options = ['idonly' => true];
            $courses = course_get_enrolled_courses_for_logged_in_user_from_search(
                0,
                $offset,
                $sort,
                $fields,
                COURSE_DB_QUERY_LIMIT,
                $searchcriteria,
                $options
            );
        } else {
            $courses = course_get_enrolled_courses_for_logged_in_user(
                0,
                $offset,
                $sort,
                $fields,
                COURSE_DB_QUERY_LIMIT,
                [],
                $hiddencourses
            );
        }

        $favouritecourseids = [];
        $ufservice = \core_favourites\service_factory::get_service_for_user_context(\context_user::instance($USER->id));
        $favourites = $ufservice->find_favourites_by_type('core_course', 'courses');

        if ($favourites) {
            $favouritecourseids = array_map(
                function ($favourite) {
                    return $favourite->itemid;
                },
                $favourites
            );
        }

        if ($classification == COURSE_FAVOURITES) {
            [$filteredcourses, $processedcount] = course_filter_courses_by_favourites(
                $courses,
                $favouritecourseids,
                $limit
            );
        } else if ($classification == COURSE_CUSTOMFIELD) {
            [$filteredcourses, $processedcount] = course_filter_courses_by_customfield(
                $courses,
                $customfieldname,
                $customfieldvalue,
                $limit
            );
        } else {
            [$filteredcourses, $processedcount] = course_filter_courses_by_timeline_classification(
                $courses,
                $classification,
                $limit
            );
        }

        $renderer = $PAGE->get_renderer('core');
        $formattedcourses = array_map(function ($course) use ($renderer, $favouritecourseids) {
            if ($course == null) {
                return;
            }
            \context_helper::preload_from_record($course);
            $context = \context_course::instance($course->id);
            $isfavourite = false;
            if (in_array($course->id, $favouritecourseids)) {
                $isfavourite = true;
            }
            $exporter = new course_summary_exporter($course, ['context' => $context, 'isfavourite' => $isfavourite]);
            return $exporter->export($renderer);
        }, $filteredcourses);

        $formattedcourses = array_filter($formattedcourses, function ($course) {
            if ($course != null) {
                return $course;
            }
        });

        $result = [
            'courses' => $formattedcourses,
            'nextoffset' => $offset + $processedcount,
        ];
        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return \core_external\external_description
     */
    public static function get_enrolled_courses_by_timeline_classification_returns() {
        return new external_single_structure(
            [
                'courses' => new external_multiple_structure(course_summary_exporter::get_read_structure(), 'Course'),
                'nextoffset' => new external_value(PARAM_INT, 'Offset for the next request'),
            ]
        );
    }

    /**
     * Get course view
     *
     * @param array $data Search data
     * @return array
     */
    public static function get_courseview(array $data) {
        global $DB;
        if (defined('AJAX_SCRIPT') && AJAX_SCRIPT) {
            $context = \context_system::instance();
            self::validate_context($context);
        }
        require_login();
        $courseids = [];
        [$searchdata, $customfields, $categories, $tags] = self::remap_searchdata($data);
        $courseids = self::get_filtered_courseids(
            $customfields,
            $categories,
            $tags,
            $searchdata['searchterm'],
            '',
            0,
            $searchdata['limit'],
            $searchdata['offset'],
            false,
            $searchdata['progress']
        );
        if (!count($courseids)) {
            return self::zero_response();
        }
        [$insql, $inparams] = $DB->get_in_or_equal($courseids);
        $sql = "
        SELECT * from {course}
        WHERE id $insql
        ";
        $courses = $DB->get_records_sql($sql, $inparams);
        return self::get_courses_rendered($courses, 0);
    }

    /**
     * Filter params helper
     *
     * @param array $d Data array
     * @return mixed
     */
    public static function filterparams(array $d) {
        return $d['id'];
    }

    /**
     * Get courseview parameters
     *
     * @return external_function_parameters
     */
    public static function get_courseview_parameters() {
        return self::get_available_parameters();
    }

    /**
     * Zero response helper
     *
     * @return array
     */
    public static function zero_response(): array {
        $result = [
            'courses' => [],
            'nextoffset' => 0,
        ];
        return $result;
    }

    /**
     * Remap search data
     *
     * @param array $data Search data
     * @return array
     */
    public static function remap_searchdata(array $data): array {
        $searchdata = [];
        foreach ($data as $value) {
            [$name, $filterdata] = match ($value['key']) {
                'currentCustomField' => ['current_customfield', (int) $value['value']],
                'selectedCategories' => ['categories', $value['categories']],
                'selectedCustomfields' => ['customfields', $value['customfields']],
                'selectedTags' => ['tags', $value['tags']],
                'name' => ['searchterm', $value['value']],
                'categoryName' => ['catsearchterm', $value['value']],
                'tagsName' => ['tagssearchterm', $value['value']],
                'limit' => ['limit', $value['value']],
                'offset' => ['offset', $value['value']],
                'progress' => ['progress', $value['value']],
                default => ['null', 'null'],
            };
            $searchdata[$name] = $filterdata;
        }
        $customfields = $searchdata['customfields'];
        $categories = array_map([self::class, 'filterparams'], $searchdata['categories']);
        $tags = array_map([self::class, 'filterparams'], $searchdata['tags']);
        return [$searchdata, $customfields, $categories, $tags];
    }

    /**
     * Returns description of method result value
     *
     * @return \core_external\external_description
     */
    public static function get_courseview_returns() {
        return new external_single_structure(
            [
                'courses' => new external_multiple_structure(course_summary_exporter::get_read_structure(), 'Course'),
                'nextoffset' => new external_value(PARAM_INT, 'Offset for the next request'),
            ]
        );
    }

    // INFO: Customfield queries are separate from course search. Two DB queries are required to populate a field through search.
    // NOTE: Oops. Theoretically. Due to time constraints for development it is four.
    // INFO: There is no need to send data about which fields are selected because it can be managed stateful by frontend.

    /**
     * Get filtered course ids
     *
     * @param array $customfields Custom fields
     * @param array $categories Categories
     * @param array $tags Tags
     * @param string $searchterm Search term
     * @param string $excludetype Exclude type
     * @param string|int $excludevalue Exclude value
     * @param int $limit Limit
     * @param int $offset Offset
     * @param bool $contextids Return context ids
     * @param string $progress Progress filter
     * @return array
     */
    protected static function get_filtered_courseids(
        array $customfields,
        array $categories = [],
        array $tags = [],
        string $searchterm = '',
        string $excludetype = 'customfield',
        string | int $excludevalue = 0,
        int $limit = 0,
        int $offset = 0,
        $contextids = false,
        $progress = 'all'
    ) {
        global $DB, $USER;
        self::validate_context(\context_user::instance($USER->id));
        $currentcustomfield = $excludetype === 'customfield' ? $excludevalue : false;
        $customfields = self::filterconvert_multiselect_customfields($customfields, $currentcustomfield);
        // Build query for all courses that have the customfield selection minus the one in question.
        $insqls = '';
        $customjoins = '';
        $customsqls = [];
        $allparams = [];
        $customfieldid = $excludetype === 'customfield' ? (string) $excludevalue : -1;
        foreach ($customfields as $customfield) {
            if ((int) $customfield['fieldid'] === (int) $customfieldid || !count($customfield['fieldvalues'])) {
                    continue;
            }
            $cid = (int) $customfield['fieldid'];
            [$insql, $params] = $DB->get_in_or_equal($customfield['fieldvalues'], SQL_PARAMS_NAMED);
            $allparams = array_merge($allparams, $params);
            $customjoins .= " LEFT JOIN {customfield_data} cd$cid ON cd$cid.contextid = ctx.id AND cd$cid.fieldid = $cid ";
            $wherequery = " cd$cid.value $insql ";
            $customsqls[] = $wherequery;
        }
        if (count($customsqls)) {
            $insqls = ' AND ' . implode(' AND ', $customsqls) . ' ';
        }

        // Builder for category filter.
        if ($excludetype === 'categories') {
            $categories = [];
        }

        if (count($categories)) {
            [$insql, $params] = $DB->get_in_or_equal($categories, SQL_PARAMS_NAMED);
            $allparams = array_merge($allparams, $params);
            $query = " AND c.category $insql ";
            $insqls .= $query;
        }

        // Builder for tags filter.
        $tagsql = '';
        if ($excludetype === 'tags') {
            $categories = [];
        }

        if (count($tags)) {
            $tagsql = " LEFT JOIN {tag_instance} ti ON ti.itemtype = 'course' AND ti.component = 'core' AND ti.itemid = c.id ";
            [$insql, $params] = $DB->get_in_or_equal($tags, SQL_PARAMS_NAMED);
            $allparams = array_merge($allparams, $params);
            $query = " AND (ti.itemtype = 'course' AND ti.component = 'core' AND ti.itemid = c.id AND ti.tagid $insql ) ";
            $insqls .= $query;
        }
        $context = \context_system::instance();

        $chelper = new \coursecat_helper();
        $chelper->set_show_courses(\core_course_renderer::COURSECAT_SHOW_COURSES_EXPANDED)
            ->set_courses_display_options([
                'recursive' => true,
                'idonly' => true,
            ]);

        $chelper->set_attributes(['class' => 'frontpage-course-list-all']);
        $userscourses = core_course_category::top()->get_courses($chelper->get_courses_display_options());

        $idtype = $contextids ? 'ctx.id' : 'c.id';

        $courseids = [];
        $contextlevel = CONTEXT_COURSE;
        $sql = "
           SELECT DISTINCT $idtype
             FROM {course} c
        LEFT JOIN {context} ctx ON c.id = ctx.instanceid AND ctx.contextlevel = $contextlevel
        LEFT JOIN {customfield_data} cd ON cd.contextid = ctx.id
        $customjoins
        LEFT JOIN {customfield_field} f ON f.id = cd.fieldid
        LEFT JOIN {customfield_category} cat ON cat.id = f.categoryid
        $tagsql
        WHERE (cat.component IS NULL OR (cat.component = 'core_course' AND cat.area = 'course'))
              $insqls
        ";
        if (!empty($searchterm)) {
            $allparams['cfullname'] = "%$searchterm%";
            $allparams['cshortname'] = "%$searchterm%";
            $allparams['csummary'] = "%$searchterm%";
            $fullnamelike = $DB->sql_like('c.fullname', ':cfullname', false);
            $shortnamelike = $DB->sql_like('c.shortname', ':cshortname', false);
            $summarylike = $DB->sql_like('c.summary', ':csummary', false);
            $sql .= " AND ($fullnamelike OR $shortnamelike OR $summarylike) ";
        }

        if ($progress === 'past') {
            $timestamp = time();
            $sql .= " AND (c.enddate < $timestamp AND c.enddate > 0 ) ";
        }

        if ($progress === 'future') {
            $timestamp = time();
            $sql .= " AND c.startdate > $timestamp ";
        }

        if ($progress === 'inprogress') {
            $timestamp = time();
            $sql .= " AND (c.startdate < $timestamp AND (c.enddate > $timestamp OR c.enddate = 0 )) ";
        }

        $idsunfiltered = $DB->get_records_sql($sql, $allparams, $offset, $limit);
        $idsunfiltered = array_keys($idsunfiltered);

        if ($contextids) {
            [$insql, $inparams] = $DB->get_in_or_equal($userscourses);
            $contextids = array_keys($DB->get_records_select(
                'context',
                " instanceid $insql AND contextlevel = $contextlevel ",
                $inparams,
                'id',
                'id'
            ));
            $idsfiltered = array_intersect($idsunfiltered, $contextids);
        } else {
            $idsfiltered = array_intersect($idsunfiltered, $userscourses);
        }
        return $idsfiltered;
    }

    /**
     * Get courses rendered
     *
     * @param array $courses Array of courses
     * @param int $offset Offset value
     * @return array
     */
    protected static function get_courses_rendered(array $courses, int $offset): array {
        global $PAGE;

        $renderer = $PAGE->get_renderer('core');
        $formattedcourses = array_map(function ($course) use ($renderer) {
            if ($course == null) {
                return;
            }
            \context_helper::preload_from_record($course);
            $context = \context_course::instance($course->id);
            $exporter = new course_summary_exporter($course, ['context' => $context]);
            return $exporter->export($renderer);
        }, $courses);

        $formattedcourses = array_filter($formattedcourses, function ($course) {
            if ($course != null) {
                return $course;
            }
        });

        $result = [
            'courses' => $formattedcourses,
            'nextoffset' => $offset + count($formattedcourses),
        ];
        return $result;
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function get_available_parameters() {
        return new external_function_parameters(
            [
                'criteria' => new external_multiple_structure(
                    new external_single_structure(
                        [
                            'key' => new external_value(PARAM_ALPHA, 'The type of what is sent.'),
                            'value' => new external_value(PARAM_RAW, 'the value to match', VALUE_OPTIONAL),
                            'customfields' => new external_multiple_structure(
                                new external_single_structure(
                                    [
                                        'fieldid' => new external_value(PARAM_INT, 'the value to match', VALUE_OPTIONAL),
                                        'fieldvalues' => new external_multiple_structure(
                                            new external_value(PARAM_RAW, 'the value to match', VALUE_OPTIONAL)
                                        ),
                                    ],
                                    'custom field objects',
                                    VALUE_OPTIONAL
                                ),
                                'custom fields',
                                VALUE_OPTIONAL
                            ),
                            'tags' => new external_multiple_structure(
                                new external_single_structure(
                                    [
                                        'id' => new external_value(PARAM_INT, 'Tag ID'),
                                        'name' => new external_value(PARAM_TEXT, 'Display name of the tag'),
                                    ],
                                    'List of tags',
                                    VALUE_OPTIONAL
                                ),
                                'all tags',
                                VALUE_OPTIONAL
                            ),
                            'categories' => new external_multiple_structure(
                                new external_single_structure(
                                    [
                                        'coursecount' => new external_value(PARAM_INT, 'the value to match', VALUE_OPTIONAL),
                                        'depth' => new external_value(PARAM_INT, 'the value to match', VALUE_OPTIONAL),
                                        'description' => new external_value(PARAM_RAW, 'the value to match', VALUE_OPTIONAL),
                                        'descriptionformat' => new external_value(PARAM_INT, 'the value to match', VALUE_OPTIONAL),
                                        'id' => new external_value(PARAM_INT, 'the value to match', VALUE_REQUIRED),
                                        'idnumber' => new external_value(PARAM_RAW, 'the value to match', VALUE_OPTIONAL),
                                        'name' => new external_value(PARAM_TEXT, 'the value to match', VALUE_OPTIONAL),
                                        'parent' => new external_value(PARAM_INT, 'the value to match', VALUE_OPTIONAL),
                                        'path' => new external_value(PARAM_RAW, 'the value to match', VALUE_OPTIONAL),
                                        'sortorder' => new external_value(PARAM_INT, 'the value to match', VALUE_OPTIONAL),
                                        'theme' => new external_value(PARAM_TEXT, 'the value to match', VALUE_OPTIONAL),
                                        'timemodified' => new external_value(PARAM_INT, 'the value to match', VALUE_OPTIONAL),
                                        'visible' => new external_value(PARAM_INT, 'the value to match', VALUE_OPTIONAL),
                                        'visibleold' => new external_value(PARAM_INT, 'the value to match', VALUE_OPTIONAL),
                                    ],
                                    'category field objects',
                                    VALUE_OPTIONAL
                                ),
                                'category fields',
                                VALUE_OPTIONAL
                            ),
                        ]
                    ),
                    'criteria',
                    VALUE_DEFAULT,
                    []
                ),
                'addsubcategories' => new external_value(PARAM_BOOL, 'return the sub categories infos
                                          (1 - default) otherwise only the category info (0)', VALUE_DEFAULT, 1),
            ]
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function get_available_categories_parameters() {
        return self::get_available_parameters();
    }

    /**
     * Returns description of method result value
     *
     * @return \core_external\external_description
     */
    public static function get_available_categories_returns() {
        return core_course_external::get_categories_returns();
    }

    /**
     * Get available categories
     *
     * @param array $data Search data
     * @return array
     */
    public static function get_available_categories(array $data): array {
        global $DB;
        if (defined('AJAX_SCRIPT') && AJAX_SCRIPT) {
            $context = \context_system::instance();
            self::validate_context($context);
        }
        require_login();
        $courseids = [];
        $whereclause = '';
        $params = null;

        [$searchdata, $customfields, $categories, $tags] = self::remap_searchdata($data);
        if (
            count($searchdata) && count($courseids = self::get_filtered_courseids(
                $customfields,
                [],
                $tags,
                $searchdata['searchterm'],
                'categories',
                0,
                0,
                0,
                false,
                $searchdata['progress']
            ))
        ) {
            [$insql, $params] = $DB->get_in_or_equal($courseids);
            $whereclause = " WHERE c.id $insql ";
        }

        if (!count($courseids)) {
            return [];
        }
        $searchterm = $searchdata['catsearchterm'];
        if (!empty($searchterm)) {
            $params[] = "%$searchterm%";
            $whereclause .= " AND cat.name ILIKE ? ";
        }

        $sql = "SELECT DISTINCT cat.id FROM {course_categories} cat
            LEFT JOIN {course} c
            ON c.category = cat.id
            $whereclause
            LIMIT 6
            ";
        $catids = [];
        foreach ($categories = $DB->get_records_sql($sql, $params) as $category) {
            $catids[] = $category->id;
        }
        if (!count($catids)) {
            return [];
        }

        $parameters = [
            [ 'key' => 'ids', 'value' => implode(',', $catids) ],
        ];
        $categories = \core_course_external::get_categories($parameters);
        return $categories;
    }

    /**
     * Returns description of method result value
     *
     * @return \core_external\external_description
     */
    public static function get_available_tags_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                [
                'id' => new external_value(PARAM_INT, 'Tag ID'),
                'name' => new external_value(PARAM_TEXT, 'Display name of the tag'),
                ],
                'List of tags',
                VALUE_OPTIONAL
            )
        );
    }

    /**
     * Get available tags parameters
     *
     * @return external_function_parameters
     */
    public static function get_available_tags_parameters() {
        return self::get_available_parameters();
    }

    /**
     * Get available tags
     *
     * @param array $data Search data
     * @return array
     */
    public static function get_available_tags(array $data): array {
        global $DB;
        if (defined('AJAX_SCRIPT') && AJAX_SCRIPT) {
            $context = \context_system::instance();
            self::validate_context($context);
        }
        $courseids = [];
        $params = null;
        $tags = [];

        [$searchdata, $customfields, $categories, $tags] = self::remap_searchdata($data);
        if (
            !count($searchdata) || !count($courseids = self::get_filtered_courseids(
                $customfields,
                $categories,
                [],
                $searchdata['searchterm'],
                'tags',
                0,
                0,
                0,
                false,
                $searchdata['progress']
            ))
        ) {
            return [];
        }

        [$insql, $params] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
        $searchterm = strtolower($searchdata['tagssearchterm']);
        $and = '';
        if (!empty($searchterm)) {
            $tagnamelike = $DB->sql_like('t.name', ':tags_name', false);
            $params['tags_name'] = "%$searchterm%";
            $and = " AND $tagnamelike ";
        }
        $sql = "
        SELECT DISTINCT t.id, t.name
        FROM {tag} t
        JOIN {tag_instance} ti ON ti.tagid = t.id
        WHERE ti.itemtype = 'course' AND ti.component = 'core' $and AND ti.itemid $insql
        LIMIT 6
        ";

        $tags = $DB->get_records_sql($sql, $params);

        if (!count($tags)) {
            return [];
        }

        return $tags;
    }

    /**
     * Export IDs of all visible custom fields.
     *
     * @param bool $info Whether to return additional info
     * @return array Array of custom field data
     */
    public static function get_customfield_fields(bool $info = false): array {
        global $DB;
        $sql = "
        SELECT f.id, f.name, f.configdata, f.description FROM {customfield_field} f
        INNER JOIN {customfield_category} c
        ON c.id = f.categoryid
        WHERE c.area = 'course'
        AND f.id IN (SELECT DISTINCT fieldid FROM {customfield_data})
        ORDER BY c.sortorder, f.sortorder
        ";
        $customfields = $DB->get_records_sql($sql);
        if (!$customfields) {
            return [];
        }

        $mapfunction = $info ? [self::class, 'map_customfield_info'] : [self::class, 'map_customfield_ids'];

        $customids = array_map($mapfunction, $customfields);
        return $customids;
    }

    /**
     * Map customfield ids
     *
     * @param object $customfield Custom field object
     * @return int|null
     */
    public static function map_customfield_ids($customfield) {
        $configdata = json_decode($customfield->configdata);
        if ((int) $configdata->visibility === 2) {
            return $customfield->id;
        }
        return null;
    }

    /**
     * Map customfield info
     *
     * @param object $customfield Custom field object
     * @return object|null
     */
    public static function map_customfield_info($customfield) {
        $configdata = json_decode($customfield->configdata);
        if ((int) $configdata->visibility === 2) {
            return (object) ['id' => $customfield->id, 'name' => self::select_translation($customfield->name),
                'description' => $customfield->description];
        }
        return null;
    }

    /**
     * Get customfields
     *
     * @return array
     */
    public static function get_customfields() {
        if (defined('AJAX_SCRIPT') && AJAX_SCRIPT) {
            $context = \context_system::instance();
            self::validate_context($context);
        }
        require_login();
        return self::get_customfield_fields(true);
    }

    /**
     * Select translation
     *
     * @param string $text Text to translate
     * @return string
     */
    public static function select_translation(string $text): string {
        $idx = explode('_', current_language())[0] === 'de' ? 0 : 1;
        $translations = explode(';', $text);
        return (isset($translations[$idx]) ? $translations[$idx] : $translations[0]);
    }

    // INFO: Search filtering is handled in frontend.
    /**
     * Get customfield available options
     *
     * @param array $data Search data
     * @return array
     */
    public static function get_customfield_available_options(array $data): array {
        global $DB;
        if (defined('AJAX_SCRIPT') && AJAX_SCRIPT) {
            $context = \context_system::instance();
            self::validate_context($context);
        }
        require_login();
        $customfieldfieldids = self::get_customfield_fields();

        [$searchdata, $customfields, $categories, $tags] = self::remap_searchdata($data);

        if (!in_array($searchdata['current_customfield'], $customfieldfieldids)) {
            return [];
        }

        $coursecontextids = self::get_filtered_courseids(
            $customfields,
            $categories,
            $tags,
            $searchdata['searchterm'],
            'customfield',
            $searchdata['current_customfield'],
            0,
            0,
            true,
            $searchdata['progress']
        );

        if (!count($coursecontextids)) {
            return [];
        }

        [$insql, $inparams] = $DB->get_in_or_equal($coursecontextids, SQL_PARAMS_NAMED);
        $inparams['fieldid'] = $searchdata['current_customfield'];
        $select = "contextid $insql AND fieldid = :fieldid";
        $distinctablevalue = $DB->sql_compare_text('value');
        $values = $DB->get_records_select_menu(
            'customfield_data',
            $select,
            $inparams,
            '',
            "DISTINCT $distinctablevalue, $distinctablevalue AS value2"
        );
        \core_collator::asort($values, \core_collator::SORT_NATURAL);
        $values = array_filter($values);
        if (!$values) {
            return [];
        }
        $field = \core_customfield\field_controller::create($searchdata['current_customfield']);
        $coursehandler = \core_course\customfield\course_handler::VISIBLETOALL;
        $isvisible = $field->get_configdata_property('visibility') == $coursehandler;
        if (
            (!$field->supports_course_grouping() || !$isvisible) &&
            !$field = \block_eledia_coursesearch\fieldcontroller_factory::create($field)
        ) {
            return [];
        }
        if (!defined('BLOCK_MYOVERVIEW_CUSTOMFIELD_EMPTY')) {
            define('BLOCK_MYOVERVIEW_CUSTOMFIELD_EMPTY', -1);
        }
        $values = $field->course_grouping_format_values($values);
        $ret = [];
        foreach ($values as $value => $name) {
            $ret[] = (object)['name' => $name, 'value' => $value];
        }
        return $ret;
    }

    /**
     * Get customfield available options parameters
     *
     * @return external_function_parameters
     */
    public static function get_customfield_available_options_parameters() {
        return self::get_available_parameters();
    }

    /**
     * Get customfield available options returns
     *
     * @return external_multiple_structure
     */
    public static function get_customfield_available_options_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                [
                    'name' => new external_value(PARAM_RAW, 'category name'),
                    'value' => new external_value(PARAM_RAW, 'category name'),
                ],
                'List of available custom field options'
            )
        );
    }

    /**
     * Get multiselect customfields
     *
     * @param int|false $excludeid Exclude ID
     * @return array
     */
    public static function get_multiselect_customfields(int | false $excludeid = false) {
        global $DB;
        $fieldids = [];
        $fields = $DB->get_records('customfield_field', ['type' => 'multiselect'], '', 'id');
        foreach ($fields as $cf) {
            if ($cf->id !== $excludeid) {
                $fieldids[] = (int)$cf->id;
            }
        }
        return $fieldids;
    }

    // Oops, forgot, this isn't JS.
    /**
     * Array find helper function
     *
     * @param array $array Array to search
     * @param callable $callback Callback function
     * @return mixed
     */
    public static function array_find(array $array, callable $callback) {
        foreach ($array as $key => $value) {
            if ($callback($value)) {
                return $key;
            }
        }
        return false;
    }

    /**
     * Filter and convert multiselect customfields
     *
     * @param array $customfields Custom fields array
     * @param int|bool $excludeid Exclude ID
     * @return array
     */
    public static function filterconvert_multiselect_customfields(array $customfields, int | bool $excludeid): array {
        global $DB;
        if ($excludeid && $DB->record_exists('customfield_field', ['id' => $excludeid, 'type' => 'multiselect'])) {
            $customfields = array_values(array_filter($customfields, function ($item) use ($excludeid) {
                return $item['fieldid'] !== $excludeid;
            }));
        } else {
            $excludeid = false;
        }

        foreach (self::get_multiselect_customfields($excludeid) as $fid) {
            $fid = (int) $fid;
            $params = [];

            $idx = self::array_find($customfields, function ($item) use ($fid) {
                return $item['fieldid'] === $fid;
            });
            if ($idx === false || !count($customfields[$idx]['fieldvalues'])) {
                continue;
            }

            $params[] = $fid;
            $spliced = array_splice($customfields, $idx, 1);
            $f = reset($spliced);
            $csqls = [];
            foreach ($f['fieldvalues'] as $v) {
                $csqls[] = $DB->sql_like('value', '?');
                $params[] = "%$v%";
            }
            $where = " WHERE fieldid = ? AND ( " . implode(" OR ", $csqls) . " ) ";
            $sql = "
                SELECT DISTINCT value
                FROM {customfield_data}
                $where
                ";
            $fieldvalues = [];
            foreach ($DB->get_records_sql($sql, $params) as $r) {
                if (count(array_intersect(explode(',', $r->value), $f['fieldvalues']))) {
                    $fieldvalues[] = $r->value;
                }
            }
            $customfields[] = [ 'fieldid' => $fid, 'fieldvalues' => $fieldvalues ];
        }
        return $customfields;
    }


    /**
     * Get customfield value options parameters
     *
     * @return external_function_parameters
     */
    public static function get_customfield_value_options_parameters() {
        return self::get_available_parameters();
    }

    /**
     * Get customfield value options returns
     *
     * @return external_function_parameters
     */
    public static function get_customfield_value_options_returns() {
        return self::get_available_parameters();
    }
}
