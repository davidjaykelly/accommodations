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
 * Accommodation type class.
 *
 * @package    local_accommodations
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_accommodations;

defined('MOODLE_INTERNAL') || die();

/**
 * Class representing an accommodation type.
 */
class accommodation_type {
    /** @var object The accommodation type record */
    protected $record;
    
    /**
     * Constructor.
     *
     * @param mixed $typeorid Either an ID or database record
     */
    public function __construct($typeorid = null) {
        global $DB;
        
        if (is_object($typeorid)) {
            $this->record = $typeorid;
        } else if (is_numeric($typeorid)) {
            $this->record = $DB->get_record('local_accommodations_types', ['id' => $typeorid], '*', MUST_EXIST);
        } else {
            $this->record = new \stdClass();
            $this->record->id = null;
            $this->record->name = '';
            $this->record->description = '';
            $this->record->timeextension = 0;
            $this->record->timecreated = time();
            $this->record->timemodified = time();
            $this->record->usermodified = 0;
        }
    }
    
    /**
     * Get the accommodation type record.
     *
     * @return object
     */
    public function get_record() {
        return $this->record;
    }
    
    /**
     * Save this accommodation type to the database.
     *
     * @param object $data Form data
     * @return bool Success
     */
    public function save($data) {
        global $DB, $USER;
        
        $this->record->name = !empty($data->name) ? $data->name : $this->record->name;
        $this->record->description = isset($data->description) ? $data->description : $this->record->description;
        $this->record->timeextension = isset($data->timeextension) ? $data->timeextension : $this->record->timeextension;
        $this->record->timemodified = time();
        $this->record->usermodified = $USER->id;
        
        if (empty($this->record->id)) {
            $this->record->timecreated = time();
            $this->record->id = $DB->insert_record('local_accommodations_types', $this->record);
            return !empty($this->record->id);
        } else {
            return $DB->update_record('local_accommodations_types', $this->record);
        }
    }
    
    /**
     * Delete this accommodation type from the database.
     *
     * @return bool Success
     */
    public function delete() {
        global $DB;
        
        if (empty($this->record->id)) {
            return false;
        }
        
        // Check if any profiles use this type
        $usecount = $DB->count_records('local_accommodations_profiles', ['typeid' => $this->record->id]);
        if ($usecount > 0) {
            return false;
        }
        
        // Delete the type
        return $DB->delete_records('local_accommodations_types', ['id' => $this->record->id]);
    }
    
    /**
     * Get all accommodation types.
     *
     * @return array Array of accommodation_type objects
     */
    public static function get_all() {
        global $DB;
        
        $types = [];
        $records = $DB->get_records('local_accommodations_types', null, 'name ASC');
        
        foreach ($records as $record) {
            $types[$record->id] = new accommodation_type($record);
        }
        
        return $types;
    }
    
    /**
     * Get all accommodation types as an array suitable for a form.
     *
     * @return array Array of accommodation types
     */
    public static function get_types_menu() {
        $types = self::get_all();
        $menu = [];
        
        foreach ($types as $type) {
            $menu[$type->get_record()->id] = $type->get_record()->name;
        }
        
        return $menu;
    }
}