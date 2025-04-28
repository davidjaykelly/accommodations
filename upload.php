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
 * CSV upload interface for bulk accommodation management.
 *
 * @package    local_accommodations
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/csvlib.class.php');

use local_accommodations\accommodation;
use local_accommodations\accommodation_type;

// Parameters
$categoryid = optional_param('categoryid', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

// Setting up the page
if ($categoryid) {
    $category = core_course_category::get($categoryid);
    $context = context_coursecat::instance($categoryid);
    require_capability('local/accommodations:managecategory', $context);
    
    $returnurl = new moodle_url('/local/accommodations/category.php', ['categoryid' => $categoryid]);
    $PAGE->set_url(new moodle_url('/local/accommodations/upload.php', ['categoryid' => $categoryid]));
    $PAGE->set_context($context);
    $PAGE->set_title(get_string('uploadcsv', 'local_accommodations'));
    $PAGE->set_heading(get_string('uploadcsvcategory', 'local_accommodations', $category->name));
    
    // Add navigation breadcrumb
    $PAGE->navbar->add(get_string('accommodations', 'local_accommodations'), $returnurl);
    $PAGE->navbar->add(get_string('uploadcsv', 'local_accommodations'));
} else if ($courseid) {
    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    $context = context_course::instance($courseid);
    require_capability('local/accommodations:manage', $context);
    
    $returnurl = new moodle_url('/local/accommodations/course.php', ['courseid' => $courseid]);
    $PAGE->set_url(new moodle_url('/local/accommodations/upload.php', ['courseid' => $courseid]));
    $PAGE->set_context($context);
    $PAGE->set_title(get_string('uploadcsv', 'local_accommodations'));
    $PAGE->set_heading(get_string('uploadcsvcourse', 'local_accommodations', $course->fullname));
    
    // Add navigation breadcrumb
    $PAGE->navbar->add(get_string('accommodations', 'local_accommodations'), $returnurl);
    $PAGE->navbar->add(get_string('uploadcsv', 'local_accommodations'));
} else {
    // System level
    $context = context_system::instance();
    require_capability('local/accommodations:bulkmanage', $context);
    
    admin_externalpage_setup('local_accommodations_upload');
    $returnurl = new moodle_url('/local/accommodations/index.php');
}

// Process uploaded file
if ($confirm && confirm_sesskey()) {
    $importid = optional_param('importid', 0, PARAM_INT);
    
    if (!$importid) {
        \core\notification::error(get_string('invalidimportid', 'local_accommodations'));
        redirect($returnurl);
    }
    
    // Get import data
    $cir = new csv_import_reader($importid, 'local_accommodations');
    $cir->init();
    
    $accommodation_types = accommodation_type::get_types_menu();
    $errors = [];
    $success = 0;
    
    while ($line = $cir->next()) {
        $lineerrors = [];
        $lineidx = $cir->get_current_line();
        
        // Process user identifier (can be id, username, or email)
        $useridentifier = trim($line[0]);
        if (empty($useridentifier)) {
            $lineerrors[] = get_string('missinguser', 'local_accommodations');
            $errors[$lineidx] = $lineerrors;
            continue;
        }
        
        // Find user by identifier
        $user = null;
        if (is_numeric($useridentifier)) {
            $user = $DB->get_record('user', ['id' => $useridentifier]);
        }
        if (!$user) {
            $user = $DB->get_record('user', ['username' => $useridentifier]);
        }
        if (!$user) {
            $user = $DB->get_record('user', ['email' => $useridentifier]);
        }
        
        if (!$user) {
            $lineerrors[] = get_string('usernotfound', 'local_accommodations', $useridentifier);
            $errors[$lineidx] = $lineerrors;
            continue;
        }
        
        // Process accommodation type
        $typename = trim($line[1]);
        if (empty($typename)) {
            $lineerrors[] = get_string('missingtype', 'local_accommodations');
            $errors[$lineidx] = $lineerrors;
            continue;
        }
        
        // Find type by name
        $typeid = array_search($typename, $accommodation_types);
        if (!$typeid) {
            // Try to find a partial match
            foreach ($accommodation_types as $id => $name) {
                if (stripos($name, $typename) !== false) {
                    $typeid = $id;
                    break;
                }
            }
        }
        
        if (!$typeid) {
            $lineerrors[] = get_string('typenotfound', 'local_accommodations', $typename);
            $errors[$lineidx] = $lineerrors;
            continue;
        }
        
        // Process time extension
        $timeextension = trim($line[2]);
        if (empty($timeextension) || !is_numeric($timeextension)) {
            $lineerrors[] = get_string('invalidtimeextension', 'local_accommodations', $timeextension);
            $errors[$lineidx] = $lineerrors;
            continue;
        }
        
        // Process dates if provided
        $startdate = null;
        $enddate = null;
        
        if (!empty($line[3])) {
            $starttime = strtotime(trim($line[3]));
            if ($starttime) {
                $startdate = $starttime;
            } else {
                $lineerrors[] = get_string('invalidstartdate', 'local_accommodations', $line[3]);
            }
        }
        
        if (!empty($line[4])) {
            $endtime = strtotime(trim($line[4]));
            if ($endtime) {
                $enddate = $endtime;
            } else {
                $lineerrors[] = get_string('invalidenddate', 'local_accommodations', $line[4]);
            }
        }
        
        // Check dates are valid
        if ($startdate && $enddate && $startdate > $enddate) {
            $lineerrors[] = get_string('enddatebeforestartdate', 'local_accommodations');
        }
        
        // Process notes
        $notes = !empty($line[5]) ? trim($line[5]) : '';
        
        // If there are errors, continue to next line
        if (!empty($lineerrors)) {
            $errors[$lineidx] = $lineerrors;
            continue;
        }
        
        // Create accommodation
        $accommodation = new accommodation();
        $accommodationdata = new stdClass();
        $accommodationdata->userid = $user->id;
        $accommodationdata->typeid = $typeid;
        $accommodationdata->timeextension = $timeextension;
        $accommodationdata->startdate = $startdate;
        $accommodationdata->enddate = $enddate;
        $accommodationdata->notes = $notes;
        
        // Set scope
        if ($categoryid) {
            $accommodationdata->categoryid = $categoryid;
        } else if ($courseid) {
            $accommodationdata->courseid = $courseid;
        }
        
        if ($accommodation->save($accommodationdata)) {
            $success++;
        } else {
            $lineerrors[] = get_string('savefailed', 'local_accommodations');
            $errors[$lineidx] = $lineerrors;
        }
    }
    
    // Show results
    if ($success > 0) {
        \core\notification::success(get_string('uploadsuccessx', 'local_accommodations', $success));
    }
    
    if (!empty($errors)) {
        $errorcount = count($errors);
        \core\notification::warning(get_string('uploaderrorscount', 'local_accommodations', $errorcount));
        
        // Store errors for display
        $SESSION->accommodation_upload_errors = $errors;
    }
    
    // Apply accommodations if requested
    $applyall = optional_param('applyall', 0, PARAM_BOOL);
    if ($applyall) {
        if ($categoryid) {
            $stats = local_accommodations_apply_to_category($categoryid, true);
            $message = get_string('accommodationsappliedcategoryx', 'local_accommodations', [
                'courses' => $stats['courses'],
                'quizzes' => $stats['quizzes'],
                'assignments' => $stats['assignments']
            ]);
            \core\notification::success($message);
        } else if ($courseid) {
            $stats = local_accommodations_apply_to_course($courseid, true);
            $message = get_string('accommodationsappliedx', 'local_accommodations', [
                'quizzes' => $stats['quizzes'],
                'assignments' => $stats['assignments']
            ]);
            \core\notification::success($message);
        }
    }
    
    redirect($returnurl);
}

// Display the upload form
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('uploadcsv', 'local_accommodations'));

