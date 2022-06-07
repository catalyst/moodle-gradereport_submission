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
 * User grade report external functions and service definitions.
 *
 * @package   gradereport_submission
 * @author    Tomo Tsuyuki <tomotsuyuki@catalyst-au.net>
 * @copyright Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$functions = array(

    'gradereport_submission_get_grades_table' => array(
        'classname' => 'gradereport_submission_external',
        'methodname' => 'get_grades_table',
        'classpath' => 'grade/report/submission/externallib.php',
        'description' => 'Get the user/s report grades table for a course',
        'type' => 'read',
        'capabilities' => 'gradereport/submission:view',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),
    'gradereport_submission_view_grade_report' => array(
        'classname' => 'gradereport_submission_external',
        'methodname' => 'view_grade_report',
        'classpath' => 'grade/report/submission/externallib.php',
        'description' => 'Trigger the report view event',
        'type' => 'write',
        'capabilities' => 'gradereport/submission:view',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),
    'gradereport_submission_get_grade_items' => array(
        'classname' => 'gradereport_submission_external',
        'methodname' => 'get_grade_items',
        'classpath' => 'grade/report/submission/externallib.php',
        'description' => 'Returns the complete list of grade items for users in a course',
        'type' => 'read',
        'capabilities' => 'gradereport/submission:view',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    )
);
