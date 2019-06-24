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