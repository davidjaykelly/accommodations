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
 * Task to apply accommodations to activities.
 *
 * @package    local_accommodations
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_accommodations\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled task to apply accommodations to activities.
 */
class apply_accommodations extends \core\task\scheduled_task {
    /**
     * Get the name of the task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('taskapplyaccommodations', 'local_accommodations');
    }
    
    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;
        
        // Check if auto-apply is enabled
        if (!get_config('local_accommodations', 'autoapply')) {
            mtrace('Auto-apply accommodations is disabled. Skipping task.');
            return;
        }
        
        mtrace('Applying accommodations to activities...');
        
        // Get all courses with accommodations
        $sql = "SELECT DISTINCT c.id, c.shortname, c.fullname
                FROM {course} c
                JOIN {local_accommodations_profiles} a ON a.courseid = c.id OR a.courseid IS NULL
                WHERE c.id != :siteid
                ORDER BY c.id";
        
        $courses = $DB->get_records_sql($sql, ['siteid' => SITEID]);
        
        $totalquizzes = 0;
        $totalassignments = 0;
        
        foreach ($courses as $course) {
            mtrace("Processing course: {$course->shortname} (ID: {$course->id})");
            
            // Apply accommodations to this course
            $stats = local_accommodations_apply_to_course($course->id, false);
            
            $totalquizzes += $stats['quizzes'];
            $totalassignments += $stats['assignments'];
            
            mtrace("  Applied to {$stats['quizzes']} quizzes and {$stats['assignments']} assignments");
        }
        
        mtrace("Task completed. Applied accommodations to $totalquizzes quizzes and $totalassignments assignments.");
    }
}