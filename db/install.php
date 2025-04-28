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
 * Custom installation steps for the accommodation management plugin.
 *
 * @package    local_accommodations
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Function executed when the plugin is installed.
 *
 * @return bool
 */
function xmldb_local_accommodations_install() {
    global $DB;

    // Add default accommodation types.
    if ($DB->count_records('local_accommodations_types') == 0) {
        $now = time();
        $admin = get_admin();

        // Add a default type for learning disabilities.
        $type = new stdClass();
        $type->name = 'Learning Disability';
        $type->description = 'Standard accommodation for students with learning disabilities.';
        $type->timeextension = 25; // 25% extra time
        $type->timecreated = $now;
        $type->timemodified = $now;
        $type->usermodified = $admin->id;
        $DB->insert_record('local_accommodations_types', $type);

        // Add a second type for language accommodations.
        $type = new stdClass();
        $type->name = 'Language Accommodation';
        $type->description = 'For students whose first language is not the language of instruction.';
        $type->timeextension = 15; // 15% extra time
        $type->timecreated = $now;
        $type->timemodified = $now;
        $type->usermodified = $admin->id;
        $DB->insert_record('local_accommodations_types', $type);
    }

    return true;
}
