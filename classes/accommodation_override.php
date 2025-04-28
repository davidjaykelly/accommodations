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
 * Accommodation override class.
 *
 * @package    local_accommodations
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_accommodations;

defined('MOODLE_INTERNAL') || die();

/**
 * Class representing an accommodation override.
 */
class accommodation_override {
    /** @var object The accommodation override record */
    protected $record;
    
    /**
     * Constructor.
     *
     * @param mixed $overrideorid Either an ID or database record
     */
    public function __construct($overrideorid = null) {
        global $DB;
        
        if (is_object($overrideorid)) {
            $this->record = $overrideorid;
        } else if (is_numeric($overrideorid)) {
            $this->record = $DB->get_record('local_accommodations_overrides', ['id' => $overrideorid], '*', MUST_EXIST);
        } else {
            $this->record = new \stdClass();
            $this->record->id = null;
            $this->record->profileid = 0;
            $this->record->cmid = null;
            $this->record->timeextension = 0;
            $this->record->notes = '';
            $this->record->timecreated = time();
            $this->record->timemodified = time();
            $this->record->usermodified = 0;
        }
    }
    
    /**
     * Get the accommodation override record.
     *
     * @return object
     */
    public function get_record() {
        return $this->record;
    }
    
    /**
     * Save this accommodation override to the database.
     *
     * @param object $data Form data
     * @return bool Success
     */
    public function save($data) {
        global $DB, $USER;
        
        $this->record->profileid = !empty($data->profileid) ? $data->profileid : $this->record->profileid;
        $this->record->cmid = isset($data->cmid) ? $data->cmid : $this->record->cmid;
        $this->record->timeextension = isset($data->timeextension) ? $data->timeextension : $this->record->timeextension;
        $this->record->notes = isset($data->notes) ? $data->notes : $this->record->notes;
        $this->record->timemodified = time();
        $this->record->usermodified = $USER->id;
        
        if (empty($this->record->id)) {
            $this->record->timecreated = time();
            $this->record->id = $DB->insert_record('local_accommodations_overrides', $this->record);
            return !empty($this->record->id);
        } else {
            return $DB->update_record('local_accommodations_overrides', $this->record);
        }
    }
    
    /**
     * Delete this accommodation override from the database.
     *
     * @return bool Success
     */
    public function delete() {
        global $DB;
        
        if (empty($this->record->id)) {
            return false;
        }
        
        return $DB->delete_records('local_accommodations_overrides', ['id' => $this->record->id]);
    }
    
    /**
     * Get accommodation profile for this override.
     *
     * @return accommodation
     */
    public function get_profile() {
        return new accommodation($this->record->profileid);
    }
    
    /**
     * Get all overrides for a specific profile.
     *
     * @param int $profileid The profile ID
     * @return array Array of accommodation_override objects
     */
    public static function get_for_profile($profileid) {
        global $DB;
        
        $overrides = [];
        $records = $DB->get_records('local_accommodations_overrides', ['profileid' => $profileid]);
        
        foreach ($records as $record) {
            $overrides[$record->id] = new accommodation_override($record);
        }
        
        return $overrides;
    }
    
    /**
     * Get overrides for a specific course module.
     *
     * @param int $cmid The course module ID
     * @return array Array of accommodation_override objects
     */
    public static function get_for_cm($cmid) {
        global $DB;
        
        $overrides = [];
        $records = $DB->get_records('local_accommodations_overrides', ['cmid' => $cmid]);
        
        foreach ($records as $record) {
            $overrides[$record->id] = new accommodation_override($record);
        }
        
        return $overrides;
    }
}