/*jslint unparam: true, browser: true, indent: 2 */

;
(function($, window, document, undefined) {
	'use strict';

	Foundation.libs.customtopbar = {
		name: 'customtopbar',

		version: '4.3.2',

		settings: {
			index: 0,
			stickyClass: 'sticky',
			scrolltop: true, // jump to top when sticky nav menu toggle is clicked
			init: false
		},

		init: function(section, method, options) {
			Foundation.inherit(this, 'data_options addCustomRule');
			var self = this;

			if (typeof method === 'object') {
				$.extend(true, this.settings, method);
			} else if (typeof options !== 'undefined') {
				$.extend(true, this.settings, options);
			}

			if (typeof method !== 'string') {

				$('.top-bar, [data-topbar]').each(function() {
					$.extend(true, self.settings, self.data_options($(this)));
					self.settings.$w = $(window);
					self.settings.$topbar = $(this);
					self.settings.$section = self.settings.$topbar.find('section');
					self.settings.$titlebar = self.settings.$topbar.children('ul').first();
					self.settings.$topbar.data('index', 0);

					var topbarContainer = self.settings.$topbar.parent();
					if (topbarContainer.hasClass('fixed') || topbarContainer.hasClass(self.settings.stickyClass)) {
						self.settings.$topbar.data('height', self.outerHeight(topbarContainer));
						self.settings.$topbar.data('stickyoffset', topbarContainer.offset().top);
					} else {
						self.settings.$topbar.data('height', self.outerHeight(self.settings.$topbar));
					}

					var breakpoint = $("<div class='top-bar-js-breakpoint'/>").insertAfter(self.settings.$topbar);
					self.settings.breakPoint = breakpoint.width();
					breakpoint.remove();

					self.assemble();

					// Pad body when sticky (scrolled) or fixed.
					self.addCustomRule('.f-topbar-fixed { padding-top: ' + self.settings.$topbar.data('height') + 'px }');

					if (self.settings.$topbar.parent().hasClass('fixed')) {
						$('body').addClass('f-topbar-fixed');
					}
				});

				if (!self.settings.init) {
					this.events();
				}

				return this.settings.init;
			} else {
				// fire method
				return this[method].call(this, options);
			}
		},

		toggle: function(target) {
			var self = this;
			var topbar = $(target).closest('.top-bar, [data-topbar]'),
				section = topbar.find('section, .section');

			if (self.breakpoint()) {
				topbar.find('.has-dropdown').removeClass('expanded');
				topbar
					.toggleClass('expanded')
					.css('height', '');
			}

			if (self.settings.scrolltop) {
				if (!topbar.hasClass('expanded')) {
					if (topbar.hasClass('fixed')) {
						topbar.parent().addClass('fixed');
						topbar.removeClass('fixed');
						$('body').addClass('f-topbar-fixed');
					}
				} else if (topbar.parent().hasClass('fixed')) {
					if (self.settings.scrolltop) {
						topbar.parent().removeClass('fixed');
						topbar.addClass('fixed');
						$('body').removeClass('f-topbar-fixed');

						window.scrollTo(0, 0);
					} else {
						topbar.parent().removeClass('expanded');
					}
				}
			} else {
				if (topbar.parent().hasClass(self.settings.stickyClass)) {
					topbar.parent().addClass('fixed');
				}

				if (topbar.parent().hasClass('fixed')) {
					if (!topbar.hasClass('expanded')) {
						topbar.removeClass('fixed');
						topbar.parent().removeClass('expanded');
						self.updateStickyPositioning();
					} else {
						topbar.addClass('fixed');
						topbar.parent().addClass('expanded');
					}
				}
			}
		},

		timer: null,

		events: function() {
			var self = this;
			$(this.scope)
				.off('.fndtn.topbar')
				.on('click.fndtn.topbar', '.top-bar .toggle-topbar, [data-topbar] .toggle-topbar', function(e) {
					e.preventDefault();
					self.toggle(e.target);
				})

				.on('click.fndtn.topbar', '.top-bar .has-dropdown>a, [data-topbar] .has-dropdown>a', function(e) {
					if (self.breakpoint() && $(window).width() != self.settings.breakPoint) {
						var parent = $(this).parent();
						if (!parent.hasClass('expanded')) {
							e.preventDefault();
							parent.addClass('expanded');
						}
					}
				});

			$(window).on('resize.fndtn.topbar', function() {
				if (typeof self.settings.$topbar === 'undefined') {
					return;
				}
				var stickyContainer = self.settings.$topbar.parent('.' + this.settings.stickyClass);
				var stickyOffset;

				if (!self.breakpoint()) {
					var doToggle = self.settings.$topbar.hasClass('expanded');
					$('.top-bar, [data-topbar]')
						.css('height', '')
						.removeClass('expanded')
						.find('li')
						.removeClass('hover');

					if (doToggle) {
						self.toggle();
					}
				}

				if (stickyContainer.length > 0) {
					if (stickyContainer.hasClass('fixed')) {
						// Remove the fixed to allow for correct calculation of the offset.
						stickyContainer.removeClass('fixed');

						stickyOffset = stickyContainer.offset().top;
						if ($(document.body).hasClass('f-topbar-fixed')) {
							stickyOffset -= self.settings.$topbar.data('height');
						}

						self.settings.$topbar.data('stickyoffset', stickyOffset);
						stickyContainer.addClass('fixed');
					} else {
						stickyOffset = stickyContainer.offset().top;
						self.settings.$topbar.data('stickyoffset', stickyOffset);
					}
				}
			}.bind(this));

			$('body').on('click.fndtn.topbar', function(e) {
				var parent = $(e.target).closest('li').closest('li.hover');

				if (parent.length > 0) {
					return;
				}

				$('.top-bar li, [data-topbar] li').removeClass('hover');
			});
		},

		breakpoint: function() {
			return $(document).width() <= this.settings.breakPoint || $('html').hasClass('lt-ie9');
		},

		assemble: function() {
			// check for sticky
			this.sticky();
		},

		height: function(ul) {
			var total = 0,
				self = this;

			ul.find('> li').each(function() {
				total += self.outerHeight($(this), true);
			});

			return total;
		},

		sticky: function() {
			var $window = $(window),
				self = this;

			$window.scroll(function() {
				self.updateStickyPositioning();
			});
		},

		updateStickyPositioning: function() {
			var klass = '.' + this.settings.stickyClass;
			var $window = $(window);

			if ($(klass).length > 0) {
				var distance = this.settings.$topbar.data('stickyoffset');
				if (!$(klass).hasClass('expanded')) {
					if ($window.scrollTop() > (distance)) {
						if (!$(klass).hasClass('fixed')) {
							$(klass).addClass('fixed');
							$('body').addClass('f-topbar-fixed');
						}
					} else if ($window.scrollTop() <= distance) {
						if ($(klass).hasClass('fixed')) {
							$(klass).removeClass('fixed');
							$('body').removeClass('f-topbar-fixed');
						}
					}
				}
			}
		},

		off: function() {
			$(this.scope).off('.fndtn.topbar');
			$(window).off('.fndtn.topbar');
		},

		reflow: function() {
		}
	};
}(Foundation.zj, this, this.document));
