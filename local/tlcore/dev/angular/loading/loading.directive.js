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