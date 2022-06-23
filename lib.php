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
 * Definition of the grade_user_report class is defined
 *
 * @package    gradereport_submission
 * @author     Tomo Tsuyuki <tomotsuyuki@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/grade/report/lib.php');
require_once($CFG->libdir.'/tablelib.php');

define("GRADE_REPORT_SUBMISSION_HIDE_HIDDEN", 0);
define("GRADE_REPORT_SUBMISSION_HIDE_UNTIL", 1);
define("GRADE_REPORT_SUBMISSION_SHOW_HIDDEN", 2);

define("GRADE_REPORT_SUBMISSION_VIEW_SELF", 1);
define("GRADE_REPORT_SUBMISSION_VIEW_USER", 2);

/**
 * Class providing an API for the user report building and displaying.
 * @uses grade_report
 * @package gradereport_submission
 */
class grade_report_submission extends grade_report {

    /**
     * The user.
     * @var object $user
     */
    public $user;

    /**
     * A flexitable to hold the data.
     * @var object $table
     */
    public $table;

    /**
     * An array of table headers
     * @var array
     */
    public $tableheaders = array();

    /**
     * An array of table columns
     * @var array
     */
    public $tablecolumns = array();

    /**
     * An array containing rows of data for the table.
     * @var type
     */
    public $tabledata = array();

    /**
     * An array containing the grade items data for external usage (web services, ajax, etc...)
     * @var array
     */
    public $gradeitemsdata = array();

    /**
     * The grade tree structure
     * @var grade_tree
     */
    public $gtree;

    /**
     * Flat structure similar to grade tree
     */
    public $gseq;

    /**
     * show student ranks
     */
    public $showrank;

    /**
     * show grade percentages
     */
    public $showpercentage;

    /**
     * Show range
     */
    public $showrange = true;

    /**
     * Show attempt number, default true
     * @var bool
     */
    public $showattemptnumber = true;

    /**
     * Show submission status, default true
     */
    public $showsubmissionstatus = true;

    /**
     * Show grading status, default true
     */
    public $showgradingstatus = true;

    /**
     * Show date of grading, default true
     */
    public $showdateofgrading = true;

    /**
     * Show grader, default true
     */
    public $showdgrader = true;

    /**
     * Show grades in the report, default true
     * @var bool
     */
    public $showgrade = true;

    /**
     * Decimal points to use for values in the report, default 2
     * @var int
     */
    public $decimals = 2;

    /**
     * The number of decimal places to round range to, default 0
     * @var int
     */
    public $rangedecimals = 0;

    /**
     * Show grade feedback in the report, default true
     * @var bool
     */
    public $showfeedback = true;

    /**
     * Show grade weighting in the report, default true.
     * @var bool
     */
    public $showweight = true;

    /**
     * Show letter grades in the report, default false
     * @var bool
     */
    public $showlettergrade = false;

    /**
     * Show the calculated contribution to the course total column.
     * @var bool
     */
    public $showcontributiontocoursetotal = true;

    /**
     * Show average grades in the report, default false.
     * @var false
     */
    public $showaverage = false;

    public $maxdepth;
    public $evenodd;

    public $canviewhidden;

    public $switch;

    /**
     * Show hidden items even when user does not have required cap
     */
    public $showhiddenitems;
    public $showtotalsifcontainhidden;

    public $baseurl;
    public $pbarurl;

    /**
     * The modinfo object to be used.
     *
     * @var course_modinfo
     */
    protected $modinfo = null;

    /**
     * View as user.
     *
     * When this is set to true, the visibility checks, and capability checks will be
     * applied to the user whose grades are being displayed. This is very useful when
     * a mentor/parent is viewing the report of their mentee because they need to have
     * access to the same information, but not more, not less.
     *
     * @var boolean
     */
    protected $viewasuser = false;

    /**
     * An array that collects the aggregationhints for every
     * grade_item. The hints contain grade, grademin, grademax
     * status, weight and parent.
     *
     * @var array
     */
    protected $aggregationhints = array();

    protected $assigngrades = null;

    protected $assignments = array();

    /**
     * Constructor. Sets local copies of user preferences and initialises grade_tree.
     * @param int $courseid
     * @param object|null $gpr grade plugin return tracking object
     * @param object $context
     * @param int $userid The id of the user
     * @param bool $viewasuser Set this to true when the current user is a mentor/parent of the targetted user.
     */
    public function __construct(int $courseid, ?object $gpr, object $context, int $userid, ?bool $viewasuser = null) {
        global $DB, $CFG;
        parent::__construct($courseid, $gpr, $context);

        $cid = $this->courseid;
        $this->showrank        = grade_get_setting($cid, 'report_submission_showrank',
            $CFG->grade_report_submission_showrank);
        $this->showpercentage  = grade_get_setting($cid, 'report_submission_showpercentage',
            $CFG->grade_report_submission_showpercentage);
        $this->showhiddenitems = grade_get_setting($cid, 'report_submission_showhiddenitems',
            $CFG->grade_report_submission_showhiddenitems);
        $this->showtotalsifcontainhidden = array($cid => grade_get_setting($cid, 'report_submission_showtotalsifcontainhidden',
            $CFG->grade_report_submission_showtotalsifcontainhidden));

        $this->showgrade       = grade_get_setting($cid, 'report_submission_showgrade',
            !empty($CFG->grade_report_submission_showgrade));
        $this->showrange       = grade_get_setting($cid, 'report_submission_showrange',
            !empty($CFG->grade_report_submission_showrange));
        $this->showfeedback    = grade_get_setting($cid, 'report_submission_showfeedback',
            !empty($CFG->grade_report_submission_showfeedback));

        $this->showweight = grade_get_setting($cid, 'report_submission_showweight',
            !empty($CFG->grade_report_submission_showweight));

        $this->showcontributiontocoursetotal = grade_get_setting($cid, 'report_submission_showcontributiontocoursetotal',
            !empty($CFG->grade_report_submission_showcontributiontocoursetotal));

        $this->showlettergrade = grade_get_setting($cid, 'report_submission_showlettergrade',
            !empty($CFG->grade_report_submission_showlettergrade));
        $this->showaverage     = grade_get_setting($cid, 'report_submission_showaverage',
            !empty($CFG->grade_report_submission_showaverage));

        $this->showattemptnumber = grade_get_setting($cid, 'report_submission_showattemptnumber',
            !empty($CFG->grade_report_submission_showattemptnumber));
        $this->showsubmissionstatus = grade_get_setting($cid, 'report_submission_showsubmissionstatus',
            !empty($CFG->grade_report_submission_showsubmissionstatus));
        $this->showgradingstatus = grade_get_setting($cid, 'report_submission_sshowgradingstatus',
            !empty($CFG->grade_report_submission_showgradingstatus));
        $this->showdateofgrading = grade_get_setting($cid, 'report_submission_showdateofgrading',
            !empty($CFG->grade_report_submission_showdateofgrading));
        $this->showgrader = grade_get_setting($cid, 'report_submission_showgrader',
            !empty($CFG->grade_report_submission_showgrader));

        $this->viewasuser = $viewasuser;

        // The default grade decimals is 2.
        $defaultdecimals = 2;
        if (!empty($CFG->grade_decimalpoints)) {
            $defaultdecimals = $CFG->grade_decimalpoints;
        }
        $this->decimals = grade_get_setting($cid, 'decimalpoints', $defaultdecimals);

        // The default range decimals is 0.
        $defaultrangedecimals = 0;
        if (!empty($CFG->grade_report_submission_rangedecimals)) {
            $defaultrangedecimals = $CFG->grade_report_submission_rangedecimals;
        }
        $this->rangedecimals = grade_get_setting($cid, 'report_submission_rangedecimals', $defaultrangedecimals);

        $this->switch = grade_get_setting($cid, 'aggregationposition', $CFG->grade_aggregationposition);

        // Grab the grade_tree for this course.
        $this->gtree = new grade_tree($cid, false, $this->switch, null, !$CFG->enableoutcomes);

        // Get the user (for full name).
        $this->user = $DB->get_record('user', array('id' => $userid));

        // What user are we viewing this as?
        $coursecontext = context_course::instance($cid);
        if ($viewasuser) {
            $this->modinfo = new course_modinfo($this->course, $this->user->id);
            $this->canviewhidden = has_capability('moodle/grade:viewhidden', $coursecontext, $this->user->id);
        } else {
            $this->modinfo = $this->gtree->modinfo;
            $this->canviewhidden = has_capability('moodle/grade:viewhidden', $coursecontext);
        }

        // Determine the number of rows and indentation.
        $this->maxdepth = 1;
        $this->inject_rowspans($this->gtree->top_element);
        $this->maxdepth++; // Need to account for the lead column that spans all children.
        for ($i = 1; $i <= $this->maxdepth; $i++) {
            $this->evenodd[$i] = 0;
        }

        $this->tabledata = array();

        // Base url for sorting by first/last name.
        $this->baseurl = $CFG->wwwroot.'/grade/report?id='.$courseid.'&amp;userid='.$userid;
        $this->pbarurl = $this->baseurl;

        // No groups on this report - rank is from all course users.
        $this->setup_table();

        // Optionally calculate grade item averages.
        $this->calculate_averages();
    }

