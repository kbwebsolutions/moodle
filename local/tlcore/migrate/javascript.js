(function() {
  'use strict';

  angular.module('tlcore', [
    'tlcore.config',
    'tlcore.event',
    'tlcore.loading',
    'tlcore.modal',
    'tlcore.util'
  ]);
})();
(function() {
  'use strict';

  angular.module('tlcore.config', []);
})();
(function() {
  'use strict';

  angular.module('tlcore.event', []);
})();
(function() {
  'use strict';

  angular.module('tlcore.modal', []);
})();
(function() {
  'use strict';

  angular.module('tlcore.loading', [
    'tlcore.event'
  ]);
})();
(function() {
  'use strict';

  angular.module('tlcore.util', []);
})();
(function() {
  'use strict';

  angular.module('tlcore.config')
    .provider('Config', Config);

  /**
   * Simple bucket for sharing properties across apps.
   */
  Config.$inject = [
    // dependencies
  ];
  function Config(
    // dependencies
  ) {
    var provider = {};

    provider.$get = function() {
      return provider;
    };

    return provider;
  }
})();
(function() {
  'use strict';

  angular.module('tlcore.event')
    .service('Event', Event);

  Event.$inject = [
    '$log',
    'Config'
  ];
  function Event(
    $log,
    Config
  ) {

    var service = this;
    var relay = {};

    // send thing:base
    // - calls listener of thing:* and thing:base
    // 
    // send thing:*
    // - calls listener of thing:*, thing:base, thing:face
    // 

    // listen to thing:*
    // listen to thing:base
    // listen to thing:face

    service.listen = function(message, callback, uid) {
      var messages = [];

      if (message instanceof Array) {
        messages = message;
      } else {
        messages.push(message);
      }

      for (var i in messages) {
        message = messages[i];
        if (!relay[message]) relay[message] = {};

        // overwrite single instance
        if (uid && relay[message][uid]) {
          relay[message][uid] = [];
        }

        if (!uid) {
          uid = 'all';
        }

        // init if not exists
        if (!relay[message][uid]) relay[message][uid] = [];
        if (Config.debug) {
          $log.debug('Listening to ' + message +', uid = ' + uid);
        }
        relay[message][uid].push(callback);
      }
      return service;
    };

    service.send = function(message, payload) {
      var messages = [];

      if (message instanceof Array) {
        messages = message;
      } else {
        messages.push(message);
      }

      for (var i in messages) {
        message = messages[i];
        if (message.indexOf(':') >= 0) {
          var s = message.split(':');
          var key;

          if (s[1] === '*') {
            for (key in relay) {
              if (key.indexOf(s[0]+':') === 0) {
                if (Config.debug) {
                  $log.debug('Calling ' + key, payload);
                }
                call(key, payload);
              }
            }
          } else {
            for (key in relay) {
              if (key.indexOf(s[0]+':*') === 0) {
                if (Config.debug) {
                  $log.debug('Calling ' + key, payload);
                }
                call(key);
              } else if (message === key) {
                if (Config.debug) {
                  $log.debug('Calling ' + key, payload);
                }
                call(key, payload);
              }
            }
          }
        } else 
        {
          if (Config.debug) {
            $log.debug('Calling ' + message, payload);
          }
          call(message, payload);
        }
      }
    };

    var call = function(message, payload) {
      if (relay[message]) {
        for (var c in relay[message]) {
          for (var cc in relay[message][c]) {
            var callback = relay[message][c][cc];
            callback(payload);
          }
        }
      }
    };

    return service;
  }
})();
(function() {
  angular.module('tlcore.modal')
    .directive('modalContent', modalContent)
  ;

  modalContent.$inject = [
    '$compile',
    'Event'
  ];
  function modalContent(
    $compile,
    Event
  ) {
    return {
      restrict: 'A',
      replace: false,
      link: function(scope, el, attrs) {
        el.append( $compile(scope.template)(scope) );
        Event.listen('modal.scope',function(s) {
          for(var i in s) {
            var value = s[i];
            scope[i] = value;
          }
        }, 'modalContent');
      }
    };
  }
})();
(function() {
  angular.module('tlcore.modal')
    .directive('modalDialog', modalDialog)
  ;

  modalDialog.$inject = [
    '$compile',
    '$log',
    '$templateCache',
    '$timeout'
  ];
  function modalDialog(
    $compile,
    $log,
    $templateCache,
    $timeout
  ) {
    return {
      replace: false,
      restrict: 'E',
      link: function(scope, el, attr) {
        var dialog = scope.dialog ? scope.dialog : $templateCache.get('modal/templates/modal.dialog.html');
        scope.el = el;
        scope.el.append( $compile( dialog )(scope) );
      }
    }
  }
})();
(function() {
  angular.module('tlcore.modal')
    .run(ModalRun);

  ModalRun.$inject = [
    '$rootScope',
    'Modal'
  ];
  function ModalRun(
    $rootScope,
    Modal
  ) {
    $rootScope.$on('$locationChangeSuccess', function() {
      Modal.close();
    });
  }
})();
(function() {
  'use strict';

  angular.module('tlcore.modal')
    .service('Modal', Modal);

  Modal.$inject = [
    '$compile',
    '$document',
    '$log',
    '$location',
    '$rootScope',
    '$sce',
    '$templateCache',
    '$timeout'
  ];
  
  function Modal(
    $compile,
    $document,
    $log,
    $location,
    $rootScope,
    $sce,
    $templateCache,
    $timeout
  ) {
    var service = this;
    var scope = null;

    service.open = function(modal) {
      // modal is already open
      if (scope) return service.another(modal);

      // create a new isolate scope
      scope = $rootScope.$new(true);

      // push a suffix to the URL
      $location.url('/#');

      // shove everything on the modal into the scope
      for (var i in modal) {
        scope[i] = modal[i];
      }

      // attach a close function so the directive can close it
      scope.close = service.close;

      // by default, don't show immediately
      scope.show = false;

      // compile the modal tag and prepend to body
      angular.element(document.body).append( $compile('<modal-dialog></modal-dialog>')(scope) );

      // add the "noscroll" class to the body so that it doesn't scroll behind the overlay
      if (!scope.scroll) {
        angular.element(document.body).addClass('noscroll');
      }

      // close after timeout delay
      if (scope.timeout) {
        scope.$timeout = $timeout(function() {
          service.close();
        }, scope.timeout);
      }

      // when pressing escape, close the modal
      document.onkeydown = function(evt) {
        if (evt.keyCode == 27) {
          service.close();
        }
      };

      // show the modal after a delay
      return $timeout(function() {
        scope.show = true;
      }, 100);
    };

    service.close = function() {
      if (!scope || !scope.show) {
        return;
      }

      // unbind the document keydown event
      document.onkeydown = null;

      // hide the modal now, allow some time for fadeout
      scope.show = false;

      if (!scope.el) $log.warn('Modal directive did not set its own element');

      // remove the "noscroll" class from the body so it can scroll again
      angular.element(document.body).removeClass('noscroll');

      // fade out and kill the modal and its scope
      return $timeout(function() {
        scope.el.remove();
        scope.$destroy();
        scope = null;
      }, 200);
    };

    service.another = function(modal) {
      if (scope.blocking) return; // sorry, modal is blocking :(

      if (scope.$timeout) {
        $timeout.cancel(scope.$timeout);
      }

      return $timeout(function() {
        service.close().then(function() {
          service.open(modal);
        });
      }, 100);
    };

    /**
     * Quick way to show a success modal.
     * @param  {string} message Message to show in the modal
     * @return {object}         This modal service
     */
    service.success = function(message) {
      return service.open({
        timeout: 3000, // close after this many seconds (default always present)
        scroll: true, // allow scrolling of the page (default false)
        message: message, // show this message
        dialog: $templateCache.get('modal/templates/modal.success.html') // use a dedicated dialog
      });
    };

    /**
     * Quick way to show an info modal.
     * @param  {string} message Message to show in the modal
     * @return {object}         This modal service
     */
    service.info = function(message) {
      return service.open({
        timeout: 3000, // close after this many seconds (default always present)
        scroll: true, // allow scrolling of the page (default false)
        message: message, // show this message
        dialog: $templateCache.get('modal/templates/modal.info.html') // use a dedicated dialog
      });
    };

    /**
     * Quick way to show a warning modal.
     * @param  {string} message Message to show in the modal
     * @return {object}         This modal service
     */
    service.warn = function(message) {
      return service.open({
        timeout: 3000, // close after this many seconds (default always present)
        scroll: true, // allow scrolling of the page (default false)
        message: message, // show this message
        dialog: $templateCache.get('modal/templates/modal.warn.html') // use a dedicated dialog
      });
    };

    /**
     * Quick way to show an error modal.
     * @param  {string} message Message to show in the modal
     * @return {object}         This modal service
     */
    service.danger = function(message) {
      return service.open({
        message: $sce.trustAsHtml(message),
        dialog: $templateCache.get('modal/templates/modal.danger.html')
      });
    };

    /**
     * Quick way to show a confirm modal.
     * @param {string} message Message to show in the modal
     * @param {array} [{label: '', callback: function}]
     * @return {object}         This modal service
     */
    service.confirm = function(message, buttons) {
      return service.open({
        message: message,
        dialog: $templateCache.get('modal/templates/modal.confirm.html'),
        buttons: buttons,
        onClick: function(button) {
          if (typeof button.callback === 'function') button.callback();
          return service.close(); 
        }
      });
    };

    return service;
  }
})();
(function() {
  angular.module('tlcore.loading')
    .directive('loading', loading)
  ;

  loading.$inject = [
    '$compile',
    '$templateCache'
  ];
  function loading(
    $compile,
    $templateCache
  ) {
    return {
      restrict: 'E',
      replace: true,
      template: '<div class="loading ng-hide" ng-show="loading === true"></div>'
    };
  }
})();
(function() {
  'use strict';

  angular.module('tlcore.loading')
    .run(Loading);

  Loading.$inject = [
    '$compile',
    '$rootScope',
    '$timeout',
    'Event'
  ];
  function Loading(
    $compile,
    $rootScope,
    $timeout,
    Event
  ) {
    var scope = $rootScope.$new(true);

    Event.listen('loading', function(val) {
      scope.loading = val;
    });
    angular.element(document).ready(function() {
      var c = $compile('<loading></loading>')(scope);
      angular.element(document.body).append(c);
    });
    
  }
})();
(function() {
  'use strict';

  angular.module('tlcore.util')
    .service('Util', Util);

  Util.$inject = [];
  function Util() {
    var service = this;

    /**
     * @see https://stackoverflow.com/questions/901115/how-can-i-get-query-string-values-in-javascript#2880929
     * @return {Object|null} Map of query params to values
     */
    service.getQueryString = function() {
      return (function(a) {
        if (a == "") return {};
        var b = {};
        for (var i = 0; i < a.length; ++i)
        {
          var p=a[i].split('=', 2);
          if (p.length == 1)
            b[p[0]] = "";
          else
            b[p[0]] = decodeURIComponent(p[1].replace(/\+/g, " "));
        }
        return b;
      })(window.location.search.substr(1).split('&'));
    }

    service.jsonToFormObject = function(obj, propertyName) {
      // var userInput = 'a\u200Bb\u200Cc\u200Dd\uFEFFe';
      // var result = userInput.replace(/[\u200B-\u200D\uFEFF]/g, '');
      var str = [];
      for (var key in obj) {
        if (obj.hasOwnProperty(key)) {
          var p, v;
          if (propertyName) {
            p = propertyName + '[' + encodeURIComponent(key)+ ']';
          } else {
            p = encodeURIComponent(key);
          }
          v = encodeURIComponent(obj[key]);

          str.push(p+'='+v);
        }
      }
      return str.join("&").replace(/[\u200B-\u200D\uFEFF]/g, '');;
    };

    service.jsonToFormArray = function(array, propertyName) {
      var str = [];
      for (var i in array) {
        var v = array[i];
        var p = propertyName + '[]';
        str.push(p+'='+v);
      }
      return str.join("&");
    };

    service.isMobile = function() {
      var check = false;
      (function(a){if(/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i.test(a)||/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(a.substr(0,4))) check = true;})(navigator.userAgent||navigator.vendor||window.opera);
      return check;
    }

    return service;
  }
})();
angular.module('tlcore').run(['$templateCache', function($templateCache) {$templateCache.put('modal/templates/modal.confirm.html','<!-- background overlay -->\n<div \n  class="modal-backdrop fade"\n  aria-hidden="true"\n  ng-class="{\'in\': show}"\n  ng-click="close(false)"\n></div>\n\n<!-- modal dialog -->\n<div \n  class="modal fade modal-confirm {{class}}"\n  ng-class="{\'show\': show, \'in\': show}"\n>\n  <div class="modal-dialog" id="modal-dialog" role="dialog">\n    <div class="modal-content">\n      <div class="modal-body">\n        {{message}}\n        <div class="text-sm-right">\n          <button\n            type="button"\n            ng-repeat="button in buttons.slice().reverse()" \n            ng-click="onClick(button)"\n            ng-class="[\'btn\', {\'btn-primary\': $index == buttons.length-1}]"\n            ng-bind="button.label"\n          ></button>\n        </div>\n      </div>\n    </div>\n  </div>\n</div> ');
$templateCache.put('modal/templates/modal.danger.html','<!-- background overlay -->\n<div \n  class="modal-backdrop fade"\n  aria-hidden="true"\n  ng-class="{\'in\': show}"\n  ng-click="close(false)"\n></div>\n\n<!-- modal dialog -->\n<div \n  class="modal fade {{class}}"\n  ng-class="{\'show\': show, \'in\': show}"\n>\n  <div class="modal-dialog alert alert-danger" id="modal-dialog" role="dialog"><span ng-bind-html="message"></span>\n    <div class="text-sm-right">\n      <button type="button" ng-click="close(false)" class="btn btn-default">OK</button>\n    </div>\n  </div>\n</div>');
$templateCache.put('modal/templates/modal.dialog.html','<!-- background overlay -->\n<div \n  class="modal-backdrop fade"\n  aria-hidden="true"\n  ng-class="{\'in\': show}"\n  ng-click="close(false)"\n></div>\n\n<!-- modal dialog -->\n<div \n  class="modal fade {{class}}"\n  ng-class="{\'show\': show, \'in\': show}"\n>\n  <div class="modal-dialog" id="modal-dialog" role="dialog">\n    <!-- bind the content to modal-content -->\n    <div class="modal-content" modal-content>\n    </div>\n  </div>\n</div>');
$templateCache.put('modal/templates/modal.info.html','<div \n  class="modal-success fade {{class}}"\n  ng-class="{\'show\': show, \'in\': show}"\n>\n  <div class="modal-dialog alert alert-info" id="modal-dialog" role="dialog">\n    {{message}}\n  </div>\n</div>');
$templateCache.put('modal/templates/modal.success.html','<div \n  class="modal-success fade {{class}}"\n  ng-class="{\'show\': show, \'in\': show}"\n>\n  <div class="modal-dialog alert alert-success" id="modal-dialog" role="dialog">\n    {{message}}\n  </div>\n</div>');
$templateCache.put('modal/templates/modal.warn.html','<div \n  class="modal-success fade {{class}}"\n  ng-class="{\'show\': show, \'in\': show}"\n>\n  <div class="modal-dialog alert alert-danger" id="modal-dialog" role="dialog">\n    {{message}}\n  </div>\n</div>');
$templateCache.put('loading/templates/loading.html','');}]);