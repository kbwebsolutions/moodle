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