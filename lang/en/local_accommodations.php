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
 * Language strings for the accommodation management plugin.
 *
 * @package    local_accommodations
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Accommodation Management';
$string['accommodations'] = 'Accommodations';

// Navigation and page titles
$string['manageall'] = 'Manage all accommodations';
$string['courseaccommodations'] = 'Accommodations for {$a}';
$string['categoryaccommodations'] = 'Accommodations for {$a} category';
$string['manageactivities'] = 'Manage activities';
$string['backtocourseaccommodations'] = 'Back to course accommodations';
$string['accommodationsforx'] = 'Accommodations for {$a}';
$string['categoryinfo'] = 'Category information';

// General terms
$string['accommodation'] = 'Accommodation';
$string['accommodationtype'] = 'Accommodation type';
$string['addaccommodation'] = 'Add accommodation';
$string['addaccommodationforx'] = 'Add accommodation for {$a}';
$string['editaccommodation'] = 'Edit accommodation';
$string['editaccommodationforx'] = 'Edit accommodation for {$a}';
$string['existingaccommodations'] = 'Existing accommodations';
$string['noaccommodations'] = 'No accommodations found.';
$string['timeextension'] = 'Time extension (%)';
$string['defaulttimeextension'] = 'Default time extension (%)';
$string['defaulttimeextensiondesc'] = 'Default percentage of additional time to grant for accommodations.';
$string['scope'] = 'Scope';
$string['allcourses'] = 'All courses';
$string['specificcourse'] = 'Specific course';
$string['specificcategory'] = 'Specific category';
$string['daterange'] = 'Date range';
$string['permanent'] = 'Permanent';
$string['notes'] = 'Notes';

// Activity management
$string['activitytoggledesc'] = 'Enable or disable accommodation support for specific activities in this course.';
$string['applytoallactivities'] = 'Apply to all activities';
$string['applytoallactivitiesdesc'] = 'Apply accommodations to all eligible activities in the course';
$string['applytoallcourses'] = 'Apply to all courses';
$string['accommodationsstatus'] = 'Accommodations status';
$string['allactivities'] = 'All activities';
$string['enableaccommodations'] = 'Enable accommodations';
$string['disableaccommodations'] = 'Disable accommodations';
$string['enabled'] = 'Enabled';
$string['disabled'] = 'Disabled';
$string['enable'] = 'Enable';
$string['disable'] = 'Disable';
$string['apply'] = 'Apply';
$string['batchoperations'] = 'Batch operations';
$string['noeligibleactivities'] = 'No eligible activities found in this course.';
$string['accommodationsstatusupdated'] = 'Accommodations {$a} for activity';
$string['statusupdatefailed'] = 'Failed to update accommodation status';
$string['type'] = 'Type';

// User management
$string['selectuser'] = 'Select user...';
$string['selectusers'] = 'Select users';
$string['adduseraccommodation'] = 'Add user accommodation';
$string['searchuser'] = 'Search user';
$string['searchuserhint'] = 'Enter name, username, or email';
$string['searchresults'] = 'Search results';
$string['nousersmatching'] = 'No users matching "{$a}"';
$string['manageaccommodations'] = 'Manage accommodations';
$string['applyaccommodations'] = 'Apply accommodations';

// Category management
$string['categorycoursecount'] = 'Courses in this category (including subcategories): {$a}';
$string['categoryaccommodationscount'] = 'Accommodations in this category: {$a}';

// Accommodation types
$string['managetypes'] = 'Manage accommodation types';
$string['addtype'] = 'Add accommodation type';
$string['edittype'] = 'Edit accommodation type';
$string['typename'] = 'Type name';
$string['typedescription'] = 'Type description';
$string['notypes'] = 'No accommodation types defined yet.';
$string['typenameexists'] = 'An accommodation type with this name already exists.';
$string['typedeleted'] = 'Accommodation type deleted successfully.';
$string['typedeletefailed'] = 'Failed to delete accommodation type. It may still be in use.';
$string['typesaved'] = 'Accommodation type saved successfully.';
$string['existingtypes'] = 'Existing types';
$string['confirmtypedelete'] = 'Are you sure you want to delete the accommodation type "{$a}"?';
$string['availableaccommodationtypes'] = 'Available accommodation types';

// Bulk actions
$string['bulkedit'] = 'Bulk edit accommodations';
$string['bulkeditsuccessx'] = 'Successfully added accommodations for {$a} users.';
$string['userinput'] = 'User selection';
$string['userinput_help'] = 'Enter usernames, IDs, or email addresses separated by commas.';
$string['nousersselected'] = 'No users selected.';

