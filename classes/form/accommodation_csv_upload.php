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
 * Form for CSV upload of accommodations.
 *
 * @package    local_accommodations
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_accommodations\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/lib/csvlib.class.php');

/**
 * Form for CSV upload of accommodations.
 */
class accommodation_csv_upload extends \moodleform {
    /**
     * Define the form.
     */
    public function definition() {
        global $CFG;
        
        $mform = $this->_form;
        $data = $this->_customdata;
        
        // Hidden fields
        if (!empty($data['courseid'])) {
            $mform->addElement('hidden', 'courseid', $data['courseid']);
            $mform->setType('courseid', PARAM_INT);
        }
        
        if (!empty($data['categoryid'])) {
            $mform->addElement('hidden', 'categoryid', $data['categoryid']);
            $mform->setType('categoryid', PARAM_INT);
        }
        
        // Instructions
        $mform->addElement('static', 'instructions', get_string('uploadcsvinstructions', 'local_accommodations'));
        
        // File format example
        $mform->addElement('static', 'fileformat', get_string('fileformat', 'local_accommodations'),
            '<pre>' . get_string('uploadcsvformat', 'local_accommodations') . '</pre>');
            
        // Template download link
        $templateurl = new \moodle_url('/local/accommodations/upload_template.php');
        $mform->addElement('static', 'template', '', 
            \html_writer::link($templateurl, get_string('downloadtemplate', 'local_accommodations'),
                ['class' => 'btn btn-secondary mb-3']
            )
        );
        
        // File upload field
        $mform->addElement('filepicker', 'userfile', get_string('csvfile', 'local_accommodations'));
        $mform->addRule('userfile', null, 'required');
        
        // CSV delimiter options
        $delimiters = [
            'comma' => get_string('comma', 'local_accommodations'),
            'semicolon' => get_string('semicolon', 'local_accommodations'),
            'tab' => get_string('tab', 'local_accommodations'),
        ];
        $mform->addElement('select', 'delimiter', get_string('csvdelimiter', 'local_accommodations'), $delimiters);
        $mform->setDefault('delimiter', 'comma');
        
        // Encoding options
        $encodings = core_text::get_encodings();
        $mform->addElement('select', 'encoding', get_string('encoding', 'tool_uploaduser'), $encodings);
        $mform->setDefault('encoding', 'UTF-8');
        
        // Apply to all checkbox
        $mform->addElement('advcheckbox', 'applyall', get_string('applytoallactivities', 'local_accommodations'),
            get_string('applytoallactivitiesdesc', 'local_accommodations'));
        $mform->setDefault('applyall', 1);
        
        // Add buttons
        $this->add_action_buttons(true, get_string('upload', 'local_accommodations'));
    }
    
    /**
     * Validate the form data.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        global $USER;
        
        $errors = parent::validation($data, $files);
        
        if (empty($files['userfile'])) {
            $errors['userfile'] = get_string('required');
            return $errors;
        }
        
        $content = $this->get_file_content('userfile');
        if (empty($content)) {
            $errors['userfile'] = get_string('csvemptyfile', 'error');
            return $errors;
        }
        
        $encoding = $data['encoding'];
        $delimiter = $this->get_delimiter($data['delimiter']);
        
        // Create a temporary CSV import reader
        $iid = csv_import_reader::get_new_iid('local_accommodations');
        $cir = new csv_import_reader($iid, 'local_accommodations');
        
        $readcount = $cir->load_csv_content($content, $encoding, $delimiter);
        
        if ($readcount === false) {
            $errors['userfile'] = $cir->get_error();
            return $errors;
        }
        
        // Check column count
        $columns = $cir->get_columns();
        if (count($columns) < 3) {
            $errors['userfile'] = get_string('csvfewcolumns', 'error');
            return $errors;
        }
        
        // Initialize the import
        $cir->init();
        
        // Check first few rows for format issues
        $rownum = 0;
        $maxrows = 5;
        $fieldissues = [];
        
        while ($row = $cir->next() and $rownum < $maxrows) {
            $rownum++;
            
            // Check user identifier (field 0)
            if (empty($row[0])) {
                $fieldissues[] = get_string('missinguser', 'local_accommodations') . " (row $rownum)";
            }
            
            // Check accommodation type (field 1)
            if (empty($row[1])) {
                $fieldissues[] = get_string('missingtype', 'local_accommodations') . " (row $rownum)";
            }
            
            // Check time extension (field 2)
            if (empty($row[2]) || !is_numeric($row[2])) {
                $fieldissues[] = get_string('invalidtimeextension', 'local_accommodations', $row[2]) . " (row $rownum)";
            }
        }
        
        if (!empty($fieldissues)) {
            $errors['userfile'] = implode('<br>', $fieldissues);
        }
        
        // Store the import ID in session for later processing
        if (empty($errors)) {
            $_SESSION['accommodation_import_id'] = $iid;
        }
        
        return $errors;
    }
    
    /**
     * Get CSV delimiter based on selection.
     *
     * @param string $delimiter Selected delimiter option
     * @return string Actual delimiter character
     */
    private function get_delimiter($delimiter) {
        switch ($delimiter) {
            case 'comma':
                return ',';
            case 'semicolon':
                return ';';
            case 'tab':
                return "\t";
            default:
                return ',';
        }
    }
}