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
 * eledia_coursesearch block rendrer
 *
 * @package block_eledia_coursesearch
 * @copyright 2025 eLeDia GmbH (made possible by TU Ilmenau)
 * @author Immanuel Pasanec <support@eledia.de>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_eledia_coursesearch\output;

use plugin_renderer_base;
use renderable;

/**
 * eledia_coursesearch block renderer
 *
 * @package block_eledia_coursesearch
 * @copyright 2025 eLeDia GmbH (made possible by TU Ilmenau)
 * @author Immanuel Pasanec <support@eledia.de>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {
    /**
     * Return the main content for the block overview.
     *
     * @param main $main The main renderable
     * @return string HTML string
     */
    public function render_main(main $main) {
        $chelper = new \coursecat_helper();
        $chelper->set_show_courses(20)
            ->set_courses_display_options([
                'recursive' => true,
                'idonly' => true,
            ]);

        $chelper->set_attributes(['class' => 'frontpage-course-list-all']);
        $userscourses = \core_course_category::top()->get_courses($chelper->get_courses_display_options());

        if (!count($userscourses)) {
            return $this->render_from_template(
                'block_eledia_coursesearch/zero-state',
                $main->export_for_zero_state_template($this)
            );
        }
        return $this->render_from_template('block_eledia_coursesearch/main', $main->export_for_template($this));
    }
}
