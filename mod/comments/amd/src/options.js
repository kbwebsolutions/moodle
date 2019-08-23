
define(['jquery', 'core/ajax', 'core/templates', 'core/notification'], function($, ajax, templates, notification) {
    return {
        remove: function() {
            // Add a click handler to the delete button
            $('[data-region="comment-posts"] #remove').on('click', function () {
                alert("This post is to be deleted");
            });
        }
    };
});