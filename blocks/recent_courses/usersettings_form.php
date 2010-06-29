<?php // $Id: $

require_once($CFG->libdir.'/formslib.php');
require_once('lib.php');

class usersettings_form extends moodleform {
      function definition() {
          global $USER, $CFG;

          $mform =& $this->_form;

          $mform->addElement('text', 'userlimit', get_string('userlimit', 'block_recent_courses'), 'maxlength="5" size="5"');
          $mform->setType('userlimit', PARAM_INT);

          $mform->addElement('hidden', 'blockid');
          $mform->addElement('hidden', 'userid');
          $mform->addElement('hidden', 'id');
          $mform->addElement('hidden', 'courseid');

          $this->add_action_buttons(true);

     }

    function validation($data, $files) {
        global $CFG;

        $errors = parent::validation($data, $files);

        if (LOWER_LIMIT > $data['userlimit']) {
            $errors['userlimit'] = get_string('error1', 'block_recent_courses');
        } elseif (UPPER_LIMIT < $data['userlimit']) {
            $errors['userlimit'] = get_string('error2', 'block_recent_courses');
        }

        return $errors;

    }

}
?>