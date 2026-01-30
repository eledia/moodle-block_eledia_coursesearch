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

/**
 * Unit tests for the block_eledia_coursesearch implementation of the privacy API.
 *
 * @package block_eledia_coursesearch
 * @category test
 * @copyright 2025 eLeDia GmbH (made possible by TU Ilmenau)
 * @author Immanuel Pasanec <support@eledia.de>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_eledia_coursesearch\privacy;

use core_privacy\local\request\writer;
use block_eledia_coursesearch\privacy\provider;

/**
 * Unit tests for the block_eledia_coursesearch implementation of the privacy API.
 *
 * @package block_eledia_coursesearch
 * @copyright 2025 eLeDia GmbH (made possible by TU Ilmenau)
 * @author Immanuel Pasanec <support@eledia.de>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \block_eledia_coursesearch\privacy\provider
 */
final class provider_test extends \core_privacy\tests\provider_testcase {
    /**
     * Ensure that export_user_preferences returns no data if the user has not visited the eledia_coursesearch block.
     */
    public function test_export_user_preferences_no_pref(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        provider::export_user_preferences($user->id);
        $writer = writer::with_context(\context_system::instance());
        $this->assertFalse($writer->has_any_data());
    }

    /**
     * Test the export_user_preferences given different inputs
     *
     * @param string $type The name of the user preference to get/set
     * @param string $value The value you are storing
     * @param string $expected The expected value
     *
     * @dataProvider user_preference_provider
     */
    public function test_export_user_preferences($type, $value, $expected): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        set_user_preference($type, $value, $user);
        provider::export_user_preferences($user->id);
        $writer = writer::with_context(\context_system::instance());
        $blockpreferences = $writer->get_user_preferences('block_eledia_coursesearch');
        if (!$expected) {
            $expected = get_string($value, 'block_eledia_coursesearch');
        }
        $this->assertEquals($expected, $blockpreferences->{$type}->value);
    }

    /**
     * Create an array of valid user preferences for the eledia_coursesearch block.
     *
     * @return array Array of valid user preferences.
     */
    public static function user_preference_provider(): array {
        return [
            ['block_eledia_coursesearch_user_sort_preference', 'lastaccessed', ''],
            ['block_eledia_coursesearch_user_sort_preference', 'title', ''],
            ['block_eledia_coursesearch_user_sort_preference', 'shortname', ''],
            ['block_eledia_coursesearch_user_grouping_preference', 'allincludinghidden', ''],
            ['block_eledia_coursesearch_user_grouping_preference', 'all', ''],
            ['block_eledia_coursesearch_user_grouping_preference', 'inprogress', ''],
            ['block_eledia_coursesearch_user_grouping_preference', 'future', ''],
            ['block_eledia_coursesearch_user_grouping_preference', 'past', ''],
            ['block_eledia_coursesearch_user_grouping_preference', 'hidden', ''],
            ['block_eledia_coursesearch_user_grouping_preference', 'favourites', ''],
            ['block_eledia_coursesearch_user_view_preference', 'card', ''],
            ['block_eledia_coursesearch_user_view_preference', 'list', ''],
            ['block_eledia_coursesearch_user_view_preference', 'summary', ''],
            ['block_eledia_coursesearch_user_paging_preference', 12, 12],
        ];
    }

    public function test_export_user_preferences_with_hidden_courses(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $name = "block_eledia_coursesearch_hidden_course_1";

        set_user_preference($name, 1, $user);
        provider::export_user_preferences($user->id);
        $writer = writer::with_context(\context_system::instance());
        $blockpreferences = $writer->get_user_preferences('block_eledia_coursesearch');

        $this->assertEquals(
            get_string("privacy:request:preference:set", 'block_eledia_coursesearch', (object) [
                'name' => $name,
                'value' => 1,
            ]),
            $blockpreferences->{$name}->description
        );
    }
}
