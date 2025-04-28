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
 * Library functions for the accommodation management plugin.
 *
 * @package    local_accommodations
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Extend the navigation to include accommodation management in the user administration.
 *
 * @param global_navigation $navigation
 */
function local_accommodations_extend_navigation(global_navigation $navigation) {
    global $PAGE, $USER, $CFG;

    if (!has_capability('local/accommodations:manage', context_system::instance())) {
        return;
    }
    
    // Add a node to the user's profile page.
    if ($PAGE->context->contextlevel == CONTEXT_USER && $PAGE->context->instanceid != $USER->id) {
        $url = new moodle_url('/local/accommodations/user.php', ['id' => $PAGE->context->instanceid]);
        $node = navigation_node::create(
            get_string('manageaccommodations', 'local_accommodations'),
            $url,
            navigation_node::TYPE_SETTING,
            null,
            'accommodationsmanage',
            new pix_icon('i/settings', '')
        );
        
        if ($usernode = $navigation->find('useraccount', navigation_node::TYPE_SETTING)) {
            $usernode->add_node($node);
        }
    }
}

/**
 * Adds accommodation navigation items to the course's secondary navigation.
 * 
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course object
 * @param context $context The course context
 */
function local_accommodations_extend_navigation_course(navigation_node $navigation, stdClass $course, context $context) {
    if (has_capability('local/accommodations:manage', $context)) {
        $url = new moodle_url('/local/accommodations/course.php', ['courseid' => $course->id]);
        
        // Add to "More" section in secondary navigation
        $node = navigation_node::create(
            get_string('accommodations', 'local_accommodations'),
            $url,
            navigation_node::TYPE_SETTING,
            null,
            'accommodations',
            new pix_icon('i/calendar', '')
        );
        
        $morenode = $navigation->get('morenavigationnode');
        if ($morenode) {
            $morenode->add_node($node);
        } else {
            $navigation->add_node($node);
        }
    }
}

/**
 * Extend the category navigation with the accommodation management.
 * 
 * @param navigation_node $navigation The navigation node to extend
 * @param context_coursecat $context The category context
 */
function local_accommodations_extend_navigation_category_settings(navigation_node $navigation, context_coursecat $context) {
    if (has_capability('local/accommodations:manage', $context)) {
        $categoryid = $context->instanceid;
        $url = new moodle_url('/local/accommodations/category.php', ['categoryid' => $categoryid]);
        
        $node = navigation_node::create(
            get_string('accommodations', 'local_accommodations'),
            $url,
            navigation_node::TYPE_SETTING,
            null,
            'categoryaccommodations',
            new pix_icon('i/calendar', '')
        );
        
        $navigation->add_node($node);
    }
}

/**
 * Get a user's accommodations.
 *
 * @param int $userid The user ID to get accommodations for
 * @param int|null $typeid Optional accommodation type ID filter
 * @param int|null $courseid Optional course ID filter
 * @param int|null $categoryid Optional category ID filter
 * @return array Array of accommodation records
 */
function local_accommodations_get_user_accommodations($userid, $typeid = null, $courseid = null, $categoryid = null) {
    global $DB;
    
    $params = ['userid' => $userid];
    $sql = "SELECT a.*, t.name as typename, t.timeextension as defaulttimeextension 
            FROM {local_accommodations_profiles} a
            JOIN {local_accommodations_types} t ON a.typeid = t.id
            WHERE a.userid = :userid";
            
    if ($typeid) {
        $sql .= " AND a.typeid = :typeid";
        $params['typeid'] = $typeid;
    }
    
    if ($courseid) {
        $sql .= " AND (a.courseid = :courseid OR a.courseid IS NULL)";
        $params['courseid'] = $courseid;
    }
    
    if ($categoryid) {
        $sql .= " AND (a.categoryid = :categoryid OR a.categoryid IS NULL)";
        $params['categoryid'] = $categoryid;
    }
    
    return $DB->get_records_sql($sql, $params);
}

/**
 * Apply accommodation time extensions to a quiz.
 *
 * @param int $quizid The quiz ID
 * @param bool $override Whether to override existing user overrides
 * @return int Number of accommodations applied
 */
