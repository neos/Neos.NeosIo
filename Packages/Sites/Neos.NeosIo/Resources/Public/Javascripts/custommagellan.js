/*jslint unparam: true, browser: true, indent: 2 */

;(function ($, window, document, undefined) {
	'use strict';

	Foundation.libs.magellan = {
		name : 'magellan',

		version : '4.3.2',

		settings : {
			activeClass: 'active',
			threshold: 0
		},

		init : function (scope, method, options) {
			this.scope = scope || this.scope;
			Foundation.inherit(this, 'data_options');

			if (typeof method === 'object') {
				$.extend(true, this.settings, method);
			}

			if (typeof method !== 'string') {
				if (!this.settings.init) {
					this.fixed_magellan = $("[data-magellan-expedition]");
					this.set_threshold();
					this.last_destination = $('[data-magellan-destination]').last();
					this.events();

					this.fixed_magellan.each(function() {
						$(this).css('margin-top', (-($(this).height() / 2)) + 'px');
					});
				}

				return this.settings.init;
			} else {
				return this[method].call(this, options);
			}
		},

		events : function () {
			var self = this;
			$(this.scope).on('arrival.fndtn.magellan', '[data-magellan-arrival]', function (e) {
				var $destination = $(this),
					$expedition = $destination.closest('[data-magellan-expedition]'),
					activeClass = $expedition.attr('data-magellan-active-class')
						|| self.settings.activeClass;

				$destination
					.closest('[data-magellan-expedition]')
					.find('[data-magellan-arrival]')
					.not($destination)
					.removeClass(activeClass);
				$destination.addClass(activeClass);
			});

			if (this.last_destination.length > 0) {
				$(window).on('scroll.fndtn.magellan', function (e) {
					var windowScrollTop = $(window).scrollTop(),
						scrolltopPlusHeight = windowScrollTop + $(window).height(),
						lastDestinationTop = Math.ceil(self.last_destination.offset().top);

					$('[data-magellan-destination]').each(function () {
						var $destination = $(this),
							destination_name = $destination.attr('data-magellan-destination'),
							topOffset = $destination.offset().top - windowScrollTop;

						if (topOffset <= self.settings.threshold) {
							$("[data-magellan-arrival='" + destination_name + "']").trigger('arrival');
						}
						// In large screens we may hit the bottom of the page and dont reach the top of the last magellan-destination, so lets force it
						if (scrolltopPlusHeight >= $(self.scope).height() && lastDestinationTop > windowScrollTop && lastDestinationTop < scrolltopPlusHeight) {
							$('[data-magellan-arrival]').last().trigger('arrival');
						}
					});
				});
			}

			this.settings.init = true;
		},

		set_threshold : function () {
			if (typeof this.settings.threshold !== 'number') {
				this.settings.threshold = (this.fixed_magellan.length > 0) ?
					this.outerHeight(this.fixed_magellan, true) : 0;
			}
		},

		off : function () {
			$(this.scope).off('.fndtn.magellan');
			$(window).off('.fndtn.magellan');
		},

		reflow : function () {}
	};
}(Foundation.zj, this, this.document));