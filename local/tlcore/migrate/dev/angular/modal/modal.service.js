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