function local_accommodations_apply_to_quiz($quizid, $override = false) {
    global $DB;
    
    // Get quiz details
    $quiz = $DB->get_record('quiz', ['id' => $quizid], '*', MUST_EXIST);
    
    // Check if this quiz should have accommodations applied
    $cm = get_coursemodule_from_instance('quiz', $quizid, $quiz->course, false, MUST_EXIST);
    $disabled = $DB->get_field('local_accommodations_activity_status', 'disabled', ['cmid' => $cm->id]);
    
    if ($disabled) {
        return 0; // Accommodations disabled for this activity
    }
    
    // Get current overrides
    $existingoverrides = $DB->get_records('quiz_overrides', 
        ['quiz' => $quizid, 'groupid' => null], '', 'userid, timeclose');
    
    // Get all users enrolled in the course with accommodations
    $context = context_course::instance($quiz->course);
    $enrolledusers = get_enrolled_users($context);
    
    $count = 0;
    
    foreach ($enrolledusers as $user) {
        // Get user's accommodations
        $accommodations = local_accommodations_get_user_accommodations($user->id, null, $quiz->course);
        
        // Skip if no accommodations
        if (empty($accommodations)) {
            continue;
        }
        
        // Get the highest time extension
        $timeextension = 0;
        foreach ($accommodations as $accommodation) {
            if ($accommodation->timeextension > $timeextension) {
                $timeextension = $accommodation->timeextension;
            }
        }
        
        // Skip if no time extension
        if ($timeextension <= 0) {
            continue;
        }
        
        // Check if override already exists
        $existingoverride = isset($existingoverrides[$user->id]) ? $existingoverrides[$user->id] : false;
        
        if ($existingoverride && !$override) {
            // Skip if we're not overriding
            continue;
        }
        
        // Calculate extended time
        $extratime = $quiz->timelimit * ($timeextension / 100);
        $newtimelimit = $quiz->timelimit + $extratime;
        
        // Create or update override
        $overridedata = [
            'quiz' => $quizid,
            'userid' => $user->id,
            'timelimit' => $newtimelimit
        ];
        
        if ($existingoverride) {
            $overridedata['id'] = $existingoverride->id;
            $DB->update_record('quiz_overrides', $overridedata);
        } else {
            $DB->insert_record('quiz_overrides', $overridedata);
        }
        
        $count++;
    }
    
    return $count;
}

/**
 * Apply accommodation time extensions to an assignment.
 *
 * @param int $assignid The assignment ID
 * @param bool $override Whether to override existing user overrides
 * @return int Number of accommodations applied
 */
function local_accommodations_apply_to_assignment($assignid, $override = false) {
    global $DB;
    
    // Get assignment details
    $assign = $DB->get_record('assign', ['id' => $assignid], '*', MUST_EXIST);
    
    // Check if this assignment should have accommodations applied
    $cm = get_coursemodule_from_instance('assign', $assignid, $assign->course, false, MUST_EXIST);
    $disabled = $DB->get_field('local_accommodations_activity_status', 'disabled', ['cmid' => $cm->id]);
    
    if ($disabled) {
        return 0; // Accommodations disabled for this activity
    }
    
    // Get current overrides
    $existingoverrides = $DB->get_records('assign_overrides', 
        ['assignid' => $assignid, 'groupid' => null], '', 'userid, duedate');
    
    // Get all users enrolled in the course with accommodations
    $context = context_course::instance($assign->course);
    $enrolledusers = get_enrolled_users($context);
    
    $count = 0;
    
    foreach ($enrolledusers as $user) {
        // Get user's accommodations
        $accommodations = local_accommodations_get_user_accommodations($user->id, null, $assign->course);
        
        // Skip if no accommodations
        if (empty($accommodations)) {
            continue;
        }
        
        // Get the highest time extension
        $timeextension = 0;
        foreach ($accommodations as $accommodation) {
            if ($accommodation->timeextension > $timeextension) {
                $timeextension = $accommodation->timeextension;
            }
        }
        
        // Skip if no time extension
        if ($timeextension <= 0) {
            continue;
        }
        
        // Check if override already exists
        $existingoverride = isset($existingoverrides[$user->id]) ? $existingoverrides[$user->id] : false;
        
        if ($existingoverride && !$override) {
            // Skip if we're not overriding
            continue;
        }
        
        if ($assign->duedate > 0) {
            // Calculate extended time (as seconds)
            $extension = round($assign->duedate * ($timeextension / 100));
            $newduedate = $assign->duedate + $extension;
            
            // Create or update override
            $overridedata = [
                'assignid' => $assignid,
                'userid' => $user->id,
                'duedate' => $newduedate
            ];
            
            if ($existingoverride) {
                $overridedata['id'] = $existingoverride->id;
                $DB->update_record('assign_overrides', $overridedata);
            } else {
                $DB->insert_record('assign_overrides', $overridedata);
            }
            
            $count++;
        }
    }
    
    return $count;
}

