<?php // $Id: $

$settings->add(new admin_setting_configtext('block_recent_courses_default', get_string('default_max', 'block_recent_courses'),
                   get_string('default_max_desc', 'block_recent_courses'), 5, PARAM_INT));

$settings->add(new admin_setting_configcheckbox('block_recent_courses_musthaverole', get_string('musthaverole', 'block_recent_courses'),
                   get_string('musthaverole_desc', 'block_recent_courses'), 1));

?>