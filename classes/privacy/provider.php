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
 * Privacy Subsystem implementation for block_course_recent.
 *
 * @package    block_course_recent
 * @copyright  The Regents of the University of California
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_course_recent\privacy;

use coding_exception;
use context;
use context_user;
use core_privacy\local\metadata\collection;
use core_privacy\local\metadata\provider as metadata_provider;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\core_userlist_provider;
use core_privacy\local\request\plugin\provider as data_provider;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use dml_exception;

/**
 * Privacy metadata provider class.
 *
 * @package    block_course_recent
 * @copyright  The Regents of the University of California
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements core_userlist_provider, data_provider, metadata_provider {
    /**
     * Returns the metadata for the user data stored in this plugin.
     *
     * @param collection $collection The metadata collection to add items to.
     * @return collection The updated metadata collection.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'block_course_recent',
            [
                'userid' => 'privacy:metadata:block_course_recent:userid',
                'userlimit' => 'privacy:metadata:block_course_recent:userlimit',
            ],
            'privacy:metadata:block_course_recent',
        );

        return $collection;
    }

    /**
     * Retrieves contexts that contain user information for a given user.
     *
     * @param int $userid The user ID.
     * @return contextlist The list of contexts applicable for this user.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $params = [
            'contextlevel'  => CONTEXT_USER,
            'userid'        => $userid,
        ];

        $sql = <<<EOD
SELECT c.id
FROM {context} c
JOIN {block_course_recent} b
ON c.instanceid = b.userid
WHERE b.userid = :userid
AND c.contextlevel = :contextlevel
EOD;
        $contextlist->add_from_sql($sql, $params);
        return $contextlist;
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;

        // Filter out any contexts that are not specific to the given user.
        $contexts = array_filter($contextlist->get_contexts(), function ($context) use ($userid) {
            return $context->contextlevel === CONTEXT_USER && $context->instanceid === $userid;
        });

        if (empty($contexts)) {
            return;
        }

        $params = ['userid' => $userid];
        $sql = "SELECT b.* FROM {block_course_recent} b WHERE b.userid = :userid ORDER BY b.id";
        $records = $DB->get_records_sql($sql, $params);
        $data = (object) array_map(function ($record) {
            return [
                'user' => $record->userid,
                'userlimit' => $record->userlimit,
            ];
        }, $records);
        $context = reset($contexts);
        writer::with_context($context)->export_data([
            get_string('pluginname', 'block_course_recent'),
        ], $data);
    }

    /**
     * Delete all data for all users in the given context.
     *
     * @param context $context The given context to delete data for.
     * @throws dml_exception
     */
    public static function delete_data_for_all_users_in_context(context $context): void {
        global $DB;
        if ($context->contextlevel !== CONTEXT_USER) {
            return;
        }

        $userid = $context->instanceid;

        $DB->delete_records('block_course_recent', ['userid' => $userid]);
    }

    /**
     * Delete all user data for a given user, in the given contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete data for.
     * @throws dml_exception
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;
        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        // Filter out any contexts that are not specific to the given user.
        $contexts = array_filter($contextlist->get_contexts(), function ($context) use ($userid) {
            return $context->contextlevel === CONTEXT_USER && $context->instanceid === $userid;
        });

        if (empty($contexts)) {
            return;
        }

        $DB->delete_records('block_course_recent', ['userid' => $userid]);
    }

    /**
     * Adds users to the list of users who have data within its context.
     *
     * @param userlist $userlist The list of users who have data in this context/plugin combination.
     * @throws dml_exception
     */
    public static function get_users_in_context(userlist $userlist): void {
        global $DB;
        $context = $userlist->get_context();

        if (!$context instanceof context_user) {
            return;
        }

        $userid = $context->instanceid;
        $hasdata = $DB->record_exists_select('block_course_recent', 'userid = ?', [$userid]);

        if ($hasdata) {
            $userlist->add_user($userid);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     * @return void
     * @throws dml_exception
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;
        $context = $userlist->get_context();
        if ($context instanceof context_user && in_array($context->instanceid, $userlist->get_userids())) {
            $DB->delete_records('block_course_recent', ['userid' => $context->instanceid]);
        }
    }
}
