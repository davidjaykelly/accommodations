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
 * Accommodation management renderer.
 *
 * @package    local_accommodations
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_accommodations\output;

defined('MOODLE_INTERNAL') || die;

use plugin_renderer_base;
use renderable;
use local_accommodations\accommodation;
use local_accommodations\accommodation_type;

/**
 * Renderer for accommodation management plugin.
 */
class renderer extends plugin_renderer_base {
    
    /**
     * Renders an accommodation summary for a user.
     *
     * @param int $userid The user ID
     * @param int|null $courseid Optional course ID
     * @param int|null $categoryid Optional category ID
     * @return string HTML output
     */
    public function render_user_accommodations_summary($userid, $courseid = null, $categoryid = null) {
        $accommodations = local_accommodations_get_user_accommodations($userid, null, $courseid, $categoryid);
        
        if (empty($accommodations)) {
            return html_writer::tag('div',
                get_string('noaccommodations', 'local_accommodations'),
                ['class' => 'alert alert-info']
            );
        }
        
        $output = html_writer::start_tag('div', ['class' => 'accommodations-summary']);
        
        foreach ($accommodations as $accommodation) {
            $output .= $this->render_accommodation_card($accommodation);
        }
        
        $output .= html_writer::end_tag('div');
        
        return $output;
    }
    
    /**
     * Render a single accommodation as a card.
     *
     * @param \stdClass $accommodation Accommodation data
     * @return string HTML output
     */
    public function render_accommodation_card($accommodation) {
        $output = html_writer::start_tag('div', ['class' => 'card mb-3']);
        
        // Card header
        $output .= html_writer::tag('div', 
            html_writer::tag('h5', $accommodation->typename, ['class' => 'mb-0']),
            ['class' => 'card-header d-flex justify-content-between align-items-center']
        );
        
        // Card body
        $output .= html_writer::start_tag('div', ['class' => 'card-body']);
        
        // Time extension
        $output .= html_writer::tag('p',
            get_string('timeextension', 'local_accommodations') . ': ' .
            html_writer::tag('span', $accommodation->timeextension . '%', ['class' => 'badge badge-info']),
            ['class' => 'mb-2']
        );
        
        // Scope
        $scope = '';
        if (!empty($accommodation->courseid)) {
            global $DB;
            $course = $DB->get_record('course', ['id' => $accommodation->courseid]);
            if ($course) {
                $scope = get_string('coursescope', 'local_accommodations', $course->shortname);
            } else {
                $scope = get_string('specificcourse', 'local_accommodations');
            }
        } else if (!empty($accommodation->categoryid)) {
            global $DB;
            $category = $DB->get_record('course_categories', ['id' => $accommodation->categoryid]);
            if ($category) {
                $scope = get_string('categoryscope', 'local_accommodations', $category->name);
            } else {
                $scope = get_string('specificcategory', 'local_accommodations');
            }
        } else {
            $scope = get_string('allcourses', 'local_accommodations');
        }
        
        $output .= html_writer::tag('p',
            get_string('scope', 'local_accommodations') . ': ' . $scope,
            ['class' => 'mb-2']
        );
        
        // Date range
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
        
        $output .= html_writer::tag('p',
            get_string('daterange', 'local_accommodations') . ': ' . $daterange,
            ['class' => 'mb-2']
        );
        
        // Notes
        if (!empty($accommodation->notes)) {
            $output .= html_writer::tag('p',
                get_string('notes', 'local_accommodations') . ': ' . $accommodation->notes,
                ['class' => 'mb-0']
            );
        }
        
        $output .= html_writer::end_tag('div'); // End card-body
        $output .= html_writer::end_tag('div'); // End card
        
        return $output;
    }
    
    /**
     * Render category tree for navigation.
     *
     * @param array $categories Array of category data
     * @return string HTML output
     */
    public function render_category_tree($categories) {
        if (empty($categories)) {
            return '';
        }
        
        $output = html_writer::start_tag('ul', ['class' => 'category-tree list-unstyled']);
        
        foreach ($categories as $category) {
            $output .= $this->render_category_node($category);
        }
        
        $output .= html_writer::end_tag('ul');
        
        return $output;
    }
    