    /**
     * Recurses through a tree of elements setting the rowspan property on each element
     *
     * @param array $element Either the top element or, during recursion, the current element
     * @return int The number of elements processed
     */
    protected function inject_rowspans(array &$element): int {

        if ($element['depth'] > $this->maxdepth) {
            $this->maxdepth = $element['depth'];
        }
        if (empty($element['children'])) {
            return 1;
        }
        $count = 1;

        foreach ($element['children'] as $key => $child) {
            // If category is hidden then do not include it in the rowspan.
            if ($child['type'] == 'category' && $child['object']->is_hidden() && !$this->canviewhidden
                    && ($this->showhiddenitems == GRADE_REPORT_SUBMISSION_HIDE_HIDDEN
                    || ($this->showhiddenitems == GRADE_REPORT_SUBMISSION_HIDE_UNTIL && !$child['object']->is_hiddenuntil()))) {
                // Just calculate the rowspans for children of this category, don't add them to the count.
                $this->inject_rowspans($element['children'][$key]);
            } else {
                $count += $this->inject_rowspans($element['children'][$key]);
            }
        }

        $element['rowspan'] = $count;
        return $count;
    }


    /**
     * Prepares the headers and attributes of the flexitable.
     */
    public function setup_table(): void {
        /*
         * Table has 1-8 columns
         *| All columns except for itemname/description are optional
         */

        // Setting up table headers.

        $this->tablecolumns = array('itemname');
        $this->tableheaders = array($this->get_lang_string('gradeitem', 'grades'));

        if ($this->showweight) {
            $this->tablecolumns[] = 'weight';
            $this->tableheaders[] = $this->get_lang_string('weightuc', 'grades');
        }

        if ($this->showgrade) {
            $this->tablecolumns[] = 'grade';
            $this->tableheaders[] = $this->get_lang_string('grade', 'grades');
        }

        if ($this->showrange) {
            $this->tablecolumns[] = 'range';
            $this->tableheaders[] = $this->get_lang_string('range', 'grades');
        }

        if ($this->showpercentage) {
            $this->tablecolumns[] = 'percentage';
            $this->tableheaders[] = $this->get_lang_string('percentage', 'grades');
        }

        if ($this->showlettergrade) {
            $this->tablecolumns[] = 'lettergrade';
            $this->tableheaders[] = $this->get_lang_string('lettergrade', 'grades');
        }

        if ($this->showrank) {
            $this->tablecolumns[] = 'rank';
            $this->tableheaders[] = $this->get_lang_string('rank', 'grades');
        }

        if ($this->showaverage) {
            $this->tablecolumns[] = 'average';
            $this->tableheaders[] = $this->get_lang_string('average', 'grades');
        }

        if ($this->showfeedback) {
            $this->tablecolumns[] = 'feedback';
            $this->tableheaders[] = $this->get_lang_string('feedback', 'grades');
        }

        if ($this->showcontributiontocoursetotal) {
            $this->tablecolumns[] = 'contributiontocoursetotal';
            $this->tableheaders[] = $this->get_lang_string('contributiontocoursetotal', 'grades');
        }

        if ($this->showattemptnumber) {
            $this->tablecolumns[] = 'attemptnumber';
            $this->tableheaders[] = $this->get_lang_string('attemptnumber', 'gradereport_submission');
        }

        if ($this->showsubmissionstatus) {
            $this->tablecolumns[] = 'submissionstatus';
            $this->tableheaders[] = $this->get_lang_string('submissionstatus', 'gradereport_submission');
        }

        if ($this->showgradingstatus) {
            $this->tablecolumns[] = 'gradingstatus';
            $this->tableheaders[] = $this->get_lang_string('gradingstatus', 'gradereport_submission');
        }

        if ($this->showdateofgrading) {
            $this->tablecolumns[] = 'dateofgrading';
            $this->tableheaders[] = $this->get_lang_string('dateofgrading', 'gradereport_submission');
        }

        if ($this->showgrader) {
            $this->tablecolumns[] = 'grader';
            $this->tableheaders[] = $this->get_lang_string('grader', 'gradereport_submission');
        }
    }

    /**
     * Fill the table for displaying.
     *
     * @return bool
     */
    public function fill_table(): bool {
        $this->fill_table_recursive($this->gtree->top_element);
        return true;
    }

