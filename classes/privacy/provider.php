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
 * Privacy Subsystem implementation for gradereport_submission.
 *
 * @package   gradereport_submission
 * @author    Tomo Tsuyuki <tomotsuyuki@catalyst-au.net>
 * @copyright Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace gradereport_submission\privacy;

defined('MOODLE_INTERNAL') || die();

use \core_privacy\local\metadata\collection;
use \core_privacy\local\request\transform;
use \core_privacy\local\request\writer;

require_once($CFG->libdir.'/grade/constants.php');


/**
 * Privacy Subsystem for gradereport_submission implementing null_provider.
 *
 * @package   gradereport_submission
 * @author    Tomo Tsuyuki <tomotsuyuki@catalyst-au.net>
 * @copyright Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\user_preference_provider {

    /**
     * Returns meta data about this system.
     *
     * @param  collection $itemcollection The initialised item collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $items): collection {
        // User preferences (shared between different courses).
        $items->add_user_preference('gradereport_submission_view_user',
            'privacy:metadata:preference:gradereport_submission_view_user');

        return $items;
    }

    /**
     * Store all user preferences for the plugin.
     *
     * @param int $userid The userid of the user whose data is to be exported.
     */
    public static function export_user_preferences(int $userid): void {
        $prefvalue = get_user_preferences('gradereport_submission_view_user', null, $userid);
        if ($prefvalue !== null) {
            $transformedvalue = transform::yesno($prefvalue);
            writer::export_user_preference(
                'gradereport_submission',
                'gradereport_submission_view_user',
                $transformedvalue,
                get_string('privacy:metadata:preference:gradereport_submission_view_user', 'gradereport_submission')
            );
        }
    }
}
