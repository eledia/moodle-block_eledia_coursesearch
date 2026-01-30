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
 * Settings for the eledia_coursesearch block
 *
 * @package block_eledia_coursesearch
 * @copyright 2025 eLeDia GmbH (made possible by TU Ilmenau)
 * @author Immanuel Pasanec <support@eledia.de>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once($CFG->dirroot . '/blocks/eledia_coursesearch/lib.php');

    // Presentation options heading.
    $settings->add(new admin_setting_heading(
        'block_eledia_coursesearch/appearance',
        get_string('appearance', 'admin'),
        ''
    ));

    // Display Course Categories on Dashboard course items (cards, lists, summary items).
    $settings->add(new admin_setting_configcheckbox(
        'block_eledia_coursesearch/displaycategories',
        get_string('displaycategories', 'block_eledia_coursesearch'),
        get_string('displaycategories_help', 'block_eledia_coursesearch'),
        1
    ));

    // Enable / Disable available layouts.
    // Note: This setting should not be changed as it breaks the plugin frontend.
    // Hidden from admin interface but accessible to code.
    $choices = [
        BLOCK_ETCOURSESEARCH_VIEW_CARD => get_string('list', 'block_eledia_coursesearch'),
        BLOCK_ETCOURSESEARCH_VIEW_SUMMARY => get_string('cards', 'block_eledia_coursesearch'),
    ];
    if (get_config('block_eledia_coursesearch', 'layouts') === false) {
        set_config('layouts', implode(',', array_keys($choices)), 'block_eledia_coursesearch');
    }
    unset($choices);

    // Enable / Disable available layouts.
    $choices = [
        BLOCK_ETCOURSESEARCH_OPTIONS_OFF => get_string('selectedoption_off', 'block_eledia_coursesearch'),
        BLOCK_ETCOURSESEARCH_OPTIONS_TOP => get_string('selectedoption_top', 'block_eledia_coursesearch'),
        BLOCK_ETCOURSESEARCH_OPTIONS_BOTTOM => get_string('selectedoption_bottom', 'block_eledia_coursesearch'),
    ];
    $settings->add(new admin_setting_configselect(
        'block_eledia_coursesearch/options_position',
        get_string('selected_options_position', 'block_eledia_coursesearch'),
        get_string('selected_options_position_description', 'block_eledia_coursesearch'),
        BLOCK_ETCOURSESEARCH_OPTIONS_OFF,
        $choices
    ));
    unset($choices);

    // Enable / Disable course filter items.
    $settings->add(new admin_setting_heading(
        'block_eledia_coursesearch/availablegroupings',
        get_string('availablegroupings', 'block_eledia_coursesearch'),
        get_string('availablegroupings_desc', 'block_eledia_coursesearch')
    ));

    // Note: This setting is obsolete but required by the code.
    // Hidden from admin interface but accessible to code.
    if (get_config('block_eledia_coursesearch', 'displaygroupingallincludinghidden') === false) {
        set_config('displaygroupingallincludinghidden', 0, 'block_eledia_coursesearch');
    }

    $settings->add(new admin_setting_configcheckbox(
        'block_eledia_coursesearch/displaygroupingall',
        get_string('all', 'block_eledia_coursesearch'),
        '',
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'block_eledia_coursesearch/displaygroupinginprogress',
        get_string('inprogress', 'block_eledia_coursesearch'),
        '',
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'block_eledia_coursesearch/displaygroupingpast',
        get_string('past', 'block_eledia_coursesearch'),
        '',
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'block_eledia_coursesearch/displaygroupingfuture',
        get_string('future', 'block_eledia_coursesearch'),
        '',
        1
    ));

    // Note: This setting is obsolete but required by the code.
    // Hidden from admin interface but accessible to code.
    if (get_config('block_eledia_coursesearch', 'displaygroupingcustomfield') === false) {
        set_config('displaygroupingcustomfield', 0, 'block_eledia_coursesearch');
    }

    $choices = \core_customfield\api::get_fields_supporting_course_grouping();
    if ($choices) {
        $choices  = ['' => get_string('choosedots')] + $choices;
        $settings->add(new admin_setting_configselect(
            'block_eledia_coursesearch/customfiltergrouping',
            get_string('customfiltergrouping', 'block_eledia_coursesearch'),
            '',
            '',
            $choices
        ));
    } else {
        $settings->add(new admin_setting_configempty(
            'block_eledia_coursesearch/customfiltergrouping',
            get_string('customfiltergrouping', 'block_eledia_coursesearch'),
            get_string('customfiltergrouping_nofields', 'block_eledia_coursesearch')
        ));
    }
    $settings->hide_if(
        'block_eledia_coursesearch/customfiltergrouping',
        'block_eledia_coursesearch/displaygroupingcustomfield'
    );

    // Note: This setting is obsolete but required by the code.
    // Hidden from admin interface but accessible to code.
    if (get_config('block_eledia_coursesearch', 'displaygroupingfavourites') === false) {
        set_config('displaygroupingfavourites', 1, 'block_eledia_coursesearch');
    }

    // Note: This setting is obsolete but required by the code.
    // Hidden from admin interface but accessible to code.
    if (get_config('block_eledia_coursesearch', 'displaygroupinghidden') === false) {
        set_config('displaygroupinghidden', 1, 'block_eledia_coursesearch');
    }
}