    /**
     * Fill the table with data.
     *
     * @param $element - An array containing the table data for the current row.
     */
    private function fill_table_recursive(array &$element) {
        global $DB, $CFG;

        $type = $element['type'];
        $depth = $element['depth'];
        $gradeobject = $element['object'];
        $eid = $gradeobject->id;
        $element['userid'] = $this->user->id;
        $fullname = $this->gtree->get_element_header($element, true, true, true, true, true);
        $data = array();
        $gradeitemdata = array();
        $hidden = '';
        $excluded = '';
        $itemlevel = ($type == 'categoryitem' || $type == 'category' || $type == 'courseitem') ? $depth : ($depth + 1);
        $class = 'level' . $itemlevel . ' level' . ($itemlevel % 2 ? 'odd' : 'even');
        $classfeedback = '';

        // If this is a hidden grade category, hide it completely from the user.
        if ($type == 'category' && $gradeobject->is_hidden() && !$this->canviewhidden && (
                $this->showhiddenitems == GRADE_REPORT_SUBMISSION_HIDE_HIDDEN ||
                ($this->showhiddenitems == GRADE_REPORT_SUBMISSION_HIDE_UNTIL && !$gradeobject->is_hiddenuntil()))) {
            return false;
        }

        if ($type == 'category') {
            $this->evenodd[$depth] = (($this->evenodd[$depth] + 1) % 2);
        }
        $alter = ($this->evenodd[$depth] == 0) ? 'even' : 'odd';

        // Process those items that have scores associated.
        if ($type == 'item' or $type == 'categoryitem' or $type == 'courseitem') {
            $headerrow = "row_{$eid}_{$this->user->id}";
            $headercat = "cat_{$gradeobject->categoryid}_{$this->user->id}";

            if (! $gradegrade = grade_grade::fetch(array('itemid' => $gradeobject->id, 'userid' => $this->user->id))) {
                $gradegrade = new grade_grade();
                $gradegrade->userid = $this->user->id;
                $gradegrade->itemid = $gradeobject->id;
            }

            $gradegrade->load_grade_item();

            // Hidden Items.
            if ($gradegrade->grade_item->is_hidden()) {
                $hidden = ' dimmed_text';
            }

            $hide = false;
            // If this is a hidden grade item, hide it completely from the user.
            if ($gradegrade->is_hidden() && !$this->canviewhidden && (
                    $this->showhiddenitems == GRADE_REPORT_SUBMISSION_HIDE_HIDDEN ||
                    ($this->showhiddenitems == GRADE_REPORT_SUBMISSION_HIDE_UNTIL && !$gradegrade->is_hiddenuntil()))) {
                $hide = true;
            } else if (!empty($gradeobject->itemmodule) && !empty($gradeobject->iteminstance)) {
                // The grade object can be marked visible but still be hidden if
                // the student cannot see the activity due to conditional access
                // and it's set to be hidden entirely.
                $instances = $this->modinfo->get_instances_of($gradeobject->itemmodule);
                if (!empty($instances[$gradeobject->iteminstance])) {
                    $cm = $instances[$gradeobject->iteminstance];
                    $gradeitemdata['cmid'] = $cm->id;
                    if (!$cm->uservisible) {
                        // If there is 'availableinfo' text then it is only greyed
                        // out and not entirely hidden.
                        if (!$cm->availableinfo) {
                            $hide = true;
                        }
                    }
                }
            }

            // Actual Grade - We need to calculate this whether the row is hidden or not.
            $gradeval = $gradegrade->finalgrade;
            $hint = $gradegrade->get_aggregation_hint();
            if (!$this->canviewhidden) {
                // Virtual Grade (may be calculated excluding hidden items etc).
                $adjustedgrade = $this->blank_hidden_total_and_adjust_bounds($this->courseid,
                                                                             $gradegrade->grade_item,
                                                                             $gradeval);

                $gradeval = $adjustedgrade['grade'];

                // We temporarily adjust the view of this grade item - because the min and
                // max are affected by the hidden values in the aggregation.
                $gradegrade->grade_item->grademax = $adjustedgrade['grademax'];
                $gradegrade->grade_item->grademin = $adjustedgrade['grademin'];
                $hint['status'] = $adjustedgrade['aggregationstatus'];
                $hint['weight'] = $adjustedgrade['aggregationweight'];
            } else {
                // The max and min for an aggregation may be different to the grade_item.
                if (!is_null($gradeval)) {
                    $gradegrade->grade_item->grademax = $gradegrade->get_grade_max();
                    $gradegrade->grade_item->grademin = $gradegrade->get_grade_min();
                }
            }

            if (!$hide) {
                $canviewall = has_capability('moodle/grade:viewall', $this->context);
                // Other class information.
                $class .= $hidden . $excluded;
                if ($this->switch) { // Alter style based on whether aggregation is first or last.
                    $class .= ($type == 'categoryitem' or $type == 'courseitem') ? " ".$alter."d$depth baggt b2b" : " item b1b";
                } else {
                    $class .= ($type == 'categoryitem' or $type == 'courseitem') ? " ".$alter."d$depth baggb" : " item b1b";
                }
                if ($type == 'categoryitem' or $type == 'courseitem') {
                    $headercat = "cat_{$gradeobject->iteminstance}_{$this->user->id}";
                }

                // Name.
                $data['itemname']['content'] = $fullname;
                $data['itemname']['class'] = $class;
                $data['itemname']['colspan'] = ($this->maxdepth - $depth);
                $data['itemname']['celltype'] = 'th';
                $data['itemname']['id'] = $headerrow;

                // Basic grade item information.
                $gradeitemdata['id'] = $gradeobject->id;
                $gradeitemdata['itemname'] = $gradeobject->itemname;
                $gradeitemdata['itemtype'] = $gradeobject->itemtype;
                $gradeitemdata['itemmodule'] = $gradeobject->itemmodule;
                $gradeitemdata['iteminstance'] = $gradeobject->iteminstance;
                $gradeitemdata['itemnumber'] = $gradeobject->itemnumber;
                $gradeitemdata['categoryid'] = $gradeobject->categoryid;
                $gradeitemdata['outcomeid'] = $gradeobject->outcomeid;
                $gradeitemdata['scaleid'] = $gradeobject->outcomeid;
                $gradeitemdata['locked'] = $canviewall ? $gradegrade->grade_item->is_locked() : null;

                if ($this->showfeedback) {
                    // Copy $class before appending itemcenter as feedback should not be centered.
                    $classfeedback = $class;
                }
                $class .= " itemcenter ";
                if ($this->showweight) {
                    $data['weight']['class'] = $class;
                    $data['weight']['content'] = '-';
                    $data['weight']['headers'] = "$headercat $headerrow weight";
                    // Has a weight assigned, might be extra credit.

                    // This obliterates the weight because it provides a more informative description.
                    if (is_numeric($hint['weight'])) {
                        $data['weight']['content'] = format_float($hint['weight'] * 100.0, 2) . ' %';
                        $gradeitemdata['weightraw'] = $hint['weight'];
                        $gradeitemdata['weightformatted'] = $data['weight']['content'];
                    }
                    if ($hint['status'] != 'used' && $hint['status'] != 'unknown') {
                        $data['weight']['content'] .= '<br>' . get_string('aggregationhint' . $hint['status'], 'grades');
                        $gradeitemdata['status'] = $hint['status'];
                    }
                }

                if ($this->showgrade) {
                    $gradeitemdata['graderaw'] = null;
                    $gradeitemdata['gradehiddenbydate'] = false;
                    $gradeitemdata['gradeneedsupdate'] = $gradegrade->grade_item->needsupdate;
                    $gradeitemdata['gradeishidden'] = $gradegrade->is_hidden();
                    $gradeitemdata['gradedatesubmitted'] = $gradegrade->get_datesubmitted();
                    $gradeitemdata['gradedategraded'] = $gradegrade->get_dategraded();
                    $gradeitemdata['gradeislocked'] = $canviewall ? $gradegrade->is_locked() : null;
                    $gradeitemdata['gradeisoverridden'] = $canviewall ? $gradegrade->is_overridden() : null;

                    if ($gradegrade->grade_item->needsupdate) {
                        $data['grade']['class'] = $class.' gradingerror';
                        $data['grade']['content'] = get_string('error');
                    } else if (!empty($CFG->grade_hiddenasdate) and $gradegrade->get_datesubmitted()
                        and !$this->canviewhidden and $gradegrade->is_hidden()
                        and !$gradegrade->grade_item->is_category_item() and !$gradegrade->grade_item->is_course_item()) {
                        // The problem here is that we do not have the time when grade value was modified,
                        // 'timemodified' is general modification date for grade_grades records.
                        $class .= ' datesubmitted';
                        $data['grade']['class'] = $class;
                        $data['grade']['content'] = get_string('submittedon', 'grades',
                            userdate($gradegrade->get_datesubmitted(), get_string('strftimedatetimeshort')));
                        $gradeitemdata['gradehiddenbydate'] = true;
                    } else if ($gradegrade->is_hidden()) {
                        $data['grade']['class'] = $class.' dimmed_text';
                        $data['grade']['content'] = '-';

                        if ($this->canviewhidden) {
                            $gradeitemdata['graderaw'] = $gradeval;
                            $data['grade']['content'] = grade_format_gradevalue($gradeval,
                                                                                $gradegrade->grade_item,
                                                                                true);
                        }
                    } else {
                        $data['grade']['class'] = $class;
                        $data['grade']['content'] = grade_format_gradevalue($gradeval,
                                                                            $gradegrade->grade_item,
                                                                            true);
                        $gradeitemdata['graderaw'] = $gradeval;
                    }
                    $data['grade']['headers'] = "$headercat $headerrow grade";
                    $gradeitemdata['gradeformatted'] = $data['grade']['content'];
                }

                // Range.
                if ($this->showrange) {
                    $data['range']['class'] = $class;
                    $data['range']['content'] = $gradegrade->grade_item->get_formatted_range(GRADE_DISPLAY_TYPE_REAL,
                        $this->rangedecimals);
                    $data['range']['headers'] = "$headercat $headerrow range";

                    $gradeitemdata['rangeformatted'] = $data['range']['content'];
                    $gradeitemdata['grademin'] = $gradegrade->grade_item->grademin;
                    $gradeitemdata['grademax'] = $gradegrade->grade_item->grademax;
                }

                // Percentage.
                if ($this->showpercentage) {
                    if ($gradegrade->grade_item->needsupdate) {
                        $data['percentage']['class'] = $class.' gradingerror';
                        $data['percentage']['content'] = get_string('error');
                    } else if ($gradegrade->is_hidden()) {
                        $data['percentage']['class'] = $class.' dimmed_text';
                        $data['percentage']['content'] = '-';
                        if ($this->canviewhidden) {
                            $data['percentage']['content'] = grade_format_gradevalue($gradeval,
                                $gradegrade->grade_item, true, GRADE_DISPLAY_TYPE_PERCENTAGE);
                        }
                    } else {
                        $data['percentage']['class'] = $class;
                        $data['percentage']['content'] = grade_format_gradevalue($gradeval,
                            $gradegrade->grade_item, true, GRADE_DISPLAY_TYPE_PERCENTAGE);
                    }
                    $data['percentage']['headers'] = "$headercat $headerrow percentage";
                    $gradeitemdata['percentageformatted'] = $data['percentage']['content'];
                }

                // Lettergrade.
                if ($this->showlettergrade) {
                    if ($gradegrade->grade_item->needsupdate) {
                        $data['lettergrade']['class'] = $class.' gradingerror';
                        $data['lettergrade']['content'] = get_string('error');
                    } else if ($gradegrade->is_hidden()) {
                        $data['lettergrade']['class'] = $class.' dimmed_text';
                        if (!$this->canviewhidden) {
                            $data['lettergrade']['content'] = '-';
                        } else {
                            $data['lettergrade']['content'] = grade_format_gradevalue($gradeval,
                                $gradegrade->grade_item, true, GRADE_DISPLAY_TYPE_LETTER);
                        }
                    } else {
                        $data['lettergrade']['class'] = $class;
                        $data['lettergrade']['content'] = grade_format_gradevalue($gradeval,
                            $gradegrade->grade_item, true, GRADE_DISPLAY_TYPE_LETTER);
                    }
                    $data['lettergrade']['headers'] = "$headercat $headerrow lettergrade";
                    $gradeitemdata['lettergradeformatted'] = $data['lettergrade']['content'];
                }

                // Rank.
                if ($this->showrank) {
                    $gradeitemdata['rank'] = 0;
                    if ($gradegrade->grade_item->needsupdate) {
                        $data['rank']['class'] = $class.' gradingerror';
                        $data['rank']['content'] = get_string('error');
                    } else if ($gradegrade->is_hidden()) {
                            $data['rank']['class'] = $class.' dimmed_text';
                            $data['rank']['content'] = '-';
                    } else if (is_null($gradeval)) {
                        // No grade, no rank.
                        $data['rank']['class'] = $class;
                        $data['rank']['content'] = '-';

                    } else {
                        // Find the number of users with a higher grade.
                        $sql = "SELECT COUNT(DISTINCT(userid))
                                  FROM {grade_grades}
                                 WHERE finalgrade > ?
                                       AND itemid = ?
                                       AND hidden = 0";
                        $rank = $DB->count_records_sql($sql, array($gradegrade->finalgrade, $gradegrade->grade_item->id)) + 1;

                        $data['rank']['class'] = $class;
                        $numusers = $this->get_numusers(false);
                        $data['rank']['content'] = "$rank/$numusers"; // Total course users.

                        $gradeitemdata['rank'] = $rank;
                        $gradeitemdata['numusers'] = $numusers;
                    }
                    $data['rank']['headers'] = "$headercat $headerrow rank";
                }

                // Average.
                if ($this->showaverage) {
                    $gradeitemdata['averageformatted'] = '';

                    $data['average']['class'] = $class;
                    if (!empty($this->gtree->items[$eid]->avg)) {
                        $data['average']['content'] = $this->gtree->items[$eid]->avg;
                        $gradeitemdata['averageformatted'] = $this->gtree->items[$eid]->avg;
                    } else {
                        $data['average']['content'] = '-';
                    }
                    $data['average']['headers'] = "$headercat $headerrow average";
                }

                // Feedback.
                if ($this->showfeedback) {
                    $gradeitemdata['feedback'] = '';
                    $gradeitemdata['feedbackformat'] = $gradegrade->feedbackformat;

                    if ($gradegrade->feedback) {
                        $gradegrade->feedback = file_rewrite_pluginfile_urls(
                            $gradegrade->feedback,
                            'pluginfile.php',
                            $gradegrade->get_context()->id,
                            GRADE_FILE_COMPONENT,
                            GRADE_FEEDBACK_FILEAREA,
                            $gradegrade->id
                        );
                    }

                    if ($gradegrade->overridden > 0 AND ($type == 'categoryitem' OR $type == 'courseitem')) {
                        $data['feedback']['class'] = $classfeedback.' feedbacktext';
                        $data['feedback']['content'] = get_string('overridden', 'grades').': ' .
                            format_text($gradegrade->feedback, $gradegrade->feedbackformat,
                                ['context' => $gradegrade->get_context()]);
                        $gradeitemdata['feedback'] = $gradegrade->feedback;
                    } else if (empty($gradegrade->feedback) or (!$this->canviewhidden and $gradegrade->is_hidden())) {
                        $data['feedback']['class'] = $classfeedback.' feedbacktext';
                        $data['feedback']['content'] = '&nbsp;';
                    } else {
                        $data['feedback']['class'] = $classfeedback.' feedbacktext';
                        $data['feedback']['content'] = format_text($gradegrade->feedback, $gradegrade->feedbackformat,
                            ['context' => $gradegrade->get_context()]);
                        $gradeitemdata['feedback'] = $gradegrade->feedback;
                    }
                    $data['feedback']['headers'] = "$headercat $headerrow feedback";
                }
                // Contribution to the course total column.
                if ($this->showcontributiontocoursetotal) {
                    $data['contributiontocoursetotal']['class'] = $class;
                    $data['contributiontocoursetotal']['content'] = '-';
                    $data['contributiontocoursetotal']['headers'] = "$headercat $headerrow contributiontocoursetotal";

                }

                // Default value for attempt number.
                if ($this->showattemptnumber) {
                    $data['attemptnumber']['class'] = $class;
                    $data['attemptnumber']['content'] = '-';
                    $gradeitemdata['attemptnumber'] = '-';
                }

                // Default value for submission status.
                if ($this->showsubmissionstatus) {
                    $data['submissionstatus']['class'] = $class;
                    $data['submissionstatus']['content'] = '-';
                    $gradeitemdata['submissionstatus'] = '-';
                }

                // Default value for grading status.
                if ($this->showgradingstatus) {
                    $data['gradingstatus']['class'] = $class;
                    $data['gradingstatus']['content'] = '-';
                    $gradeitemdata['gradingstatus'] = '-';
                }

                // Default value for date of grading.
                if ($this->showdateofgrading) {
                    $data['dateofgrading']['class'] = $class;
                    $data['dateofgrading']['content'] = '-';
                    $gradeitemdata['dateofgrading'] = '-';
                }

                // Default value for grader.
                if ($this->showgrader) {
                    $data['grader']['class'] = $class;
                    $data['grader']['content'] = '-';
                    $gradeitemdata['grader'] = '-';
                }

                // These Options for mod_assign only.
                if ($gradeobject->itemmodule === 'assign') {
                    // Load assign grades.
                    $assigmentid = $gradeobject->iteminstance;
                    $assigngrade = $this->get_assign_grade($assigmentid);
                    $assignment = $this->get_assignment($assigmentid);
                    if ($assigngrade) {
                        // Attempt number.
                        if ($this->showattemptnumber) {
                            $attemptnumber = isset($assigngrade) ? $assigngrade->attemptnumber + 1 : '-';
                            $data['attemptnumber']['content'] = $attemptnumber;
                            $gradeitemdata['attemptnumber'] = $attemptnumber;
                        }

                        // Submission status.
                        if ($this->showsubmissionstatus) {
                            $submissionstatus = isset($assigngrade) ? $assigngrade->submissionstatus : '-';
                            $data['submissionstatus']['content'] = get_string('submissionstatus_' . $submissionstatus, 'assign');
                            $gradeitemdata['submissionstatus'] = get_string('submissionstatus_' . $submissionstatus, 'assign');
                        }

                        // Grading status.
                        if ($this->showgradingstatus) {
                            $gradingstatus = $assignment->get_grading_status($this->user->id);
                            if ($assignment->get_instance()->markingworkflow) {
                                $data['gradingstatus']['content'] = get_string('markingworkflowstate' . $gradingstatus, 'assign');
                                $gradeitemdata['gradingstatus'] = get_string('markingworkflowstate' . $gradingstatus, 'assign');
                            } else {
                                $data['gradingstatus']['content'] = get_string($gradingstatus, 'assign');
                                $gradeitemdata['gradingstatus'] = get_string($gradingstatus, 'assign');
                            }
                        }

                        // Date of grading.
                        if ($this->showdateofgrading) {
                            $dateofgrading = $assigngrade->dategraded;
                            if (!empty($dateofgrading)) {
                                $data['dateofgrading']['content'] = userdate($assigngrade->dategraded,
                                        get_string('strftimedatetimeshort'));
                                $gradeitemdata['dateofgrading'] = userdate($assigngrade->dategraded,
                                        get_string('strftimedatetimeshort'));
                            }
                        }

                        // Grader.
                        if ($this->showgrader) {
                            $gradingstatus = $assignment->get_grading_status($this->user->id);
                            $grader = null;
                            $context = context_module::instance($cm->id);
                            // Only display the grader if it is in the right state.
                            if (in_array($gradingstatus, [ASSIGN_GRADING_STATUS_GRADED, ASSIGN_MARKING_WORKFLOW_STATE_RELEASED])) {
                                if (isset($assigngrade->grader) && $assigngrade->grader > 0) {
                                    $grader = $DB->get_record('user', array('id' => $assigngrade->grader));
                                } else if (isset($gradegrade->usermodified)
                                    && $gradegrade->usermodified > 0
                                    && has_capability('mod/assign:grade', $context, $gradegrade->usermodified)) {
                                    // Grader not provided. Check that usermodified is a user who can grade.
                                    // Case 1: When an assignment is reopened an empty assign_grade is created so the feedback
                                    // plugin can know which attempt it's referring to. In this case, usermodifed is a student.
                                    // Case 2: When an assignment's grade is overrided via the gradebook, usermodified is a grader.
                                    $grader = $DB->get_record('user', array('id' => $gradegrade->usermodified));
                                }
                            }

                            $viewfullnames = has_capability('moodle/site:viewfullnames', $context);
                            $data['grader']['content'] = isset($grader) ? fullname($grader, $viewfullnames) : '-';
                            $gradeitemdata['grader'] = isset($grader) ? fullname($grader, $viewfullnames) : '-';
                        }
                    }
                }

                $this->gradeitemsdata[] = $gradeitemdata;
            }
            // We collect the aggregation hints whether they are hidden or not.
            if ($this->showcontributiontocoursetotal) {
                $hint['grademax'] = $gradegrade->grade_item->grademax;
                $hint['grademin'] = $gradegrade->grade_item->grademin;
                $hint['grade'] = $gradeval;
                $parent = $gradeobject->load_parent_category();
                if ($gradeobject->is_category_item()) {
                    $parent = $parent->load_parent_category();
                }
                $hint['parent'] = $parent->load_grade_item()->id;
                $this->aggregationhints[$gradegrade->itemid] = $hint;
            }
        }

        // Category.
        if ($type == 'category') {
            $data['leader']['class'] = $class.' '.$alter."d$depth b1t b2b b1l";
            $data['leader']['rowspan'] = $element['rowspan'];

            if ($this->switch) { // Alter style based on whether aggregation is first or last.
                $data['itemname']['class'] = $class.' ' .$alter."d$depth b1b b1t";
            } else {
                $data['itemname']['class'] = $class.' '.$alter."d$depth b2t";
            }
            $data['itemname']['colspan'] = ($this->maxdepth - $depth + count($this->tablecolumns) - 1);
            $data['itemname']['content'] = $fullname;
            $data['itemname']['celltype'] = 'th';
            $data['itemname']['id'] = "cat_{$gradeobject->id}_{$this->user->id}";
        }

        // Add this row to the overall system.
        foreach ($data as $key => $celldata) {
            $data[$key]['class'] .= ' column-' . $key;
        }
        $this->tabledata[] = $data;

        // Recursively iterate through all child elements.
        if (isset($element['children'])) {
            foreach ($element['children'] as $key => $child) {
                $this->fill_table_recursive($element['children'][$key]);
            }
        }

        // Check we are showing this column, and we are looking at the root of the table.
        // This should be the very last thing this fill_table_recursive function does.
        if ($this->showcontributiontocoursetotal && ($type == 'category' && $depth == 1)) {
            // We should have collected all the hints by now - walk the tree again and build the contributions column.

            $this->fill_contributions_column($element);
        }
    }

