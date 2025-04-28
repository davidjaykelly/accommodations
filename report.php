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
 * Accommodation reports.
 *
 * @package    local_accommodations
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// Parameters
$reporttype = optional_param('type', 'users', PARAM_ALPHA);
$courseid = optional_param('courseid', 0, PARAM_INT);
$categoryid = optional_param('categoryid', 0, PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);

// Set up the page
$context = context_system::instance();
require_login();
require_capability('local/accommodations:reportview', $context);

admin_externalpage_setup('local_accommodations_report');
$PAGE->set_url(new moodle_url('/local/accommodations/report.php', ['type' => $reporttype]));
$PAGE->set_title(get_string('accommodationsreport', 'local_accommodations'));
$PAGE->set_heading(get_string('accommodationsreport', 'local_accommodations'));

// Add required JavaScript for charts
$PAGE->requires->js_call_amd('core/chartjs', 'init');

// Get report data based on type
$reportdata = [];
$chartdata = [];

switch ($reporttype) {
    case 'users':
        // Report on users with accommodations
        $reportdata = get_users_report_data($courseid, $categoryid);
        $chartdata = get_users_chart_data($reportdata);
        break;
        
    case 'courses':
        // Report on courses with accommodations
        $reportdata = get_courses_report_data($categoryid);
        $chartdata = get_courses_chart_data($reportdata);
        break;
        
    case 'history':
        // Report on accommodation application history
        $reportdata = get_history_report_data($courseid, $categoryid);
        $chartdata = get_history_chart_data($reportdata);
        break;
        
    default:
        // Default to users report
        $reporttype = 'users';
        $reportdata = get_users_report_data($courseid, $categoryid);
        $chartdata = get_users_chart_data($reportdata);
}

// Output page
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('accommodationsreport', 'local_accommodations'));

// Show report type tabs
$tabs = [
    new tabobject('users', new moodle_url('/local/accommodations/report.php', ['type' => 'users']),
                 get_string('reporttypeusers', 'local_accommodations')),
    new tabobject('courses', new moodle_url('/local/accommodations/report.php', ['type' => 'courses']),
                 get_string('reporttypecourses', 'local_accommodations')),
    new tabobject('history', new moodle_url('/local/accommodations/report.php', ['type' => 'history']),
                 get_string('reporttypehistory', 'local_accommodations'))
];

echo $OUTPUT->tabtree($tabs, $reporttype);

// Show filter form
echo html_writer::start_tag('form', ['class' => 'report-filters', 'method' => 'get']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'type', 'value' => $reporttype]);

echo html_writer::start_div('form-group row');
echo html_writer::tag('label', get_string('category'), ['class' => 'col-sm-2 col-form-label']);
echo html_writer::start_div('col-sm-4');

// Category selector
$categories = core_course_category::make_categories_list();
echo html_writer::select($categories, 'categoryid', $categoryid, ['' => get_string('all')], ['class' => 'form-control']);
echo html_writer::end_div();

// Course selector (if category is selected)
if ($categoryid) {
    echo html_writer::tag('label', get_string('course'), ['class' => 'col-sm-2 col-form-label']);
    echo html_writer::start_div('col-sm-4');
    
    $coursesInCategory = get_courses_in_category($categoryid);
    echo html_writer::select($coursesInCategory, 'courseid', $courseid, ['' => get_string('all')], ['class' => 'form-control']);
    
    echo html_writer::end_div();
}

echo html_writer::end_div(); // End form-group

echo html_writer::start_div('form-group row');
echo html_writer::start_div('col-sm-8 offset-sm-2');
echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'value' => get_string('generatereport', 'local_accommodations'),
    'class' => 'btn btn-primary mr-2'
]);

// Download buttons
echo html_writer::link(
    new moodle_url('/local/accommodations/report.php', [
        'type' => $reporttype,
        'categoryid' => $categoryid,
        'courseid' => $courseid,
        'download' => 'csv'
    ]),
    get_string('downloadcsv', 'tool_uploaduser'),
    ['class' => 'btn btn-secondary']
);

echo html_writer::end_div();
echo html_writer::end_div(); // End form-group

echo html_writer::end_tag('form');

// Display chart
echo html_writer::start_div('report-chart-container');
echo $chartdata['chart'];
echo html_writer::end_div();

// Display report data in a table
echo html_writer::start_div('report-data-container');
echo $reportdata['table'];
echo html_writer::end_div();

