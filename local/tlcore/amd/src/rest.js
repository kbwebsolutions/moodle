define(['jquery', 'core/notification'], function($, notification) {
    return {

        restUrl: null,

        setRestURL: function(restUrl) {
            this.restUrl = restUrl;
        },

        /**
         *
         * @param string action
         * @param object data
         * @param string method
         * @param function ? customErrorHandler
         * @param ajaxOverlay ajaxOverlay
         * @returns {deferred|boolean}
         */
        call: function(action, data, method, customErrorHandler, ajaxOverlay) {
            if (!this.restUrl) {
                notification.alert('Coding issue: Rest URL has not been set');
            }
            if (!data) {
                data = {};
            }
            data.action = action;

            var notLoggedInError = function() {
                var logInLink = M.cfg.wwwroot + '/login';
                // TODO localise
                var msg = 'It appears that you are not logged in. Please '
                        + '<a target="_blank" href="' + logInLink + '">log in</a> to continue';
                notification.alert('Not logged in',
                    msg, 'OK');
            };

            var onErrorGeneral = function(jqXHR) {

                if (ajaxOverlay) {
                    ajaxOverlay.removeOverlay();
                }

                if (!jqXHR) {
                    jqXHR = {status: 'jqXHR object invalid', responseText: ''};
                }

                window.console.error('error - jqXHR', jqXHR);

                var error, errorcode, stacktrace;

                if (!jqXHR.responseJSON) {
                    error = 'Unknown error';
                    errorcode = 'unknown';
                    stacktrace = 'unknown - possible bad JSON? ' + jqXHR.responseText;
                } else {
                    error = jqXHR.responseJSON.error;
                    errorcode = jqXHR.responseJSON.errorcode;
                    stacktrace = jqXHR.responseJSON.stacktrace;
                }

                if (jqXHR.responseJSON && jqXHR.responseJSON.errorcode && jqXHR.responseJSON.errorcode === 'requireloginerror') {
                    return notLoggedInError();
                }

                var msg = '<div class="ajaxErrors">'
                    + '<div class="ajaxErrorMsg">' + error + '</div>'
                    + '<div class="ajaxErrorStatus">Error status code: ' + jqXHR.status + '</div>'
                    + '<div class="ajaxErrorCode">Error code: ' + errorcode + '</div>'
                    + '<div class="ajaxErrorStackTrace">Stack trace: ' + stacktrace + '</div>'
                    + '</div>';

                // TODO localise Error, OK.
                notification.alert('An error has occurred',
                    msg, 'OK');


            };

            var errorFunction = function(jqXHR) {
                if (typeof(customErrorHandler) === 'function') {
                    if (customErrorHandler(jqXHR)) {
                        return;
                    } else {
                        // If the custom error handler doesn't return true then we go onto call the general error handler.
                        onErrorGeneral(jqXHR);
                    }
                }
            };

            return $.ajax({
                url: this.restUrl,
                data: data,
                method: method,
                error: errorFunction
            }).then(function(data) {
                if (ajaxOverlay) {
                    ajaxOverlay.removeOverlay();
                }
                return data;
            });
        },
        get: function(action, data, error, ajaxOverlay) {
            return this.call(action, data, 'GET', error, ajaxOverlay);
        },
        post: function(action, data, error, ajaxOverlay) {
            return this.call(action, data, 'POST', error, ajaxOverlay);
        },
        put: function(action, data, error, ajaxOverlay) {
            return this.call(action, data, 'PUT', error, ajaxOverlay);
        },
        patch: function(action, data, error, ajaxOverlay) {
            return this.call(action, data, 'PATCH', error, ajaxOverlay);
        },
        delete: function(action, data, error, ajaxOverlay) {
            return this.call(action, data, 'DELETE', error, ajaxOverlay);
        }
    };
});