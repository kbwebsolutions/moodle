define(['jquery', 'core/str', 'core/notification'], function($, Str, Notification) {
    return {
        /**
         * get_strings - better than core.
         * @param array keyComps - {key, component, param}
         */
        get_strings: function(keyComps) {
            var dfd = $.Deferred();

            Str.get_strings(keyComps).done(function(strings) {
                var stringsByKey = {};
                for (var s in strings) {
                    var string = strings[s];
                    var keyComp = keyComps[s];
                    stringsByKey[keyComp.key] = string;
                }
                dfd.resolve(stringsByKey);
            }).fail(Notification.exception);

            return dfd;
        }
    };
});