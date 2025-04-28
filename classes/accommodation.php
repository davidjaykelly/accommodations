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
 * Accommodation profile class.
 *
 * @package    local_accommodations
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_accommodations;

defined('MOODLE_INTERNAL') || die();

/**
 * Class representing a user accommodation profile.
 */
class accommodation {
    /** @var object The accommodation record */
    protected $record;
    
    /** @var object The related accommodation type */
    protected $type;
    
    /**
     * Constructor.
     *
     * @param mixed $accommodationorid Either an ID or database record
     */
    public function __construct($accommodationorid = null) {
        global $DB;
        
        if (is_object($accommodationorid)) {
            $this->record = $accommodationorid;
        } else if (is_numeric($accommodationorid)) {
            $this->record = $DB->get_record('local_accommodations_profiles', ['id' => $accommodationorid], '*', MUST_EXIST);
        } else {
            $this->record = new \stdClass();
            $this->record->id = null;
            $this->record->userid = 0;
            $this->record->typeid = 0;
            $this->record->timeextension = 0;
            $this->record->courseid = null;
            $this->record->categoryid = null;
            $this->record->startdate = null;
            $this->record->enddate = null;
            $this->record->notes = '';
            $this->record->timecreated = time();
            $this->record->timemodified = time();
            $this->record->usermodified = 0;
        }
    }
    
    /**
     * Get the accommodation record.
     *
     * @return object
     */
    public function get_record() {
        return $this->record;
    }
    
    /**
     * Get the accommodation type.
     *
     * @return accommodation_type
     */
    public function get_type() {
        if (!isset($this->type)) {
            $this->type = new accommodation_type($this->record->typeid);
        }
        return $this->type;
    }
    
    /**
     * Get the user who has this accommodation.
     *
     * @return object User record
     */
    public function get_user() {
        global $DB;
        return $DB->get_record('user', ['id' => $this->record->userid], '*', MUST_EXIST);
    }
    
    /**
     * Save this accommodation to the database.
     *
     * @param object $data Form data
     * @return bool Success
     */
    public function save($data) {
        global $DB, $USER;
        
        $this->record->userid = !empty($data->userid) ? $data->userid : $this->record->userid;
        $this->record->typeid = !empty($data->typeid) ? $data->typeid : $this->record->typeid;
        $this->record->timeextension = !empty($data->timeextension) ? $data->timeextension : $this->record->timeextension;
        $this->record->courseid = isset($data->courseid) ? $data->courseid : $this->record->courseid;
        $this->record->categoryid = isset($data->categoryid) ? $data->categoryid : $this->record->categoryid;
        $this->record->startdate = isset($data->startdate) ? $data->startdate : $this->record->startdate;
        $this->record->enddate = isset($data->enddate) ? $data->enddate : $this->record->enddate;
        $this->record->notes = isset($data->notes) ? $data->notes : $this->record->notes;
        $this->record->timemodified = time();
        $this->record->usermodified = $USER->id;
        
        if (empty($this->record->id)) {
            $this->record->timecreated = time();
            $this->record->id = $DB->insert_record('local_accommodations_profiles', $this->record);
            return !empty($this->record->id);
        } else {
            return $DB->update_record('local_accommodations_profiles', $this->record);
        }
    }
    
    /**
     * Delete this accommodation from the database.
     *
     * @return bool Success
     */
    public function delete() {
        global $DB;
        
        if (empty($this->record->id)) {
            return false;
        }
        
        // Delete any overrides
        $DB->delete_records('local_accommodations_overrides', ['profileid' => $this->record->id]);
        
        // Delete the accommodation
        return $DB->delete_records('local_accommodations_profiles', ['id' => $this->record->id]);
    }
    
