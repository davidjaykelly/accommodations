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
 * Admin functionality for accommodations.
 *
 * @module     local_accommodations/accommodation_admin
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/modal_factory', 'core/modal_events', 'core/str', 'core/ajax', 'core/notification'],
    function($, ModalFactory, ModalEvents, Str, Ajax, Notification) {
        /**
         * Initialize the admin functionality.
         */
        var init = function() {
            // Initialize delete confirmation dialogs
            initDeleteConfirmation();

            // Initialize bulk selection
            initBulkSelection();

            // Initialize category tree expansion
            initCategoryTree();
        };

        /**
         * Initialize delete confirmation dialogs.
         */
        function initDeleteConfirmation() {
            // Handle delete links
            $(document).on('click', '.delete-accommodation', function(e) {
                e.preventDefault();

                var deleteUrl = $(this).attr('href');
                var typeName = $(this).data('type');
                var userName = $(this).data('user');

                // Get confirmation strings
                Str.get_string('confirmdelete', 'local_accommodations', {
                    type: typeName,
                    user: userName
                }).then(function(confirmText) {
                    return ModalFactory.create({
                        type: ModalFactory.types.SAVE_CANCEL,
                        title: Str.get_string('confirm', 'moodle'),
                        body: confirmText
                    });
                }).then(function(modal) {
                    modal.setSaveButtonText(Str.get_string('delete', 'moodle'));

                    // Handle delete button click
                    modal.getRoot().on(ModalEvents.save, function() {
                        window.location.href = deleteUrl;
                    });

                    modal.show();

                    return modal;
                }).catch(Notification.exception);
            });
        }

        /**
         * Initialize bulk selection functionality.
         */
        function initBulkSelection() {
            // Handle select all checkbox
            $('#select-all-accommodations').on('change', function() {
                var checked = $(this).prop('checked');
                $('.accommodation-checkbox').prop('checked', checked);
                updateBulkActionButtons();
            });

            // Handle individual checkboxes
            $(document).on('change', '.accommodation-checkbox', function() {
                updateBulkActionButtons();

                // Update select all checkbox
                var allChecked = $('.accommodation-checkbox:checked').length === $('.accommodation-checkbox').length;
                $('#select-all-accommodations').prop('checked', allChecked);
            });

            // Handle bulk action button clicks
            $('.bulk-action-button').on('click', function(e) {
                e.preventDefault();

                var action = $(this).data('action');
                var selectedIds = getSelectedAccommodationIds();

                if (selectedIds.length === 0) {
                    Str.get_string('noselecteditems', 'moodle').then(function(message) {
                        Notification.addNotification({
                            message: message,
                            type: 'info'
                        });
                        return;
                    }).catch(Notification.exception);
                    return;
                }

                // Perform the bulk action
                performBulkAction(action, selectedIds);
            });
        }

        /**
         * Get selected accommodation IDs.
         *
         * @return {Array} Array of selected accommodation IDs
         */
        function getSelectedAccommodationIds() {
            var selectedIds = [];

            $('.accommodation-checkbox:checked').each(function() {
                selectedIds.push($(this).val());
            });

            return selectedIds;
        }

        /**
         * Perform bulk action on selected accommodations.
         *
         * @param {string} action The action to perform
         * @param {Array} ids Array of accommodation IDs
         */
        function performBulkAction(action, ids) {
            var promises = Ajax.call([{
                methodname: 'local_accommodations_bulk_action',
                args: {
                    action: action,
                    ids: ids
                }
            }]);

            promises[0].then(function(result) {
                if (result.success) {
                    Notification.addNotification({
                        message: result.message,
                        type: 'success'
                    });

                    // Reload page to reflect changes
                    window.location.reload();
                } else {
                    Notification.addNotification({
                        message: result.message,
                        type: 'error'
                    });
                }

                return;
            }).catch(Notification.exception);
        }

        /**
         * Update bulk action button states.
         */
        function updateBulkActionButtons() {
            var selectedCount = $('.accommodation-checkbox:checked').length;

            if (selectedCount > 0) {
                $('.bulk-action-button').removeClass('disabled');

                // Update the button text to show count
                $('.bulk-action-button').each(function() {
                    var originalText = $(this).data('original-text') || $(this).text();

                    // Store original text if not already stored
                    if (!$(this).data('original-text')) {
                        $(this).data('original-text', originalText);
                    }

                    $(this).text(originalText + ' (' + selectedCount + ')');
                });
            } else {
                $('.bulk-action-button').addClass('disabled');

                // Reset button text
                $('.bulk-action-button').each(function() {
                    var originalText = $(this).data('original-text');
                    if (originalText) {
                        $(this).text(originalText);
                    }
                });
            }
        }

        /**
         * Initialize category tree expansion.
         */
        function initCategoryTree() {
            // Handle category tree expansion
            $(document).on('click', '.category-tree .has-children > .category-link', function(e) {
                e.preventDefault();

                var li = $(this).parent();
                li.toggleClass('expanded');

                var ul = li.find('> ul');
                if (li.hasClass('expanded')) {
                    ul.slideDown();
                } else {
                    ul.slideUp();
                }
            });

            // Initialize batch action for activities
            $('#batch-apply-button').on('click', function() {
                var activityType = $('#batch-activity-type').val();
                var action = $('#batch-action').val();

                // Get all toggle checkboxes matching the filter
                var toggles = [];

                if (activityType === 'all') {
                    toggles = $('.activity-toggle');
                } else {
                    toggles = $('.activity-toggle[data-type="' + activityType + '"]');
                }

                // Apply the action to all toggles
                var checked = (action === 'enable');
                toggles.prop('checked', checked).trigger('change');

                // Show a notification
                var messageKey = checked ? 'enabledallaccommodations' : 'disabledallaccommodations';
                Str.get_string(messageKey, 'local_accommodations').then(function(message) {
                    Notification.addNotification({
                        message: message,
                        type: 'success'
                    });
                    return;
                }).catch(Notification.exception);
            });

            // Initialize CSV file upload preview
            $('#accommodations-csv-file').on('change', function() {
                var file = this.files[0];
                if (file) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        var contents = e.target.result;
                        var lines = contents.split('\n');

                        // Show preview of first few lines
                        var preview = '';
                        for (var i = 0; i < Math.min(5, lines.length); i++) {
                            preview += lines[i] + '\n';
                        }

                        $('#csv-preview').text(preview);
                    };
                    reader.readAsText(file);
                }
            });
        }

        return {
            init: init
        };
    });