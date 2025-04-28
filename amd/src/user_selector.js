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
 * User selector for accommodations.
 *
 * @module     local_accommodations/user_selector
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/str', 'core/notification'], function($, Ajax, Str, Notification) {
    /**
     * Initialize the user selector.
     */
    var init = function() {
        // Elements
        var searchInput = $('#accommodation-user-search');
        var resultsContainer = $('#accommodation-user-results');
        var selectedUserField = $('#accommodation-selected-userid');

        if (searchInput.length === 0) {
            return;
        }

        // Minimum search length
        var minLength = 3;

        // Get course and category context
        var courseId = $('input[name="courseid"]').val() || 0;
        var categoryId = $('input[name="categoryid"]').val() || 0;

        // Handle search input
        searchInput.on('keyup', function() {
            var searchTerm = $(this).val().trim();

            // Clear results if search term is too short
            if (searchTerm.length < minLength) {
                resultsContainer.empty();
                return;
            }

            // Get search results
            searchUsers(searchTerm, courseId, categoryId).then(function(users) {
                displayResults(users);
            }).catch(Notification.exception);
        });

        /**
         * Search for users.
         *
         * @param {string} searchTerm The search term
         * @param {number} courseId Optional course ID
         * @param {number} categoryId Optional category ID
         * @return {Promise} Promise resolved with users array
         */
        function searchUsers(searchTerm, courseId, categoryId) {
            var promises = Ajax.call([{
                methodname: 'core_user_search_identity',
                args: {
                    query: searchTerm,
                    courseid: courseId > 0 ? courseId : undefined,
                    categoryId: courseId > 0 ? categoryId : undefined,
                }
            }]);

            return promises[0];
        }

        /**
         * Display search results.
         *
         * @param {Array} users Array of user objects
         */
        function displayResults(users) {
            resultsContainer.empty();

            if (users.length === 0) {
                Str.get_string('nousersmatching', 'local_accommodations', searchInput.val()).then(function(message) {
                    resultsContainer.html('<div class="alert alert-info">' + message + '</div>');
                    return;
                }).catch(Notification.exception);
            }

            var resultsList = $('<ul class="list-group user-search-results"></ul>');

            users.forEach(function(user) {
                var listItem = $('<li class="list-group-item user-result" data-id="' + user.id + '">' +
                    '<img src="' + user.profileimageurlsmall + '" class="user-picture mr-2" alt="" />' +
                    user.fullname + ' (' + user.email + ')' +
                    '</li>');
                resultsList.append(listItem);
            });

            resultsContainer.append(resultsList);

            // Handle user selection
            $('.user-result').on('click', function() {
                var userId = $(this).data('id');
                var userName = $(this).text();

                // Set the selected user ID
                selectedUserField.val(userId);

                // Update the search input with selected user name
                searchInput.val(userName);

                // Clear the results
                resultsContainer.empty();

                // Submit the form
                searchInput.closest('form').submit();
            });
        }
    };

    return {
        init: init
    };
});