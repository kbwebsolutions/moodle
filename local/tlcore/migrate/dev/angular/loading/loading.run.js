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