/**
 * Apply all accommodations across a course.
 *
 * @param int $courseid The course ID
 * @param bool $override Whether to override existing user overrides
 * @return array Stats of applied accommodations
 */
function local_accommodations_apply_to_course($courseid, $override = false) {
    global $DB;
    
    $stats = [
        'quizzes' => 0,
        'assignments' => 0
    ];
    
    // Apply to all quizzes in the course
    $quizzes = $DB->get_records('quiz', ['course' => $courseid]);
    foreach ($quizzes as $quiz) {
        $stats['quizzes'] += local_accommodations_apply_to_quiz($quiz->id, $override);
    }
    
    // Apply to all assignments in the course
    $assignments = $DB->get_records('assign', ['course' => $courseid]);
    foreach ($assignments as $assign) {
        $stats['assignments'] += local_accommodations_apply_to_assignment($assign->id, $override);
    }
    
    return $stats;
}

/**
 * Apply accommodations to all courses in a category and its subcategories.
 *
 * @param int $categoryid The category ID
 * @param bool $override Whether to override existing user overrides
 * @return array Stats of applied accommodations
 */
function local_accommodations_apply_to_category($categoryid, $override = false) {
    global $DB;
    
    $stats = [
        'courses' => 0,
        'quizzes' => 0,
        'assignments' => 0
    ];
    
    // Get all categories (including subcategories)
    $categories = [$categoryid];
    $subcategories = core_course_category::get($categoryid)->get_all_children_ids();
    $categories = array_merge($categories, $subcategories);
    
    // Apply to all courses in these categories
    foreach ($categories as $catid) {
        $courses = $DB->get_records('course', ['category' => $catid]);
        foreach ($courses as $course) {
            $coursestats = local_accommodations_apply_to_course($course->id, $override);
            $stats['quizzes'] += $coursestats['quizzes'];
            $stats['assignments'] += $coursestats['assignments'];
            
            if ($coursestats['quizzes'] > 0 || $coursestats['assignments'] > 0) {
                $stats['courses']++;
            }
        }
    }
    
    return $stats;
}

/**
 * Get child categories recursively for a given category
 *
 * @param int $categoryid The category ID
 * @return array Array of category objects with id, name, and path
 */
function local_accommodations_get_child_categories($categoryid) {
    $category = core_course_category::get($categoryid);
    $children = $category->get_children();
    
    $result = [];
    foreach ($children as $child) {
        $childdata = [
            'id' => $child->id,
            'name' => $child->name,
            'path' => $child->path,
            'coursecount' => $child->coursecount,
            'children' => local_accommodations_get_child_categories($child->id)
        ];
        $result[] = $childdata;
    }
    
    return $result;
}

/**
 * Toggle the accommodation status for a specific activity.
 *
 * @param int $cmid The course module ID
 * @param bool $disabled Whether accommodations should be disabled
 * @return bool Success
 */
function local_accommodations_toggle_activity($cmid, $disabled) {
    global $DB;
    
    // Check if a record already exists
    $existing = $DB->get_record('local_accommodations_activity_status', ['cmid' => $cmid]);
    
    if ($existing) {
        $existing->disabled = $disabled ? 1 : 0;
        return $DB->update_record('local_accommodations_activity_status', $existing);
    } else {
        $record = new stdClass();
        $record->cmid = $cmid;
        $record->disabled = $disabled ? 1 : 0;
        return $DB->insert_record('local_accommodations_activity_status', $record) > 0;
    }
}