    /**
     * This function is called after the table has been built and the aggregationhints
     * have been collected. We need this info to walk up the list of parents of each
     * grade_item.
     *
     * @param $element - An array containing the table data for the current row.
     */
    public function fill_contributions_column(array $element): void {

        // Recursively iterate through all child elements.
        if (isset($element['children'])) {
            foreach ($element['children'] as $key => $child) {
                $this->fill_contributions_column($element['children'][$key]);
            }
        } else if ($element['type'] == 'item') {
            // This is a grade item (We don't do this for categories or we would double count).
            $gradeobject = $element['object'];
            $itemid = $gradeobject->id;

            // Ignore anything with no hint - e.g. a hidden row.
            if (isset($this->aggregationhints[$itemid])) {

                // Normalise the gradeval.
                $gradecat = $gradeobject->load_parent_category();
                if ($gradecat->aggregation == GRADE_AGGREGATE_SUM) {
                    // Natural aggregation/Sum of grades does not consider the mingrade, cannot traditionnally normalise it.
                    $graderange = $this->aggregationhints[$itemid]['grademax'];

                    if ($graderange != 0) {
                        $gradeval = $this->aggregationhints[$itemid]['grade'] / $graderange;
                    } else {
                        $gradeval = 0;
                    }
                } else {
                    $gradeval = grade_grade::standardise_score($this->aggregationhints[$itemid]['grade'],
                        $this->aggregationhints[$itemid]['grademin'], $this->aggregationhints[$itemid]['grademax'], 0, 1);
                }

                // Multiply the normalised value by the weight
                // of all the categories higher in the tree.
                $parent = null;
                do {
                    if (!is_null($this->aggregationhints[$itemid]['weight'])) {
                        $gradeval *= $this->aggregationhints[$itemid]['weight'];
                    } else if (empty($parent)) {
                        // If we are in the first loop, and the weight is null, then we cannot calculate the contribution.
                        $gradeval = null;
                        break;
                    }

                    // The second part of this if is to prevent infinite loops
                    // in case of crazy data.
                    if (isset($this->aggregationhints[$itemid]['parent']) &&
                            $this->aggregationhints[$itemid]['parent'] != $itemid) {
                        $parent = $this->aggregationhints[$itemid]['parent'];
                        $itemid = $parent;
                    } else {
                        // We are at the top of the tree.
                        $parent = false;
                    }
                } while ($parent);

                // Finally multiply by the course grademax.
                if (!is_null($gradeval)) {
                    // Convert to percent.
                    $gradeval *= 100;
                }

                // Now we need to loop through the "built" table data and update the
                // contributions column for the current row.
                $headerrow = "row_{$gradeobject->id}_{$this->user->id}";
                foreach ($this->tabledata as $key => $row) {
                    if (isset($row['itemname']) && ($row['itemname']['id'] == $headerrow)) {
                        // Found it - update the column.
                        $content = '-';
                        if (!is_null($gradeval)) {
                            $decimals = $gradeobject->get_decimals();
                            $content = format_float($gradeval, $decimals, true) . ' %';
                        }
                        $this->tabledata[$key]['contributiontocoursetotal']['content'] = $content;
                        break;
                    }
                }
            }
        }
    }

