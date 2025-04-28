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
 * Accommodations table.
 *
 * @package    local_accommodations
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_accommodations\output\tables;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');

/**
 * Table to display accommodations.
 */
class accommodations_table extends \table_sql {
    /**
     * @var \context
     */
    protected $context;
    
    /**
     * @var int|null
     */
    protected $courseid;
    
    /**
     * @var int|null
     */
    protected $categoryid;
    
    /**
     * Constructor
     *
     * @param string $uniqueid Unique id of table
     * @param int|null $courseid Optional course ID
     * @param int|null $categoryid Optional category ID
     */
    public function __construct($uniqueid, $courseid = null, $categoryid = null) {
        parent::__construct($uniqueid);
        
        $this->courseid = $courseid;
        $this->categoryid = $categoryid;
        
        // Define the list of columns to show.
        $columns = ['username', 'type', 'timeextension', 'scope', 'daterange', 'actions'];
        $this->define_columns($columns);
        
        // Define the titles of columns to show in the header.
        $headers = [
            get_string('user'),
            get_string('accommodationtype', 'local_accommodations'),
            get_string('timeextension', 'local_accommodations'),
            get_string('scope', 'local_accommodations'),
            get_string('daterange', 'local_accommodations'),
            get_string('actions')
        ];
        $this->define_headers($headers);
        
        // Table configuration
        $this->sortable(true, 'lastname', SORT_ASC);
        $this->no_sorting('actions');
        $this->collapsible(false);
        $this->set_attribute('class', 'generaltable accommodationstable');
        
        // Setup the SQL query
        $fields = 'a.id, a.userid, a.typeid, a.timeextension, a.courseid, a.categoryid, 
                  a.startdate, a.enddate, a.notes, a.timecreated,
                  t.name as typename, 
                  u.firstname, u.lastname, u.email';
                  
        $from = '{local_accommodations_profiles} a
                JOIN {local_accommodations_types} t ON a.typeid = t.id
                JOIN {user} u ON a.userid = u.id';
                
        $where = '1=1';
        $params = [];
        
        if ($courseid) {
            $where .= ' AND (a.courseid = :courseid OR a.courseid IS NULL)';
            $params['courseid'] = $courseid;
        }
        
        if ($categoryid) {
            $where .= ' AND (a.categoryid = :categoryid OR a.categoryid IS NULL)';
            $params['categoryid'] = $categoryid;
        }
        
        $this->set_sql($fields, $from, $where, $params);
        $this->set_count_sql("SELECT COUNT(1) FROM $from WHERE $where", $params);
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
     * Format the username column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_username($row) {
        global $OUTPUT;
        
        $user = (object)[
            'id' => $row->userid,
            'firstname' => $row->firstname,
            'lastname' => $row->lastname,
            'email' => $row->email
        ];
        
        $picturelink = $OUTPUT->user_picture($user, ['size' => 35, 'class' => 'mr-2']);
        
        $urlparams = ['id' => $user->id];
        if ($this->courseid) {
            $urlparams['course'] = $this->courseid;
        }
        
        $namelink = html_writer::link(
            new \moodle_url('/user/view.php', $urlparams),
            fullname($user)
        );
        
        return $picturelink . $namelink;
    }
    
    /**
     * Format the type column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_type($row) {
        return $row->typename;
    }
    
    /**
     * Format the timeextension column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_timeextension($row) {
        return html_writer::tag('span', 
            $row->timeextension . '%', 
            ['class' => 'badge badge-info']
        );
    }
    
    /**
     * Format the scope column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_scope($row) {
        global $DB;
        
        if (!empty($row->courseid)) {
            $course = $DB->get_record('course', ['id' => $row->courseid]);
            if ($course) {
                return html_writer::link(
                    new \moodle_url('/course/view.php', ['id' => $course->id]),
                    $course->shortname,
                    ['title' => $course->fullname]
                );
            } else {
                return get_string('specificcourse', 'local_accommodations');
            }
        } else if (!empty($row->categoryid)) {
            $category = $DB->get_record('course_categories', ['id' => $row->categoryid]);
            if ($category) {
                return html_writer::link(
                    new \moodle_url('/course/index.php', ['categoryid' => $category->id]),
                    $category->name
                );
            } else {
                return get_string('specificcategory', 'local_accommodations');
            }
        } else {
            return get_string('allcourses', 'local_accommodations');
        }
    }
    
    /**
     * Format the daterange column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_daterange($row) {
        if (!empty($row->startdate) && !empty($row->enddate)) {
            return userdate($row->startdate, get_string('strftimedatefullshort', 'core_langconfig')) .
                   ' - ' . 
                   userdate($row->enddate, get_string('strftimedatefullshort', 'core_langconfig'));
        } else if (!empty($row->startdate)) {
            return get_string('from') . ' ' . 
                   userdate($row->startdate, get_string('strftimedatefullshort', 'core_langconfig'));
        } else if (!empty($row->enddate)) {
            return get_string('to') . ' ' . 
                   userdate($row->enddate, get_string('strftimedatefullshort', 'core_langconfig'));
        } else {
            return get_string('permanent', 'local_accommodations');
        }
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
        
        // Edit action
        if ($this->courseid) {
            $editurl = new \moodle_url('/local/accommodations/course.php', [
                'courseid' => $this->courseid,
                'action' => 'edit',
                'aid' => $row->id
            ]);
        } else if ($this->categoryid) {
            $editurl = new \moodle_url('/local/accommodations/category.php', [
                'categoryid' => $this->categoryid,
                'action' => 'edit',
                'aid' => $row->id
            ]);
        } else {
            $editurl = new \moodle_url('/local/accommodations/user.php', [
                'id' => $row->userid,
                'aid' => $row->id
            ]);
        }
        
        $actions[] = html_writer::link(
            $editurl,
            $OUTPUT->pix_icon('t/edit', get_string('edit')),
            ['title' => get_string('edit')]
        );
        
        // Delete action
        if ($this->courseid) {
            $deleteurl = new \moodle_url('/local/accommodations/course.php', [
                'courseid' => $this->courseid,
                'action' => 'delete',
                'aid' => $row->id
            ]);
        } else if ($this->categoryid) {
            $deleteurl = new \moodle_url('/local/accommodations/category.php', [
                'categoryid' => $this->categoryid,
                'action' => 'delete',
                'aid' => $row->id
            ]);
        } else {
            $deleteurl = new \moodle_url('/local/accommodations/user.php', [
                'id' => $row->userid,
                'action' => 'delete',
                'aid' => $row->id
            ]);
        }
        
        $actions[] = html_writer::link(
            $deleteurl,
            $OUTPUT->pix_icon('t/delete', get_string('delete')),
            ['title' => get_string('delete')]
        );
        
        return implode(' ', $actions);
    }
}