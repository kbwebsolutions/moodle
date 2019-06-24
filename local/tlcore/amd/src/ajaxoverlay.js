define(
    [
        'jquery'
    ],
    function($) {

        var Overlay = function(el) {
            var self = this;

            this.selector = null;
            this.overlay = null;
            this.interval = null;
            this.overlayFs = false;

            this.positionOverlay = function() {
                var el = $(self.selector);
                var overlay = this.overlay;
                $(overlay).css('width', el.width() + 'px');
                $(overlay).css('height', el.height() + 'px');
                var offset = el.offset();
                $(overlay).css('top', offset.top + 'px');
                $(overlay).css('left', offset.left + 'px');
            };

            this.applyOverlay = function(selector) {
                if (!selector) {
                    selector = 'body';
                    this.overlayFs = true;
                }
                if ($(selector + ' .ajax-overlay' ).length) {
                    // Overlay already exists.
                    return;
                }
                self.selector = selector;
                self.overlay = $('<div class="ajax-overlay"></div>');
                var el = $(selector);

                if (el.css('position') === 'relative' || this.overlayFs) {
                    $(el).append(self.overlay);
                    self.overlay.addClass('ajax-overlay-within');
                    self.overlay.css('opacity', 1);
                    if (this.overlayFs) {
                        self.overlay.addClass('ajax-overlay-fs');
                    }
                } else {
                    $(document.body).append(self.overlay);
                    self.positionOverlay();
                    self.overlay.css('opacity', 1);
                    this.interval = window.setInterval(function() {
                        self.positionOverlay();
                    }, 1000);
                }
            };

            this.applyOverlay(el);

            this.removeOverlay = function() {
                if (self.interval !== null) {
                    clearInterval(self.interval);
                }
                // We can't remove self.overlay because it's potentially inside a vue element which would mean it's reference
                // could be stale.
                $(self.selector + ' .ajax-overlay').remove();
            };
        };

        return {
            applyOverlay: function(el) {
                return new Overlay(el);
            }
        };
    }
);