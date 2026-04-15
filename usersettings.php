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
 * Display the page to handle user instance configuration settings.
 *
 * @package   block_course_recent
 * @copyright 2014 The Regents of the University of California
 * @copyright 2010 Remote Learner - http://www.remote-learner.net/
 * @author    Carson Tam <carson.tam@ucsf.edu>, Akin Delamarre <adelamarre@remote-learner.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('usersettings_form.php');

defined('MOODLE_INTERNAL') || die();

$courseid = required_param('courseid', PARAM_INT);

$PAGE->set_url('/blocks/course_recent/usersettings.php', ['courseid' => $courseid]);
$PAGE->set_pagelayout('standard');

if (!$course = $DB->get_record('course', ['id' => $courseid])) {
    throw new moodle_exception('invalidcourse');
}

require_login($course);

$usersettingform = new usersettings_form();

$record = $DB->get_record('block_course_recent', ['userid' => $USER->id]);

// Set the hidden form elements.
if (!empty($record)) {
    $usersettingform->set_data([
        'userid'    => $USER->id,
        'id'        => $record->id,
        'userlimit' => $record->userlimit,
        'courseid'  => $courseid,
    ]);
} else {
    $usersettingform->set_data([
        'userid'   => $USER->id,
        'id'       => 0,
        'courseid' => $courseid,
    ]);
}

if ($usersettingform->is_cancelled()) {
    redirect($CFG->wwwroot . '/course/view.php?id=' . $courseid);
} else if ($data = $usersettingform->get_data()) {
    if (!empty($data->id)) {
        $DB->update_record('block_course_recent', $data);
    } else {
        $DB->insert_record('block_course_recent', $data);
    }

    redirect($CFG->wwwroot . '/course/view.php?id=' . $courseid);
}

if ($courseid && $courseid != SITEID) {
    $shortname = $DB->get_field('course', 'shortname', ['id' => $courseid]);
    $PAGE->navbar->add(format_string($shortname), new moodle_url('/course/view.php', ['id' => $courseid]));
}
$PAGE->navbar->add(get_string('breadcrumb', 'block_course_recent'));

$site = get_site();
$PAGE->set_title($site->shortname . ': ' . get_string('block', 'moodle') . ': '
                 . get_string('pluginname', 'block_course_recent') . ': '
                 . get_string('settings', 'block_course_recent'));

$PAGE->set_heading($site->fullname);
echo $OUTPUT->header();

$usersettingform->display();

echo $OUTPUT->footer();
