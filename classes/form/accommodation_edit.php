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
 * Form for editing user accommodations.
 *
 * @package    local_accommodations
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_accommodations\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/user/selector/lib.php');

use local_accommodations\accommodation_type;

/**
 * Form for editing a user accommodation.
 */
class accommodation_edit extends \moodleform {
    /**
     * Define the form.
     */
    public function definition() {
        global $CFG, $DB;
        
        $mform = $this->_form;
        $accommodationid = $this->_customdata['id'];
        $userid = $this->_customdata['userid'];
        $courseid = $this->_customdata['courseid'] ?? null;
        $categoryid = $this->_customdata['categoryid'] ?? null;
        
        // Hidden fields
        $mform->addElement('hidden', 'id', $accommodationid);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'userid', $userid);
        $mform->setType('userid', PARAM_INT);
        
        if ($courseid) {
            $mform->addElement('hidden', 'courseid', $courseid);
            $mform->setType('courseid', PARAM_INT);
        }
        
        if ($categoryid) {
            $mform->addElement('hidden', 'categoryid', $categoryid);
            $mform->setType('categoryid', PARAM_INT);
        }
        
        // User information (display only)
        if ($userid) {
            $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
            $userinfo = fullname($user) . ' (' . $user->email . ')';
            $mform->addElement('static', 'userinfo', get_string('user'), $userinfo);
        }
        
        // Course information (if applicable)
        if ($courseid) {
            $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
            $mform->addElement('static', 'courseinfo', get_string('course'), $course->fullname);
        }
        
        // Category information (if applicable)
        if ($categoryid) {
            $category = $DB->get_record('course_categories', ['id' => $categoryid], '*', MUST_EXIST);
            $mform->addElement('static', 'categoryinfo', get_string('category'), $category->name);
        }
        
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
        
        return $errors;
    }
}