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


class block_course_recent extends block_list {
    function init() {
        $this->title   = get_string('blockname', 'block_course_recent');
        $this->version = 2010071300;
    }

    function get_content() {
        global $CFG, $USER, $COURSE;

        require_once($CFG->dirroot.'/blocks/course_recent/lib.php');

        if ($this->content !== NULL) {
          print_object('debugging 1');
          return $this->content;
        }

        $this->content         =  new stdClass;
        $this->content->items  = array();
        $this->content->icons  = array();
        $this->content->footer = '';

        if (!isloggedin() or isguestuser()) {
            return $this->content;
        }

        $context = get_context_instance(CONTEXT_BLOCK, $this->instance->id);

        if (has_capability('block/course_recent:changelimit', $context, $USER->id)) {
            $this->content->footer = '<a href="' . $CFG->wwwroot.'/blocks/course_recent/usersettings.php?' .
                                     'courseid='.$COURSE->id . '">' . get_string('settings', 'block_course_recent') .
                                     '</a>';
        }

        $maximum = isset($CFG->block_course_recent_default) ? $CFG->block_course_recent_default : DEFAULT_MAX;

        $userlimit = get_field('block_course_recent', 'userlimit', 'userid', $USER->id);

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
        //$checkrole = true;

        $showhidden = true;

        // Get a list of all courses that have been viewed by the user.
        if (!$checkrole) {
            $sql = "SELECT DISTINCT(logs.course), c.fullname, c.visible
                    FROM (
                        SELECT l.course, l.time
                        FROM {$CFG->prefix}log l
                        ";

            $sql .= "WHERE l.userid = {$USER->id}
                        AND l.course NOT IN(0, 1)
                        AND l.action = 'view'
                        ";

            $sql .= "ORDER BY l.time DESC
                    ) AS logs
                    INNER JOIN {$CFG->prefix}course c ON logs.course = c.id";
        } else {
            // The following SQL will ensure that the user has a current role assignment within the course.
            $sql = "SELECT DISTINCT(logs.course), c.fullname, c.visible
                    FROM (
                        SELECT l.course, l.time
                        FROM {$CFG->prefix}log l
                        INNER JOIN {$CFG->prefix}context ctx ON l.course = ctx.instanceid
                        INNER JOIN {$CFG->prefix}role_assignments ra ON ra.contextid = ctx.id
                        ";

            $sql .= "WHERE l.userid = {$USER->id}
                        AND l.course NOT IN(0, 1)
                        AND ctx.contextlevel = " . CONTEXT_COURSE . "
                        AND ra.userid = l.userid
                        AND l.action = 'view'
                        ";

            $sql .= "ORDER BY l.time DESC
                    ) AS logs
                    INNER JOIN {$CFG->prefix}course c ON logs.course = c.id";
        }

        $records = get_recordset_sql($sql, 0, $maximum);

        if (!$records or rs_EOF($records)) {

            $this->content->items[] = get_string('youhavenotentredanycourses', 'block_course_recent');
            $this->content->icons[] = '';
            return $this->content;
        }

        $icon  = '<img src="' . $CFG->pixpath . '/i/course.gif" class="icon" alt="' .
                 get_string('coursecategory') . '" />';

        // Create links for each course that was viewed by the user
        while ($record = rs_fetch_next_record($records)) {

            $context = get_context_instance(CONTEXT_COURSE, $record->course);
            $showhidden = has_capability('moodle/course:viewhiddencourses', $context, $USER->id);

            // Check the 'view participants' capability if the block has the
            // 'most have role in course' is turned off.  We need this because
            // Users may have roles outside of the course context
            if (!$checkrole) {
                $showcourse = has_capability('moodle/course:viewparticipants', $context, $USER->id);
            } else {
                $showcourse = true;
            }

            if ($showcourse) {
                if ($showhidden and !$record->visible) {
                    $this->content->items[] = '<a class="' . 'dimmed' . '" href="'.
                                              $CFG->wwwroot .'/course/view.php?id=' . $record->course . '">' .
                                              $record->fullname . '</a>';
                    $this->content->icons[] = $icon;
                } else {
                    $this->content->items[] = '<a class="' . (($record->visible) ? 'visible' : 'dimmed') . '" href="'.
                                              $CFG->wwwroot .'/course/view.php?id=' . $record->course . '">' .
                                              $record->fullname . '</a>';
                    $this->content->icons[] = $icon;
                }
            }
        }

        rs_close($records);

        return $this->content;
    }

    function has_config() {
        return true;
    }
}

?>
