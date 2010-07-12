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
 * Global block settings definition.
 *
 * @package   blocks-course_recent
 * @copyright 2010 Remote Learner - http://www.remote-learner.net/
 * @author    Akin Delamarre <adelamarre@remote-learner.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


$settings->add(new admin_setting_configtext('block_course_recent_default', get_string('default_max', 'block_course_recent'),
                   get_string('default_max_desc', 'block_course_recent'), 5, PARAM_INT));

$settings->add(new admin_setting_configcheckbox('block_course_recent_musthaverole', get_string('musthaverole', 'block_course_recent'),
                   get_string('musthaverole_desc', 'block_course_recent'), 1));

?>