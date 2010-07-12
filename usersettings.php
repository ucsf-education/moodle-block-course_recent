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
 * Display the page to handle user instance configuration settings.
 *
 * @package   blocks-course_recent
 * @copyright 2010 Remote Learner - http://www.remote-learner.net/
 * @author    Akin Delamarre <adelamarre@remote-learner.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('usersettings_form.php');

defined('MOODLE_INTERNAL') OR die('Direct access to this script is forbidden');

require_login();

$blockid    = required_param('blockid', PARAM_INT);
$courseid   = required_param('courseid', PARAM_INT);

global $CFG, $USER;

$usersetting_form = new usersettings_form();

$record = get_record('block_course_recent', 'userid', $USER->id, 'blockid', $blockid);

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
        update_record('block_course_recent', $data);
    } else {
        insert_record('block_course_recent', $data);
    }

    redirect($CFG->wwwroot.'/course/view.php?id='. $courseid);
}


$navlinks = array();

if ($courseid && $courseid !== SITEID) {
    $shortname = get_field('course', 'shortname', 'id', $courseid);
    $navlinks[] = array(
        'name' => format_string($shortname),
        'link' => $CFG->wwwroot . '/course/view.php?id=' . $courseid,
        'type' => 'link'
    );
}
$navlinks[] = array('name' => get_string('breadcrumb', 'block_course_recent'), 'link' => '', 'type' => 'misc');

$navigation = build_navigation($navlinks);

print_header_simple(get_string('header', 'block_course_recent'), '', $navigation);

$usersetting_form->display();

print_footer();

?>