echo $OUTPUT->footer();

/**
 * Get users report data.
 *
 * @param int $courseid Optional course ID
 * @param int $categoryid Optional category ID
 * @return array
 */
function get_users_report_data($courseid = 0, $categoryid = 0) {
    global $DB;
    
    $params = [];
    $sql = "SELECT u.id, u.firstname, u.lastname, u.email, 
                   COUNT(DISTINCT a.id) as accommodationcount,
                   MAX(a.timeextension) as maxtimeextension,
                   STRING_AGG(DISTINCT t.name, ', ') as accommodationtypes
            FROM {user} u
            JOIN {local_accommodations_profiles} a ON a.userid = u.id
            JOIN {local_accommodations_types} t ON a.typeid = t.id
            WHERE 1=1";
    
    if ($courseid) {
        $sql .= " AND (a.courseid = :courseid OR a.courseid IS NULL)";
        $params['courseid'] = $courseid;
    }
    
    if ($categoryid) {
        $sql .= " AND (a.categoryid = :categoryid OR a.categoryid IS NULL)";
        $params['categoryid'] = $categoryid;
    }
    
    $sql .= " GROUP BY u.id, u.firstname, u.lastname, u.email
              ORDER BY accommodationcount DESC, u.lastname, u.firstname";
    
    $users = $DB->get_records_sql($sql, $params);
    
    // Build table
    $table = new html_table();
    $table->head = [
        get_string('fullname'),
        get_string('email'),
        get_string('accommodationcount', 'local_accommodations'),
        get_string('maxtimeextension', 'local_accommodations'),
        get_string('accommodationtypes', 'local_accommodations')
    ];
    $table->attributes['class'] = 'generaltable accommodations-report-table';
    
    foreach ($users as $user) {
        $namelink = html_writer::link(
            new moodle_url('/user/view.php', ['id' => $user->id]),
            fullname($user)
        );
        
        $row = [
            $namelink,
            $user->email,
            $user->accommodationcount,
            $user->maxtimeextension . '%',
            $user->accommodationtypes
        ];
        
        $table->data[] = $row;
    }
    
    return [
        'users' => $users,
        'table' => html_writer::table($table)
    ];
}

/**
 * Get chart data for users report.
 *
 * @param array $reportdata Report data from get_users_report_data()
 * @return array
 */
function get_users_chart_data($reportdata) {
    global $OUTPUT;
    
    // Group users by accommodation count
    $countGroups = [];
    foreach ($reportdata['users'] as $user) {
        $count = $user->accommodationcount;
        if (!isset($countGroups[$count])) {
            $countGroups[$count] = 0;
        }
        $countGroups[$count]++;
    }
    
    // Sort by count
    ksort($countGroups);
    
    // Prepare chart data
    $labels = [];
    $dataset = [];
    
    foreach ($countGroups as $count => $userCount) {
        $labels[] = get_string('userswithnaccommodations', 'local_accommodations', $count);
        $dataset[] = $userCount;
    }
    
    // Create chart
    $chart = new \core\chart_pie();
    $chart->set_title(get_string('usersbyaccommodationcount', 'local_accommodations'));
    
    $series = new \core\chart_series(get_string('users'), $dataset);
    $chart->add_series($series);
    
    $chart->set_labels($labels);
    
    return [
        'chart' => $OUTPUT->render_chart($chart, false)
    ];
}

/**
 * Get courses report data.
 *
 * @param int $categoryid Optional category ID
 * @return array
 */
