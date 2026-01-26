<?php
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

namespace block_eledia_telc_coursesearch;

/**
 * Unit tests for web service functions
 *
 * @package    block_eledia_telc_coursesearch
 * @category   test
 * @copyright  2024 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class externallib_test extends \advanced_testcase {

    /**
     * Setup function
     */
    protected function setUp(): void {
        $this->resetAfterTest(true);
    }

    /**
     * Test get_data web service function
     *
     * @covers \block_eledia_telc_coursesearch\externallib::get_data
     */
    public function test_get_data(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course(['fullname' => 'Course 1', 'shortname' => 'C1', 'visible' => 1]);
        $course2 = $this->getDataGenerator()->create_course(['fullname' => 'Course 2', 'shortname' => 'C2', 'visible' => 1]);
        $course3 = $this->getDataGenerator()->create_course(['fullname' => 'Course 3', 'shortname' => 'C3', 'visible' => 0]);

        $this->getDataGenerator()->enrol_user($user->id, $course1->id, 'student');
        $this->getDataGenerator()->enrol_user($user->id, $course2->id, 'student');
        $this->getDataGenerator()->enrol_user($user->id, $course3->id, 'student');

        $this->setUser($user);

        $result = externallib::get_data();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        $courseids = array_column($result, 'id');
        $this->assertContains($course1->id, $courseids);
        $this->assertContains($course2->id, $courseids);
        $this->assertNotContains($course3->id, $courseids);
    }

    /**
     * Test get_courseview web service function
     *
     * @covers \block_eledia_telc_coursesearch\externallib::get_courseview
     */
    public function test_get_courseview(): void {
        $user = $this->getDataGenerator()->create_user();
        $category1 = $this->getDataGenerator()->create_category(['name' => 'Category 1']);
        $course1 = $this->getDataGenerator()->create_course([
            'fullname' => 'Test Course 1',
            'shortname' => 'TC1',
            'category' => $category1->id,
        ]);

        $this->getDataGenerator()->enrol_user($user->id, $course1->id, 'student');
        $this->setUser($user);

        $data = [
            ['key' => 'name', 'value' => 'Test'],
            ['key' => 'selectedCategories', 'categories' => []],
            ['key' => 'selectedCustomfields', 'customfields' => []],
            ['key' => 'selectedTags', 'tags' => []],
            ['key' => 'limit', 'value' => 10],
            ['key' => 'offset', 'value' => 0],
            ['key' => 'progress', 'value' => 'all'],
        ];

        $result = externallib::get_courseview($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('courses', $result);
        $this->assertArrayHasKey('nextoffset', $result);
    }

    /**
     * Test get_available_categories web service function
     *
     * @covers \block_eledia_telc_coursesearch\externallib::get_available_categories
     */
    public function test_get_available_categories(): void {
        $user = $this->getDataGenerator()->create_user();
        $category1 = $this->getDataGenerator()->create_category(['name' => 'Test Category']);
        $course1 = $this->getDataGenerator()->create_course(['category' => $category1->id]);

        $this->getDataGenerator()->enrol_user($user->id, $course1->id, 'student');
        $this->setUser($user);

        $data = [
            ['key' => 'name', 'value' => ''],
            ['key' => 'selectedCategories', 'categories' => []],
            ['key' => 'selectedCustomfields', 'customfields' => []],
            ['key' => 'selectedTags', 'tags' => []],
            ['key' => 'categoryName', 'value' => ''],
            ['key' => 'progress', 'value' => 'all'],
        ];

        $result = externallib::get_available_categories($data);

        $this->assertIsArray($result);
    }

    /**
     * Test get_available_tags web service function
     *
     * @covers \block_eledia_telc_coursesearch\externallib::get_available_tags
     */
    public function test_get_available_tags(): void {
        global $CFG;
        require_once($CFG->dirroot . '/tag/lib.php');

        $user = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course(['fullname' => 'Tagged Course']);

        $this->getDataGenerator()->enrol_user($user->id, $course1->id, 'student');

        \core_tag_tag::set_item_tags('core', 'course', $course1->id, \context_course::instance($course1->id), ['testtag']);

        $this->setUser($user);

        $data = [
            ['key' => 'name', 'value' => ''],
            ['key' => 'selectedCategories', 'categories' => []],
            ['key' => 'selectedCustomfields', 'customfields' => []],
            ['key' => 'selectedTags', 'tags' => []],
            ['key' => 'tagsName', 'value' => ''],
            ['key' => 'progress', 'value' => 'all'],
        ];

        $result = externallib::get_available_tags($data);

        $this->assertIsArray($result);
    }

    /**
     * Test get_customfields web service function
     *
     * @covers \block_eledia_telc_coursesearch\externallib::get_customfields
     */
    public function test_get_customfields(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $fieldcategory = $this->getDataGenerator()->create_custom_field_category(['name' => 'Test Fields']);

        $customfield = [
            'shortname' => 'testfield',
            'name' => 'Test Field',
            'type' => 'text',
            'categoryid' => $fieldcategory->get('id'),
            'configdata' => ['visibility' => 2],
        ];
        $field = $this->getDataGenerator()->create_custom_field($customfield);

        $customfieldvalue = ['shortname' => 'testfield', 'value' => 'Test value'];
        $course1 = $this->getDataGenerator()->create_course(['customfields' => [$customfieldvalue]]);

        $this->getDataGenerator()->enrol_user($user->id, $course1->id, 'student');

        $result = externallib::get_customfields();

        $this->assertIsArray($result);
    }

    /**
     * Test get_customfield_available_options web service function
     *
     * @covers \block_eledia_telc_coursesearch\externallib::get_customfield_available_options
     */
    public function test_get_customfield_available_options(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $fieldcategory = $this->getDataGenerator()->create_custom_field_category(['name' => 'Options']);

        $customfield = [
            'shortname' => 'optionfield',
            'name' => 'Option Field',
            'type' => 'text',
            'categoryid' => $fieldcategory->get('id'),
            'configdata' => ['visibility' => 2],
        ];
        $field = $this->getDataGenerator()->create_custom_field($customfield);

        $customfieldvalue = ['shortname' => 'optionfield', 'value' => 'Option A'];
        $course1 = $this->getDataGenerator()->create_course(['customfields' => [$customfieldvalue]]);

        $this->getDataGenerator()->enrol_user($user->id, $course1->id, 'student');

        $data = [
            ['key' => 'currentCustomField', 'value' => $field->get('id')],
            ['key' => 'name', 'value' => ''],
            ['key' => 'selectedCategories', 'categories' => []],
            ['key' => 'selectedCustomfields', 'customfields' => []],
            ['key' => 'selectedTags', 'tags' => []],
            ['key' => 'progress', 'value' => 'all'],
        ];

        $result = externallib::get_customfield_available_options($data);

        $this->assertIsArray($result);
    }

    /**
     * Test get_enrolled_courses_by_timeline_classification with different classifications
     *
     * @covers \block_eledia_telc_coursesearch\externallib::get_enrolled_courses_by_timeline_classification
     */
    public function test_get_enrolled_courses_by_timeline_classification(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $now = time();
        $pastcourse = $this->getDataGenerator()->create_course([
            'fullname' => 'Past Course',
            'startdate' => $now - WEEKSECS * 10,
            'enddate' => $now - WEEKSECS * 2,
        ]);
        $inprogresscourse = $this->getDataGenerator()->create_course([
            'fullname' => 'In Progress Course',
            'startdate' => $now - WEEKSECS * 2,
            'enddate' => $now + WEEKSECS * 2,
        ]);
        $futurecourse = $this->getDataGenerator()->create_course([
            'fullname' => 'Future Course',
            'startdate' => $now + WEEKSECS * 2,
            'enddate' => $now + WEEKSECS * 10,
        ]);

        $this->getDataGenerator()->enrol_user($user->id, $pastcourse->id, 'student');
        $this->getDataGenerator()->enrol_user($user->id, $inprogresscourse->id, 'student');
        $this->getDataGenerator()->enrol_user($user->id, $futurecourse->id, 'student');

        $result = externallib::get_enrolled_courses_by_timeline_classification(
            COURSE_TIMELINE_PAST,
            10,
            0,
            null,
            null,
            null,
            ''
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('courses', $result);
        $this->assertArrayHasKey('nextoffset', $result);

        $result = externallib::get_enrolled_courses_by_timeline_classification(
            COURSE_TIMELINE_INPROGRESS,
            10,
            0
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('courses', $result);

        $result = externallib::get_enrolled_courses_by_timeline_classification(
            COURSE_TIMELINE_FUTURE,
            10,
            0
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('courses', $result);
    }

    /**
     * Test search functionality in get_courseview
     *
     * @covers \block_eledia_telc_coursesearch\externallib::get_courseview
     */
    public function test_get_courseview_with_search(): void {
        $user = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course([
            'fullname' => 'Mathematics Course',
            'shortname' => 'MATH101',
        ]);
        $course2 = $this->getDataGenerator()->create_course([
            'fullname' => 'Physics Course',
            'shortname' => 'PHYS101',
        ]);

        $this->getDataGenerator()->enrol_user($user->id, $course1->id, 'student');
        $this->getDataGenerator()->enrol_user($user->id, $course2->id, 'student');
        $this->setUser($user);

        $data = [
            ['key' => 'name', 'value' => 'Mathematics'],
            ['key' => 'selectedCategories', 'categories' => []],
            ['key' => 'selectedCustomfields', 'customfields' => []],
            ['key' => 'selectedTags', 'tags' => []],
            ['key' => 'limit', 'value' => 10],
            ['key' => 'offset', 'value' => 0],
            ['key' => 'progress', 'value' => 'all'],
        ];

        $result = externallib::get_courseview($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('courses', $result);
    }

    /**
     * Test category filtering in get_courseview
     *
     * @covers \block_eledia_telc_coursesearch\externallib::get_courseview
     */
    public function test_get_courseview_with_category_filter(): void {
        $user = $this->getDataGenerator()->create_user();
        $category1 = $this->getDataGenerator()->create_category(['name' => 'Science']);
        $category2 = $this->getDataGenerator()->create_category(['name' => 'Arts']);

        $course1 = $this->getDataGenerator()->create_course([
            'fullname' => 'Biology',
            'category' => $category1->id,
        ]);
        $course2 = $this->getDataGenerator()->create_course([
            'fullname' => 'Painting',
            'category' => $category2->id,
        ]);

        $this->getDataGenerator()->enrol_user($user->id, $course1->id, 'student');
        $this->getDataGenerator()->enrol_user($user->id, $course2->id, 'student');
        $this->setUser($user);

        $data = [
            ['key' => 'name', 'value' => ''],
            ['key' => 'selectedCategories', 'categories' => [
                [
                    'id' => $category1->id,
                    'name' => 'Science',
                ],
            ]],
            ['key' => 'selectedCustomfields', 'customfields' => []],
            ['key' => 'selectedTags', 'tags' => []],
            ['key' => 'limit', 'value' => 10],
            ['key' => 'offset', 'value' => 0],
            ['key' => 'progress', 'value' => 'all'],
        ];

        $result = externallib::get_courseview($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('courses', $result);
    }

    /**
     * Test remap_searchdata helper function
     *
     * @covers \block_eledia_telc_coursesearch\externallib::remap_searchdata
     */
    public function test_remap_searchdata(): void {
        $data = [
            ['key' => 'name', 'value' => 'test search'],
            ['key' => 'selectedCategories', 'categories' => [['id' => 1, 'name' => 'Cat1']]],
            ['key' => 'selectedCustomfields', 'customfields' => []],
            ['key' => 'selectedTags', 'tags' => []],
            ['key' => 'limit', 'value' => 20],
            ['key' => 'offset', 'value' => 10],
            ['key' => 'progress', 'value' => 'inprogress'],
        ];

        [$searchdata, $customfields, $categories, $tags] = externallib::remap_searchdata($data);

        $this->assertIsArray($searchdata);
        $this->assertEquals('test search', $searchdata['searchterm']);
        $this->assertEquals(20, $searchdata['limit']);
        $this->assertEquals(10, $searchdata['offset']);
        $this->assertEquals('inprogress', $searchdata['progress']);
        $this->assertIsArray($customfields);
        $this->assertIsArray($categories);
        $this->assertIsArray($tags);
    }

    /**
     * Test zero response helper
     *
     * @covers \block_eledia_telc_coursesearch\externallib::zero_response
     */
    public function test_zero_response(): void {
        $result = externallib::zero_response();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('courses', $result);
        $this->assertArrayHasKey('nextoffset', $result);
        $this->assertEmpty($result['courses']);
        $this->assertEquals(0, $result['nextoffset']);
    }

    /**
     * Test filterparams helper
     *
     * @covers \block_eledia_telc_coursesearch\externallib::filterparams
     */
    public function test_filterparams(): void {
        $data = ['id' => 42, 'name' => 'Test'];
        $result = externallib::filterparams($data);

        $this->assertEquals(42, $result);
    }

    /**
     * Test get_multiselect_customfields
     *
     * @covers \block_eledia_telc_coursesearch\externallib::get_multiselect_customfields
     */
    public function test_get_multiselect_customfields(): void {
        $fieldcategory = $this->getDataGenerator()->create_custom_field_category(['name' => 'Multi']);

        $customfield = [
            'shortname' => 'multifield',
            'name' => 'Multi Field',
            'type' => 'multiselect',
            'categoryid' => $fieldcategory->get('id'),
        ];
        $field = $this->getDataGenerator()->create_custom_field($customfield);

        $result = externallib::get_multiselect_customfields();

        $this->assertIsArray($result);
        $this->assertContains($field->get('id'), $result);
    }

    /**
     * Test select_translation helper
     *
     * @covers \block_eledia_telc_coursesearch\externallib::select_translation
     */
    public function test_select_translation(): void {
        $text = 'German;English';
        $result = externallib::select_translation($text);

        $this->assertIsString($result);
        $this->assertContains($result, ['German', 'English']);

        $singletext = 'Single Text';
        $result = externallib::select_translation($singletext);
        $this->assertEquals('Single Text', $result);
    }
}