// CSV upload
$string['bulkupload'] = 'Bulk upload accommodations';
$string['uploadcsv'] = 'Upload CSV';
$string['uploadcsvcourse'] = 'Upload accommodations for {$a}';
$string['uploadcsvcategory'] = 'Upload accommodations for {$a} category';
$string['uploadcsvinstructions'] = 'Upload a CSV file containing user accommodations. The file should have the following columns: User Identifier (ID, Username, or Email), Accommodation Type, Time Extension (%), Start Date (optional), End Date (optional), Notes (optional).';
$string['fileformat'] = 'File format';
$string['uploadcsvformat'] = "User Identifier (ID, Username, or Email),Accommodation Type,Time Extension (%),Start Date (optional),End Date (optional),Notes (optional)\nuser1@example.com,Learning Disability,25,2025-01-01,2025-12-31,Extra time for exams\nstudent2,Language Accommodation,15,,,No end date";
$string['downloadtemplate'] = 'Download template';
$string['csvfile'] = 'CSV file';
$string['csvdelimiter'] = 'CSV delimiter';
$string['comma'] = 'Comma (,)';
$string['semicolon'] = 'Semicolon (;)';
$string['tab'] = 'Tab';
$string['upload'] = 'Upload';
$string['uploadexceptions'] = 'Upload errors';
$string['line'] = 'Line';
$string['uploadsuccessx'] = 'Successfully imported {$a} accommodations';
$string['uploaderrorscount'] = '{$a} errors occurred during import. See details below.';
$string['invalidimportid'] = 'Invalid import ID';

// CSV import errors
$string['missinguser'] = 'Missing user identifier';
$string['usernotfound'] = 'User "{$a}" not found';
$string['missingtype'] = 'Missing accommodation type';
$string['typenotfound'] = 'Accommodation type "{$a}" not found';
$string['invalidtimeextension'] = 'Invalid time extension value: {$a}';
$string['invalidstartdate'] = 'Invalid start date format: {$a}';
$string['invalidenddate'] = 'Invalid end date format: {$a}';

// Messages and notifications
$string['accommodationdeleted'] = 'Accommodation deleted successfully.';
$string['accommodationsaved'] = 'Accommodation saved successfully.';
$string['savefailed'] = 'Failed to save accommodation.';
$string['deletefailed'] = 'Failed to delete accommodation.';
$string['confirmdelete'] = 'Are you sure you want to delete the {$a->type} accommodation for {$a->user}?';
$string['accommodationsappliedx'] = 'Applied accommodations to {$a->quizzes} quizzes and {$a->assignments} assignments.';
$string['accommodationsappliedcategoryx'] = 'Applied accommodations to {$a->courses} courses ({$a->quizzes} quizzes and {$a->assignments} assignments).';
$string['enddatebeforestartdate'] = 'End date cannot be before start date.';
$string['timeextensionnegative'] = 'Time extension cannot be negative.';
$string['invalidaccess'] = 'Invalid access attempt.';
$string['notificationsubject'] = 'Accommodations applied to {$a->modulename}: {$a->name}';
$string['notificationbody'] = 'Accommodations have been applied to the {$a->modulename} "{$a->name}" in the course "{$a->course}". {$a->count} students have received time extensions based on their registered accommodations.';

// Help strings
$string['timeextension_help'] = 'The percentage of additional time to grant. For example, 25 means 25% extra time (if a quiz is 60 minutes, the student gets 75 minutes).';
$string['defaulttimeextension_help'] = 'The default percentage of additional time to grant for this accommodation type.';
$string['typename_help'] = 'The name of this accommodation type (e.g., "Learning Disability", "Language Accommodation").';
$string['typedescription_help'] = 'A description of the accommodation type, including any relevant policies or procedures.';

// Settings
$string['generalsettings'] = 'General settings';
$string['notificationsettings'] = 'Notification settings';
$string['displaysettings'] = 'Display settings';
$string['autoapplyaccommodations'] = 'Auto-apply accommodations';
$string['autoapplyaccommodationsdesc'] = 'Automatically apply accommodations when new activities are created.';
$string['notifyapplied'] = 'Notify on application';
$string['notifyapplieddesc'] = 'Send notifications when accommodations are applied to activities.';
$string['notifyteachers'] = 'Notify teachers';
$string['notifyteachersdesc'] = 'Send notifications to teachers when accommodations are applied to their courses.';
$string['notifystudents'] = 'Notify students';
$string['notifystudentsdesc'] = 'Send notifications to students when accommodations are applied to their activities.';
$string['itemsperpage'] = 'Items per page';
$string['itemsperpagedesc'] = 'Number of items to show per page in accommodation listings.';
$string['showindicator'] = 'Show accommodation indicator';
$string['showindicatordesc'] = 'Show an indicator next to user names in participant lists if they have accommodations.';

// Reports
$string['accommodationsreport'] = 'Accommodations report';
$string['reportdesc'] = 'View and export reports on accommodations across the system.';
$string['generatereport'] = 'Generate report';
$string['reporttype'] = 'Report type';
$string['reporttypeusers'] = 'Users with accommodations';
$string['reporttypecourses'] = 'Courses with accommodations';
$string['reporttypehistory'] = 'Accommodation application history';
$string['accommodationhistory'] = 'Accommodation history';

// Scheduled tasks
$string['taskapplyaccommodations'] = 'Apply accommodations to activities';

// Capabilities
$string['accommodations:manage'] = 'Manage accommodations';
$string['accommodations:managesystem'] = 'Manage system-wide accommodations';
$string['accommodations:managecategory'] = 'Manage category accommodations';
$string['accommodations:reportview'] = 'View accommodations reports';
$string['accommodations:configuretypes'] = 'Configure accommodation types';
$string['accommodations:toggleactivity'] = 'Toggle activity accommodation status';
$string['accommodations:bulkmanage'] = 'Bulk manage accommodations';
$string['accommodations:viewown'] = 'View own accommodations';