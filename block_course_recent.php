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
 * The recent courses block.
 *
 * @package   block_course_recent
 * @copyright 2010 Remote Learner - http://www.remote-learner.net/
 * @author    Akin Delamarre <adelamarre@remote-learner.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/course_recent/lib.php');

/**
 * Recent courses block class.
 *
 * @package    block_course_recent
 * @copyright  The Regents of the University of California
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_course_recent extends block_list {
    /**
     * Initializes block.
     *
     * @return void
     * @throws coding_exception
     */
    public function init(): void {
        $this->title = get_string('course_recent', 'block_course_recent');
    }

    /**
     * Retrieves block content.
     *
     * @return stdClass The content object.
     * @throws coding_exception
     * @throws dml_exception
     */
    public function get_content(): stdClass {
        global $CFG, $DB, $USER, $COURSE, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content         = new stdClass();
        $this->content->items  = [];
        $this->content->footer = '';

        if (!isloggedin() || isguestuser()) {
            return $this->content;
        }

        $context = context_block::instance($this->instance->id);

        if (has_capability('block/course_recent:changelimit', $context, $USER->id)) {
            $this->content->footer = '<a href="' . $CFG->wwwroot . '/blocks/course_recent/usersettings.php?' .
                                     'courseid=' . $COURSE->id . '">' . get_string('settings', 'block_course_recent') .
                                     '</a>';
        }

        $maximum = isset($CFG->block_course_recent_default) ? $CFG->block_course_recent_default : DEFAULT_MAX;

        $userlimit = $DB->get_field('block_course_recent', 'userlimit', ['userid' => $USER->id]);

        // Override the global setting if the user limit is set.
        if (!empty($userlimit)) {
            $maximum = $userlimit;
        }

        // Make sure the maximum record number is within the acceptible range.
        if (LOWER_LIMIT > $maximum) {
            $maximum = LOWER_LIMIT;
        } else if (UPPER_LIMIT < $maximum) {
            $maximum = UPPER_LIMIT;
        }

        // Set flag to check user's role on the course.
        $checkrole = !empty($CFG->block_course_recent_musthaverole);

        if (has_capability('block/course_recent:showall', $context, $USER->id)) {
            $checkrole = false;
        }

        $showhidden = true;

        $threemonthsago = strtotime('-3 months');

        $queryparams = [];

        // Get a list of all courses that have been viewed by the user.
        if (!$checkrole) {
            // Note: make sure this query utilizes the key, `mdl_logsstanlog_useconconcr_ix`
            // (`userid`,`contextlevel`,`contextinstanceid`,`crud`,`edulevel`,`timecreated`)
            // in the `mdl_logstore_standard_log` table to improve performance.

            $sql = "SELECT l.courseid, c.fullname, c.visible, c.shortname
                    FROM {logstore_standard_log} l
                    JOIN {course} c ON l.courseid = c.id
                    WHERE l.userid = ?
                      AND l.contextlevel = ?
                      AND l.target = 'course'
                      AND l.courseid NOT IN(0, 1)
                      AND l.action = 'viewed'
                      AND l.timecreated >= ?
                    GROUP BY l.courseid
                    ORDER BY max(l.timecreated) DESC";

            $queryparams[] = $USER->id;
            $queryparams[] = CONTEXT_COURSE;
            $queryparams[] = $threemonthsago;
        } else {
            // The following SQL will ensure that the user has a current role assignment within the course.
            // Note: make sure the query utilizes this key, `mdl_logsstanlog_useconconcr_ix`
            // (`userid`,`contextlevel`,`contextinstanceid`,`crud`,`edulevel`,`timecreated`)
            // in the `mdl_logstore_standard_log` table to improve performance.

            $sql = "SELECT l.courseid, c.fullname, c.visible, c.shortname
                    FROM {logstore_standard_log} l
                    JOIN {course} c ON l.courseid = c.id
                    JOIN {context} ctx ON l.courseid = ctx.instanceid
                    JOIN {role_assignments} ra ON ra.contextid = ctx.id
                    WHERE l.userid = ?
                      AND l.contextlevel = ?
                      AND l.target = 'course'
                      AND l.courseid NOT IN(0, 1)
                      AND l.action = 'viewed'
                      AND l.timecreated >= ?
-- not sure if I should remove AND ra.userid = l.userid
                    GROUP BY l.courseid
                    ORDER BY max(l.timecreated) DESC";

            $queryparams[] = $USER->id;
            $queryparams[] = CONTEXT_COURSE;
            $queryparams[] = $threemonthsago;
        }

        $records = $DB->get_recordset_sql($sql, $queryparams, 0, $maximum);

        if (!$records->valid()) {
            $this->content->items[] = get_string('youhavenotentredanycourses', 'block_course_recent');
            return $this->content;
        }


        // Create links for each course that was viewed by the user.
        foreach ($records as $record) {
            $context = context_course::instance($record->courseid);
            $showhidden = has_capability('moodle/course:viewhiddencourses', $context, $USER->id);

            // Check the 'view participants' capability if the block has the
            // 'most have role in course' is turned off.  We need this because
            // Users may have roles outside the course context.
            if (!$checkrole) {
                $showcourse = has_capability('moodle/course:viewparticipants', $context, $USER->id);
            } else {
                $showcourse = true;
            }

            if ($showcourse || (isset($record->guest) && !empty($record->guest))) {
                if ($showhidden && !$record->visible) {
                    $this->content->items[] = '<a class="' . 'dimmed' . '" title="' . $record->shortname . '" href="' .
                                              $CFG->wwwroot . '/course/view.php?id=' . $record->courseid . '">' .
                                              $record->fullname . '</a>';
                } else {
                    $this->content->items[] = '<a class="' . (($record->visible) ? 'visible' : 'dimmed') . '"' .
                                              ' title="' . $record->shortname . '" href="' .
                                              $CFG->wwwroot . '/course/view.php?id=' . $record->courseid . '">' .
                                              $record->fullname . '</a>';
                }
            }
        }

        $records->close();

        return $this->content;
    }

    /**
     * Indicates if this block has its own settings form or not.
     * @return bool Always TRUE.
     */
    public function has_config(): bool {
        return true;
    }

    /**
     * Returns a list of page types that this block may appear on.
     *
     * @return array The page types.
     */
    public function applicable_formats(): array {
        return ['all' => true];
    }

    /**
     * Checks if multiple instances of this block are allowed.
     *
     * @return bool Always FALSE.
     */
    public function instance_allow_multiple(): bool {
        return false;
    }
}
