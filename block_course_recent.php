<?php // $Id: $

class block_course_recent extends block_base {
    function init() {
        $this->title   = get_string('course_recent', 'block_course_recent');
        $this->version = 201006280;
    }

    function get_content() {
        global $CFG, $USER, $COURSE;

        require_once($CFG->dirroot.'/blocks/course_recent/lib.php');

        if ($this->content !== NULL) {
          return $this->content;
        }

        $maximum = isset($CFG->block_course_recent_default) ? $CFG->block_course_recent_default : DEFAULT_MAX;

        $userlimit = get_field('block_course_recent', 'userlimit', 'blockid', $this->instance->id,
                               'userid', $USER->id);

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

        // Get a list of all courses that have been viewed by the user.
        if (!$checkrole) {
            $sql = "SELECT DISTINCT(logs.course)
                    FROM (
                        SELECT l.course, l.time
                        FROM {$CFG->prefix}log l
                        WHERE l.userid = 2
                        AND l.course NOT IN(0, 1)
                        AND l.action = 'view'
                        ORDER BY l.time DESC
                    ) AS logs";
        } else {
            $sql = "SELECT DISTINCT(logs.course)
                    FROM (
                        SELECT l.course, l.time
                        FROM {$CFG->prefix}log l
                        INNER JOIN {$CFG->prefix}context ctx ON l.course = ctx.instanceid
                        INNER JOIN {$CFG->prefix}role_assignments ra ON ra.contextid = ctx.id
                        WHERE l.userid = 2
                        AND l.course NOT IN(0, 1)
                        AND ctx.contextlevel = " . CONTEXT_COURSE . "
                        AND ra.userid = l.userid
                        AND l.action = 'view'
                        ORDER BY l.time DESC
                    ) AS logs";
        }

        $records = get_records_sql($sql, 0, $maximum);

        if (empty($records)) {
            $records = array();
        }

        $text = '';

        $i = 1;

        // Set flag to display hidden courses
        $context    = get_context_instance(CONTEXT_SYSTEM);
        $showhidden = has_capability('moodle/course:viewhiddencourses', $context, $USER->id);

        // Set flag to true by defafult
        $showcourse = true;

        // Create links for each course that was viewed by the user
        foreach ($records as $key => $record) {
            $visible = get_field('course', 'visible', 'id', $record->course);

            $class = ($visible) ? 'visible' : 'notvisible';

            if ($visible or $showhidden) {
                // Get a list or courses where the user has the student role
                $fullname = get_field('course', 'fullname', 'id', $record->course);
                $text .= '<a class="'.$class.'" href="'. $CFG->wwwroot .'/course/view.php?id='.
                         $record->course .'">' . $fullname . '</a><br />';
            }
        }

        $this->content         =  new stdClass;
        $this->content->text   = $text;

        $context = get_context_instance(CONTEXT_BLOCK, $this->instance->id);

        if (has_capability('block/course_recent:changelimit', $context, $USER->id)) {
            $this->content->footer = '<a href="'.$CFG->wwwroot.'/blocks/course_recent/usersettings.php?'.
                                     'blockid='. $this->instance->id.'&courseid='.$COURSE->id.'">'.
                                     get_string('settings', 'block_course_recent') . '</a>';
        } else {
            $this->content->footer = '';
        }

        return $this->content;
    }

    function has_config() {
        return true;
    }
}
?>