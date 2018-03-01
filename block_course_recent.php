<?php
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
 * Recent courses block main class.
 *
 * @package   blocks-course_recent
 * @copyright 2010 Remote Learner - http://www.remote-learner.net/
 * @author    Akin Delamarre <adelamarre@remote-learner.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once($CFG->dirroot.'/blocks/course_recent/lib.php');

class block_course_recent extends block_list {
    function init() {
        $this->title   = get_string('course_recent', 'block_course_recent');
    }

    function get_content() {
        global $CFG, $DB, $USER, $COURSE, $OUTPUT;

        if ($this->content !== NULL) {
          return $this->content;
        }

        $this->content         =  new stdClass;
        $this->content->items  = array();
        $this->content->icons  = array();
        $this->content->footer = '';

        if (!isloggedin() or isguestuser()) {
            return $this->content;
        }

        //$context = get_context_instance(CONTEXT_BLOCK, $this->instance->id);
        $context = context_block::instance($this->instance->id);

        if (has_capability('block/course_recent:changelimit', $context, $USER->id)) {
            $this->content->footer = '<a href="' . $CFG->wwwroot.'/blocks/course_recent/usersettings.php?' .
                                     'courseid='.$COURSE->id . '">' . get_string('settings', 'block_course_recent') .
                                     '</a>';
        }

        $maximum = isset($CFG->block_course_recent_default) ? $CFG->block_course_recent_default : DEFAULT_MAX;

        $userlimit = $DB->get_field('block_course_recent', 'userlimit', array('userid' => $USER->id));

        // Override the global setting if the user limit is set
        if (!empty($userlimit)) {
            $maximum = $userlimit;
        }

        // Make sure the maximum record number is within the acceptible range.
        if (LOWER_LIMIT > $maximum) {
            $maximum = LOWER_LIMIT;
        } elseif (UPPER_LIMIT < $maximum) {
            $maximum = UPPER_LIMIT;
        }

        // Set flag to check user's role on the course
        $checkrole = !empty($CFG->block_course_recent_musthaverole);

        if (has_capability('block/course_recent:showall', $context, $USER->id)) {
          $checkrole = false;
        }

        $showhidden = true;

        $three_months_ago = strtotime('-3 months');

        $query_params = array();

        // Get a list of all courses that have been viewed by the user.
        if (!$checkrole) {
            $sql = "SELECT l.courseid, c.fullname, c.visible, c.shortname
                    FROM {logstore_standard_log} l
                    JOIN {course} c ON l.courseid = c.id
                    WHERE l.userid = ?
                      AND l.target = 'course'
                      AND l.courseid NOT IN(0, 1)
                      AND l.action = 'viewed'
                      AND l.timecreated >= ?
                    GROUP BY l.courseid
                    ORDER BY max(l.timecreated) DESC";

            $query_params[] = $USER->id;
            $query_params[] = $three_months_ago;
        } else {
            // The following SQL will ensure that the user has a current role assignment within the course.

            $sql = "SELECT l.courseid, c.fullname, c.visible, c.shortname
                    FROM {logstore_standard_log} l
                    JOIN {course} c ON l.courseid = c.id
                    JOIN {context} ctx ON l.courseid = ctx.instanceid
                    JOIN {role_assignments} ra ON ra.contextid = ctx.id
                    WHERE l.userid = ?
                      AND l.target = 'course'
                      AND l.courseid NOT IN(0, 1)
                      AND l.action = 'viewed'
                      AND l.timecreated >= ?
                      AND ctx.contextlevel = ?
                      AND ra.userid = l.userid
                    GROUP BY l.courseid
                    ORDER BY max(l.timecreated) DESC";

            $query_params[] = $USER->id;
            $query_params[] = $three_months_ago;
            $query_params[] = CONTEXT_COURSE;
         }

        $records = $DB->get_recordset_sql($sql, $query_params, 0, $maximum);

        if (!$records->valid()) {

            $this->content->items[] = get_string('youhavenotentredanycourses', 'block_course_recent');
            $this->content->icons[] = '';
            return $this->content;
        }

        $icon  = $OUTPUT->pix_icon('i/course_recent', get_string('coursecategory'), 'block_course_recent');

        // Create links for each course that was viewed by the user
        foreach ($records as $record) {

            //$context = get_context_instance(CONTEXT_COURSE, $record->course);
            $context = context_course::instance($record->courseid);
            $showhidden = has_capability('moodle/course:viewhiddencourses', $context, $USER->id);

            // Check the 'view participants' capability if the block has the
            // 'most have role in course' is turned off.  We need this because
            // Users may have roles outside of the course context
            if (!$checkrole) {
                $showcourse = has_capability('moodle/course:viewparticipants', $context, $USER->id);
            } else {
                $showcourse = true;
            }

            if ($showcourse or (isset($record->guest) and !empty($record->guest))) {

                if ($showhidden and !$record->visible) {
                    $this->content->items[] = '<a class="' . 'dimmed' . '" title="' . $record->shortname . '" href="'.
                                              $CFG->wwwroot .'/course/view.php?id=' . $record->courseid . '">' . $icon .
                                              $record->fullname . '</a>';
                } else {
                    $this->content->items[] = '<a class="' . (($record->visible) ? 'visible' : 'dimmed') . '"'.
                                              ' title="' . $record->shortname . '" href="'.
                                              $CFG->wwwroot .'/course/view.php?id=' . $record->courseid . '">' . $icon .
                                              $record->fullname . '</a>';
                }
            }
        }

        $records->close();

        return $this->content;
    }

    function has_config() {
        return true;
    }

    /**
     * Which page types this block may appear on.
     *
     * @return array page-type prefix => true/false.
     */
    function applicable_formats() {
      return array('all' => true);
    }


    public function instance_allow_multiple() {
      return false;
    }
}
