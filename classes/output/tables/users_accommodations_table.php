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
 * Users with accommodations table.
 *
 * @package    local_accommodations
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_accommodations\output\tables;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');

/**
 * Table to display users with accommodations.
 */
class users_accommodations_table extends \table_sql {
    /**
     * @var \context
     */
    protected $context;
    
    /**
     * Constructor
     *
     * @param string $uniqueid Unique id of table
     */
    public function __construct($uniqueid) {
        parent::__construct($uniqueid);
        
        // Define the list of columns to show.
        $columns = ['name', 'accommodations', 'actions'];
        $this->define_columns($columns);
        
        // Define the titles of columns to show in the header.
        $headers = [
            get_string('name'),
            get_string('accommodations', 'local_accommodations'),
            get_string('actions')
        ];
        $this->define_headers($headers);
        
        // Table configuration
        $this->sortable(true, 'name', SORT_ASC);
        $this->collapsible(false);
        $this->set_attribute('class', 'generaltable accommodations-table');
    }
    
    /**
     * Set the context for this table.
     *
     * @param \context $context
     */
    public function set_context($context) {
        $this->context = $context;
    }
    
    /**
     * Format the name column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_name($row) {
        global $OUTPUT;
        
        $user = (object)[
            'id' => $row->id,
            'firstname' => $row->firstname,
            'lastname' => $row->lastname,
            'email' => $row->email
        ];
        
        $picturelink = $OUTPUT->user_picture($user, ['size' => 35, 'class' => 'mr-2']);
        
        $urlparams = ['id' => $user->id];
        if ($this->context->contextlevel == CONTEXT_COURSE) {
            $urlparams['course'] = $this->context->instanceid;
        }
        
        $namelink = html_writer::link(
            new \moodle_url('/user/view.php', $urlparams),
            fullname($user)
        );
        
        return $picturelink . $namelink;
    }
    
    /**
     * Format the accommodations column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_accommodations($row) {
        $output = '';
        
        if (!empty($row->accommodations)) {
            $output = html_writer::start_tag('ul', ['class' => 'list-unstyled']);
            
            foreach ($row->accommodations as $accommodation) {
                $timeext = html_writer::tag('span', 
                    $accommodation->timeextension . '%', 
                    ['class' => 'badge badge-info']
                );
                
                $output .= html_writer::tag('li', 
                    $accommodation->typename . ' (' . 
                    get_string('timeextension', 'local_accommodations') . ': ' . 
                    $timeext . ')'
                );
            }
            
            $output .= html_writer::end_tag('ul');
        } else {
            $output = get_string('noaccommodations', 'local_accommodations');
        }
        
        return $output;
    }
    
    /**
     * Format the actions column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_actions($row) {
        global $OUTPUT;
        
        $actions = [];
        
        // Add accommodation
        if ($this->context->contextlevel == CONTEXT_COURSE) {
            $addurl = new \moodle_url('/local/accommodations/course.php', [
                'courseid' => $this->context->instanceid,
                'action' => 'add',
                'userid' => $row->id
            ]);
        } else if ($this->context->contextlevel == CONTEXT_COURSECAT) {
            $addurl = new \moodle_url('/local/accommodations/category.php', [
                'categoryid' => $this->context->instanceid,
                'action' => 'add',
                'userid' => $row->id
            ]);
        } else {
            $addurl = new \moodle_url('/local/accommodations/user.php', [
                'id' => $row->id,
                'action' => 'add'
            ]);
        }
        
        $actions[] = html_writer::link(
            $addurl,
            $OUTPUT->pix_icon('t/add', get_string('addaccommodation', 'local_accommodations')),
            ['title' => get_string('addaccommodation', 'local_accommodations')]
        );
        
        // View/manage accommodations
        $viewurl = new \moodle_url('/local/accommodations/user.php', [
            'id' => $row->id
        ]);
        
        if ($this->context->contextlevel == CONTEXT_COURSE) {
            $viewurl->param('courseid', $this->context->instanceid);
        } else if ($this->context->contextlevel == CONTEXT_COURSECAT) {
            $viewurl->param('categoryid', $this->context->instanceid);
        }
        
        $actions[] = html_writer::link(
            $viewurl,
            $OUTPUT->pix_icon('i/settings', get_string('manageaccommodations', 'local_accommodations')),
            ['title' => get_string('manageaccommodations', 'local_accommodations')]
        );
        
        return implode(' ', $actions);
    }
}