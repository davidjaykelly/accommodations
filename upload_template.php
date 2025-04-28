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
 * Download CSV template for accommodations.
 *
 * @package    local_accommodations
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_login();

use local_accommodations\accommodation_type;

// Check permissions
$systemcontext = context_system::instance();
require_capability('local/accommodations:manage', $systemcontext);

// Set up response
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="accommodations_template.csv"');
header('Pragma: no-cache');

// Create a template with example data
$delimiter = ',';
$enclosure = '"';

// Open output
$fp = fopen('php://output', 'w');

// Headers row
fputcsv($fp, [
    'User Identifier (ID, Username, or Email)',
    'Accommodation Type',
    'Time Extension (%)',
    'Start Date (optional)',
    'End Date (optional)',
    'Notes (optional)'
], $delimiter, $enclosure);

// Get accommodation types for examples
$types = accommodation_type::get_all();
$typenames = [];
foreach ($types as $type) {
    $typenames[] = $type->get_record()->name;
}

// Example rows
for ($i = 1; $i <= 3; $i++) {
    $typename = !empty($typenames) ? $typenames[array_rand($typenames)] : 'Learning Disability';
    $timeextension = rand(10, 50);
    
    // Generate start date between 1 month ago and now
    $startdate = date('Y-m-d', strtotime("-" . rand(0, 30) . " days"));
    
    // Generate end date between now and 6 months in the future
    $enddate = date('Y-m-d', strtotime("+" . rand(30, 180) . " days"));
    
    fputcsv($fp, [
        'student' . $i . '@example.com',
        $typename,
        $timeextension,
        $startdate,
        $enddate,
        'Example accommodation notes for student ' . $i
    ], $delimiter, $enclosure);
}

fclose($fp);
exit;