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