    /**
     * Prints or returns the HTML from the flexitable.
     * @param bool $return Whether or not to return the data instead of printing it directly.
     * @return string
     */
    public function print_table(bool $return = false) {
         $maxspan = $this->maxdepth;

        // Build table structure.
        $html = "
            <table cellspacing='0'
                   cellpadding='0'
                   summary='" . s($this->get_lang_string('tablesummary', 'gradereport_submission')) . "'
                   class='boxaligncenter generaltable user-grade'>
            <thead>
                <tr>
                    <th id='".$this->tablecolumns[0]."' class=\"header column-{$this->tablecolumns[0]}\"
                        colspan='$maxspan'>".$this->tableheaders[0]."</th>\n";

        for ($i = 1; $i < count($this->tableheaders); $i++) {
            $html .= "<th id='".$this->tablecolumns[$i]."' class=\"header column-{$this->tablecolumns[$i]}\">"
                . $this->tableheaders[$i]."</th>\n";
        }

        $html .= "
                </tr>
            </thead>
            <tbody>\n";

        // Print out the table data.
        for ($i = 0; $i < count($this->tabledata); $i++) {
            $html .= "<tr>\n";
            if (isset($this->tabledata[$i]['leader'])) {
                $rowspan = $this->tabledata[$i]['leader']['rowspan'];
                $class = $this->tabledata[$i]['leader']['class'];
                $html .= "<td class='$class' rowspan='$rowspan'></td>\n";
            }
            for ($j = 0; $j < count($this->tablecolumns); $j++) {
                $name = $this->tablecolumns[$j];
                $data = $this->tabledata[$i][$name] ?? null;
                $class = $data['class'] ?? '';
                $colspan = (isset($data['colspan'])) ? "colspan='".$data['colspan']."'" : '';
                $content = (isset($data['content'])) ? $data['content'] : null;
                $celltype = (isset($data['celltype'])) ? $data['celltype'] : 'td';
                $id = (isset($data['id'])) ? "id='{$data['id']}'" : '';
                $headers = (isset($data['headers'])) ? "headers='{$data['headers']}'" : '';
                if (isset($content)) {
                    $html .= "<$celltype $id $headers class='$class' $colspan>$content</$celltype>\n";
                }
            }
            $html .= "</tr>\n";
        }

        $html .= "</tbody></table>";

        if ($return) {
            return $html;
        } else {
            echo $html;
        }
    }

