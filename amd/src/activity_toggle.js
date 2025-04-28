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
 * Activity toggle for accommodations.
 *
 * @module     local_accommodations/activity_toggle
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/str', 'core/notification'], function($, Ajax, Str, Notification) {
    /**
     * Initialize the activity toggles.
     */
    var init = function() {
        // Handle toggle changes
        $(document).on('change', '.activity-toggle', function() {
            var toggle = $(this);
            var cmid = toggle.data('cmid');
            var courseid = toggle.data('courseid');
            var status = toggle.prop('checked') ? 0 : 1; // Status = 1 means disabled

            // Disable toggle while processing
            toggle.prop('disabled', true);

            // Update the status via AJAX
            updateActivityStatus(cmid, courseid, status).then(function(success) {
                if (success) {
                    // Show success notification
                    return Str.get_string(status ? 'disabled' : 'enabled', 'local_accommodations').then(function(statusText) {
                        return Str.get_string(
                            'accommodationsstatusupdated',
                            'local_accommodations',
                            statusText).then(function(message) {
                            Notification.addNotification({
                                message: message,
                                type: 'success'
                            });
                        });
                    });
                } else {
                    // Revert toggle if failed
                    toggle.prop('checked', !toggle.prop('checked'));

                    // Show error notification
                    return Str.get_string('statusupdatefailed', 'local_accommodations').then(function(message) {
                        Notification.addNotification({
                            message: message,
                            type: 'error'
                        });
                    });
                }
            }).catch(function(error) {
                // Revert toggle if error
                toggle.prop('checked', !toggle.prop('checked'));
                Notification.exception(error);
            }).always(function() {
                // Re-enable toggle
                toggle.prop('disabled', false);
            });
        });

        /**
         * Update activity accommodation status.
         *
         * @param {number} cmid Course module ID
         * @param {number} courseid Course ID
         * @param {number} status Status (0 = enabled, 1 = disabled)
         * @return {Promise} Promise resolved with success boolean
         */
        function updateActivityStatus(cmid, courseid, status) {
            var promises = Ajax.call([{
                methodname: 'local_accommodations_toggle_activity',
                args: {
                    cmid: cmid,
                    courseid: courseid,
                    status: status
                }
            }]);

            return promises[0];
        }
    };

    return {
        init: init
    };
});