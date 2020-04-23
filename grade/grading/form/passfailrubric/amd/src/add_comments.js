/**
 * Dialog to show comments for this assignment that have been pulled from local_commentbank
 * and placed in the body of the form in the comment_popup div. The button that is clicked passes
 * in the id of the textfield above it.
 * The user can then click on feedback items displayed and they will be 'pasted' into the
 * comment/remark entry field.
 */
define(['jquery', 'core/modal_factory'], function($, ModalFactory) {



  var remarkfield = '';

  /**
   * Mark the already used comments
   * with css class
   */
  var markSelected = function() {
    var remarks = $(".reusable_remark");
    for (i = 0; i < remarks.length; i++) {
      var remarktext = $(remarks[i]).text();
      var pastetarget = $("#advancedgrading-" + remarkfield);
     // pastetarget  = $(pastetarget);
      /* if this comment is in the remarks box/already used */
      if(pastetarget.val().search(remarktext) > -1 ){
        $(remarks[i]).addClass('reusable_remark_selected');
      } else {
        $(remarks[i]).removeClass('reusable_remark_selected');

      }

    }

  };
    /**
     * Register event listeners for when a comment is clicked.
     *
     * @param {object} root The body of the modal
     */
    var registerEventListeners = function(root) {
        root.on('click', function(e) {
            if ($(e.target).is(".reusable_remark")) {
                e.preventDefault();
                var copytext = (e.target.innerHTML);
                /* name of the comment/remark text box where the text will be 'pasted'*/
                var pastetarget = "#advancedgrading-" + remarkfield;
                pastetarget  = $(pastetarget);
                if(pastetarget.val().search(copytext) < 0 ){
                  /* There does not appear to be a js equivalent of PHP EOL */
                   EOL= "\r\n";
                  var pastetext = $(pastetarget).val() + EOL + copytext;
                  pastetarget.val(pastetext.trim());
                  $(e.target).addClass('reusable_remark_selected');
                  $(e.target).attr("aria-selected", "true");
                } else {
                  $(e.target).removeClass('reusable_remark_selected');
                  // Over write the removed feedback item then trim anything like \r\n
                  pastetarget.val(pastetarget.val().replace(copytext,"").trim());
                }
            }

        });

    };

    return {
        init: function() {
            var trigger = $('#create-modal');
            $(".add_comment").on('click', function(e) {
                ModalFactory.create({
                        title: 'Click text to add to feedback',
                        body: $("#comment_popup")[0].innerHTML,
                        type: ModalFactory.types.DEFAULT
                    }, trigger)
                    .done(function(modal) {
                        remarkfield = e.target.id;
                        var root = modal.getRoot();
                        registerEventListeners(root);
                        modal.show();
                        markSelected(root);
                    });
            });


        }
    };
});