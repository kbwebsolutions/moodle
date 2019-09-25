define(['jquery', 'core/ajax', 'core/templates', 'core/notification'], function($, ajax, templates, notification) {
    return {
        init: function () {
            // add click handler to create alert to show message id to be liked
            $('[data-region="comment-posts"] .like').on('click', function () {
                var msgID = $(this).data('id');
                alert("This post is to be liked " + msgID);
            });
            // add click handler to create alert to show message id to be deleted
            $('[data-region="comment-posts"] .delete').on('click', function () {
                var msgID = $(this).data('id');
                alert("This post is to be deleted " + msgID);
            });
        }
    };
});