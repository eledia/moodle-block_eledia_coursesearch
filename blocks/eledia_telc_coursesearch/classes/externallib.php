<?php
namespace block_eledia_telc_coursesearch;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use core_external\external_value;
use core_course_category;
use coursecat_helper;
use moodle_url;
use context_system;


use core_course\external\course_summary_exporter;
use core_external\external_description;
use core_external\external_files;
use core_external\external_format_value;
use core_external\external_warnings;
use core_external\util;

require_once(__DIR__ . "/../../../course/lib.php");

defined('MOODLE_INTERNAL') || die();

class externallib extends external_api {

    // Define input parameters (none in this case)
    public static function get_data_parameters() {
        return new external_function_parameters([]);
    }

    // Webservice logic to return courses for the logged-in user
    public static function get_data() {
        global $USER, $DB;

        // Ensure the user is logged in
        $context = context_system::instance();
        self::validate_context($context);

        // Get the user's enrolled courses
        $sql = "SELECT c.id, c.fullname, c.shortname
                  FROM {course} c
                  JOIN {enrol} e ON e.courseid = c.id
                  JOIN {user_enrolments} ue ON ue.enrolid = e.id
                 WHERE ue.userid = :userid AND c.visible = 1";
        $params = ['userid' => $USER->id];
        $courses = $DB->get_records_sql($sql, $params);

        // Format the data to return
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

    // Define the output structure for the webservice
    public static function get_data_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Course ID'),
                'fullname' => new external_value(PARAM_TEXT, 'Full name of the course'),
                'shortname' => new external_value(PARAM_TEXT, 'Short name of the course'),
            ])
        );
    }

    
	private static function extract_subset_by_strings(array $searchStrings, array $result, int $allowedRecursions = 20 ){
	    if (empty($searchStrings) || $allowedRecursions < count( $searchStrings ) ) {
	        return $result;
	    }
	    #$allowedRecursions = $allowedRecursions - 1;	
	    $currentSearchStr = array_shift($searchStrings);
	    $result['courses'] = array_map(function ($course) use ($currentSearchStr) {
		$course = ( array )$course;    
	/*	
		$course = array_filter($course, function ($value, $key) {
			return !in_array($key, ['summary', 'courseimage']); 
	        }, ARRAY_FILTER_USE_BOTH);
	 */	
	        $matchFound = array_reduce(array_keys($course), function ($carry, $key) use ($course, $currentSearchStr) {
	            return $carry || (is_string($course[$key]) && preg_match("/" . $currentSearchStr . "/i", $course[$key]));
	        }, false);
	        return $matchFound ? $course : null;
	    }, $result['courses']);
	    
	    $result['courses'] = array_filter($result['courses']);
	
	    return self::extract_subset_by_strings($searchStrings, $result, $allowedRecursions);
	}

    public static function get_enrolled_courses_by_timeline_classification_parameters() {
        return new external_function_parameters(
            array(
                'classification' => new external_value(PARAM_ALPHA, 'future, inprogress, or past'),
                'limit' => new external_value(PARAM_INT, 'Result set limit', VALUE_DEFAULT, 0),
                'offset' => new external_value(PARAM_INT, 'Result set offset', VALUE_DEFAULT, 0),
                'sort' => new external_value(PARAM_TEXT, 'Sort string', VALUE_DEFAULT, null),
                'customfieldname' => new external_value(PARAM_ALPHANUMEXT, 'Used when classification = customfield',
                    VALUE_DEFAULT, null),
                'customfieldvalue' => new external_value(PARAM_RAW, 'Used when classification = customfield',
                    VALUE_DEFAULT, null),
                'searchvalue' => new external_value(PARAM_RAW, 'The value a user wishes to search against',
                    VALUE_DEFAULT, null),
                'requiredfields' => new \core_external\external_multiple_structure(
                    new external_value(PARAM_ALPHANUMEXT, 'Field name to be included from the results', VALUE_DEFAULT),
                    'Array of the only field names that need to be returned. If empty, all fields will be returned.',
                    VALUE_DEFAULT, []
                ),
            )
        );
    }
    
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
	$raw_course_data = self::get_enrolled_courses_by_timeline_classification_raw(
		$classification,
		$limit,
		$offset,
		$sort,
		$customfieldname,
		$customfieldvalue,
		#searchvalue
		'',
		$requiredfields
	);

	return self::extract_subset_by_strings( [ $searchvalue ], $raw_course_data );
	#return $raw_course_data;

    }
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

        $params = self::validate_parameters(self::get_enrolled_courses_by_timeline_classification_parameters(),
            array(
                'classification' => $classification,
                'limit' => $limit,
                'offset' => $offset,
                'sort' => $sort,
                'customfieldvalue' => $customfieldvalue,
                'searchvalue' => $searchvalue,
                'requiredfields' => $requiredfields,
            )
        );

        $classification = $params['classification'];
        $limit = $params['limit'];
        $offset = $params['offset'];
        $sort = $params['sort'];
        $customfieldvalue = $params['customfieldvalue'];
        $searchvalue = clean_param($params['searchvalue'], PARAM_TEXT);
        $requiredfields = $params['requiredfields'];

        switch($classification) {
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
            $courses = course_get_enrolled_courses_for_logged_in_user(0, $offset, $sort, $fields,
                COURSE_DB_QUERY_LIMIT, $hiddencourses);

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

	    #echo( ">>>>>>>>>>>>>>>>>>>>>>>" . $searchcriteria );
	    #echo( ">>>>>>>>>>>>>>>>>>>>>>>" . $options);
	    //die();

        } else {
            $courses = course_get_enrolled_courses_for_logged_in_user(0, $offset, $sort, $fields,
                COURSE_DB_QUERY_LIMIT, [], $hiddencourses);
        }

        $favouritecourseids = [];
        $ufservice = \core_favourites\service_factory::get_service_for_user_context(\context_user::instance($USER->id));
        $favourites = $ufservice->find_favourites_by_type('core_course', 'courses');

        if ($favourites) {
            $favouritecourseids = array_map(
                function($favourite) {
                    return $favourite->itemid;
                }, $favourites);
        }

        if ($classification == COURSE_FAVOURITES) {
            list($filteredcourses, $processedcount) = course_filter_courses_by_favourites(
                $courses,
                $favouritecourseids,
                $limit
            );
        } else if ($classification == COURSE_CUSTOMFIELD) {
            list($filteredcourses, $processedcount) = course_filter_courses_by_customfield(
                $courses,
                $customfieldname,
                $customfieldvalue,
                $limit
            );
        } else {
            list($filteredcourses, $processedcount) = course_filter_courses_by_timeline_classification(
                $courses,
                $classification,
                $limit
            );
        }

        $renderer = $PAGE->get_renderer('core');
        $formattedcourses = array_map(function($course) use ($renderer, $favouritecourseids) {
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

        $formattedcourses = array_filter($formattedcourses, function($course) {
            if ($course != null) {
                return $course;
            }
        });

	$result = [
            'courses' => $formattedcourses,
            'nextoffset' => $offset + $processedcount
        ];
	return $result;
	//return self::extract_subset_by_strings( [ $searchvalue ], $result );
	#return self::extract_subset_by_strings( [''], $result );
/*
        return [
            'courses' => $formattedcourses,
            'nextoffset' => $offset + $processedcount
	];
 */
    }

    /**
     * Returns description of method result value
     *
     * @return \core_external\external_description
     */
    public static function get_enrolled_courses_by_timeline_classification_returns() {
        return new external_single_structure(
            array(
                'courses' => new external_multiple_structure(course_summary_exporter::get_read_structure(), 'Course'),
                'nextoffset' => new external_value(PARAM_INT, 'Offset for the next request')
            )
        );
    }


	// Maybe not necessary.
	public static function get_courses_for_user(array $category_ids = [], array $customfields = [], string $searchterm = '', int $category_contextid = 0) {
		global $DB, $USER;
		$where = 'c.id <> :siteid';
        $params = array('siteid' => SITEID);
		if ($category_contextid) {
			$context = context_coursecat::instance($category_contextid);
			$where .= ' AND ctx.path like :path';
			$params['path'] = $context->path. '/%';
			$list = self::get_course_records($where, $params, array_diff_key($options, array('coursecontacts' => 1)), true);
		}
	}

	// INFO: Customfield queries are separate from course search. Two DB queries are required to populate a field through search.
	// INFO: There is no need to send data about which fields are selected because it can be managed stateful by frontend.

	protected static function get_filtered_courseids(array $customfields, array $categories = [],string $excludetype = 'customfield', string | int $excludevalue = 0) {
		global $DB, $USER;
		// $insqls = [];
		// Build query for all courses that have the customfield selection minus the one in question.
		$insqls = '';
		$allparams = [];
		$customfield_id = $excludetype === 'customfield' ? (string) $excludevalue : -1;
		foreach ($customfields as $customfield) {
			if ($customfield['id'] === $customfield_id)
					continue;
			$cid = $customfield['id'];
			[$insql, $params] = $DB->get_in_or_equal($customfield['values']);
			$allparams = array_merge($allparams, $params);
			$query = " AND ( cd.fieldid = $cid AND cd.value $insql ) ";
			// $insqls[] = $query;
			$insqls .= $query;
		}
		// TODO: Builer for category filter.
		$catids = [];
		foreach ($categories as $category) {
			if ($excludetype === 'categories')
					break;
			$catids = $category['id'];
		}

		// Expand query for categories.
		[$insql, $params] = $DB->get_in_or_equal($catids);
		$allparams = array_merge($allparams, $params);
		$query = " AND c.category $insql ";
		// $insqls[] = $query;
		$insqls .= $query;
        $chelper = new coursecat_helper();
        // $chelper->set_show_courses(self::COURSECAT_SHOW_COURSES_EXPANDED)->
        $chelper->set_show_courses(20)->
                set_courses_display_options([
				'recursive' => true,
				'idonly' => true,
				// 'limit' => $CFG->frontpagecourselimit,
				//'viewmoreurl' => new moodle_url('/course/index.php'),
				//'viewmoretext' => new lang_string('fulllistofcourses')
			]);

        $chelper->set_attributes(array('class' => 'frontpage-course-list-all'));
        $users_courses = core_course_category::top()->get_courses($chelper->get_courses_display_options());
		

        // $comparevalue = $DB->sql_compare_text('cd.value');
		$course_ids = [];
		// TODO: Account for parent categories. An extra self join query might be required.
        $sql = "
           SELECT DISTINCT c.id
             FROM {course} c
        LEFT JOIN {customfield_data} cd ON cd.instanceid = c.id
		LEFT JOIN {customfield_field} f ON f.id = cd.fieldid
		LEFT JOIN {customfield_category} cat ON cat.id = f.categoryid
		    WHERE cat.component = 'core_course'
			  AND cat.area = 'course'
		      $insqls
        ";
		$course_ids = $DB->get_records_sql($sql, $allparams);
		// $courseids_filtered = array_intersect($course_ids, array_keys($users_courses)); // New method gives back array of ids.
		$courseids_filtered = array_intersect($course_ids, array_keys($users_courses));
		return $courseids_filtered;

		// Better use get_customfield_value_options() for this.
		/*
		[$insql, $params] = $DB->get_in_or_equal((array) $course_ids);
		$sql = "
		   SELECT DISTINCT cd.fieldid, cd.value
			 FROM {customfield_data} cd
		     JOIN {course} c ON cd.instanceid = c.id AND cd.value = :value
			WHERE c.id $insql
					   ";
		*/
	}

	protected static function get_customfield_available_values(array $customfields, array $categories = [], string | int $customfield_id) {
		$courseids = self::get_filtered_courseids($customfields, $categories, 'customfield', $customfield_id);
		return self::get_customfield_value_options($customfield_id, $courseids);
	}

	protected static function get_available_categories(array $customfields): array {
		global $DB;
		$courseids = [];
		$whereclause = '';
		$inparams = null;

		if (sizeof($customfields)) {
			$courseids = self::get_filtered_courseids($customfields, [], 'categories');
			[$insql, $params] = $DB->get_in_or_equal($courseids);
			$whereclause = " WHERE c.id $insql ";
		}

		if (!sizeof($courseids))
			return [];


		$sql = "SELECT DISTINCT cat.id FROM {course_categories}
			LEFT JOIN {course} c
			ON c.category = cat.id
			$whereclause
			LIMIT 6
			";
		$catids = [];
		foreach ($categories = $DB->get_records_sql($sql, $params) as $category) {
			$catids[] = $category->id;
		}

		$categories = \core_course_external::get_courses(['ids' => $catids, 'limit' => 6]);
		return $categories;
	}

	protected static function get_customfield_value_options(int | string $fieldid, array $courseids) {
		// See get_customfield_values_for_export() in main.php and get_config_for_external() in block_eledia...php
		// Field identification is the field shortname.
		// There should be a LIMIT which is checked in frontend for displaying "too many entries to display".
        global $DB, $USER;

        if (!$fieldid) {
            return [];
        }
        // $courses = enrol_get_all_users_courses($USER->id, false); // INFO: Maybe a settig would be useful to show only courses the user is enrlled in.
        if (!$courseids) {
            return [];
        }
        list($csql, $params) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
        $select = "instanceid $csql AND fieldid = :fieldid";
        $params['fieldid'] = $fieldid;
        $distinctablevalue = $DB->sql_compare_text('value');
        $values = $DB->get_records_select_menu('customfield_data', $select, $params, '',
            "DISTINCT $distinctablevalue, $distinctablevalue AS value2");
        \core_collator::asort($values, \core_collator::SORT_NATURAL);
        $values = array_filter($values);
        if (!$values) {
            return [];
        }
        $field = \core_customfield\field_controller::create($fieldid);
        $isvisible = $field->get_configdata_property('visibility') == \core_course\customfield\course_handler::VISIBLETOALL;
        // Only visible fields to everybody supporting course grouping will be displayed.
		// TODO: Check if there are unsupported custom fields to be used.
        if (!$field->supports_course_grouping() || !$isvisible) {
            return []; // The field shouldn't have been selectable in the global settings, but just skip it now.
        }
        $values = $field->course_grouping_format_values($values);
        $ret = [];
        foreach ($values as $value => $name) {
            $ret[] = (object)[
                'name' => $name,
                'value' => $value,
            ];
        }
        return $ret;
	}

    /**
     * Retrieves number of records from course table
     *
     * Not all fields are retrieved. Records are ready for preloading context
     *
     * @param string $whereclause
     * @param array $params
     * @param array $options may indicate that summary needs to be retrieved
     * @param bool $checkvisibility if true, capability 'moodle/course:viewhiddencourses' will be checked
     *     on not visible courses and 'moodle/category:viewcourselist' on all courses
     * @return array array of stdClass objects
     */
    protected static function get_course_records($whereclauses, $wherefields, $params, $options, $checkvisibility = false, $additionalfields, $distinct = false) {
		// INFO: One query is required for search only. There is no need to send data for the searching field because it should be already populated.
        global $DB;
		$whereclause = '';
		$distinct = $distinct ? ' DISTINCT ' : '';
		if (sizeof($whereclauses)) {
			$whereclause = ' (' . join(') AND (', $whereclauses) . ')';
		}
        $ctxselect = context_helper::get_preload_record_columns_sql('ctx');
		if (!$distinct) {
			$fields = array('c.id', 'c.category', 'c.sortorder',
				'c.shortname', 'c.fullname', 'c.idnumber',
				'c.startdate', 'c.enddate', 'c.visible', 'c.cacherev');
			if (!empty($options['summary'])) {
				$fields[] = 'c.summary';
				$fields[] = 'c.summaryformat';
			} else {
				$fields[] = $DB->sql_substr('c.summary', 1, 1). ' as hassummary';
			}
		}
		$additional_fields = join(',', $additionalfields);
        $sql = "SELECT " . $distinct . join(',', $fields). ", $ctxselect
                FROM {course} c
                JOIN {context} ctx ON c.id = ctx.instanceid AND ctx.contextlevel = :contextcourse
                WHERE ". $whereclause." ORDER BY c.sortorder";
        $list = $DB->get_records_sql($sql,
                array('contextcourse' => CONTEXT_COURSE) + $params);

        if ($checkvisibility) {
            $mycourses = enrol_get_my_courses();
            // Loop through all records and make sure we only return the courses accessible by user.
            foreach ($list as $course) {
                if (isset($list[$course->id]->hassummary)) {
                    $list[$course->id]->hassummary = strlen($list[$course->id]->hassummary) > 0;
                }
                context_helper::preload_from_record($course);
                $context = context_course::instance($course->id);
                // Check that course is accessible by user.
                if (!array_key_exists($course->id, $mycourses) && !self::can_view_course_info($course)) {
                    unset($list[$course->id]);
                }
            }
        }

        return $list;
    }

}

