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
     * Register event listeners for when a comment is clicked.
     *
     * @param {object} root The body of the modal
     */
    var registerEventListeners = function(root) {
        root.on('click', function(e) {
            if ($(e.target).is(".reusable_remark")){
                e.preventDefault();
                /* should copytext be trimmed? */
                var copytext = (e.target.innerHTML);
                /* name of the comment/remark text box where the text will be 'pasted'*/
                var pastetarget = "#advancedgrading-" + remarkfield;
                /* There does not appear to be a js equivalent of PHP EOL */
                var pastetext = $(pastetarget).val() + copytext + "\r\n";
                $(pastetarget).val(pastetext);
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
                    });
            });
        }
    };
});
