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