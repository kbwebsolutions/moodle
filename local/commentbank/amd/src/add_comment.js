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
 * JavaScript code for  local_commentbank form
 *
 * @package    local_commentbank
 * @copyright  2019 Titus Learning by Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery'], function($) {
    return {
        init: function(action, instanceid, contextlevel) {
            /* using closest to find enclosing item makes this work with mdl35 and 36/7 */
            $("select#id_coursecategory").closest('#autoselects').children(0).unwrap();
            $("select#id_coursecategory").closest('.fitem').addClass('hidden');
            $("select#id_course").closest('.fitem').addClass('hidden');
            /*The unwrap removes the surrounding divs and makes the presentation of the form
            more smooth, i.e. it doesn't do that show/hide thing that happens if you just use js */
            var contexts = { CONTEXT_SYSTEM: 10, CONTEXT_COURSECAT: 40, CONTEXT_COURSE: 50 };
            if (action == 'edit' || action == 'delete') {
                if (contextlevel == contexts.CONTEXT_COURSECAT) {
                    $("select#id_coursecategory").closest('.fitem').addClass('hidden');
                    $("select#id_coursecategory").value = instanceid;
                }
                if (contextlevel == contexts.CONTEXT_COURSE) {
                    $("select#id_course").closest('.fitem').addClass('hidden');
                    $("select#id_course").parent(0).value = instanceid;
                }
            }
            /* without max-width it goes to width 100% */
            $("#id_context").css({ 'max-width': 'fit-content' });
            $("#id_context").change(function() {
                var selection = $(this).children("option:selected").val();
                if (selection == contexts.CONTEXT_COURSECAT) {
                    $("select#id_course").parent(0).closest('.fitem').addClass('hidden');
                    $("select#id_coursecategory").closest('.fitem').removeClass('hidden');
                }
                if (selection == contexts.CONTEXT_COURSE) {
                    $("select#id_course").closest('.fitem').removeClass('hidden');
                    $("select#id_coursecategory").closest('.fitem').addClass('hidden');
                }
                if (selection == contexts.CONTEXT_SYSTEM) {
                    $("select#id_course").closest('.fitem').addClass('hidden');
                    $("select#id_coursecategory").closest('.fitem').addClass('hidden');
                }
            });
        }
    };
});