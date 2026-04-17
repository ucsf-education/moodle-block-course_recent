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
 * Privacy provider tests for block_course_recent.
 *
 * @package    block_course_recent
 * @copyright  The Regents of the University of California
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_course_recent\privacy;

use coding_exception;
use context_system;
use context_user;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use core_privacy\tests\provider_testcase;
use dml_exception;

/**
 * Privacy provider test case.
 *
 * @package    block_course_recent
 * @copyright  The Regents of the University of California
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_course_recent\privacy\provider
 */
final class provider_test extends provider_testcase {
    /**
     * Test getting the context for the user ID related to this plugin.
     * @throws dml_exception
     */
    public function test_get_contexts_for_userid(): void {
        global $DB;
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();

        // Create two users.
        $user1 = $generator->create_user();
        $usercontext1 = context_user::instance($user1->id);
        $user2 = $generator->create_user();
        $usercontext2 = context_user::instance($user2->id);

        // No contexts returned before user-specific block configurations are applied.
        $contextlist1 = provider::get_contexts_for_userid($user1->id);
        $this->assertCount(0, $contextlist1);
        $contextlist2 = provider::get_contexts_for_userid($user2->id);
        $this->assertCount(0, $contextlist2);

        // Apply user-specific block config.
        $DB->insert_record('block_course_recent', (object) ['userid' => $user1->id, 'userlimit' => 10]);
        $DB->insert_record('block_course_recent', (object) ['userid' => $user2->id, 'userlimit' => 10]);

        // Ensure provider only fetches the user's own context.
        $contextlist1 = provider::get_contexts_for_userid($user1->id);
        $this->assertCount(1, $contextlist1);
        $this->assertEquals($usercontext1, $contextlist1->current());

        $contextlist1 = provider::get_contexts_for_userid($user2->id);
        $this->assertCount(1, $contextlist1);
        $this->assertEquals($usercontext2, $contextlist1->current());
    }

    /**
     * Test getting users in the context ID related to this plugin.
     * @throws dml_exception
     */
    public function test_get_users_in_context(): void {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $component = 'block_course_recent';

        // Create two users.
        $user1 = $generator->create_user();
        $usercontext1 = context_user::instance($user1->id);
        $user2 = $generator->create_user();
        $usercontext2 = context_user::instance($user2->id);

        // No users in given user-contexts before user-specific block configurations are applied.
        $userlist1 = new userlist($usercontext1, $component);
        provider::get_users_in_context($userlist1);
        $this->assertCount(0, $userlist1);
        $userlist2 = new userlist($usercontext2, $component);
        provider::get_users_in_context($userlist2);
        $this->assertCount(0, $userlist2);

        // Apply user-specific block config.
        $DB->insert_record('block_course_recent', (object) ['userid' => $user1->id, 'userlimit' => 10]);
        $DB->insert_record('block_course_recent', (object) ['userid' => $user2->id, 'userlimit' => 10]);

        // Ensure provider only fetches the user whose user context is checked.
        $userlist1 = new userlist($usercontext1, $component);
        provider::get_users_in_context($userlist1);
        $this->assertCount(1, $userlist1);
        $this->assertEquals($user1, $userlist1->current());

        $userlist2 = new userlist($usercontext2, $component);
        provider::get_users_in_context($userlist2);
        $this->assertCount(1, $userlist2);
        $this->assertEquals($user2, $userlist2->current());
    }

    /**
     * Test fetching information about user data stored.
     */
    public function test_get_metadata(): void {
        $collection = new collection('block_course_recent');
        $newcollection = provider::get_metadata($collection);
        $itemcollection = $newcollection->get_collection();
        $this->assertCount(1, $itemcollection);

        $table = reset($itemcollection);
        $this->assertEquals('block_course_recent', $table->get_name());

        $privacyfields = $table->get_privacy_fields();
        $this->assertCount(2, $privacyfields);
        $this->assertArrayHasKey('userid', $privacyfields);
        $this->assertArrayHasKey('userlimit', $privacyfields);
        $this->assertEquals('privacy:metadata:block_course_recent', $table->get_summary());
    }