    /**
     * Implement the function as the function is abstract in the parent.
     * @var array $data
     * @return mixed True or array of errors
     */
    public function process_data($data) {
    }

    /**
     * Implement the function as the function is abstract in the parent.
     * @param string $target Sortorder
     * @param string $action Which action to take (edit, delete etc...)
     * @return
     */
    public function process_action($target, $action) {
    }

    /**
     * Builds the grade item averages.
     */
    protected function calculate_averages(): void {
        global $USER, $DB, $CFG;

        if ($this->showaverage) {
            // This settings are actually grader report settings (not user report)
            // however we're using them as having two separate but identical settings the
            // user would have to keep in sync would be annoying.
            $averagesdisplaytype   = $this->get_pref('averagesdisplaytype');
            $averagesdecimalpoints = $this->get_pref('averagesdecimalpoints');
            $meanselection         = $this->get_pref('meanselection');
            $shownumberofgrades    = $this->get_pref('shownumberofgrades');

            $avghtml = '';
            $groupsql = $this->groupsql;
            $groupwheresql = $this->groupwheresql;
            $totalcount = $this->get_numusers(false);

            // We want to query both the current context and parent contexts.
            list($relatedctxsql, $relatedctxparams) = $DB->get_in_or_equal($this->context->get_parent_context_ids(true),
                SQL_PARAMS_NAMED, 'relatedctx');

            // Limit to users with a gradeable role ie students.
            list($gradebookrolessql, $gradebookrolesparams) = $DB->get_in_or_equal(explode(',', $this->gradebookroles),
                SQL_PARAMS_NAMED, 'grbr0');

            // Limit to users with an active enrolment.
            $coursecontext = $this->context->get_course_context(true);
            $defaultgradeshowactiveenrol = !empty($CFG->grade_report_showonlyactiveenrol);
            $showonlyactiveenrol = get_user_preferences('grade_report_showonlyactiveenrol', $defaultgradeshowactiveenrol);
            $showonlyactiveenrol = $showonlyactiveenrol || !has_capability('moodle/course:viewsuspendedusers', $coursecontext);
            list($enrolledsql, $enrolledparams) = get_enrolled_sql($this->context, '', 0, $showonlyactiveenrol);

            $params = array_merge($this->groupwheresql_params, $gradebookrolesparams, $enrolledparams, $relatedctxparams);
            $params['courseid'] = $this->courseid;

            // Find sums of all grade items in course.
            $sql = "SELECT gg.itemid, SUM(gg.finalgrade) AS sum
                      FROM {grade_items} gi
                      JOIN {grade_grades} gg ON gg.itemid = gi.id
                      JOIN {user} u ON u.id = gg.userid
                      JOIN ($enrolledsql) je ON je.id = gg.userid
                      JOIN (
                                   SELECT DISTINCT ra.userid
                                     FROM {role_assignments} ra
                                    WHERE ra.roleid $gradebookrolessql
                                      AND ra.contextid $relatedctxsql
                           ) rainner ON rainner.userid = u.id
                      $groupsql
                     WHERE gi.courseid = :courseid
                       AND u.deleted = 0
                       AND gg.finalgrade IS NOT NULL
                       AND gg.hidden = 0
                       $groupwheresql
                  GROUP BY gg.itemid";

            $sumarray = array();
            $sums = $DB->get_recordset_sql($sql, $params);
            foreach ($sums as $itemid => $csum) {
                $sumarray[$itemid] = $csum->sum;
            }
            $sums->close();

            $columncount = 0;

            // Empty grades must be evaluated as grademin, NOT always 0
            // This query returns a count of ungraded grades (NULL finalgrade OR no matching record in grade_grades table)
            // No join condition when joining grade_items and user to get a grade item row for every user
            // Then left join with grade_grades and look for rows with null final grade
            // (which includes grade items with no grade_grade).
            $sql = "SELECT gi.id, COUNT(u.id) AS count
                      FROM {grade_items} gi
                      JOIN {user} u ON u.deleted = 0
                      JOIN ($enrolledsql) je ON je.id = u.id
                      JOIN (
                               SELECT DISTINCT ra.userid
                                 FROM {role_assignments} ra
                                WHERE ra.roleid $gradebookrolessql
                                  AND ra.contextid $relatedctxsql
                           ) rainner ON rainner.userid = u.id
                      LEFT JOIN {grade_grades} gg
                             ON (gg.itemid = gi.id AND gg.userid = u.id AND gg.finalgrade IS NOT NULL AND gg.hidden = 0)
                      $groupsql
                     WHERE gi.courseid = :courseid
                           AND gg.finalgrade IS NULL
                           $groupwheresql
                  GROUP BY gi.id";

            $ungradedcounts = $DB->get_records_sql($sql, $params);

            foreach ($this->gtree->items as $itemid => $unused) {
                if (!empty($this->gtree->items[$itemid]->avg)) {
                    continue;
                }
                $item = $this->gtree->items[$itemid];

                if ($item->needsupdate) {
                    $avghtml .= '<td class="cell c' . $columncount++
                        .'"><span class="gradingerror">'.get_string('error').'</span></td>';
                    continue;
                }

                if (empty($sumarray[$item->id])) {
                    $sumarray[$item->id] = 0;
                }

                if (empty($ungradedcounts[$itemid])) {
                    $ungradedcount = 0;
                } else {
                    $ungradedcount = $ungradedcounts[$itemid]->count;
                }

                // Do they want the averages to include all grade items.
                if ($meanselection == GRADE_REPORT_MEAN_GRADED) {
                    $meancount = $totalcount - $ungradedcount;
                } else { // Bump up the sum by the number of ungraded items * grademin.
                    $sumarray[$item->id] += ($ungradedcount * $item->grademin);
                    $meancount = $totalcount;
                }

                // Determine which display type to use for this average.
                if (!empty($USER->gradeediting) && $USER->gradeediting[$this->courseid]) {
                    $displaytype = GRADE_DISPLAY_TYPE_REAL;

                } else if ($averagesdisplaytype == GRADE_REPORT_PREFERENCE_INHERIT) {
                    // No ==0 here, please resave the report and user preferences.
                    $displaytype = $item->get_displaytype();

                } else {
                    $displaytype = $averagesdisplaytype;
                }

                // Override grade_item setting if a display preference (not inherit) was set for the averages.
                if ($averagesdecimalpoints == GRADE_REPORT_PREFERENCE_INHERIT) {
                    $decimalpoints = $item->get_decimals();
                } else {
                    $decimalpoints = $averagesdecimalpoints;
                }

                if (empty($sumarray[$item->id]) || $meancount == 0) {
                    $this->gtree->items[$itemid]->avg = '-';
                } else {
                    $sum = $sumarray[$item->id];
                    $avgradeval = $sum / $meancount;
                    $gradehtml = grade_format_gradevalue($avgradeval, $item, true, $displaytype, $decimalpoints);

                    $numberofgrades = '';
                    if ($shownumberofgrades) {
                        $numberofgrades = " ($meancount)";
                    }

                    $this->gtree->items[$itemid]->avg = $gradehtml.$numberofgrades;
                }
            }
        }
    }

