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

        // Get a list of all courses that have been viewed by the user,
        $sql = "SELECT DISTINCT(course) FROM {$CFG->prefix}log WHERE userid = {$USER->id} AND action = 'view' ORDER BY time DESC";
        $records = get_records_sql($sql);

        if (empty($records)) {
            $records = array();
        }

        $text = '';

        $roleid = get_field('role', 'id', 'shortname', 'student');

        $i = 1;

        $maximum = isset($CFG->block_course_recent_default) ? $CFG->block_course_recent_default : DEFAULT_MAX;

        $userlimit = get_field('block_course_recent', 'userlimit', 'blockid', $this->instance->id,
                               'userid', $USER->id);

        // Override the global setting if the user limit is set
        if (!empty($userlimit)) {
            $maximum = $userlimit;
        }

        if (LOWER_LIMIT > $maximum) {
            $maximum = LOWER_LIMIT;
        } elseif (UPPER_LIMIT < $maximum) {
            $maximum = UPPER_LIMIT;
        }

        // Set flag to check user's role on the course
        $checkrole = false;
        if ($CFG->block_course_recent_musthaverole) {
            $checkrole = true;
        }


        // Set flag to display hidden courses
        $showhidden = false;
        $context = get_context_instance(CONTEXT_SYSTEM);
        if (has_capability('moodle/course:viewhiddencourses', $context, $USER->id)) {
            $showhidden = true;
        }

        // Set flag to true by defafult
        $showcourse = true;

        // Create links for each course that was viewed by the user
        foreach ($records as $key => $record) {

            if ($i <= $maximum) {
                if ($record->course == 0 or $record->course == 1) {

                    unset($records[$key]);
                } else {

                    $i++;

                    $visible = get_field('course', 'visible', 'id', $record->course);

                    $class = ($visible) ? 'visible' : 'notvisible';

                    $context = get_context_instance(CONTEXT_COURSE, $record->course);

                    if ($checkrole) {
                        if (record_exists('role_assignments', 'userid', $USER->id, 'contextid', $context->id)) {
//                        if (user_has_role_assignment($USER->id, $roleid, $context->id)) {
                            $showcourse = true;
                        } else {
                            $showcourse = false;
                        }
                    }

                    if ( ($visible or $showhidden) and $showcourse ) {
                        // Get a list or courses where the user has the student role
                        $fullname = get_field('course', 'fullname', 'id', $record->course);
                        $text .= '<a class="'.$class.'" href="'. $CFG->wwwroot .'/course/view.php?id='.
                                 $record->course .'">' . $fullname . '</a><br />';
                    }
                }
            } else {
                // break out of loop once maximum is reached
                break;
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