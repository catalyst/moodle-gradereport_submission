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
 * Renderer for the grade user report
 *
 * @package   gradereport_submission
 * @author    Tomo Tsuyuki <tomotsuyuki@catalyst-au.net>
 * @copyright Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Custom renderer for the user grade report
 *
 * To get an instance of this use the following code:
 * $renderer = $PAGE->get_renderer('gradereport_submission');
 *
 * @package   gradereport_submission
 * @author    Tomo Tsuyuki <tomotsuyuki@catalyst-au.net>
 * @copyright Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gradereport_submission_renderer extends plugin_renderer_base {

    /**
     * Render user select box.
     *
     * @param $report
     * @param $course
     * @param $userid
     * @param $groupid
     * @param $includeall
     * @return string
     */
    public function graded_users_selector($report, $course, $userid, $groupid, $includeall) {
        $select = grade_get_graded_users_select($report, $course, $userid, $groupid, $includeall);
        $output = html_writer::tag('div', $this->output->render($select), array('id' => 'graded_users_selector'));
        $output .= html_writer::tag('p', '', array('style' => 'page-break-after: always;'));

        return $output;
    }

    /**
     * Creates and renders the single select box for the user view.
     *
     * @param int $userid The selected userid
     * @param int $userview The current view user setting constant
     * @return string
     */
    public function view_user_selector($userid, $userview) {
        global $USER;
        $url = $this->page->url;
        if ($userid != $USER->id) {
            $url->param('userid', $userid);
        }

        $options = array(GRADE_REPORT_SUBMISSION_VIEW_USER => get_string('otheruser', 'gradereport_submission'),
                GRADE_REPORT_SUBMISSION_VIEW_SELF => get_string('myself', 'gradereport_submission'));
        $select = new single_select($url, 'userview', $options, $userview, null);

        $select->label = get_string('viewas', 'gradereport_submission');

        $output = html_writer::tag('div', $this->output->render($select), array('class' => 'view_users_selector'));

        return $output;
    }

}
