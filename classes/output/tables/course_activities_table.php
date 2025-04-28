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
 * Course activities table.
 *
 * @package    local_accommodations
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_accommodations\output\tables;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');

/**
 * Table to display course activities with accommodation toggles.
 */
class course_activities_table extends \table_sql {
    /**
     * @var int
     */
    protected $courseid;
    
    /**
     * @var array
     */
    protected $activitystatus;
    
    /**
     * Constructor
     *
     * @param string $uniqueid Unique id of table
     * @param int $courseid Course ID
     * @param array $activitystatus Activity status data (cmid => disabled)
     */
    public function __construct($uniqueid, $courseid, $activitystatus = []) {
        global $DB;
        
        parent::__construct($uniqueid);
        
        $this->courseid = $courseid;
        $this->activitystatus = $activitystatus;
        
        // Define the list of columns to show.
        $columns = ['activity', 'module', 'status', 'actions'];
        $this->define_columns($columns);
        
        // Define the titles of columns to show in the header.
        $headers = [
            get_string('activity'),
            get_string('type', 'local_accommodations'),
            get_string('accommodationsstatus', 'local_accommodations'),
            get_string('actions')
        ];
        $this->define_headers($headers);
        
        // Table configuration
        $this->sortable(true, 'activity', SORT_ASC);
        $this->no_sorting('actions');
        $this->collapsible(false);
        $this->set_attribute('class', 'generaltable course-activities-table');
        
        // Setup SQL for the table - we'll use all course modules that are quizzes or assignments
        $sql = "SELECT cm.id, cm.instance, cm.module, cm.visible, m.name AS modulename
                FROM {course_modules} cm
                JOIN {modules} m ON cm.module = m.id
                WHERE cm.course = :courseid 
                AND m.name IN ('quiz', 'assign')";
        
        $this->set_sql('*', "($sql) temp", 'temp.id > 0', ['courseid' => $courseid]);
        $this->set_count_sql("SELECT COUNT(1) FROM ($sql) temp", ['courseid' => $courseid]);
    }
    
    /**
     * Format the activity column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_activity($row) {
        global $DB, $OUTPUT;
        
        // Get activity details
        $modulename = $row->modulename;
        $instance = $DB->get_record($modulename, ['id' => $row->instance]);
        
        if (!$instance) {
            return '-';
        }
        
        // Get the activity link
        $modinfo = get_fast_modinfo($this->courseid);
        $cminfo = $modinfo->get_cm($row->id);
        
        $icon = $OUTPUT->pix_icon($cminfo->get_icon_url(), $modulename);
        $link = html_writer::link($cminfo->url, $instance->name);
        
        return $icon . ' ' . $link;
    }
    
    /**
     * Format the module column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_module($row) {
        return get_string('modulename', 'mod_' . $row->modulename);
    }
    
    /**
     * Format the status column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_status($row) {
        $disabled = isset($this->activitystatus[$row->id]) ? $this->activitystatus[$row->id] : false;
        
        // Toggle switch
        $toggleid = 'activity-toggle-' . $row->id;
        $checked = !$disabled;
        
        $output = html_writer::start_div('custom-control custom-switch');
        $output .= html_writer::empty_tag('input', [
            'type' => 'checkbox',
            'class' => 'custom-control-input activity-toggle',
            'id' => $toggleid,
            'data-cmid' => $row->id,
            'data-courseid' => $this->courseid,
            'checked' => $checked ? 'checked' : null
        ]);
        $output .= html_writer::tag('label', '', [
            'class' => 'custom-control-label',
            'for' => $toggleid
        ]);
        $output .= html_writer::end_div();
        
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
        
        $disabled = isset($this->activitystatus[$row->id]) ? $this->activitystatus[$row->id] : false;
        
        // Create toggle URL
        $newstatus = $disabled ? 0 : 1;
        $toggleurl = new \moodle_url('/local/accommodations/activity.php', [
            'courseid' => $this->courseid,
            'cmid' => $row->id,
            'action' => 'toggle',
            'status' => $newstatus
        ]);
        
        if ($disabled) {
            $toggletext = get_string('enable', 'local_accommodations');
            $toggleicon = 't/show';
            $toggleclass = 'btn btn-sm btn-success';
        } else {
            $toggletext = get_string('disable', 'local_accommodations');
            $toggleicon = 't/hide';
            $toggleclass = 'btn btn-sm btn-warning';
        }
        
        $actions = html_writer::link(
            $toggleurl,
            $OUTPUT->pix_icon($toggleicon, $toggletext) . ' ' . $toggletext,
            ['class' => $toggleclass]
        );
        
        return $actions;
    }
}