    /**
     * Render a single category node in the tree.
     *
     * @param array $category Category data
     * @return string HTML output
     */
    private function render_category_node($category) {
        $haschildren = !empty($category['children']);
        $nodeclass = $haschildren ? 'has-children' : '';
        $output = html_writer::start_tag('li', ['class' => $nodeclass]);
        
        // Category link
        $caturl = new \moodle_url('/local/accommodations/category.php', [
            'categoryid' => $category['id']
        ]);
        
        $output .= html_writer::link(
            $caturl,
            $category['name'] . ' (' . $category['coursecount'] . ')',
            ['class' => 'category-link']
        );
        
        // Render children if any
        if ($haschildren) {
            $output .= html_writer::start_tag('ul', ['class' => 'list-unstyled ml-4']);
            
            foreach ($category['children'] as $child) {
                $output .= $this->render_category_node($child);
            }
            
            $output .= html_writer::end_tag('ul');
        }
        
        $output .= html_writer::end_tag('li');
        
        return $output;
    }
    
    /**
     * Render accommodation statistics for reporting.
     *
     * @param array $stats Statistics data
     * @return string HTML output
     */
    public function render_accommodation_stats($stats) {
        $output = html_writer::start_tag('div', ['class' => 'accommodation-stats card-deck']);
        
        // Users with accommodations
        $output .= html_writer::start_tag('div', ['class' => 'card text-center']);
        $output .= html_writer::tag('div', get_string('usercount', 'local_accommodations'), ['class' => 'card-header']);
        $output .= html_writer::start_tag('div', ['class' => 'card-body']);
        $output .= html_writer::tag('h1', $stats['users'], ['class' => 'display-4']);
        $output .= html_writer::tag('p', get_string('userswithaccommodations', 'local_accommodations'), ['class' => 'card-text']);
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');
        
        // Accommodations count
        $output .= html_writer::start_tag('div', ['class' => 'card text-center']);
        $output .= html_writer::tag('div', get_string('accommodationcount', 'local_accommodations'), ['class' => 'card-header']);
        $output .= html_writer::start_tag('div', ['class' => 'card-body']);
        $output .= html_writer::tag('h1', $stats['accommodations'], ['class' => 'display-4']);
        $output .= html_writer::tag('p', get_string('totalaccommodations', 'local_accommodations'), ['class' => 'card-text']);
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');
        
        // Activities with accommodations
        $output .= html_writer::start_tag('div', ['class' => 'card text-center']);
        $output .= html_writer::tag('div', get_string('activitycount', 'local_accommodations'), ['class' => 'card-header']);
        $output .= html_writer::start_tag('div', ['class' => 'card-body']);
        $output .= html_writer::tag('h1', $stats['activities'], ['class' => 'display-4']);
        $output .= html_writer::tag('p', get_string('activitieswithaccommodations', 'local_accommodations'), ['class' => 'card-text']);
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');
        
        $output .= html_writer::end_tag('div');
        
        return $output;
    }
    
