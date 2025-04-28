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
 * Course level accommodations management.
 *
 * @package    local_accommodations
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once($CFG->libdir . '/adminlib.php');

use local_accommodations\accommodation;
use local_accommodations\form\accommodation_edit;
use local_accommodations\output\tables\users_accommodations_table;

// Get parameters.
$courseid = required_param('courseid', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$userid = optional_param('userid', 0, PARAM_INT);
$accommodationid = optional_param('aid', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

// Setup course page.
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);
require_capability('local/accommodations:manage', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/accommodations/course.php', ['courseid' => $courseid]));
$PAGE->set_title($course->shortname . ': ' . get_string('accommodations', 'local_accommodations'));
$PAGE->set_heading(get_string('courseaccommodations', 'local_accommodations', $course->fullname));
$PAGE->set_pagelayout('incourse');

// Handle actions.
if ($action == 'add' && $userid) {
    // Add accommodation for a user.
    $returnurl = new moodle_url('/local/accommodations/course.php', ['courseid' => $courseid]);
    
    $customdata = [
        'id' => 0,
        'userid' => $userid,
        'courseid' => $courseid
    ];
    
    $form = new accommodation_edit(null, $customdata);
    
    if ($form->is_cancelled()) {
        redirect($returnurl);
    } else if ($data = $form->get_data()) {
        // Create accommodation.
        $accommodation = new accommodation();
        if ($accommodation->save($data)) {
            \core\notification::success(get_string('accommodationsaved', 'local_accommodations'));
            
            // Apply to activities if requested.
            if (!empty($data->applytoall)) {
                local_accommodations_apply_to_course($courseid, true);
            }
            
            redirect($returnurl);
        } else {
            \core\notification::error(get_string('savefailed', 'local_accommodations'));
        }
    }
    
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('addaccommodationforx', 'local_accommodations', fullname($DB->get_record('user', ['id' => $userid]))));
    
    $form->display();
    
    echo $OUTPUT->footer();
    exit;
    
} else if ($action == 'edit' && $accommodationid) {
    // Edit accommodation.
    $returnurl = new moodle_url('/local/accommodations/course.php', ['courseid' => $courseid]);
    
    $accommodation = new accommodation($accommodationid);
    $record = $accommodation->get_record();
    
    // Security check
    if ($record->courseid != $courseid) {
        \core\notification::error(get_string('invalidaccess', 'local_accommodations'));
        redirect($returnurl);
    }
    
    $customdata = [
        'id' => $accommodationid,
        'userid' => $record->userid,
        'courseid' => $courseid
    ];
    
    $form = new accommodation_edit(null, $customdata);
    $form->set_data($record);
    
    if ($form->is_cancelled()) {
        redirect($returnurl);
    } else if ($data = $form->get_data()) {
        // Update accommodation.
        if ($accommodation->save($data)) {
            \core\notification::success(get_string('accommodationsaved', 'local_accommodations'));
            
            // Apply to activities if requested.
            if (!empty($data->applytoall)) {
                local_accommodations_apply_to_course($courseid, true);
            }
            
            redirect($returnurl);
        } else {
            \core\notification::error(get_string('savefailed', 'local_accommodations'));
        }
    }
    
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('editaccommodationforx', 'local_accommodations', fullname($DB->get_record('user', ['id' => $record->userid]))));
    
    $form->display();
    
    echo $OUTPUT->footer();
    exit;
    
} else if ($action == 'delete' && $accommodationid) {
    // Delete accommodation.
    $returnurl = new moodle_url('/local/accommodations/course.php', ['courseid' => $courseid]);
    
    $accommodation = new accommodation($accommodationid);
    $record = $accommodation->get_record();
    
    // Security check.
    if ($record->courseid != $courseid) {
        \core\notification::error(get_string('invalidaccess', 'local_accommodations'));
        redirect($returnurl);
    }
    
    if ($confirm) {
        // Delete the accommodation.
        if ($accommodation->delete()) {
            \core\notification::success(get_string('accommodationdeleted', 'local_accommodations'));
        } else {
            \core\notification::error(get_string('deletefailed', 'local_accommodations'));
        }
        redirect($returnurl);
    } else {
        // Show confirmation dialog.
        $user = $DB->get_record('user', ['id' => $record->userid]);
        
        echo $OUTPUT->header();
        echo $OUTPUT->confirm(
            get_string('confirmdelete', 'local_accommodations', [
                'type' => $accommodation->get_type()->get_record()->name,
                'user' => fullname($user)
            ]),
            new moodle_url('/local/accommodations/course.php', [
                'courseid' => $courseid,
                'action' => 'delete',
                'aid' => $accommodationid,
                'confirm' => 1
            ]),
            $returnurl
        );
        echo $OUTPUT->footer();
        exit;
    }
    
} else if ($action == 'apply') {
    // Apply accommodations to all activities in the course.
    $stats = local_accommodations_apply_to_course($courseid, true);
    
    $message = get_string('accommodationsappliedx', 'local_accommodations', [
        'quizzes' => $stats['quizzes'],
        'assignments' => $stats['assignments']
    ]);
    
    \core\notification::success($message);
    
    redirect(new moodle_url('/local/accommodations/course.php', ['courseid' => $courseid]));
}

