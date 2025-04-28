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
 * Plugin administration pages settings.
 *
 * @package    local_accommodations
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Create the new settings category
    $ADMIN->add('localplugins', new admin_category('local_accommodations_settings', 
                                                get_string('pluginname', 'local_accommodations')));
    
    // General settings page
    $settings = new admin_settingpage('local_accommodations_general', 
                                    get_string('generalsettings', 'local_accommodations'));
    
    // Add to admin menu tree.
    $ADMIN->add('local_accommodations_settings', $settings);
    
    // Auto-apply accommodations to new activities.
    $settings->add(new admin_setting_configcheckbox('local_accommodations/autoapply',
        get_string('autoapplyaccommodations', 'local_accommodations'),
        get_string('autoapplyaccommodationsdesc', 'local_accommodations'),
        1));
    
    // Default time extension percentage
    $settings->add(new admin_setting_configtext('local_accommodations/defaulttimeextension',
        get_string('defaulttimeextension', 'local_accommodations'),
        get_string('defaulttimeextensiondesc', 'local_accommodations'),
        25, PARAM_INT));
    
    // Notification settings page
    $notifications = new admin_settingpage('local_accommodations_notifications', 
                                        get_string('notificationsettings', 'local_accommodations'));
    $ADMIN->add('local_accommodations_settings', $notifications);
    
    // Send notifications when accommodations are applied.
    $notifications->add(new admin_setting_configcheckbox('local_accommodations/notifyapplied',
        get_string('notifyapplied', 'local_accommodations'),
        get_string('notifyapplieddesc', 'local_accommodations'),
        0));
    
    // Send notifications to teachers.
    $notifications->add(new admin_setting_configcheckbox('local_accommodations/notifyteachers',
        get_string('notifyteachers', 'local_accommodations'),
        get_string('notifyteachersdesc', 'local_accommodations'),
        0));
    
    // Send notifications to students.
    $notifications->add(new admin_setting_configcheckbox('local_accommodations/notifystudents',
        get_string('notifystudents', 'local_accommodations'),
        get_string('notifystudentsdesc', 'local_accommodations'),
        0));
    
    // Display settings page
    $display = new admin_settingpage('local_accommodations_display', 
                                    get_string('displaysettings', 'local_accommodations'));
    $ADMIN->add('local_accommodations_settings', $display);
    
    // Items per page in listings
    $display->add(new admin_setting_configtext('local_accommodations/itemsperpage',
        get_string('itemsperpage', 'local_accommodations'),
        get_string('itemsperpagedesc', 'local_accommodations'),
        20, PARAM_INT));
    
    // Show accommodation indicator in user lists
    $display->add(new admin_setting_configcheckbox('local_accommodations/showindicator',
        get_string('showindicator', 'local_accommodations'),
        get_string('showindicatordesc', 'local_accommodations'),
        1));
    
    // External links
    
    // Add links to manage accommodation types
    $ADMIN->add('local_accommodations_settings', new admin_externalpage('local_accommodations_types',
        get_string('managetypes', 'local_accommodations'),
        new moodle_url('/local/accommodations/type.php')));
    
    // Add link to view accommodations report
    $ADMIN->add('local_accommodations_settings', new admin_externalpage('local_accommodations_report',
        get_string('accommodationsreport', 'local_accommodations'),
        new moodle_url('/local/accommodations/report.php')));
    
    // Add link to bulk upload accommodations
    $ADMIN->add('local_accommodations_settings', new admin_externalpage('local_accommodations_upload',
        get_string('bulkupload', 'local_accommodations'),
        new moodle_url('/local/accommodations/upload.php')));
    
    // Add link to system-wide management 
    $ADMIN->add('local_accommodations_settings', new admin_externalpage('local_accommodations_manage',
        get_string('manageall', 'local_accommodations'),
        new moodle_url('/local/accommodations/index.php')));
}