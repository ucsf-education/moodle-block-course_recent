<?php // $Id: $
    require_once('../../config.php');
    require_once('usersettings_form.php');

    defined('MOODLE_INTERNAL') OR die('Direct access to this script is forbidden');

    require_login();

    $blockid    = required_param('blockid', PARAM_INT);
    $courseid   = required_param('courseid', PARAM_INT);

    global $CFG, $USER;

    $usersetting_form = new usersettings_form();

    $record = get_record('block_recent_courses', 'userid', $USER->id, 'blockid', $blockid);

    // Set the hidden form elements
    if (!empty($record)) {
        $usersetting_form->set_data(array('blockid' => $blockid, 'userid' => $USER->id,
                                          'id' => $record->id, 'userlimit' => $record->userlimit,
                                          'courseid' => $courseid));
    } else {
        $usersetting_form->set_data(array('blockid' => $blockid, 'userid' => $USER->id,
                                          'id' => 0, 'courseid' => $courseid));
    }

    if ($usersetting_form->is_cancelled()) {
        redirect($CFG->wwwroot.'/course/view.php?id='. $courseid);

    } else if ($data = $usersetting_form->get_data()) {
        if (!empty($data->id)) {
            update_record('block_recent_courses', $data);
        } else {
            insert_record('block_recent_courses', $data);
        }

        redirect($CFG->wwwroot.'/course/view.php?id='. $courseid);
    }


    $navlinks = array();

    if ($courseid and 1 < $courseid) {
        $shortname = get_field('course', 'shortname', 'id', $courseid);
        $navlinks[] = array('name' => format_string($shortname), 'link' => "view.php?id=$courseid", 'type' => 'link');
    }
    $navlinks[] = array('name' => get_string('breadcrumb', 'block_recent_courses'), 'link' => '', 'type' => 'misc');

    $navigation = build_navigation($navlinks);

    print_header_simple(get_string('header', 'block_recent_courses'), '', $navigation);

    $usersetting_form->display();

    print_footer();

?>