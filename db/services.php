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
 * External services definition for accommodations.
 *
 * @package    local_accommodations
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_accommodations_toggle_activity' => [
        'classname' => 'local_accommodations\external\accommodation_api',
        'methodname' => 'toggle_activity',
        'description' => 'Toggle accommodation status for an activity',
        'type' => 'write',
        'capabilities' => 'local/accommodations:toggleactivity',
        'ajax' => true
    ],
    'local_accommodations_bulk_action' => [
        'classname' => 'local_accommodations\external\accommodation_api',
        'methodname' => 'bulk_action',
        'description' => 'Perform bulk action on accommodations',
        'type' => 'write',
        'capabilities' => 'local/accommodations:managesystem',
        'ajax' => true
    ]
];

// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = [
    'Accommodation Management' => [
        'functions' => [
            'local_accommodations_toggle_activity',
            'local_accommodations_bulk_action'
        ],
        'restrictedusers' => 0,
        'enabled' => 1,
    ]
];