    /**
     * Render a user search box with AJAX support.
     *
     * @param int|null $courseid Optional course ID for context
     * @param int|null $categoryid Optional category ID for context
     * @return string HTML output
     */
    public function render_user_selector($courseid = null, $categoryid = null) {
        global $PAGE;
        
        // Add required JS
        $PAGE->requires->js_call_amd('local_accommodations/user_selector', 'init');
        
        $formaction = '';
        $hiddenfields = '';
        
        if ($courseid) {
            $formaction = new \moodle_url('/local/accommodations/course.php');
            $hiddenfields .= html_writer::empty_tag('input', [
                'type' => 'hidden',
                'name' => 'courseid',
                'value' => $courseid
            ]);
        } else if ($categoryid) {
            $formaction = new \moodle_url('/local/accommodations/category.php');
            $hiddenfields .= html_writer::empty_tag('input', [
                'type' => 'hidden',
                'name' => 'categoryid',
                'value' => $categoryid
            ]);
        } else {
            $formaction = new \moodle_url('/local/accommodations/user.php');
        }
        
        $hiddenfields .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'action',
            'value' => 'add'
        ]);
        
        $output = html_writer::start_tag('form', [
            'action' => $formaction,
            'method' => 'get',
            'class' => 'user-selector-form'
        ]);
        
        $output .= $hiddenfields;
        
        $output .= html_writer::start_div('input-group');
        
        $output .= html_writer::empty_tag('input', [
            'type' => 'text',
            'name' => 'usersearch',
            'id' => 'accommodation-user-search',
            'class' => 'form-control',
            'placeholder' => get_string('searchuser', 'local_accommodations'),
            'autocomplete' => 'off'
        ]);
        
        $output .= html_writer::start_div('input-group-append');
        $output .= html_writer::tag('button',
            get_string('search'),
            ['type' => 'submit', 'class' => 'btn btn-secondary']
        );
        $output .= html_writer::end_div(); // End input-group-append
        
        $output .= html_writer::end_div(); // End input-group
        
        // Hidden field for selected user ID
        $output .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'userid',
            'id' => 'accommodation-selected-userid',
            'value' => ''
        ]);
        
        // User search results container
        $output .= html_writer::div('', 'user-search-results', ['id' => 'accommodation-user-results']);
        
        $output .= html_writer::end_tag('form');
        
        return $output;
    }
    
    /**
     * Render activity toggle switches.
     *
     * @param array $activities List of activity data
     * @param int $courseid Course ID
     * @return string HTML output
     */
    public function render_activity_toggles($activities, $courseid) {
        global $PAGE;
        
        // Add required JS
        $PAGE->requires->js_call_amd('local_accommodations/activity_toggle', 'init');
        
        if (empty($activities)) {
            return html_writer::div(
                get_string('noeligibleactivities', 'local_accommodations'),
                'alert alert-info'
            );
        }
        
        $output = html_writer::start_tag('div', ['class' => 'activity-toggles']);
        
        $output .= html_writer::start_tag('table', ['class' => 'table table-hover']);
        $output .= html_writer::start_tag('thead');
        $output .= html_writer::start_tag('tr');
        $output .= html_writer::tag('th', get_string('activity'));
        $output .= html_writer::tag('th', get_string('type', 'local_accommodations'));
        $output .= html_writer::tag('th', get_string('accommodationsstatus', 'local_accommodations'));
        $output .= html_writer::end_tag('tr');
        $output .= html_writer::end_tag('thead');
        
        $output .= html_writer::start_tag('tbody');
        
        foreach ($activities as $activity) {
            $output .= html_writer::start_tag('tr');
            
            // Activity name with icon
            $output .= html_writer::start_tag('td');
            $output .= html_writer::link(
                $activity->url,
                $this->output->pix_icon($activity->icon, '') . ' ' . $activity->name
            );
            $output .= html_writer::end_tag('td');
            
            // Activity type
            $output .= html_writer::tag('td', get_string('modulename', 'mod_' . $activity->type));
            
            // Toggle switch
            $checked = !$activity->disabled;
            $toggleid = 'activity-toggle-' . $activity->cmid;
            
            $output .= html_writer::start_tag('td');
            $output .= html_writer::start_div('custom-control custom-switch');
            $output .= html_writer::empty_tag('input', [
                'type' => 'checkbox',
                'class' => 'custom-control-input activity-toggle',
                'id' => $toggleid,
                'data-cmid' => $activity->cmid,
                'data-courseid' => $courseid,
                'checked' => $checked ? 'checked' : null
            ]);
            $output .= html_writer::tag('label', '', [
                'class' => 'custom-control-label',
                'for' => $toggleid
            ]);
            $output .= html_writer::end_div();
            $output .= html_writer::end_tag('td');
            
            $output .= html_writer::end_tag('tr');
        }
        
        $output .= html_writer::end_tag('tbody');
        $output .= html_writer::end_tag('table');
        
        $output .= html_writer::end_tag('div');
        
        return $output;
    }
}