// Get enrolled users with accommodations in this course.
$enrolledusers = get_enrolled_users($context);
$usersdata = [];

foreach ($enrolledusers as $user) {
    $accommodations = local_accommodations_get_user_accommodations($user->id, null, $courseid);
    
    if (!empty($accommodations)) {
        $userdata = [
            'id' => $user->id,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'email' => $user->email,
            'accommodations' => $accommodations
        ];
        
        $usersdata[] = $userdata;
    }
}

// Output the page.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('courseaccommodations', 'local_accommodations', $course->fullname));

// Action buttons.
echo html_writer::start_div('accommodation-actions mb-3');

// Add accommodation button (with user selector).
echo $OUTPUT->single_button(
    new moodle_url('/local/accommodations/activity.php', ['courseid' => $courseid]),
    get_string('manageactivities', 'local_accommodations'),
    'get',
    ['class' => 'btn btn-secondary mr-2']
);

// Apply accommodations button
echo $OUTPUT->single_button(
    new moodle_url('/local/accommodations/course.php', ['courseid' => $courseid, 'action' => 'apply']),
    get_string('applyaccommodations', 'local_accommodations'),
    'get',
    ['class' => 'btn btn-secondary mr-2']
);

// Show only users with accommodations
$showallurl = new moodle_url('/local/accommodations/course.php', ['courseid' => $courseid, 'showall' => 1]);
$searchurl = new moodle_url('/local/accommodations/course.php', ['courseid' => $courseid]);

$renderer = $PAGE->get_renderer('local_accommodations');

// Create the users with accommodations table
$table = new users_accommodations_table('users-accommodations-table');
$table->set_context($context);
$table->define_baseurl($PAGE->url);
$table->show_download_buttons_at([TABLE_P_BOTTOM]);

// Add header row
$table->define_columns(['name', 'accommodations', 'actions']);
$table->define_headers([
    get_string('name'),
    get_string('accommodations', 'local_accommodations'),
    get_string('actions')
]);

// Set up the table
$table->setup();

// Add data rows
foreach ($usersdata as $userdata) {
    $userlink = html_writer::link(
        new moodle_url('/user/view.php', ['id' => $userdata['id'], 'course' => $courseid]),
        fullname($userdata)
    );
    
    $accommodationscell = '';
    foreach ($userdata['accommodations'] as $accommodation) {
        $accommodationscell .= html_writer::div(
            $accommodation->typename . ' (' . $accommodation->timeextension . '% ' . 
            get_string('timeextension', 'local_accommodations') . ')'
        );
    }
    
    $actions = '';
    
    // Add accommodation
    $actions .= html_writer::link(
        new moodle_url('/local/accommodations/course.php', [
            'courseid' => $courseid,
            'action' => 'add',
            'userid' => $userdata['id']
        ]),
        $OUTPUT->pix_icon('t/add', get_string('addaccommodation', 'local_accommodations')),
        ['title' => get_string('addaccommodation', 'local_accommodations')]
    ) . ' ';
    
    // View accommodations
    $actions .= html_writer::link(
        new moodle_url('/local/accommodations/user.php', [
            'id' => $userdata['id'],
            'courseid' => $courseid
        ]),
        $OUTPUT->pix_icon('i/settings', get_string('manageaccommodations', 'local_accommodations')),
        ['title' => get_string('manageaccommodations', 'local_accommodations')]
    );
    
    $table->add_data([$userlink, $accommodationscell, $actions]);
}

// Output the table
$table->finish_output();

// Form to find users without accommodations
echo $OUTPUT->heading(get_string('adduseraccommodation', 'local_accommodations'), 3);

// User selector - first 100 users from the course
$allusers = [];
foreach ($enrolledusers as $user) {
    $allusers[$user->id] = fullname($user) . ' (' . $user->email . ')';
}

// Autocomplete form for adding a user
echo html_writer::start_tag('form', [
    'action' => new moodle_url('/local/accommodations/course.php'),
    'method' => 'get',
    'class' => 'form-inline mb-3'
]);

echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'courseid',
    'value' => $courseid
]);

echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'action',
    'value' => 'add'
]);

echo html_writer::select(
    $allusers,
    'userid',
    '',
    ['' => get_string('selectuser', 'local_accommodations')],
    ['class' => 'custom-select mr-2']
);

echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'value' => get_string('addaccommodation', 'local_accommodations'),
    'class' => 'btn btn-primary'
]);

echo html_writer::end_tag('form');

echo $OUTPUT->footer();