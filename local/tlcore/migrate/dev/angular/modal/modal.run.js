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