    /**
     * Test exporting data for an approved contextlist.
     * @throws coding_exception
     * @throws dml_exception
     */
    public function test_export_user_data(): void {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $component = 'block_course_recent';

        $user = $generator->create_user();
        $usercontext = context_user::instance($user->id);

        // Apply user-specific block config.
        $params = ['userid' => $user->id, 'userlimit' => 10];
        $DB->insert_record('block_course_recent', (object) $params);

        // Confirm data is present.

        $result = $DB->count_records('block_course_recent', $params);
        $this->assertEquals(1, $result);

        // Export data for user.
        $approvedlist = new approved_contextlist($user, $component, [$usercontext->id]);
        provider::export_user_data($approvedlist);

        // Confirm user's data is exported.
        $subcontext = get_string('pluginname', 'block_course_recent');
        $writer = writer::with_context($usercontext);
        $this->assertTrue($writer->has_any_data([$subcontext]));
        $data = array_values((array) $writer->get_data([$subcontext]));
        $this->assertCount(1, $data);
        $this->assertEquals($params['userid'], $data[0]['user']);
        $this->assertEquals($params['userlimit'], $data[0]['userlimit']);
    }

    /**
     * Test deleting data for all users within an approved contextlist.
     * @throws dml_exception
     */
    public function test_delete_data_for_all_users_in_context(): void {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator();

        // Create two users.
        $user1 = $generator->create_user();
        $usercontext1 = context_user::instance($user1->id);
        $user2 = $generator->create_user();

        // Apply user-specific block config.
        $DB->insert_record('block_course_recent', (object) ['userid' => $user1->id, 'userlimit' => 10]);
        $DB->insert_record('block_course_recent', (object) ['userid' => $user2->id, 'userlimit' => 10]);

        $result = $DB->count_records('block_course_recent', ['userid' => $user1->id]);
        $this->assertEquals(1, $result);
        $result = $DB->count_records('block_course_recent', ['userid' => $user2->id]);
        $this->assertEquals(1, $result);

        // Attempt system context deletion (should have no effect).
        $systemcontext = context_system::instance();
        provider::delete_data_for_all_users_in_context($systemcontext);

        // Confirm that user data is still there.
        $result = $DB->count_records('block_course_recent', ['userid' => $user1->id]);
        $this->assertEquals(1, $result);
        $result = $DB->count_records('block_course_recent', ['userid' => $user2->id]);
        $this->assertEquals(1, $result);

        // Delete all data in user1 user context.
        provider::delete_data_for_all_users_in_context($usercontext1);

        // Confirm that only user1's block data got deleted.
        $result = $DB->count_records('block_course_recent', ['userid' => $user1->id]);
        $this->assertEquals(0, $result);
        $result = $DB->count_records('block_course_recent', ['userid' => $user2->id]);
        $this->assertEquals(1, $result);
    }

    /**
     * Test deleting data within an approved contextlist for a user.
     * @throws dml_exception
     */
    public function test_delete_data_for_user(): void {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $component = 'block_course_recent';

        // Create two users.
        $user1 = $generator->create_user();
        $usercontext1 = context_user::instance($user1->id);
        $user2 = $generator->create_user();

        // Apply user-specific block config.
        $DB->insert_record('block_course_recent', (object) ['userid' => $user1->id, 'userlimit' => 10]);
        $DB->insert_record('block_course_recent', (object) ['userid' => $user2->id, 'userlimit' => 10]);

        $result = $DB->count_records('block_course_recent', ['userid' => $user1->id]);
        $this->assertEquals(1, $result);
        $result = $DB->count_records('block_course_recent', ['userid' => $user2->id]);
        $this->assertEquals(1, $result);

        // Attempt system context deletion (should have no effect).
        $systemcontext = context_system::instance();
        $approvedlist = new approved_contextlist($user1, $component, [$systemcontext->id]);
        provider::delete_data_for_user($approvedlist);

        // Confirm that user data is still there.
        $result = $DB->count_records('block_course_recent', ['userid' => $user1->id]);
        $this->assertEquals(1, $result);
        $result = $DB->count_records('block_course_recent', ['userid' => $user2->id]);
        $this->assertEquals(1, $result);

        // Attempt to delete user1 data in user2 user context (should have no effect).
        $approvedlist = new approved_contextlist($user2, $component, [$usercontext1->id]);
        provider::delete_data_for_user($approvedlist);

        // Confirm that user data is still there.
        $result = $DB->count_records('block_course_recent', ['userid' => $user1->id]);
        $this->assertEquals(1, $result);
        $result = $DB->count_records('block_course_recent', ['userid' => $user2->id]);
        $this->assertEquals(1, $result);

        // Delete user1 data in their own user context.
        $approvedlist = new approved_contextlist($user1, $component, [$usercontext1->id]);
        provider::delete_data_for_user($approvedlist);

        // Confirm that only user2 user data is still there.
        $result = $DB->count_records('block_course_recent', ['userid' => $user1->id]);
        $this->assertEquals(0, $result);
        $result = $DB->count_records('block_course_recent', ['userid' => $user2->id]);
        $this->assertEquals(1, $result);
    }

