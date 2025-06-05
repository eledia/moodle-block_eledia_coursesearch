<?php
$functions = [
    // The name of your web service function, as discussed above.
    'block_eledia_telc_coursesearch_get_categories' => [
        'classname' => 'core_course_external',
        'methodname' => 'get_categories',
        'classpath' => 'course/externallib.php',
        'description' => 'Return category details',
        'type' => 'read',
        'capabilities' => 'moodle/category:viewhiddencategories',

        // An optional list of services where the function will be included.
        /*'services' => [*/
        /*    // A standard Moodle install includes one default service:*/
        /*    // - MOODLE_OFFICIAL_MOBILE_SERVICE.*/
        /*    // Specifying this service means that your function will be available for*/
        /*    // use in the Moodle Mobile App.*/
        /*    MOODLE_OFFICIAL_MOBILE_SERVICE,*/
        /*]*/
    ],
    'block_eledia_telc_coursesearch_get_available_categories' => [
        'classname' => 'block_eledia_telc_coursesearch\externallib',
        'methodname' => 'get_available_categories',
        // 'classpath' => 'course/externallib.php',
        'description' => 'Return category details',
        'type' => 'read',
        // 'capabilities' => 'moodle/category:viewhiddencategories',
		'ajax' => true,
        // An optional list of services where the function will be included.
        /*'services' => [*/
        /*    // A standard Moodle install includes one default service:*/
        /*    // - MOODLE_OFFICIAL_MOBILE_SERVICE.*/
        /*    // Specifying this service means that your function will be available for*/
        /*    // use in the Moodle Mobile App.*/
        /*    MOODLE_OFFICIAL_MOBILE_SERVICE,*/
        /*]*/
    ],
    'block_eledia_telc_coursesearch_get_customfield_available_options' => [
        'classname' => 'block_eledia_telc_coursesearch\externallib',
        'methodname' => 'get_customfield_available_options',
        // 'classpath' => 'course/externallib.php',
        'description' => 'Return category details',
        'type' => 'read',
        // 'capabilities' => 'moodle/category:viewhiddencategories',
		'ajax' => true,
        // An optional list of services where the function will be included.
        /*'services' => [*/
        /*    // A standard Moodle install includes one default service:*/
        /*    // - MOODLE_OFFICIAL_MOBILE_SERVICE.*/
        /*    // Specifying this service means that your function will be available for*/
        /*    // use in the Moodle Mobile App.*/
        /*    MOODLE_OFFICIAL_MOBILE_SERVICE,*/
        /*]*/
    ],
    'block_eledia_telc_coursesearch_get_customfields' => [
        'classname' => 'block_eledia_telc_coursesearch\externallib',
        'methodname' => 'get_customfields',
        // 'classpath' => 'course/externallib.php',
        'description' => 'Return customfields for filter rendering.',
        'type' => 'read',
        // 'capabilities' => 'moodle/category:viewhiddencategories',
		'ajax' => true,
        // An optional list of services where the function will be included.
        /*'services' => [*/
        /*    // A standard Moodle install includes one default service:*/
        /*    // - MOODLE_OFFICIAL_MOBILE_SERVICE.*/
        /*    // Specifying this service means that your function will be available for*/
        /*    // use in the Moodle Mobile App.*/
        /*    MOODLE_OFFICIAL_MOBILE_SERVICE,*/
        /*]*/
    ],
    'block_eledia_telc_coursesearch_get_courseview' => [
        'classname' => 'block_eledia_telc_coursesearch\externallib',
        'methodname' => 'get_courseview',
        // 'classpath' => 'course/externallib.php',
        'description' => 'Return category details',
        'type' => 'read',
        // 'capabilities' => 'moodle/category:viewhiddencategories',
		'ajax' => true,
        // An optional list of services where the function will be included.
        /*'services' => [*/
        /*    // A standard Moodle install includes one default service:*/
        /*    // - MOODLE_OFFICIAL_MOBILE_SERVICE.*/
        /*    // Specifying this service means that your function will be available for*/
        /*    // use in the Moodle Mobile App.*/
        /*    MOODLE_OFFICIAL_MOBILE_SERVICE,*/
        /*]*/
    ],
];
