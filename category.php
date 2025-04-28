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
 * Category level accommodations management.
 *
 * @package    local_accommodations
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_accommodations\accommodation;
use local_accommodations\form\accommodation_edit;
use local_accommodations\form\accommodation_bulk_edit;

// Get parameters
$categoryid = required_param('categoryid', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$userid = optional_param('userid', 0, PARAM_INT);
$accommodationid = optional_param('aid', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 20, PARAM_INT);

// Setup category page
$category = core_course_category::get($categoryid);
$context = context_coursecat::instance($categoryid);

require_login();
require_capability('local/accommodations:managecategory', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/accommodations/category.php', ['categoryid' => $categoryid]));
$PAGE->set_title(get_string('categoryaccommodations', 'local_accommodations', $category->name));
$PAGE->set_heading(get_string('categoryaccommodations', 'local_accommodations', $category->name));
$PAGE->set_pagelayout('admin');

// Add page navigation
$PAGE->navbar->add(get_string('accommodations', 'local_accommodations'));

// Handle actions
if ($action == 'add' && $userid) {
    // Add accommodation for a user
    $returnurl = new moodle_url('/local/accommodations/category.php', ['categoryid' => $categoryid]);
    
    $customdata = [
        'id' => 0,
        'userid' => $userid,
        'categoryid' => $categoryid
    ];
    
    $form = new accommodation_edit(null, $customdata);
    
    if ($form->is_cancelled()) {
        redirect($returnurl);
    } else if ($data = $form->get_data()) {
        // Create accommodation
        $accommodation = new accommodation();
        if ($accommodation->save($data)) {
            \core\notification::success(get_string('accommodationsaved', 'local_accommodations'));
            
            // Apply to all courses if requested
            if (!empty($data->applytoall)) {
                $stats = local_accommodations_apply_to_category($categoryid, true);
                
                $message = get_string('accommodationsappliedcategoryx', 'local_accommodations', [
                    'courses' => $stats['courses'],
                    'quizzes' => $stats['quizzes'],
                    'assignments' => $stats['assignments']
                ]);
                
                \core\notification::success($message);
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
    // Edit accommodation
    $returnurl = new moodle_url('/local/accommodations/category.php', ['categoryid' => $categoryid]);
    
    $accommodation = new accommodation($accommodationid);
    $record = $accommodation->get_record();
    
    // Security check
    if ($record->categoryid != $categoryid) {
        \core\notification::error(get_string('invalidaccess', 'local_accommodations'));
        redirect($returnurl);
    }
    
    $customdata = [
        'id' => $accommodationid,
        'userid' => $record->userid,
        'categoryid' => $categoryid
    ];
    
    $form = new accommodation_edit(null, $customdata);
    $form->set_data($record);
    
    if ($form->is_cancelled()) {
        redirect($returnurl);
    } else if ($data = $form->get_data()) {
        // Update accommodation
        if ($accommodation->save($data)) {
            \core\notification::success(get_string('accommodationsaved', 'local_accommodations'));
            
            // Apply to all courses if requested
            if (!empty($data->applytoall)) {
                $stats = local_accommodations_apply_to_category($categoryid, true);
                
                $message = get_string('accommodationsappliedcategoryx', 'local_accommodations', [
                    'courses' => $stats['courses'],
                    'quizzes' => $stats['quizzes'],
                    'assignments' => $stats['assignments']
                ]);
                
                \core\notification::success($message);
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
    // Delete accommodation
    $returnurl = new moodle_url('/local/accommodations/category.php', ['categoryid' => $categoryid]);
    
    $accommodation = new accommodation($accommodationid);
    $record = $accommodation->get_record();
    
    // Security check
    if ($record->categoryid != $categoryid) {
        \core\notification::error(get_string('invalidaccess', 'local_accommodations'));
        redirect($returnurl);
    }
    
    if ($confirm) {
        // Delete the accommodation
        if ($accommodation->delete()) {
            \core\notification::success(get_string('accommodationdeleted', 'local_accommodations'));
        } else {
            \core\notification::error(get_string('deletefailed', 'local_accommodations'));
        }
        redirect($returnurl);
    } else {
        // Show confirmation dialog
        $user = $DB->get_record('user', ['id' => $record->userid]);
        
        echo $OUTPUT->header();
        echo $OUTPUT->confirm(
            get_string('confirmdelete', 'local_accommodations', [
                'type' => $accommodation->get_type()->get_record()->name,
                'user' => fullname($user)
            ]),
            new moodle_url('/local/accommodations/category.php', [
                'categoryid' => $categoryid,
                'action' => 'delete',
                'aid' => $accommodationid,
                'confirm' => 1
            ]),
            $returnurl
        );
        echo $OUTPUT->footer();
        exit;
    }
    
} else if ($action == 'bulkedit') {
    // Bulk edit accommodations
    $returnurl = new moodle_url('/local/accommodations/category.php', ['categoryid' => $categoryid]);
    
    $customdata = [
        'categoryid' => $categoryid
    ];
    
    $form = new accommodation_bulk_edit(null, $customdata);
    
    if ($form->is_cancelled()) {
        redirect($returnurl);
    } else if ($data = $form->get_data()) {
        // Process the bulk edit
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
            $accommodationdata->categoryid = $categoryid;
            $accommodationdata->courseid = null;
            $accommodationdata->startdate = !empty($data->startdate) ? $data->startdate : null;
            $accommodationdata->enddate = !empty($data->enddate) ? $data->enddate : null;
            $accommodationdata->notes = $data->notes;
            
            if ($accommodation->save($accommodationdata)) {
                $count++;
            }
        }
        
        // Apply to all activities in this category if requested
        if (!empty($data->applytoall) && $count > 0) {
            $stats = local_accommodations_apply_to_category($categoryid, true);
            
            $message = get_string('accommodationsappliedcategoryx', 'local_accommodations', [
                'courses' => $stats['courses'],
                'quizzes' => $stats['quizzes'],
                'assignments' => $stats['assignments']
            ]);
            
            \core\notification::success($message);
        }
        
        \core\notification::success(get_string('bulkeditsuccessx', 'local_accommodations', $count));
        redirect($returnurl);
    }
    
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('bulkedit', 'local_accommodations'));
    
    $form->display();
    
    echo $OUTPUT->footer();
    exit;
    
} else if ($action == 'apply') {
    // Apply accommodations to all courses in the category
    $stats = local_accommodations_apply_to_category($categoryid, true);
    
    $message = get_string('accommodationsappliedcategoryx', 'local_accommodations', [
        'courses' => $stats['courses'],
        'quizzes' => $stats['quizzes'],
        'assignments' => $stats['assignments']
    ]);
    
    \core\notification::success($message);
    redirect(new moodle_url('/local/accommodations/category.php', ['categoryid' => $categoryid]));
}

// Get all accommodations in this category
$sql = "SELECT a.*, t.name as typename, u.firstname, u.lastname, u.email 
        FROM {local_accommodations_profiles} a
        JOIN {local_accommodations_types} t ON a.typeid = t.id
        JOIN {user} u ON a.userid = u.id
        WHERE a.categoryid = :categoryid
        ORDER BY u.lastname, u.firstname";
$params = ['categoryid' => $categoryid];

$accommodationscount = $DB->count_records_sql("SELECT COUNT(*) FROM {local_accommodations_profiles} WHERE categoryid = :categoryid", $params);
$accommodations = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);

// Output the page
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('categoryaccommodations', 'local_accommodations', $category->name));

// Action buttons
echo html_writer::start_div('accommodation-actions mb-3');

// Apply accommodations button
echo $OUTPUT->single_button(
    new moodle_url('/local/accommodations/category.php', [
        'categoryid' => $categoryid, 
        'action' => 'apply'
    ]),
    get_string('applytoallcourses', 'local_accommodations'),
    'get',
    ['class' => 'btn btn-secondary mr-2']
);

// Bulk edit button
echo $OUTPUT->single_button(
    new moodle_url('/local/accommodations/category.php', [
        'categoryid' => $categoryid, 
        'action' => 'bulkedit'
    ]),
    get_string('bulkedit', 'local_accommodations'),
    'get',
    ['class' => 'btn btn-secondary mr-2']
);

// Upload CSV button
echo $OUTPUT->single_button(
    new moodle_url('/local/accommodations/upload.php', [
        'categoryid' => $categoryid
    ]),
    get_string('uploadcsv', 'local_accommodations'),
    'get',
    ['class' => 'btn btn-secondary']
);

echo html_writer::end_div();

// Category information
echo html_writer::start_div('category-info mb-3');
echo html_writer::tag('h4', get_string('categoryinfo', 'local_accommodations'));

// Get course count in this category and subcategories
$subcategories = $category->get_all_children_ids();
$allcategories = array_merge([$categoryid], $subcategories);

$coursecountquery = "SELECT COUNT(*) FROM {course} WHERE category IN (" . implode(',', $allcategories) . ")";
$coursecount = $DB->count_records_sql($coursecountquery);

echo html_writer::tag('p', get_string('categorycoursecount', 'local_accommodations', $coursecount));
echo html_writer::tag('p', get_string('categoryaccommodationscount', 'local_accommodations', $accommodationscount));

echo html_writer::end_div();

// Show accommodations
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
        
        // User column with link
        $userlink = html_writer::link(
            new moodle_url('/user/view.php', ['id' => $accommodation->userid]),
            $fullname
        );
        
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
        $editurl = new moodle_url('/local/accommodations/category.php', [
            'categoryid' => $categoryid,
            'action' => 'edit',
            'aid' => $accommodation->id
        ]);
        $actions[] = html_writer::link($editurl, $OUTPUT->pix_icon('t/edit', get_string('edit')));
        
        // Delete action
        $deleteurl = new moodle_url('/local/accommodations/category.php', [
            'categoryid' => $categoryid,
            'action' => 'delete',
            'aid' => $accommodation->id
        ]);
        $actions[] = html_writer::link($deleteurl, $OUTPUT->pix_icon('t/delete', get_string('delete')));
        
        // Add the row to the table
        $row = [
            $userlink,
            $accommodation->typename,
            $accommodation->timeextension . '%',
            $daterange,
            implode(' ', $actions)
        ];
        
        $table->data[] = $row;
    }
    
    echo html_writer::table($table);
    
    // Pagination
    echo $OUTPUT->paging_bar($accommodationscount, $page, $perpage, $PAGE->url);
}

// User search for adding accommodations
echo $OUTPUT->heading(get_string('adduseraccommodation', 'local_accommodations'), 3);

// User selector form
echo html_writer::start_tag('form', [
    'action' => new moodle_url('/local/accommodations/category.php'),
    'method' => 'get',
    'class' => 'form-inline mb-3'
]);

echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'categoryid',
    'value' => $categoryid
]);

echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'action',
    'value' => 'add'
]);

// User search field
echo html_writer::tag('label', get_string('searchuser', 'local_accommodations') . ': ', [
    'for' => 'usersearch',
    'class' => 'mr-2'
]);

echo html_writer::empty_tag('input', [
    'type' => 'text',
    'id' => 'usersearch',
    'name' => 'usersearch',
    'class' => 'form-control mr-2',
    'placeholder' => get_string('searchuserhint', 'local_accommodations')
]);

echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'value' => get_string('search'),
    'class' => 'btn btn-secondary'
]);

echo html_writer::end_tag('form');

// Process user search if submitted
$usersearch = optional_param('usersearch', '', PARAM_TEXT);
if (!empty($usersearch)) {
    echo $OUTPUT->heading(get_string('searchresults', 'local_accommodations'), 4);
    
    // Search for users matching the search term
    $searchparams = [
        'search' => $usersearch,
        'page' => 0,
        'perpage' => 10
    ];
    
    $users = get_users(true, $usersearch, true, [], '', '', '', '', '', '*', '');
    
    if (empty($users)) {
        echo $OUTPUT->notification(get_string('nousersmatching', 'local_accommodations', $usersearch), 'info');
    } else {
        // Show matching users in a table
        $usertable = new html_table();
        $usertable->head = [
            get_string('fullname'),
            get_string('email'),
            get_string('actions')
        ];
        $usertable->attributes['class'] = 'generaltable accommodations-table';
        
        foreach ($users as $user) {
            // Add accommodation action
            $addurl = new moodle_url('/local/accommodations/category.php', [
                'categoryid' => $categoryid,
                'action' => 'add',
                'userid' => $user->id
            ]);
            
            $actions = html_writer::link($addurl, 
                get_string('addaccommodation', 'local_accommodations'),
                ['class' => 'btn btn-sm btn-primary']);
            
            // Add row to table
            $usertable->data[] = [
                fullname($user),
                $user->email,
                $actions
            ];
        }
        
        echo html_writer::table($usertable);
    }
}

echo $OUTPUT->footer();