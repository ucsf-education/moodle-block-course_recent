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
 * Definition of the form for user instance configuration settings.
 *
 * @package   blocks-course_recent
 * @copyright &copy; 2014 The Regents of the University of California
 *            2010 Remote Learner - http://www.remote-learner.net/
 * @author    Carson Tam <carson.tam@ucsf.edu>, Akin Delamarre <adelamarre@remote-learner.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once($CFG->libdir.'/formslib.php');
require_once('lib.php');

class usersettings_form extends moodleform {
    function definition() {
        global $USER, $CFG;

        $mform =& $this->_form;

        $choices = array();

        for ($i = 1; $i <= 10; $i++) {
            $choices[$i] = $i;
        }

        $mform->addElement('select', 'userlimit', get_string('userlimit', 'block_course_recent'), $choices);
        $mform->addHelpButton('userlimit', 'userlimit', 'block_course_recent');
        $mform->setDefault('userlimit', DEFAULT_MAX);
        $mform->setType('userlimit', PARAM_INT);

        $mform->addElement('hidden', 'userid');
	$mform->setType('userid', PARAM_INT);
        $mform->addElement('hidden', 'id');
	$mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'courseid');
	$mform->setType('courseid', PARAM_INT);

        $this->add_action_buttons(true);
    }

    function validation($data, $files) {
        global $CFG;

        $errors = parent::validation($data, $files);

        if (LOWER_LIMIT > $data['userlimit']) {
            $errors['userlimit'] = get_string('error1', 'block_course_recent');
        } elseif (UPPER_LIMIT < $data['userlimit']) {
            $errors['userlimit'] = get_string('error2', 'block_course_recent');
        }

        return $errors;

    }
}

?>