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
 * Privacy provider implementation for local_accommodations.
 *
 * @package    local_accommodations
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_accommodations\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\deletion_criteria;
use core_privacy\local\request\helper;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for local_accommodations.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {
    
    /**
     * Returns metadata about this plugin's privacy features.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'local_accommodations_profiles',
            [
                'userid' => 'privacy:metadata:profiles:userid',
                'typeid' => 'privacy:metadata:profiles:typeid',
                'timeextension' => 'privacy:metadata:profiles:timeextension',
                'courseid' => 'privacy:metadata:profiles:courseid',
                'categoryid' => 'privacy:metadata:profiles:categoryid',
                'startdate' => 'privacy:metadata:profiles:startdate',
                'enddate' => 'privacy:metadata:profiles:enddate',
                'notes' => 'privacy:metadata:profiles:notes',
                'timecreated' => 'privacy:metadata:profiles:timecreated',
                'timemodified' => 'privacy:metadata:profiles:timemodified',
                'usermodified' => 'privacy:metadata:profiles:usermodified'
            ],
            'privacy:metadata:profiles'
        );
        
        $collection->add_database_table(
            'local_accommodations_history',
            [
                'userid' => 'privacy:metadata:history:userid',
                'courseid' => 'privacy:metadata:history:courseid',
                'cmid' => 'privacy:metadata:history:cmid',
                'modulename' => 'privacy:metadata:history:modulename',
                'moduleinstance' => 'privacy:metadata:history:moduleinstance',
                'timeextension' => 'privacy:metadata:history:timeextension',
                'originaltime' => 'privacy:metadata:history:originaltime',
                'extendedtime' => 'privacy:metadata:history:extendedtime',
                'applied' => 'privacy:metadata:history:applied',
                'timecreated' => 'privacy:metadata:history:timecreated',
                'usermodified' => 'privacy:metadata:history:usermodified'
            ],
            'privacy:metadata:history'
        );
        
        return $collection;
    }
    
    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist $contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        
        // User context - accommodation profiles
        $contextlist->add_user_context($userid);
        
        // Course contexts that this user has accommodations for
        $sql = "SELECT ctx.id
                FROM {context} ctx
                JOIN {course} c ON ctx.instanceid = c.id AND ctx.contextlevel = :contextlevel
                JOIN {local_accommodations_profiles} a ON a.courseid = c.id
                WHERE a.userid = :userid";
        
        $params = [
            'contextlevel' => CONTEXT_COURSE,
            'userid' => $userid
        ];
        
        $contextlist->add_from_sql($sql, $params);
        
        // Course contexts where accommodations have been applied
        $sql = "SELECT ctx.id
                FROM {context} ctx
                JOIN {course} c ON ctx.instanceid = c.id AND ctx.contextlevel = :contextlevel
                JOIN {local_accommodations_history} h ON h.courseid = c.id
                WHERE h.userid = :userid";
        
        $contextlist->add_from_sql($sql, $params);
        
        return $contextlist;
    }
    
    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        
        if ($context->contextlevel == CONTEXT_USER) {
            // Add user accommodations
            $sql = "SELECT userid
                    FROM {local_accommodations_profiles}
                    WHERE userid = :userid";
            $params = ['userid' => $context->instanceid];
            $userlist->add_from_sql('userid', $sql, $params);
            
            // Add accommodation history
            $sql = "SELECT userid
                    FROM {local_accommodations_history}
                    WHERE userid = :userid";
            $userlist->add_from_sql('userid', $sql, $params);
        } else if ($context->contextlevel == CONTEXT_COURSE) {
            // Add users with accommodations in this course
            $sql = "SELECT userid
                    FROM {local_accommodations_profiles}
                    WHERE courseid = :courseid";
            $params = ['courseid' => $context->instanceid];
            $userlist->add_from_sql('userid', $sql, $params);
            
            // Add users with applied accommodations in this course
            $sql = "SELECT userid
                    FROM {local_accommodations_history}
                    WHERE courseid = :courseid";
            $userlist->add_from_sql('userid', $sql, $params);
        }
    }
    
    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        
        $user = $contextlist->get_user();
        $userid = $user->id;
        $contexts = $contextlist->get_contexts();
        
        // Export accommodation profiles
        $profiles = $DB->get_records('local_accommodations_profiles', ['userid' => $userid]);
        
        if (!empty($profiles)) {
            // Get accommodation types
            $typeids = array_column($profiles, 'typeid');
            $types = $DB->get_records_list('local_accommodations_types', 'id', $typeids);
            
            foreach ($profiles as &$profile) {
                // Add type name
                if (isset($types[$profile->typeid])) {
                    $profile->typename = $types[$profile->typeid]->name;
                }
                
                // Format dates
                if (!empty($profile->startdate)) {
                    $profile->startdate = userdate($profile->startdate);
                }
                if (!empty($profile->enddate)) {
                    $profile->enddate = userdate($profile->enddate);
                }
                if (!empty($profile->timecreated)) {
                    $profile->timecreated = userdate($profile->timecreated);
                }
                if (!empty($profile->timemodified)) {
                    $profile->timemodified = userdate($profile->timemodified);
                }
            }
            
            $usercontext = \context_user::instance($userid);
            writer::with_context($usercontext)->export_data(
                [get_string('pluginname', 'local_accommodations'), get_string('accommodations', 'local_accommodations')],
                (object)['profiles' => $profiles]
            );
        }
        
        // Export accommodation history
        $history = $DB->get_records('local_accommodations_history', ['userid' => $userid]);
        
        if (!empty($history)) {
            foreach ($history as &$entry) {
                // Format dates
                if (!empty($entry->timecreated)) {
                    $entry->timecreated = userdate($entry->timecreated);
                }
                
                // Get module name
                if (!empty($entry->cmid)) {
                    $entry->modulename = get_coursemodule_from_id($entry->modulename, $entry->cmid)->name;
                }
            }
            
            $usercontext = \context_user::instance($userid);
            writer::with_context($usercontext)->export_data(
                [get_string('pluginname', 'local_accommodations'), get_string('accommodationhistory', 'local_accommodations')],
                (object)['history' => $history]
            );
        }
    }
    
    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        
        $userid = $contextlist->get_user()->id;
        
        // Delete accommodation profiles
        $DB->delete_records('local_accommodations_profiles', ['userid' => $userid]);
        
        // Delete accommodation history
        $DB->delete_records('local_accommodations_history', ['userid' => $userid]);
    }
    
    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        
        $context = $userlist->get_context();
        $userids = $userlist->get_userids();
        
        if (empty($userids)) {
            return;
        }
        
        // Delete accommodation profiles
        list($usersql, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $DB->delete_records_select('local_accommodations_profiles', "userid $usersql", $userparams);
        
        // Delete accommodation history
        $DB->delete_records_select('local_accommodations_history', "userid $usersql", $userparams);
    }
    
    /**
     * Delete all use data which matches the specified context.
     *
     * @param \context $context The context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        
        if ($context->contextlevel == CONTEXT_USER) {
            // Delete accommodation profiles
            $DB->delete_records('local_accommodations_profiles', ['userid' => $context->instanceid]);
            
            // Delete accommodation history
            $DB->delete_records('local_accommodations_history', ['userid' => $context->instanceid]);
        } else if ($context->contextlevel == CONTEXT_COURSE) {
            // Delete accommodation profiles for this course
            $DB->delete_records('local_accommodations_profiles', ['courseid' => $context->instanceid]);
            
            // Delete accommodation history for this course
            $DB->delete_records('local_accommodations_history', ['courseid' => $context->instanceid]);
        }
    }
}