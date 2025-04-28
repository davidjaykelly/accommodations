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
 * Upgrade code for the accommodation management plugin.
 *
 * @package    local_accommodations
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade function for the local_accommodations plugin.
 *
 * @param int $oldversion The old version of the plugin
 * @return bool
 */
function xmldb_local_accommodations_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2025040808) {
        // Define table local_accommodations_types
        $table = new xmldb_table('local_accommodations_types');

        // Add fields
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timeextension', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Add keys
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        // Create the table if it doesn't exist
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table local_accommodations_profiles
        $table = new xmldb_table('local_accommodations_profiles');

        // Add fields
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('typeid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timeextension', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('categoryid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('startdate', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('enddate', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('notes', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Add keys
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_key('typeid', XMLDB_KEY_FOREIGN, ['typeid'], 'local_accommodations_types', ['id']);
        $table->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
        $table->add_key('categoryid', XMLDB_KEY_FOREIGN, ['categoryid'], 'course_categories', ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        // Add indexes
        $table->add_index('user_course', XMLDB_INDEX_NOTUNIQUE, ['userid', 'courseid']);
        $table->add_index('user_category', XMLDB_INDEX_NOTUNIQUE, ['userid', 'categoryid']);

        // Create the table if it doesn't exist
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table local_accommodations_overrides
        $table = new xmldb_table('local_accommodations_overrides');

        // Add fields
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('profileid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('cmid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timeextension', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('notes', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Add keys
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('profileid', XMLDB_KEY_FOREIGN, ['profileid'], 'local_accommodations_profiles', ['id']);
        $table->add_key('cmid', XMLDB_KEY_FOREIGN, ['cmid'], 'course_modules', ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        // Create the table if it doesn't exist
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table local_accommodations_history
        $table = new xmldb_table('local_accommodations_history');

        // Add fields
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('cmid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('modulename', XMLDB_TYPE_CHAR, '20', null, null, null, null);
        $table->add_field('moduleinstance', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timeextension', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('originaltime', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('extendedtime', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('applied', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Add keys
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
        $table->add_key('cmid', XMLDB_KEY_FOREIGN, ['cmid'], 'course_modules', ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        // Add indexes
        $table->add_index('user_module', XMLDB_INDEX_NOTUNIQUE, ['userid', 'modulename', 'moduleinstance']);

        // Create the table if it doesn't exist
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Add default accommodation types
        if ($DB->count_records('local_accommodations_types') == 0) {
            $now = time();
            $admin = get_admin();
            
            // Add a default type for learning disabilities
            $type = new stdClass();
            $type->name = 'Learning Disability';
            $type->description = 'Standard accommodation for students with learning disabilities.';
            $type->timeextension = 25; // 25% extra time
            $type->timecreated = $now;
            $type->timemodified = $now;
            $type->usermodified = $admin->id;
            $DB->insert_record('local_accommodations_types', $type);
            
            // Add a second type for language accommodations
            $type = new stdClass();
            $type->name = 'Language Accommodation';
            $type->description = 'For students whose first language is not the language of instruction.';
            $type->timeextension = 15; // 15% extra time
            $type->timecreated = $now;
            $type->timemodified = $now;
            $type->usermodified = $admin->id;
            $DB->insert_record('local_accommodations_types', $type);
        }

        // Set the current version.
        upgrade_plugin_savepoint(true, 2025040807, 'local', 'accommodations');
    }

    if ($oldversion < 2025040801) {
        // Define table local_accommodations_activity_status
        $table = new xmldb_table('local_accommodations_activity_status');

        // Add fields
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('cmid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('disabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Add keys
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('cmid', XMLDB_KEY_FOREIGN_UNIQUE, ['cmid'], 'course_modules', ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        // Create the table if it doesn't exist
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Set the current version.
        upgrade_plugin_savepoint(true, 2025040806, 'local', 'accommodations');
    }

    return true;
}