    /**
     * Test deleting data within a context for an approved userlist.
     * @throws dml_exception
     */
    public function test_delete_data_for_users(): void {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $component = 'block_course_recent';

        // Create three users.
        $user1 = $generator->create_user();
        $usercontext1 = context_user::instance($user1->id);
        $user2 = $generator->create_user();
        $user3 = $generator->create_user();

        // Apply user-specific block config.
        $DB->insert_record('block_course_recent', (object) ['userid' => $user1->id, 'userlimit' => 10]);
        $DB->insert_record('block_course_recent', (object) ['userid' => $user2->id, 'userlimit' => 10]);
        $DB->insert_record('block_course_recent', (object) ['userid' => $user3->id, 'userlimit' => 10]);

        $result = $DB->count_records('block_course_recent', ['userid' => $user1->id]);
        $this->assertEquals(1, $result);
        $result = $DB->count_records('block_course_recent', ['userid' => $user2->id]);
        $this->assertEquals(1, $result);
        $result = $DB->count_records('block_course_recent', ['userid' => $user3->id]);
        $this->assertEquals(1, $result);

        // Attempt system context deletion (should have no effect).
        $systemcontext = context_system::instance();
        $approvedlist = new approved_userlist($systemcontext, $component, [$user1->id, $user2->id]);
        provider::delete_data_for_users($approvedlist);

        // Confirm that user data is still there.
        $result = $DB->count_records('block_course_recent', ['userid' => $user1->id]);
        $this->assertEquals(1, $result);
        $result = $DB->count_records('block_course_recent', ['userid' => $user2->id]);
        $this->assertEquals(1, $result);
        $result = $DB->count_records('block_course_recent', ['userid' => $user3->id]);
        $this->assertEquals(1, $result);

        // Attempt to delete data in another user's context (should have no effect).
        $approvedlist = new approved_userlist($usercontext1, $component, [$user2->id]);
        provider::delete_data_for_users($approvedlist);

        // Confirm that user data is still there.
        $result = $DB->count_records('block_course_recent', ['userid' => $user1->id]);
        $this->assertEquals(1, $result);
        $result = $DB->count_records('block_course_recent', ['userid' => $user2->id]);
        $this->assertEquals(1, $result);
        $result = $DB->count_records('block_course_recent', ['userid' => $user3->id]);
        $this->assertEquals(1, $result);

        // Delete data for user1 and user2 in the user context for user1.
        $approvedlist = new approved_userlist($usercontext1, $component, [$user1->id, $user2->id]);
        provider::delete_data_for_users($approvedlist);

        // Confirm only user1's data is deleted.
        $result = $DB->count_records('block_course_recent', ['userid' => $user1->id]);
        $this->assertEquals(0, $result);
        $result = $DB->count_records('block_course_recent', ['userid' => $user2->id]);
        $this->assertEquals(1, $result);
        $result = $DB->count_records('block_course_recent', ['userid' => $user3->id]);
        $this->assertEquals(1, $result);
    }
}
