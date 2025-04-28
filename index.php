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
 * Main page for managing accommodations.
 *
 * @package    local_accommodations
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_accommodations\accommodation;
use local_accommodations\form\accommodation_bulk_edit;

// Parameters
$courseid = optional_param('courseid', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$id = optional_param('id', 0, PARAM_INT);

// Set up the page
if ($courseid) {
    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    $context = context_course::instance($courseid);
    $PAGE->set_context($context);
    $PAGE->set_course($course);
    $PAGE->set_url(new moodle_url('/local/accommodations/index.php', ['courseid' => $courseid]));
    $PAGE->set_title($course->shortname . ': ' . get_string('accommodations', 'local_accommodations'));
    $PAGE->set_heading($course->fullname);
    
    require_login($course);
    require_capability('local/accommodations:manage', $context);
} else {
    $context = context_system::instance();
    $PAGE->set_context($context);
    $PAGE->set_url(new moodle_url('/local/accommodations/index.php'));
    $PAGE->set_title(get_string('accommodations', 'local_accommodations'));
    $PAGE->set_heading(get_string('accommodations', 'local_accommodations'));
    
    admin_externalpage_setup('local_accommodations_bulk');
}

// Set up navigation
$PAGE->set_pagelayout('admin');
$PAGE->navbar->add(get_string('accommodations', 'local_accommodations'));

// Process the action
if ($action === 'bulkedit') {
    // Bulk edit accommodations
    $PAGE->navbar->add(get_string('bulkedit', 'local_accommodations'));
    
    $form = new accommodation_bulk_edit(null, [
        'courseid' => $courseid
    ]);
    
    if ($form->is_cancelled()) {
        if ($courseid) {
            redirect(new moodle_url('/local/accommodations/index.php', ['courseid' => $courseid]));
        } else {
            redirect(new moodle_url('/local/accommodations/index.php'));
        }
    } else if ($data = $form->get_data()) {
        // Process form data
        $userids = explode(',', $data->userids);
        $count = 0;
        
        foreach ($userids as $userid) {
            if (!$userid) {
                continue;
            }
            
            $accommodation = new accommodation();
            $accommodationdata = new stdClass();
            $accommodationdata->userid = $userid;
            $accommodationdata->typeid = $data->typeid;
            $accommodationdata->timeextension = $data->timeextension;
            $accommodationdata->courseid = $courseid ? $courseid : null;
            $accommodationdata->startdate = !empty($data->startdate) ? $data->startdate : null;
            $accommodationdata->enddate = !empty($data->enddate) ? $data->enddate : null;
            $accommodationdata->notes = $data->notes;
            
            if ($accommodation->save($accommodationdata)) {
                $count++;
                
                // Apply to all activities in this course if requested
                if ($courseid && !empty($data->applytoall)) {
                    $accommodation->apply_to_quiz($courseid);
                    $accommodation->apply_to_assignment($courseid);
                }
            }
        }
        
        // Show success message
        \core\notification::success(get_string('bulkeditsuccessx', 'local_accommodations', $count));
        
        if ($courseid) {
            redirect(new moodle_url('/local/accommodations/index.php', ['courseid' => $courseid]));
        } else {
            redirect(new moodle_url('/local/accommodations/index.php'));
        }
    }
    
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('bulkedit', 'local_accommodations'));
    $form->display();
    echo $OUTPUT->footer();
    exit;
    
} else if ($action === 'delete' && $id) {
    // Delete an accommodation
    $accommodation = new accommodation($id);
    $record = $accommodation->get_record();
    
    // Security check
    if ($courseid && $record->courseid != $courseid) {
        throw new moodle_exception('invalidaccess', 'local_accommodations');
    }
    
    if ($confirm) {
        // Delete the accommodation
        $accommodation->delete();
        \core\notification::success(get_string('accommodationdeleted', 'local_accommodations'));
        
        // Redirect based on context
        if ($courseid) {
            redirect(new moodle_url('/local/accommodations/index.php', ['courseid' => $courseid]));
        } else {
            redirect(new moodle_url('/local/accommodations/index.php'));
        }
    } else {
        // Show confirmation dialog
        $user = $accommodation->get_user();
        $type = $accommodation->get_type()->get_record();
        $message = get_string('confirmdelete', 'local_accommodations', [
            'type' => $type->name,
            'user' => fullname($user)
        ]);
        
        $continueurl = new moodle_url('/local/accommodations/index.php', [
            'action' => 'delete',
            'id' => $id,
            'courseid' => $courseid,
            'confirm' => 1
        ]);
        
        $cancelurl = new moodle_url('/local/accommodations/index.php', [
            'courseid' => $courseid
        ]);
        
        echo $OUTPUT->header();
        echo $OUTPUT->confirm($message, $continueurl, $cancelurl);
        echo $OUTPUT->footer();
        exit;
    }
    
} else if ($action === 'apply' && $courseid) {
    // Apply accommodations to all activities in the course
    $stats = local_accommodations_apply_to_course($courseid, true);
    
    // Show success message
    $message = get_string('accommodationsappliedx', 'local_accommodations', [
        'quizzes' => $stats['quizzes'],
        'assignments' => $stats['assignments']
    ]);
    
    \core\notification::success($message);
    redirect(new moodle_url('/local/accommodations/index.php', ['courseid' => $courseid]));
}