    /**
     * Trigger the grade_report_viewed event
     *
     * @since Moodle 2.9
     */
    public function viewed(): void {
        $event = \gradereport_submission\event\grade_report_viewed::create(
            array(
                'context' => $this->context,
                'courseid' => $this->courseid,
                'relateduserid' => $this->user->id,
            )
        );
        $event->trigger();
    }

    /**
     * Get a grade record of the assignment.
     * @param int $assignmentid
     * @return object|null
     */
    protected function get_assign_grade(int $assignmentid): ?object {
        global $DB;

        if (is_null($this->assigngrades)) {
            $assigngrades = array();
            $rows = $DB->get_recordset_sql('
                    SELECT s.assignment AS assignmentid,
                           s.userid AS userid,
                           s.attemptnumber,
                           s.status AS submissionstatus,
                           s.timemodified as datesubmitted,
                           g.grade as rawgrade,
                           g.timemodified as dategraded,
                           g.grader as grader
                      FROM {assign} a
                      JOIN {assign_submission} s
                           ON s.assignment = a.id
                           AND s.latest = 1
                 LEFT JOIN {assign_grades} g
                           ON g.assignment = s.assignment
                           AND s.userid = g.userid
                           AND g.attemptnumber = s.attemptnumber
                     WHERE s.latest = 1 AND s.userid = :userid AND a.course = :courseid',
                ['userid' => $this->user->id, 'courseid' => $this->courseid]);
            foreach ($rows as $row) {
                $assigngrades[$row->assignmentid] = $row;
            }
            $rows->close();
            $this->assigngrades = $assigngrades;
        }
        return $this->assigngrades[$assignmentid] ?? null;
    }

    /**
     * Get assign object for the assignmentid.
     * @param $assignmentid
     * @return assign
     */
    protected function get_assignment($assignmentid): assign {
        if (empty($this->assignments[$assignmentid])) {
            global $CFG, $DB;
            require_once($CFG->dirroot . '/mod/assign/locallib.php');
            $instances = $this->modinfo->get_instances_of('assign');
            $cm = $instances[$assignmentid];
            $dbparams = array('id' => $assignmentid);
            $assign = $DB->get_record('assign', $dbparams);
            $assign->cmidnumber = $cm->idnumber;
            $cm = get_coursemodule_from_instance('assign', $assign->id, 0, false, MUST_EXIST);
            $context = context_module::instance($cm->id);
            $assignment = new assign($context, null, null);
            $assignment->set_instance($assign);
            $this->assignments[$assignmentid] = $assignment;
        }
        return $this->assignments[$assignmentid];
    }
}

