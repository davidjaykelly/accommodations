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
 * External API for accommodations.
 *
 * @package    local_accommodations
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_accommodations\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/local/accommodations/lib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_multiple_structure;
use external_single_structure;
use context_course;
use context_module;
use context_system;
use moodle_exception;

/**
 * External API class.
 */
class accommodation_api extends external_api {
    
    /**
     * Toggle activity accommodation status parameters.
     *
     * @return external_function_parameters
     */
    public static function toggle_activity_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'status' => new external_value(PARAM_INT, 'Status (0 = enabled, 1 = disabled)')
        ]);
    }
    
    /**
     * Toggle activity accommodation status.
     *
     * @param int $cmid Course module ID
     * @param int $courseid Course ID
     * @param int $status Status (0 = enabled, 1 = disabled)
     * @return bool Success flag
     */
    public static function toggle_activity($cmid, $courseid, $status) {
        global $DB;
        
        // Parameter validation
        $params = self::validate_parameters(self::toggle_activity_parameters(), [
            'cmid' => $cmid,
            'courseid' => $courseid,
            'status' => $status
        ]);
        
        // Context validation
        $coursecontext = context_course::instance($params['courseid']);
        self::validate_context($coursecontext);
        
        // Capability check
        require_capability('local/accommodations:toggleactivity', $coursecontext);
        
        // Check if course module exists
        $cm = get_coursemodule_from_id('', $params['cmid'], $params['courseid'], false, MUST_EXIST);
        $cmcontext = context_module::instance($cm->id);
        
        // Toggle the activity status
        $result = local_accommodations_toggle_activity($params['cmid'], $params['status']);
        
        return $result;
    }
    
    /**
     * Toggle activity return value.
     *
     * @return external_value
     */
    public static function toggle_activity_returns() {
        return new external_value(PARAM_BOOL, 'Success flag');
    }
    
    /**
     * Bulk action parameters.
     *
     * @return external_function_parameters
     */
    public static function bulk_action_parameters() {
        return new external_function_parameters([
            'action' => new external_value(PARAM_ALPHA, 'Action (delete, apply)'),
            'ids' => new external_multiple_structure(
                new external_value(PARAM_INT, 'Accommodation ID')
            )
        ]);
    }
    
    /**
     * Perform bulk action on accommodations.
     *
     * @param string $action Action to perform
     * @param array $ids Accommodation IDs
     * @return array Result data
     */
    public static function bulk_action($action, $ids) {
        global $DB;
        
        // Parameter validation
        $params = self::validate_parameters(self::bulk_action_parameters(), [
            'action' => $action,
            'ids' => $ids
        ]);
        
        // Context validation
        $systemcontext = context_system::instance();
        self::validate_context($systemcontext);
        
        // Capability check
        require_capability('local/accommodations:managesystem', $systemcontext);
        
        $result = [
            'success' => false,
            'message' => '',
            'count' => 0
        ];
        
        // Perform the action
        switch ($params['action']) {
            case 'delete':
                $count = 0;
                foreach ($params['ids'] as $id) {
                    $accommodation = new \local_accommodations\accommodation($id);
                    if ($accommodation->delete()) {
                        $count++;
                    }
                }
                $result['success'] = true;
                $result['message'] = get_string('bulkdeletesuccessx', 'local_accommodations', $count);
                $result['count'] = $count;
                break;
                
            case 'apply':
                $count = 0;
                $quizcount = 0;
                $assigncount = 0;
                
                foreach ($params['ids'] as $id) {
                    $accommodation = new \local_accommodations\accommodation($id);
                    $record = $accommodation->get_record();
                    
                    if ($record->courseid) {
                        // Apply to course
                        $stats = local_accommodations_apply_to_course($record->courseid, true);
                        $quizcount += $stats['quizzes'];
                        $assigncount += $stats['assignments'];
                        $count++;
                    } else if ($record->categoryid) {
                        // Apply to category
                        $stats = local_accommodations_apply_to_category($record->categoryid, true);
                        $quizcount += $stats['quizzes'];
                        $assigncount += $stats['assignments'];
                        $count++;
                    }
                }
                
                $result['success'] = true;
                $result['message'] = get_string('bulkapplysuccessx', 'local_accommodations', [
                    'count' => $count,
                    'quizzes' => $quizcount,
                    'assignments' => $assigncount
                ]);
                $result['count'] = $count;
                break;
                
            default:
                throw new moodle_exception('invalidaction', 'local_accommodations');
        }
        
        return $result;
    }
    
    /**
     * Bulk action return value.
     *
     * @return external_single_structure
     */
    public static function bulk_action_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success flag'),
            'message' => new external_value(PARAM_TEXT, 'Result message'),
            'count' => new external_value(PARAM_INT, 'Number of items processed')
        ]);
    }
}