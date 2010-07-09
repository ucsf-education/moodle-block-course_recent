<?php // $Id: $

$settings->add(new admin_setting_configtext('block_course_recent_default', get_string('default_max', 'block_course_recent'),
                   get_string('default_max_desc', 'block_course_recent'), 5, PARAM_INT));

$settings->add(new admin_setting_configcheckbox('block_course_recent_musthaverole', get_string('musthaverole', 'block_course_recent'),
                   get_string('musthaverole_desc', 'block_course_recent'), 1));

?>