function grade_report_submission_settings_definition(&$mform) {
    global $CFG;

    $options = array(-1 => get_string('default', 'grades'),
                      0 => get_string('hide'),
                      1 => get_string('show'));

    if (empty($CFG->grade_report_submission_showrank)) {
        $options[-1] = get_string('defaultprev', 'grades', $options[0]);
    } else {
        $options[-1] = get_string('defaultprev', 'grades', $options[1]);
    }

    $mform->addElement('select', 'report_submission_showrank', get_string('showrank', 'grades'), $options);
    $mform->addHelpButton('report_submission_showrank', 'showrank', 'grades');

    if (empty($CFG->grade_report_submission_showpercentage)) {
        $options[-1] = get_string('defaultprev', 'grades', $options[0]);
    } else {
        $options[-1] = get_string('defaultprev', 'grades', $options[1]);
    }

    $mform->addElement('select', 'report_submission_showpercentage', get_string('showpercentage', 'grades'), $options);
    $mform->addHelpButton('report_submission_showpercentage', 'showpercentage', 'grades');

    if (empty($CFG->grade_report_submission_showgrade)) {
        $options[-1] = get_string('defaultprev', 'grades', $options[0]);
    } else {
        $options[-1] = get_string('defaultprev', 'grades', $options[1]);
    }

    $mform->addElement('select', 'report_submission_showgrade', get_string('showgrade', 'grades'), $options);

    if (empty($CFG->grade_report_submission_showfeedback)) {
        $options[-1] = get_string('defaultprev', 'grades', $options[0]);
    } else {
        $options[-1] = get_string('defaultprev', 'grades', $options[1]);
    }

    $mform->addElement('select', 'report_submission_showfeedback', get_string('showfeedback', 'grades'), $options);

    if (empty($CFG->grade_report_submission_showweight)) {
        $options[-1] = get_string('defaultprev', 'grades', $options[0]);
    } else {
        $options[-1] = get_string('defaultprev', 'grades', $options[1]);
    }

    $mform->addElement('select', 'report_submission_showweight', get_string('showweight', 'grades'), $options);

    if (empty($CFG->grade_report_submission_showaverage)) {
        $options[-1] = get_string('defaultprev', 'grades', $options[0]);
    } else {
        $options[-1] = get_string('defaultprev', 'grades', $options[1]);
    }

    $mform->addElement('select', 'report_submission_showaverage', get_string('showaverage', 'grades'), $options);
    $mform->addHelpButton('report_submission_showaverage', 'showaverage', 'grades');

    if (empty($CFG->grade_report_submission_showlettergrade)) {
        $options[-1] = get_string('defaultprev', 'grades', $options[0]);
    } else {
        $options[-1] = get_string('defaultprev', 'grades', $options[1]);
    }

    $mform->addElement('select', 'report_submission_showlettergrade', get_string('showlettergrade', 'grades'), $options);
    if (empty($CFG->grade_report_submission_showcontributiontocoursetotal)) {
        $options[-1] = get_string('defaultprev', 'grades', $options[0]);
    } else {
        $options[-1] = get_string('defaultprev', 'grades', $options[$CFG->grade_report_submission_showcontributiontocoursetotal]);
    }

    $mform->addElement('select', 'report_submission_showcontributiontocoursetotal',
        get_string('showcontributiontocoursetotal', 'grades'), $options);
    $mform->addHelpButton('report_submission_showcontributiontocoursetotal', 'showcontributiontocoursetotal', 'grades');

    if (empty($CFG->grade_report_submission_showrange)) {
        $options[-1] = get_string('defaultprev', 'grades', $options[0]);
    } else {
        $options[-1] = get_string('defaultprev', 'grades', $options[1]);
    }

    $mform->addElement('select', 'report_submission_showrange', get_string('showrange', 'grades'), $options);

    if (empty($CFG->grade_report_submission_showattemptnumber)) {
        $options[-1] = get_string('defaultprev', 'grades', $options[0]);
    } else {
        $options[-1] = get_string('defaultprev', 'grades', $options[1]);
    }

    $mform->addElement('select', 'report_submission_showattemptnumber',
        get_string('showattemptnumber', 'gradereport_submission'), $options);

    if (empty($CFG->grade_report_submission_showsubmissionstatus)) {
        $options[-1] = get_string('defaultprev', 'grades', $options[0]);
    } else {
        $options[-1] = get_string('defaultprev', 'grades', $options[1]);
    }

    $mform->addElement('select', 'report_submission_showsubmissionstatus',
        get_string('showsubmissionstatus', 'gradereport_submission'), $options);

    if (empty($CFG->grade_report_submission_showgradingstatus)) {
        $options[-1] = get_string('defaultprev', 'grades', $options[0]);
    } else {
        $options[-1] = get_string('defaultprev', 'grades', $options[1]);
    }

    $mform->addElement('select', 'report_submission_showgradingstatus',
        get_string('showgradingstatus', 'gradereport_submission'), $options);

    if (empty($CFG->grade_report_submission_showdateofgrading)) {
        $options[-1] = get_string('defaultprev', 'grades', $options[0]);
    } else {
        $options[-1] = get_string('defaultprev', 'grades', $options[1]);
    }

    $mform->addElement('select', 'report_submission_showdateofgrading',
        get_string('showdateofgrading', 'gradereport_submission'), $options);

    if (empty($CFG->grade_report_submission_showgrader)) {
        $options[-1] = get_string('defaultprev', 'grades', $options[0]);
    } else {
        $options[-1] = get_string('defaultprev', 'grades', $options[1]);
    }

    $mform->addElement('select', 'report_submission_showgrader', get_string('showgrader', 'gradereport_submission'), $options);

    $options = array(0 => 0, 1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5);
    if (! empty($CFG->grade_report_submission_rangedecimals)) {
        $options[-1] = $options[$CFG->grade_report_submission_rangedecimals];
    }
    $mform->addElement('select', 'report_submission_rangedecimals', get_string('rangedecimals', 'grades'), $options);

    $options = array(-1 => get_string('default', 'grades'),
                      0 => get_string('shownohidden', 'grades'),
                      1 => get_string('showhiddenuntilonly', 'grades'),
                      2 => get_string('showallhidden', 'grades'));

    if (empty($CFG->grade_report_submission_showhiddenitems)) {
        $options[-1] = get_string('defaultprev', 'grades', $options[0]);
    } else {
        $options[-1] = get_string('defaultprev', 'grades', $options[$CFG->grade_report_submission_showhiddenitems]);
    }

    $mform->addElement('select', 'report_submission_showhiddenitems', get_string('showhiddenitems', 'grades'), $options);
    $mform->addHelpButton('report_submission_showhiddenitems', 'showhiddenitems', 'grades');

    // Showtotalsifcontainhidden.
    $options = array(-1 => get_string('default', 'grades'),
                      GRADE_REPORT_HIDE_TOTAL_IF_CONTAINS_HIDDEN => get_string('hide'),
                      GRADE_REPORT_SHOW_TOTAL_IF_CONTAINS_HIDDEN => get_string('hidetotalshowexhiddenitems', 'grades'),
                      GRADE_REPORT_SHOW_REAL_TOTAL_IF_CONTAINS_HIDDEN => get_string('hidetotalshowinchiddenitems', 'grades') );

    if (empty($CFG->grade_report_submission_showtotalsifcontainhidden)) {
        $options[-1] = get_string('defaultprev', 'grades', $options[0]);
    } else {
        $options[-1] = get_string('defaultprev', 'grades', $options[$CFG->grade_report_submission_showtotalsifcontainhidden]);
    }

    $mform->addElement('select', 'report_submission_showtotalsifcontainhidden',
        get_string('hidetotalifhiddenitems', 'grades'), $options);
    $mform->addHelpButton('report_submission_showtotalsifcontainhidden', 'hidetotalifhiddenitems', 'grades');

}

/**
 * Profile report callback.
 *
 * @param object $course The course.
 * @param object $user The user.
 * @param boolean $viewasuser True when we are viewing this as the targetted user sees it.
 */
function grade_report_submission_profilereport($course, $user, $viewasuser = false) {
    global $OUTPUT;
    if (!empty($course->showgrades)) {

        $context = context_course::instance($course->id);

        // Return tracking object.
        $gpr = new grade_plugin_return(array('type' => 'report', 'plugin' => 'user',
            'courseid' => $course->id, 'userid' => $user->id));
        // Create a report instance.
        $report = new grade_report_submission($course->id, $gpr, $context, $user->id, $viewasuser);

        // Print the page.
        echo '<div class="grade-report-user">'; // Css fix to share styles with real report page.
        if ($report->fill_table()) {
            echo $report->print_table(true);
        }
        echo '</div>';
    }
}

/**
 * Add nodes to myprofile page.
 *
 * @param \core_user\output\myprofile\tree $tree Tree object
 * @param stdClass $user user object
 * @param bool $iscurrentuser
 * @param stdClass $course Course object
 */
function gradereport_submission_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    global $CFG, $USER;
    if (empty($course)) {
        // We want to display these reports under the site context.
        $course = get_fast_modinfo(SITEID)->get_course();
    }
    $usercontext = context_user::instance($user->id);
    $anyreport = has_capability('moodle/user:viewuseractivitiesreport', $usercontext);

    // Start capability checks.
    if ($anyreport || $iscurrentuser) {
        // Add grade hardcoded grade report if necessary.
        $gradeaccess = false;
        $coursecontext = context_course::instance($course->id);
        if (has_capability('moodle/grade:viewall', $coursecontext)) {
            // Can view all course grades.
            $gradeaccess = true;
        } else if ($course->showgrades) {
            if ($iscurrentuser && has_capability('moodle/grade:view', $coursecontext)) {
                // Can view own grades.
                $gradeaccess = true;
            } else if (has_capability('moodle/grade:viewall', $usercontext)) {
                // Can view grades of this user - parent most probably.
                $gradeaccess = true;
            } else if ($anyreport) {
                // Can view grades of this user - parent most probably.
                $gradeaccess = true;
            }
        }
        if ($gradeaccess) {
            $url = new moodle_url('/grade/report/submission/index.php', array('id' => $course->id, 'user' => $user->id));
            $node = new core_user\output\myprofile\node('reports', 'submission',
                get_string('pluginname', 'gradereport_submission'), null, $url);
            $tree->add_node($node);
        }
    }
}