function get_courses_report_data($categoryid = 0) {
    global $DB;
    
    $params = [];
    $sql = "SELECT c.id, c.fullname, c.shortname, c.category,
                   COUNT(DISTINCT a.id) as accommodationcount,
                   COUNT(DISTINCT a.userid) as usercount
            FROM {course} c
            JOIN {local_accommodations_profiles} a ON a.courseid = c.id
            WHERE c.id <> :siteid";
    
    $params['siteid'] = SITEID;
    
    if ($categoryid) {
        $sql .= " AND c.category = :categoryid";
        $params['categoryid'] = $categoryid;
    }
    
    $sql .= " GROUP BY c.id, c.fullname, c.shortname, c.category
              ORDER BY usercount DESC, c.fullname";
    
    $courses = $DB->get_records_sql($sql, $params);
    
    // Add courses with users that have global accommodations
    $globalSql = "SELECT DISTINCT c.id, c.fullname, c.shortname, c.category
                 FROM {course} c
                 JOIN {enrol} e ON e.courseid = c.id
                 JOIN {user_enrolments} ue ON ue.enrolid = e.id
                 JOIN {local_accommodations_profiles} a ON a.userid = ue.userid
                 WHERE c.id <> :siteid
                 AND a.courseid IS NULL";
                 
    if ($categoryid) {
        $globalSql .= " AND c.category = :categoryid";
    }
    
    $globalCourses = $DB->get_records_sql($globalSql, $params);
    
    foreach ($globalCourses as $course) {
        if (!isset($courses[$course->id])) {
            // Count users with global accommodations in this course
            $userCountSql = "SELECT COUNT(DISTINCT a.userid) as usercount
                             FROM {enrol} e
                             JOIN {user_enrolments} ue ON ue.enrolid = e.id
                             JOIN {local_accommodations_profiles} a ON a.userid = ue.userid
                             WHERE e.courseid = :courseid
                             AND a.courseid IS NULL";
            
            $userCount = $DB->get_field_sql($userCountSql, ['courseid' => $course->id]);
            
            $course->accommodationcount = 0;
            $course->usercount = $userCount;
            $courses[$course->id] = $course;
        }
    }
    
    // Build table
    $table = new html_table();
    $table->head = [
        get_string('course'),
        get_string('category'),
        get_string('userscount', 'local_accommodations'),
        get_string('accommodationscount', 'local_accommodations')
    ];
    $table->attributes['class'] = 'generaltable accommodations-report-table';
    
    foreach ($courses as $course) {
        $courselink = html_writer::link(
            new moodle_url('/course/view.php', ['id' => $course->id]),
            $course->fullname
        );
        
        $category = core_course_category::get($course->category, IGNORE_MISSING);
        $categoryName = $category ? $category->name : '';
        
        $row = [
            $courselink,
            $categoryName,
            $course->usercount,
            $course->accommodationcount
        ];
        
        $table->data[] = $row;
    }
    
    return [
        'courses' => $courses,
        'table' => html_writer::table($table)
    ];
}

/**
 * Get chart data for courses report.
 *
 * @param array $reportdata Report data from get_courses_report_data()
 * @return array
 */
function get_courses_chart_data($reportdata) {
    global $OUTPUT;
    
    // Group courses by user count
    $userCountGroups = [
        '0' => 0,
        '1-5' => 0,
        '6-10' => 0,
        '11-20' => 0,
        '21+' => 0
    ];
    
    foreach ($reportdata['courses'] as $course) {
        $count = $course->usercount;
        
        if ($count == 0) {
            $userCountGroups['0']++;
        } else if ($count <= 5) {
            $userCountGroups['1-5']++;
        } else if ($count <= 10) {
            $userCountGroups['6-10']++;
        } else if ($count <= 20) {
            $userCountGroups['11-20']++;
        } else {
            $userCountGroups['21+']++;
        }
    }
    
    // Prepare chart data
    $labels = array_keys($userCountGroups);
    $dataset = array_values($userCountGroups);
    
    // Create chart
    $chart = new \core\chart_bar();
    $chart->set_title(get_string('coursesbystudentcount', 'local_accommodations'));
    
    $series = new \core\chart_series(get_string('courses'), $dataset);
    $chart->add_series($series);
    
    $chart->set_labels($labels);
    
    return [
        'chart' => $OUTPUT->render_chart($chart, false)
    ];
}

/**
 * Get accommodation history report data.
 *
 * @param int $courseid Optional course ID
 * @param int $categoryid Optional category ID
 * @return array
 */
