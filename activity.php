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
 * Activity level accommodations management.
 *
 * @package    local_accommodations
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// Get parameters
$courseid = required_param('courseid', PARAM_INT);
$cmid = optional_param('cmid', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$status = optional_param('status', 0, PARAM_INT);

// Setup course page
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);
require_capability('local/accommodations:manage', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/accommodations/activity.php', ['courseid' => $courseid]));
$PAGE->set_title($course->shortname . ': ' . get_string('manageactivities', 'local_accommodations'));
$PAGE->set_heading(get_string('manageactivities', 'local_accommodations'));
$PAGE->set_pagelayout('incourse');

// Add navigation breadcrumb
$accommodationsurl = new moodle_url('/local/accommodations/course.php', ['courseid' => $courseid]);
$PAGE->navbar->add(get_string('accommodations', 'local_accommodations'), $accommodationsurl);
$PAGE->navbar->add(get_string('manageactivities', 'local_accommodations'));

// Toggle activity status
if ($action == 'toggle' && $cmid) {
    $cm = get_coursemodule_from_id('', $cmid, $courseid, false, MUST_EXIST);
    $modulecontext = context_module::instance($cmid);
    require_capability('local/accommodations:toggleactivity', $modulecontext);
    
    // Toggle status (1 = disabled, 0 = enabled)
    if (local_accommodations_toggle_activity($cmid, $status)) {
        $statustext = $status ? get_string('disabled', 'local_accommodations') : get_string('enabled', 'local_accommodations');
        \core\notification::success(get_string('accommodationsstatusupdated', 'local_accommodations', $statustext));
    } else {
        \core\notification::error(get_string('statusupdatefailed', 'local_accommodations'));
    }
    
    redirect(new moodle_url('/local/accommodations/activity.php', ['courseid' => $courseid]));
}

// Get all activities eligible for accommodations (quizzes and assignments)
$modinfo = get_fast_modinfo($course);
$activities = [];

// Get status data for all activities
$activitystatus = $DB->get_records_menu('local_accommodations_activity_status', 
                                        [], 'cmid', 'cmid, disabled');

// Process quizzes
if (isset($modinfo->instances['quiz'])) {
    foreach ($modinfo->instances['quiz'] as $cm) {
        $activity = new stdClass();
        $activity->cmid = $cm->id;
        $activity->name = $cm->name;
        $activity->type = 'quiz';
        $activity->icon = $cm->get_icon_url();
        $activity->url = $cm->url;
        $activity->disabled = isset($activitystatus[$cm->id]) ? (bool)$activitystatus[$cm->id] : false;
        
        $activities[] = $activity;
    }
}

// Process assignments
if (isset($modinfo->instances['assign'])) {
    foreach ($modinfo->instances['assign'] as $cm) {
        $activity = new stdClass();
        $activity->cmid = $cm->id;
        $activity->name = $cm->name;
        $activity->type = 'assign';
        $activity->icon = $cm->get_icon_url();
        $activity->url = $cm->url;
        $activity->disabled = isset($activitystatus[$cm->id]) ? (bool)$activitystatus[$cm->id] : false;
        
        $activities[] = $activity;
    }
}

// Output page
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manageactivities', 'local_accommodations'));

// Show explanation
echo html_writer::tag('p', get_string('activitytoggledesc', 'local_accommodations'));

// Show back button
$backurl = new moodle_url('/local/accommodations/course.php', ['courseid' => $courseid]);
echo html_writer::div(
    $OUTPUT->single_button($backurl, get_string('backtocourseaccommodations', 'local_accommodations'), 'get'),
    'mb-3'
);

// Display activities in a table
if (empty($activities)) {
    echo $OUTPUT->notification(get_string('noeligibleactivities', 'local_accommodations'), 'info');
} else {
    $table = new html_table();
    $table->head = [
        get_string('activity'),
        get_string('type', 'local_accommodations'),
        get_string('accommodationsstatus', 'local_accommodations'),
        get_string('actions')
    ];
    $table->attributes['class'] = 'generaltable accommodations-table';
    
    foreach ($activities as $activity) {
        // Activity name with icon
        $namelink = html_writer::link(
            $activity->url,
            $OUTPUT->pix_icon($activity->icon, '') . ' ' . $activity->name
        );
        
        // Activity type
        $typestr = get_string('modulename', 'mod_' . $activity->type);
        
        // Status display
        $status = $activity->disabled 
            ? html_writer::span(get_string('disabled', 'local_accommodations'), 'badge badge-warning')
            : html_writer::span(get_string('enabled', 'local_accommodations'), 'badge badge-success');
        
        // Toggle action
        $newstatus = $activity->disabled ? 0 : 1;
        $toggleurl = new moodle_url('/local/accommodations/activity.php', [
            'courseid' => $courseid,
            'cmid' => $activity->cmid,
            'action' => 'toggle',
            'status' => $newstatus
        ]);
        
        if ($activity->disabled) {
            $toggletext = get_string('enable', 'local_accommodations');
            $toggleicon = 't/show';
            $toggleclass = 'btn btn-sm btn-success';
        } else {
            $toggletext = get_string('disable', 'local_accommodations');
            $toggleicon = 't/hide';
            $toggleclass = 'btn btn-sm btn-warning';
        }
        
        $togglebutton = html_writer::link(
            $toggleurl,
            $OUTPUT->pix_icon($toggleicon, $toggletext) . ' ' . $toggletext,
            ['class' => $toggleclass]
        );
        
        // Add row to table
        $table->data[] = [
            $namelink,
            $typestr,
            $status,
            $togglebutton
        ];
    }
    
    echo html_writer::table($table);
}

// Batch operations
echo $OUTPUT->heading(get_string('batchoperations', 'local_accommodations'), 3);

// Batch enable/disable form
echo html_writer::start_tag('form', [
    'action' => new moodle_url('/local/accommodations/activity.php'),
    'method' => 'post',
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
    'value' => 'batch'
]);

echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'sesskey',
    'value' => sesskey()
]);

// Activity type dropdown
$typeoptions = [
    'all' => get_string('allactivities', 'local_accommodations'),
    'quiz' => get_string('modulename', 'mod_quiz'),
    'assign' => get_string('modulename', 'mod_assign')
];

echo html_writer::select(
    $typeoptions,
    'activitytype',
    'all',
    null,
    ['class' => 'custom-select mr-2']
);

// Action dropdown
$actionoptions = [
    'enable' => get_string('enableaccommodations', 'local_accommodations'),
    'disable' => get_string('disableaccommodations', 'local_accommodations')
];

echo html_writer::select(
    $actionoptions,
    'batchaction',
    'enable',
    null,
    ['class' => 'custom-select mr-2']
);

echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'value' => get_string('apply', 'local_accommodations'),
    'class' => 'btn btn-primary'
]);

echo html_writer::end_tag('form');

echo $OUTPUT->footer();