// Show instructions
echo html_writer::tag('p', get_string('uploadcsvinstructions', 'local_accommodations'));

// Show file format
echo html_writer::tag('h4', get_string('fileformat', 'local_accommodations'));
echo html_writer::start_tag('pre');
echo get_string('uploadcsvformat', 'local_accommodations');
echo html_writer::end_tag('pre');

// Download template
echo html_writer::tag('p', 
    html_writer::link(
        new moodle_url('/local/accommodations/upload_template.php'),
        get_string('downloadtemplate', 'local_accommodations'),
        ['class' => 'btn btn-secondary mb-3']
    )
);

// Show accommodation types
echo html_writer::tag('h4', get_string('availableaccommodationtypes', 'local_accommodations'));
$types = accommodation_type::get_all();
if (!empty($types)) {
    echo html_writer::start_tag('ul');
    foreach ($types as $type) {
        echo html_writer::tag('li', 
            $type->get_record()->name . ' (' . 
            get_string('defaulttimeextension', 'local_accommodations') . ': ' . 
            $type->get_record()->timeextension . '%)'
        );
    }
    echo html_writer::end_tag('ul');
} else {
    echo html_writer::tag('p', get_string('notypes', 'local_accommodations'));
}

// Upload form
echo html_writer::start_tag('form', [
    'method' => 'post',
    'action' => $PAGE->url,
    'enctype' => 'multipart/form-data',
    'class' => 'accommodations-upload-form'
]);

