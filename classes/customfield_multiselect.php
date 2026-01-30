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

namespace block_eledia_coursesearch;

/**
 * Custom field multiselect class
 *
 * @package block_eledia_coursesearch
 * @copyright 2025 eLeDia GmbH (made possible by TU Ilmenau)
 * @author Immanuel Pasanec <support@eledia.de>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class customfield_multiselect {
    /**
     * @var object Custom field object
     */
    private $customfield;

    /**
     * Constructor
     *
     * @param object $customfield Custom field object
     */
    public function __construct($customfield) {
        $this->customfield = $customfield;
    }

    /**
     * Format course grouping values
     *
     * @param array $values Values to format
     * @return array
     */
    public function course_grouping_format_values($values) {
        $alloptions = $this->customfield->get_options_array($this->customfield);

        $intermediate = [];
        foreach ($values as $value) {
            $intermediate = array_merge($intermediate, explode(',', $value));
        }
        $uniquevalues = array_values(array_unique($intermediate));
        $options = [];
        foreach ($uniquevalues as $uniquevalue) {
            $options[$uniquevalue] = $alloptions[$uniquevalue];
        }
        return $options;
    }
}