function get_history_report_data($courseid = 0, $categoryid = 0) {
    global $DB;
    
    $params = [];
    $sql = "SELECT h.id, h.userid, h.courseid, h.cmid, h.modulename, h.moduleinstance,
                   h.timeextension, h.originaltime, h.extendedtime, h.applied, h.timecreated,
                   u.firstname, u.lastname, u.email,
                   c.fullname as coursename, c.shortname
            FROM {local_accommodations_history} h
            JOIN {user} u ON h.userid = u.id
            JOIN {course} c ON h.courseid = c.id
            WHERE 1=1";
    
    if ($courseid) {
        $sql .= " AND h.courseid = :courseid";
        $params['courseid'] = $courseid;
    }
    
    if ($categoryid) {
        $sql .= " AND c.category = :categoryid";
        $params['categoryid'] = $categoryid;
    }
    
    $sql .= " ORDER BY h.timecreated DESC, c.fullname, u.lastname, u.firstname";
    
    $history = $DB->get_records_sql($sql, $params);
    
    // Build table
    $table = new html_table();
    $table->head = [
        get_string('date'),
        get_string('user'),
        get_string('course'),
        get_string('activity'),
        get_string('timeextension', 'local_accommodations'),
        get_string('applied', 'local_accommodations')
    ];
    $table->attributes['class'] = 'generaltable accommodations-report-table';
    
    foreach ($history as $record) {
        $userlink = html_writer::link(
            new moodle_url('/user/view.php', ['id' => $record->userid]),
            fullname($record)
        );
        
        $courselink = html_writer::link(
            new moodle_url('/course/view.php', ['id' => $record->courseid]),
            $record->shortname
        );
        
        // Get activity name
        $activity = '';
        if ($record->cmid) {
            $cm = get_coursemodule_from_id($record->modulename, $record->cmid, $record->courseid, false, IGNORE_MISSING);
            if ($cm) {
                $activity = html_writer::link(
                    new moodle_url('/mod/' . $record->modulename . '/view.php', ['id' => $record->cmid]),
                    $cm->name
                );
            } else {
                $activity = get_string('modulename', 'mod_' . $record->modulename) . ' (deleted)';
            }
        }
        
        $applied = $record->applied ? 
                  html_writer::span(get_string('yes'), 'badge badge-success') : 
                  html_writer::span(get_string('no'), 'badge badge-secondary');
        
        $row = [
            userdate($record->timecreated),
            $userlink,
            $courselink,
            $activity,
            $record->timeextension . '%',
            $applied
        ];
        
        $table->data[] = $row;
    }
    
    return [
        'history' => $history,
        'table' => html_writer::table($table)
    ];
}

/**
 * Get chart data for history report.
 *
 * @param array $reportdata Report data from get_history_report_data()
 * @return array
 */
function get_history_chart_data($reportdata) {
    global $OUTPUT;
    
    // Group history by month
    $monthGroups = [];
    $moduleGroups = [
        'quiz' => 0,
        'assign' => 0
    ];
    
    foreach ($reportdata['history'] as $record) {
        $month = date('Y-m', $record->timecreated);
        
        if (!isset($monthGroups[$month])) {
            $monthGroups[$month] = 0;
        }
        
        $monthGroups[$month]++;
        
        // Count by module type
        if (isset($moduleGroups[$record->modulename])) {
            $moduleGroups[$record->modulename]++;
        }
    }
    
    // Sort by month
    ksort($monthGroups);
    
    // Prepare time series chart data
    $timeLabels = array_keys($monthGroups);
    $timeDataset = array_values($monthGroups);
    
    // Create time series chart
    $timeChart = new \core\chart_line();
    $timeChart->set_title(get_string('accommodationsovertime', 'local_accommodations'));
    
    $timeSeries = new \core\chart_series(get_string('accommodations', 'local_accommodations'), $timeDataset);
    $timeChart->add_series($timeSeries);
    
    $timeChart->set_labels($timeLabels);
    
    // Create module type chart
    $moduleChart = new \core\chart_pie();
    $moduleChart->set_title(get_string('accommodationsbymodule', 'local_accommodations'));
    
    $moduleLabels = [];
    $moduleDataset = [];
    
    foreach ($moduleGroups as $module => $count) {
        $moduleLabels[] = get_string('modulename', 'mod_' . $module);
        $moduleDataset[] = $count;
    }
    
    $moduleSeries = new \core\chart_series(get_string('modules'), $moduleDataset);
    $moduleChart->add_series($moduleSeries);
    
    $moduleChart->set_labels($moduleLabels);
    
    // Combine charts in a grid
    $output = html_writer::div($OUTPUT->render_chart($timeChart, false), 'mb-4');
    $output .= html_writer::div($OUTPUT->render_chart($moduleChart, false), 'mb-4');
    
    return [
        'chart' => $output
    ];
}

/**
 * Get courses in a category.
 *
 * @param int $categoryid Category ID
 * @return array Course ID => Course name
 */
function get_courses_in_category($categoryid) {
    global $DB;
    
    $courses = $DB->get_records('course', ['category' => $categoryid], 'fullname', 'id, fullname');
    $courseList = [];
    
    foreach ($courses as $course) {
        $courseList[$course->id] = $course->fullname;
    }
    
    return $courseList;
}