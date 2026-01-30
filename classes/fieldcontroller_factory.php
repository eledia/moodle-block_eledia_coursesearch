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

use core_customfield\field_controller;

/**
 * Field controller factory class
 *
 * @package block_eledia_coursesearch
 * @copyright 2025 eLeDia GmbH (made possible by TU Ilmenau)
 * @author Immanuel Pasanec <support@eledia.de>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class fieldcontroller_factory {
    /**
     * Create field controller
     *
     * @param field_controller $field Field controller
     * @return mixed
     */
    public static function create(field_controller $field) {
        $classname = "block_eledia_coursesearch\\" . explode('\\', get_class($field))[0];
        if (!class_exists($classname)) {
            return false;
        }

        return new $classname($field);
    }
}