// File upload field
echo html_writer::start_div('form-group');
echo html_writer::tag('label', get_string('csvfile', 'local_accommodations'), ['for' => 'csvfile']);
echo html_writer::empty_tag('input', [
    'type' => 'file',
    'name' => 'userfile',
    'id' => 'csvfile',
    'class' => 'form-control'
]);
echo html_writer::end_div();

// CSV options
echo html_writer::start_div('form-group');
echo html_writer::tag('label', get_string('csvdelimiter', 'local_accommodations'), ['for' => 'delimiter']);
echo html_writer::select(
    [
        'comma' => get_string('comma', 'local_accommodations'),
        'semicolon' => get_string('semicolon', 'local_accommodations'),
        'tab' => get_string('tab', 'local_accommodations')
    ],
    'delimiter',
    'comma',
    false,
    ['class' => 'form-control']
);
echo html_writer::end_div();

// Apply to all checkbox
echo html_writer::start_div('form-group');
echo html_writer::checkbox('applyall', 1, true, get_string('applytoallactivities', 'local_accommodations'), ['class' => 'form-check-input mr-2']);
echo html_writer::end_div();

// Hidden fields
if ($categoryid) {
    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'categoryid',
        'value' => $categoryid
    ]);
} else if ($courseid) {
    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'courseid',
        'value' => $courseid
    ]);
}

echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'sesskey',
    'value' => sesskey()
]);

// Submit button
echo html_writer::tag('button', 
    get_string('upload', 'local_accommodations'),
    ['type' => 'submit', 'class' => 'btn btn-primary']
);

echo html_writer::end_tag('form');

// Display errors if any
if (isset($SESSION->accommodation_upload_errors) && !empty($SESSION->accommodation_upload_errors)) {
    echo $OUTPUT->heading(get_string('uploadexceptions', 'local_accommodations'), 3);
    
    echo html_writer::start_tag('table', ['class' => 'generaltable']);
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', get_string('line', 'local_accommodations'));
    echo html_writer::tag('th', get_string('errors'));
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');
    
    echo html_writer::start_tag('tbody');
    foreach ($SESSION->accommodation_upload_errors as $line => $errors) {
        echo html_writer::start_tag('tr');
        echo html_writer::tag('td', $line);
        echo html_writer::start_tag('td');
        echo html_writer::start_tag('ul');
        foreach ($errors as $error) {
            echo html_writer::tag('li', $error);
        }
        echo html_writer::end_tag('ul');
        echo html_writer::end_tag('td');
        echo html_writer::end_tag('tr');
    }
    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
    
    // Clear the errors from session
    unset($SESSION->accommodation_upload_errors);
}

// Back button
echo html_writer::tag('div',
    $OUTPUT->single_button($returnurl, get_string('back'), 'get'),
    ['class' => 'mt-3']
);

echo $OUTPUT->footer();