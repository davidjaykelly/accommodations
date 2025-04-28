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
 * Form for bulk editing user accommodations.
 *
 * @package    local_accommodations
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_accommodations\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

use local_accommodations\accommodation_type;

/**
 * Form for bulk editing user accommodations.
 */
class accommodation_bulk_edit extends \moodleform {
    /**
     * Define the form.
     */
    public function definition() {
        global $CFG;
        
        $mform = $this->_form;
        $courseid = $this->_customdata['courseid'] ?? null;
        $categoryid = $this->_customdata['categoryid'] ?? null;
        
        // Hidden fields
        if ($courseid) {
            $mform->addElement('hidden', 'courseid', $courseid);
            $mform->setType('courseid', PARAM_INT);
        }
        
        if ($categoryid) {
            $mform->addElement('hidden', 'categoryid', $categoryid);
            $mform->setType('categoryid', PARAM_INT);
        }
        
        // User selection - options differ based on context
        if ($courseid) {
            // Course context - select enrolled users
            $userlist = $this->get_enrolled_users($courseid);
            $mform->addElement('select', 'userids', get_string('selectusers', 'local_accommodations'), 
                              $userlist, ['multiple' => 'multiple', 'size' => 10]);
        } else {
            // System context - use user selector
            $mform->addElement('text', 'userinput', get_string('selectusers', 'local_accommodations'), 
                             ['size' => 50]);
            $mform->setType('userinput', PARAM_TEXT);
            $mform->addHelpButton('userinput', 'userinput', 'local_accommodations');
            
            // Hidden field to store selected user IDs (populated via JS)
            $mform->addElement('hidden', 'userids', '');
            $mform->setType('userids', PARAM_RAW);
        }
        
        $mform->addRule('userids', get_string('required'), 'required', null, 'client');
        
        // Accommodation type
        $types = accommodation_type::get_types_menu();
        $mform->addElement('select', 'typeid', get_string('accommodationtype', 'local_accommodations'), $types);
        $mform->addRule('typeid', get_string('required'), 'required', null, 'client');
        
        // Time extension
        $mform->addElement('text', 'timeextension', get_string('timeextension', 'local_accommodations'), ['size' => 5]);
        $mform->setType('timeextension', PARAM_INT);
        $mform->addHelpButton('timeextension', 'timeextension', 'local_accommodations');
        $mform->setDefault('timeextension', 0);
        $mform->addRule('timeextension', get_string('numeric'), 'numeric', null, 'client');
        
        // Date range
        $mform->addElement('date_selector', 'startdate', get_string('from'), ['optional' => true]);
        $mform->addElement('date_selector', 'enddate', get_string('to'), ['optional' => true]);
        
        // Notes
        $mform->addElement('textarea', 'notes', get_string('notes', 'local_accommodations'), 
                          ['rows' => 5, 'cols' => 50]);
        $mform->setType('notes', PARAM_TEXT);
        
        // Apply to all activities in course
        if ($courseid) {
            $mform->addElement('advcheckbox', 'applytoall', get_string('applytoallactivities', 'local_accommodations'), 
                              get_string('applytoallactivitiesdesc', 'local_accommodations'));
            $mform->setDefault('applytoall', 1);
        }
        
        // Add buttons
        $this->add_action_buttons();
    }
    
    /**
     * Validate the form data.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        
        // Check dates
        if (!empty($data['startdate']) && !empty($data['enddate']) && $data['startdate'] > $data['enddate']) {
            $errors['enddate'] = get_string('enddatebeforestartdate', 'local_accommodations');
        }
        
        // Check time extension is not negative
        if (isset($data['timeextension']) && $data['timeextension'] < 0) {
            $errors['timeextension'] = get_string('timeextensionnegative', 'local_accommodations');
        }
        
        // Check users are selected
        if (empty($data['userids'])) {
            $errors['userids'] = get_string('nousersselected', 'local_accommodations');
        }
        
        return $errors;
    }
    
    /**
     * Get enrolled users from a course.
     *
     * @param int $courseid Course ID
     * @return array Array of user fullnames indexed by ID
     */
    private function get_enrolled_users($courseid) {
        $context = \context_course::instance($courseid);
        $users = get_enrolled_users($context);
        
        $userlist = [];
        foreach ($users as $user) {
            $userlist[$user->id] = fullname($user);
        }
        
        return $userlist;
    }
}