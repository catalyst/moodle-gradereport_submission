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
 * Defines site settings for the submission gradebook report
 *
 * @package   gradereport_submission
 * @author    Tomo Tsuyuki <tomotsuyuki@catalyst-au.net>
 * @copyright Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_configcheckbox('grade_report_submission_showrank',
        get_string('showrank', 'grades'), get_string('showrank_help', 'grades'), 0));
    $settings->add(new admin_setting_configcheckbox('grade_report_submission_showpercentage',
        get_string('showpercentage', 'grades'), get_string('showpercentage_help', 'grades'), 1));
    $settings->add(new admin_setting_configcheckbox('grade_report_submission_showgrade',
        get_string('showgrade', 'grades'), get_string('showgrade_help', 'grades'), 1));
    $settings->add(new admin_setting_configcheckbox('grade_report_submission_showfeedback',
        get_string('showfeedback', 'grades'), get_string('showfeedback_help', 'grades'), 1));
    $settings->add(new admin_setting_configcheckbox('grade_report_submission_showrange',
        get_string('showrange', 'grades'), get_string('showrange_help', 'grades'), 1));
    $settings->add(new admin_setting_configcheckbox('grade_report_submission_showweight',
        get_string('showweight', 'grades'), get_string('showweight_help', 'grades'), 1));
    $settings->add(new admin_setting_configcheckbox('grade_report_submission_showaverage',
        get_string('showaverage', 'grades'), get_string('showaverage_help', 'grades'), 0));

    $settings->add(new admin_setting_configcheckbox('grade_report_submission_showattemptnumber',
        get_string('showattemptnumber', 'gradereport_submission'),
        get_string('showattemptnumber_help', 'gradereport_submission'), 1));
    $settings->add(new admin_setting_configcheckbox('grade_report_submission_showsubmissionstatus',
        get_string('showsubmissionstatus', 'gradereport_submission'),
        get_string('showsubmissionstatus_help', 'gradereport_submission'), 1));
    $settings->add(new admin_setting_configcheckbox('grade_report_submission_showgradingstatus',
        get_string('showgradingstatus', 'gradereport_submission'),
        get_string('showgradingstatus_help', 'gradereport_submission'), 1));
    $settings->add(new admin_setting_configcheckbox('grade_report_submission_showdateofgrading',
        get_string('showdateofgrading', 'gradereport_submission'),
        get_string('showdateofgrading_help', 'gradereport_submission'), 1));
    $settings->add(new admin_setting_configcheckbox('grade_report_submission_showgrader',
        get_string('showgrader', 'gradereport_submission'), get_string('showgrader_help', 'gradereport_submission'), 1));

    $settings->add(new admin_setting_configcheckbox('grade_report_submission_showlettergrade',
        get_string('showlettergrade', 'grades'), get_string('showlettergrade_help', 'grades'), 0));
    $settings->add(new admin_setting_configselect('grade_report_submission_rangedecimals',
        get_string('rangedecimals', 'grades'),
        get_string('rangedecimals_help', 'grades'), 0, array(0 => 0, 1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5)));

    $options = array(0 => get_string('shownohidden', 'grades'),
                     1 => get_string('showhiddenuntilonly', 'grades'),
                     2 => get_string('showallhidden', 'grades'));
    $settings->add(new admin_setting_configselect('grade_report_submission_showhiddenitems',
        get_string('showhiddenitems', 'grades'), get_string('showhiddenitems_help', 'grades'), 1, $options));

    $settings->add(new admin_setting_configselect('grade_report_submission_showtotalsifcontainhidden',
        get_string('hidetotalifhiddenitems', 'grades'),
        get_string('hidetotalifhiddenitems_help', 'grades'), GRADE_REPORT_HIDE_TOTAL_IF_CONTAINS_HIDDEN,
        array(GRADE_REPORT_HIDE_TOTAL_IF_CONTAINS_HIDDEN => get_string('hide'),
            GRADE_REPORT_SHOW_TOTAL_IF_CONTAINS_HIDDEN => get_string('hidetotalshowexhiddenitems', 'grades'),
            GRADE_REPORT_SHOW_REAL_TOTAL_IF_CONTAINS_HIDDEN => get_string('hidetotalshowinchiddenitems', 'grades'))));

    $settings->add(new admin_setting_configcheckbox('grade_report_submission_showcontributiontocoursetotal',
        get_string('showcontributiontocoursetotal', 'grades'), get_string('showcontributiontocoursetotal_help', 'grades'), 1));
}