    /**
     * Check if this accommodation is currently active (within date range if specified).
     *
     * @return bool
     */
    public function is_active() {
        $now = time();
        
        if (!empty($this->record->startdate) && $this->record->startdate > $now) {
            return false;
        }
        
        if (!empty($this->record->enddate) && $this->record->enddate < $now) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get the effective time extension for this accommodation.
     *
     * @param int|null $cmid Optional course module ID for specific overrides
     * @return int Time extension percentage
     */
    public function get_effective_time_extension($cmid = null) {
        global $DB;
        
        // Start with the profile's time extension
        $extension = $this->record->timeextension;
        
        // If timeextension is 0, use the default from the type
        if ($extension == 0) {
            $extension = $this->get_type()->get_record()->timeextension;
        }
        
        // Check for overrides if a course module is specified
        if ($cmid) {
            $override = $DB->get_record('local_accommodations_overrides', 
                ['profileid' => $this->record->id, 'cmid' => $cmid]);
            
            if ($override && $override->timeextension > 0) {
                $extension = $override->timeextension;
            }
        }
        
        return $extension;
    }
    
    /**
     * Apply this accommodation to a specific quiz.
     *
     * @param int $quizid Quiz ID
     * @param bool $override Whether to override existing user overrides
     * @return bool Success
     */
    public function apply_to_quiz($quizid) {
        global $DB;
        
        if (!$this->is_active()) {
            return false;
        }
        
        // Get quiz details
        $quiz = $DB->get_record('quiz', ['id' => $quizid], '*', MUST_EXIST);
        
        // Get course module ID
        $cm = get_coursemodule_from_instance('quiz', $quizid, $quiz->course, false, MUST_EXIST);
        
        // Get effective time extension
        $timeextension = $this->get_effective_time_extension($cm->id);
        
        if ($timeextension <= 0) {
            return false;
        }
        
        // Get existing override
        $existingoverride = $DB->get_record('quiz_overrides', 
            ['quiz' => $quizid, 'userid' => $this->record->userid, 'groupid' => null]);
        
        // Calculate extended time
        $extratime = $quiz->timelimit * ($timeextension / 100);
        $newtimelimit = round($quiz->timelimit + $extratime);
        
        // Create or update override
        $overridedata = [
            'quiz' => $quizid,
            'userid' => $this->record->userid,
            'timelimit' => $newtimelimit
        ];
        
        if ($existingoverride) {
            $overridedata['id'] = $existingoverride->id;
            $success = $DB->update_record('quiz_overrides', $overridedata);
        } else {
            $success = $DB->insert_record('quiz_overrides', $overridedata) > 0;
        }
        
        // Log the accommodation application
        if ($success) {
            $history = new \stdClass();
            $history->userid = $this->record->userid;
            $history->courseid = $quiz->course;
            $history->cmid = $cm->id;
            $history->modulename = 'quiz';
            $history->moduleinstance = $quizid;
            $history->timeextension = $timeextension;
            $history->originaltime = $quiz->timelimit;
            $history->extendedtime = $newtimelimit;
            $history->applied = 1;
            $history->timecreated = time();
            $history->usermodified = $this->record->usermodified;
            
            $DB->insert_record('local_accommodations_history', $history);
        }
        
        return $success;
    }
    
    /**
     * Apply this accommodation to a specific assignment.
     *
     * @param int $assignid Assignment ID
     * @param bool $override Whether to override existing user overrides
     * @return bool Success
     */
    public function apply_to_assignment($assignid) {
        global $DB;
        
        if (!$this->is_active()) {
            return false;
        }
        
        // Get assignment details
        $assign = $DB->get_record('assign', ['id' => $assignid], '*', MUST_EXIST);
        
        // Get course module ID
        $cm = get_coursemodule_from_instance('assign', $assignid, $assign->course, false, MUST_EXIST);
        
        // Get effective time extension
        $timeextension = $this->get_effective_time_extension($cm->id);
        
        if ($timeextension <= 0 || $assign->duedate <= 0) {
            return false;
        }
        
        // Get existing override
        $existingoverride = $DB->get_record('assign_overrides', 
            ['assignid' => $assignid, 'userid' => $this->record->userid, 'groupid' => null]);
        
        // Calculate extended time (as seconds)
        $extension = round($timeextension / 100 * (($assign->duedate - $assign->allowsubmissionsfromdate)));
        $newduedate = $assign->duedate + $extension;
        
        // Create or update override
        $overridedata = [
            'assignid' => $assignid,
            'userid' => $this->record->userid,
            'duedate' => $newduedate
        ];
        
        if ($existingoverride) {
            $overridedata['id'] = $existingoverride->id;
            $success = $DB->update_record('assign_overrides', $overridedata);
        } else {
            $success = $DB->insert_record('assign_overrides', $overridedata) > 0;
        }
        
        // Log the accommodation application
        if ($success) {
            $history = new \stdClass();
            $history->userid = $this->record->userid;
            $history->courseid = $assign->course;
            $history->cmid = $cm->id;
            $history->modulename = 'assign';
            $history->moduleinstance = $assignid;
            $history->timeextension = $timeextension;
            $history->originaltime = $assign->duedate;
            $history->extendedtime = $newduedate;
            $history->applied = 1;
            $history->timecreated = time();
            $history->usermodified = $this->record->usermodified;
            
            $DB->insert_record('local_accommodations_history', $history);
        }
        
        return $success;
    }
}