// Display the list of accommodations
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('accommodations', 'local_accommodations'));

// Add action buttons
echo html_writer::start_div('accommodation-actions');

if ($courseid) {
    // Course context buttons
    $bulkurl = new moodle_url('/local/accommodations/index.php', [
        'courseid' => $courseid,
        'action' => 'bulkedit'
    ]);
    
    $applyurl = new moodle_url('/local/accommodations/index.php', [
        'courseid' => $courseid,
        'action' => 'apply'
    ]);
    
    echo $OUTPUT->single_button($bulkurl, get_string('addaccommodations', 'local_accommodations'), 'get');
    echo $OUTPUT->single_button($applyurl, get_string('applytoallactivities', 'local_accommodations'), 'get');
} else {
    // System context button
    $bulkurl = new moodle_url('/local/accommodations/index.php', [
        'action' => 'bulkedit'
    ]);
    
    echo $OUTPUT->single_button($bulkurl, get_string('addaccommodations', 'local_accommodations'), 'get');
}

echo html_writer::end_div();

// Get and display accommodations
$params = [];
$sql = "SELECT a.*, t.name as typename, u.firstname, u.lastname, u.email
        FROM {local_accommodations_profiles} a
        JOIN {local_accommodations_types} t ON a.typeid = t.id
        JOIN {user} u ON a.userid = u.id";

if ($courseid) {
    $sql .= " WHERE (a.courseid = :courseid OR a.courseid IS NULL)";
    $params['courseid'] = $courseid;
}

$sql .= " ORDER BY u.lastname, u.firstname, t.name";
$accommodations = $DB->get_records_sql($sql, $params);

if (empty($accommodations)) {
    echo $OUTPUT->notification(get_string('noaccommodations', 'local_accommodations'), 'info');
} else {
    // Display as table
    $table = new html_table();
    $table->head = [
        get_string('user'),
        get_string('accommodationtype', 'local_accommodations'),
        get_string('timeextension', 'local_accommodations'),
        get_string('daterange', 'local_accommodations'),
        get_string('actions')
    ];
    $table->attributes['class'] = 'generaltable accommodations-table';
    
    foreach ($accommodations as $accommodation) {
        $fullname = fullname((object)[
            'firstname' => $accommodation->firstname,
            'lastname' => $accommodation->lastname
        ]);
        
        // Format date range
        $daterange = '';
        if (!empty($accommodation->startdate) && !empty($accommodation->enddate)) {
            $daterange = userdate($accommodation->startdate, get_string('strftimedatefullshort', 'core_langconfig')) .
                ' - ' . userdate($accommodation->enddate, get_string('strftimedatefullshort', 'core_langconfig'));
        } else if (!empty($accommodation->startdate)) {
            $daterange = get_string('from') . ' ' . 
                userdate($accommodation->startdate, get_string('strftimedatefullshort', 'core_langconfig'));
        } else if (!empty($accommodation->enddate)) {
            $daterange = get_string('to') . ' ' . 
                userdate($accommodation->enddate, get_string('strftimedatefullshort', 'core_langconfig'));
        } else {
            $daterange = get_string('permanent', 'local_accommodations');
        }
        
        // Create action buttons
        $actions = [];
        
        // Edit action
        $editurl = new moodle_url('/local/accommodations/user.php', [
            'id' => $accommodation->userid,
            'aid' => $accommodation->id,
            'courseid' => $courseid
        ]);
        $actions[] = html_writer::link($editurl, $OUTPUT->pix_icon('t/edit', get_string('edit')));
        
        // Delete action
        $deleteurl = new moodle_url('/local/accommodations/index.php', [
            'action' => 'delete',
            'id' => $accommodation->id,
            'courseid' => $courseid
        ]);
        $actions[] = html_writer::link($deleteurl, $OUTPUT->pix_icon('t/delete', get_string('delete')));
        
        // Add the row to the table
        $row = [
            $fullname,
            $accommodation->typename,
            $accommodation->timeextension . '%',
            $daterange,
            implode(' ', $actions)
        ];
        
        $table->data[] = $row;
    }
    
    echo html_writer::table($table);
}

echo $OUTPUT->footer();
