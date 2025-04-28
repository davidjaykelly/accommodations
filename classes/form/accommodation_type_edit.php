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
 * Form for editing accommodation types.
 *
 * @package    local_accommodations
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_accommodations\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for editing an accommodation type.
 */
class accommodation_type_edit extends \moodleform {
    /**
     * Define the form.
     */
    public function definition() {
        $mform = $this->_form;
        $typeid = $this->_customdata['id'];
        
        // Hidden fields
        $mform->addElement('hidden', 'id', $typeid);
        $mform->setType('id', PARAM_INT);
        
        // Type name
        $mform->addElement('text', 'name', get_string('typename', 'local_accommodations'), ['size' => 50]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('name', 'typename', 'local_accommodations');
        
        // Description
        $mform->addElement('textarea', 'description', get_string('description'), 
                          ['rows' => 5, 'cols' => 50]);
        $mform->setType('description', PARAM_TEXT);
        $mform->addHelpButton('description', 'typedescription', 'local_accommodations');
        
        // Default time extension
        $mform->addElement('text', 'timeextension', get_string('defaulttimeextension', 'local_accommodations'), 
                          ['size' => 5]);
        $mform->setType('timeextension', PARAM_INT);
        $mform->addHelpButton('timeextension', 'defaulttimeextension', 'local_accommodations');
        $mform->setDefault('timeextension', 0);
        $mform->addRule('timeextension', get_string('numeric'), 'numeric', null, 'client');
        
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
        global $DB;
        $errors = parent::validation($data, $files);
        
        // Check if name already exists (for new types)
        if (empty($data['id'])) {
            $exists = $DB->record_exists('local_accommodations_types', ['name' => $data['name']]);
            if ($exists) {
                $errors['name'] = get_string('typenameexists', 'local_accommodations');
            }
        }
        
        // Check time extension is not negative
        if (isset($data['timeextension']) && $data['timeextension'] < 0) {
            $errors['timeextension'] = get_string('timeextensionnegative', 'local_accommodations');
        }
        
        return $errors;
    }
}