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
 * @copyright  2020 Titus Learning by Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery','core/config'], function($,config) {
    return {

        init: function(action, instanceid, contextlevel) {
          function get_assignments(courseid) {
            var dfd = $.Deferred();
            var args = {
              action: 'get_course_assignments',
              courseid: courseid,
             };
             var wwwRoot = config.wwwroot + "/local/commentbank/rest.php";
             $.ajax({
              method: "GET",
              url: wwwRoot,
              data: args,
              success: function(data) {
                dfd.resolve(data).then(
                  function(data) {
                    var $dropdown = $("#id_assignments");
                    Object.keys(data.result).forEach(function(key) {
                      $dropdown.append(new Option(data.result[key].name, data.result[key].id));
                    });
                    $('select#id_assignments').val($('input[name=assignmentid]').val());
                    $('input[name=assignmentid]').val(0);
                  }
                );
              },
              error: function(error) {
                  var message = JSON.parse(error.responseText.substring(0, error.responseText.length-4)).error;
                  alert(message);
              }
          });
          return dfd;
          }
            /* using closest to find enclosing item makes this work with mdl35 and 36/7 */
            $("select#id_coursecategory").closest('#autoselects').children(0).unwrap();
            $("select#id_coursecategory").closest('.fitem').addClass('hidden');
            $("select#id_course").closest('.fitem').addClass('hidden');
            $("select#id_assignments").closest('.fitem').addClass('hidden');

            /*The unwrap removes the surrounding divs and makes the presentation of the form
            more smooth, i.e. it doesn't do that show/hide thing that happens if you just use js */
            var contexts = { CONTEXT_SYSTEM: 10, CONTEXT_COURSECAT: 40, CONTEXT_COURSE: 50, CONTEXT_MODULE: 70 };
          if (action == 'edit' || action == 'delete') {
            // The move option was not in specification. so 0 for Edit and 1 for Copy
            $("input[id^='id_editmode_']").not('[id$="0"]').click(function () {
              $("select#id_context").prop('disabled', false);
              $("select#id_course").prop('disabled', false);
              $("select#id_assignments").prop('disabled', false);
            });
            if (contextlevel == contexts.CONTEXT_COURSECAT) {
              $("select#id_coursecategory").closest('.fitem').addClass('hidden');
              $("select#id_coursecategory").value = instanceid;
            }
            if (contextlevel == contexts.CONTEXT_COURSE) {
              $("select#id_course").closest('.fitem').addClass('hidden');
              $("select#id_assignments").closest('.fitem').addClass('hidden');
              $("select#id_course").parent(0).value = instanceid;
            }
            if (contextlevel == contexts.CONTEXT_MODULE) {
              $("select#id_course").closest('.fitem').removeClass('hidden');
              $("select#id_assignments").closest('.fitem').removeClass('hidden');
              get_assignments($("select#id_course").val());
              $("select#id_context").prop('disabled', true);
              $("select#id_course").prop('disabled', true);
              $("select#id_assignments").prop('disabled', true);

            }
          }
            /* without max-width it goes to width 100% */
            $("#id_context").css({ 'max-width': 'fit-content' });
          $("#id_context").change(function () {
            var selection = $(this).children("option:selected").val();
            if (selection == contexts.CONTEXT_COURSECAT) {
              $("select#id_course").parent(0).closest('.fitem').addClass('hidden');
              $("select#id_coursecategory").closest('.fitem').removeClass('hidden');
              $("select#id_assignments").closest('.fitem').addClass('hidden');

            }
            if (selection == contexts.CONTEXT_SYSTEM) {
              $("select#id_course").closest('.fitem').addClass('hidden');
              $("select#id_coursecategory").closest('.fitem').addClass('hidden');
              $("select#id_assignments").closest('.fitem').addClass('hidden');
            }
            if (selection == contexts.CONTEXT_COURSE) {
              $("select#id_course").closest('.fitem').removeClass('hidden');
              $("select#id_coursecategory").closest('.fitem').addClass('hidden');
              $("select#id_assignments").closest('.fitem').addClass('hidden');
            }
            if (selection == contexts.CONTEXT_MODULE) {
              $("select#id_course").closest('.fitem').removeClass('hidden');
              $("select#id_coursecategory").closest('.fitem').addClass('hidden');
              get_assignments($("select#id_course").val());
              $("select#id_course").prepend(new Option('No Selection', '0')).val(0);
            }

          });
          $("#id_course").change(function () {
            var courseid = $(this).children("option:selected").val();
            var $dropdown = $("#id_assignments");
            $dropdown.empty();

            $dropdown.append(new Option('No Selection', '0'));
            $dropdown.val(0);
            if ($("select#id_context").val() == contexts.CONTEXT_MODULE) {
              $("select#id_assignments").closest('.fitem').removeClass('hidden');
            }
            get_assignments(courseid);
          });
          $("#id_assignments").change(function () {
            $('input[name=assignmentid]').val($("select#id_assignments").val());
          });
        }
    };
});