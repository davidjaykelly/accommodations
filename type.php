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
 * Page for managing accommodation types.
 *
 * @package    local_accommodations
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_accommodations\accommodation_type;
use local_accommodations\form\accommodation_type_edit;

// Parameters
$id = optional_param('id', 0, PARAM_INT);
$delete = optional_param('delete', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

// Set up the page
admin_externalpage_setup('local_accommodations_types');

$returnurl = new moodle_url('/local/accommodations/type.php');
$PAGE->set_url($returnurl);
$PAGE->set_title(get_string('managetypes', 'local_accommodations'));
$PAGE->set_heading(get_string('managetypes', 'local_accommodations'));

// Handle deletion
if ($delete && $id) {
    $type = new accommodation_type($id);
    
    if ($confirm) {
        if ($type->delete()) {
            \core\notification::success(get_string('typedeleted', 'local_accommodations'));
        } else {
            \core\notification::error(get_string('typedeletefailed', 'local_accommodations'));
        }
        redirect($returnurl);
    } else {
        // Show confirmation dialog
        $message = get_string('confirmtypedelete', 'local_accommodations', $type->get_record()->name);
        $continueurl = new moodle_url('/local/accommodations/type.php', [
            'delete' => 1,
            'id' => $id,
            'confirm' => 1,
            'sesskey' => sesskey()
        ]);
        
        echo $OUTPUT->header();
        echo $OUTPUT->confirm($message, $continueurl, $returnurl);
        echo $OUTPUT->footer();
        exit;
    }
}

// Set up the form
$customdata = ['id' => $id];
$form = new accommodation_type_edit(null, $customdata);

// If we're editing an existing type, set the form data
if ($id) {
    $type = new accommodation_type($id);
    $form->set_data($type->get_record());
}

// Process form submissions
if ($form->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $form->get_data()) {
    // Create or update type
    $type = new accommodation_type($data->id);
    
    if ($type->save($data)) {
        \core\notification::success(get_string('typesaved', 'local_accommodations'));
        redirect($returnurl);
    } else {
        \core\notification::error(get_string('savefailed', 'local_accommodations'));
    }
}

// Display the page
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('managetypes', 'local_accommodations'));

// Show the form for adding/editing types
if ($id) {
    echo $OUTPUT->heading(get_string('edittype', 'local_accommodations'), 3);
} else {
    echo $OUTPUT->heading(get_string('addtype', 'local_accommodations'), 3);
}

$form->display();

// Show existing types
echo $OUTPUT->heading(get_string('existingtypes', 'local_accommodations'), 3);

$types = accommodation_type::get_all();

if (empty($types)) {
    echo $OUTPUT->notification(get_string('notypes', 'local_accommodations'), 'info');
} else {
    // Display as table
    $table = new html_table();
    $table->head = [
        get_string('typename', 'local_accommodations'),
        get_string('description'),
        get_string('defaulttimeextension', 'local_accommodations'),
        get_string('actions')
    ];
    $table->attributes['class'] = 'generaltable accommodations-table';
    
    foreach ($types as $type) {
        $record = $type->get_record();
        
        // Create action buttons
        $actions = [];
        
        // Edit action
        $editurl = new moodle_url('/local/accommodations/type.php', ['id' => $record->id]);
        $actions[] = html_writer::link($editurl, $OUTPUT->pix_icon('t/edit', get_string('edit')));
        
        // Delete action
        $deleteurl = new moodle_url('/local/accommodations/type.php', ['delete' => 1, 'id' => $record->id]);
        $actions[] = html_writer::link($deleteurl, $OUTPUT->pix_icon('t/delete', get_string('delete')));
        
        // Add the row to the table
        $row = [
            $record->name,
            $record->description,
            $record->timeextension . '%',
            implode(' ', $actions)
        ];
        
        $table->data[] = $row;
    }
    
    echo html_writer::table($table);
}

echo $OUTPUT->footer();