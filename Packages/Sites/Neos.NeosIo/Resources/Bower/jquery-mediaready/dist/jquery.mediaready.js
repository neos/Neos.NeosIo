/*! mediaready - v1.0.0 - 2014-01-11
* https://github.com/pixlscript/jquery-mediaready
* Copyright (c) 2014 Severin Glöckle, pixlscript GbR; Licensed MIT */
(function ($, window) {

	"use strict";

	$.mediaReady = (function() {

		var plugin = {
			activeMedias: [],
			currentWidth: null,
			init: function(options) {
				this.options = $.extend({}, $.mediaReady.options, options);
				this.medias = $.merge($.merge([], this.options.breakpoints), this.options.medias);

				// Cache some jQuery objects for efficient usage
				this.$window = $(window);
				this.$mediator = this.options.mediator;
				this.$body = $(this.options.body);

				this.calculate();

				if (this.options.appendClasses) {
					this.allClassnames = utils.buildClassNames(this.medias);
					this.appendClasses();
				}

				this.options.resize && this.bindToResize();
			},
			calculate: function(onResize) {
				var self = this;

				// Cross-browser width-detection
				this.currentWidth = Math.max(window.innerWidth || this.$window.width());
				// Backup activeMedias, then reset the array
				this.currentMedias = this.activeMedias;
				this.activeMedias = [];

				$.each(this.medias, function(i, media) {
					// For medias that provide a .check() method, perform this check
					// If it's a calculation after a resize, skip any .check() - but the ones with .onResize = true
					if ((!onResize || media.onResize) && $.isFunction(media.check) && media.check()) {
						self.addActiveMedia(media);
					}
					// Other medias are breakpoints, compare their min/max properties to this.currentWidth
					else if (self.currentWidth >= media.min && self.currentWidth <= media.max) {
						self.addActiveMedia(media);
					}
				});
			},
			addActiveMedia: function(media) {
				this.activeMedias.push(media.name);

				// Only publish event when this media wasn't active before
				if (this.options.events && $.inArray(media.name, this.currentMedias) === -1) {
					this.publishMediaRady(media);
				}
			},
			appendClasses: function() {
				this.$body
					.removeClass(this.allClassnames)
					.addClass(this.activeMedias.join(' '));
			},
			checkMedias: function(medias, reversed) {
				var self = this,
					isActive = false;

				medias = utils.assureArray(medias);

				$.each(medias, function(i, requiredMedia) {
					if ((!reversed && self.isActiveMedia(requiredMedia))) {
						isActive = true;

						// Stop checking any further
						return false;
					}

					if ((reversed && !self.isActiveMedia(requiredMedia))) {
						isActive += 1;
					}
				});

				return (!reversed && isActive) || (reversed && isActive === medias.length);
			},
			bindToResize: function() {
				var self = this,
					_timer = null;

				// Simple debounced resize listener
				this.$window.on('resize.mediaReady', function() {
					_timer && clearTimeout(_timer);
					_timer = setTimeout(function() {
						self.onResize();
					}, self.options.resize.debouncedInterval);
				});
			},
			onResize: function() {
				this.calculate(true);
				this.options.appendClasses && this.appendClasses();
			},
			publishMediaRady: function(media) {
				this.$mediator
					.triggerHandler({
						type: 'mediaReady.' + media.name,
						media: media,
						width: this.currentWidth
					});

				// $().triggerHandler() does not support method-chaining :(
				this.$mediator
					.triggerHandler({
						type: 'mediaReady.all',
						media: media,
						width: this.currentWidth
					});
			},
			addListener: function(requiredMedias, callback, once) {
				var self = this;

				requiredMedias = utils.assureArray(requiredMedias);

				if (once) {
					// since $().one() still fires callback once for every event,
					// extend the callback to use $().off() to make sure it is fired only once,
					// even for multiple events
					var _callback = callback,
						callback = function() {
							_callback.call();
							self.$mediator.off(utils.buildEventNames(requiredMedias), callback);
						};
				}

				self.$mediator.on(utils.buildEventNames(requiredMedias), callback);
			},
			removeListener: function(requiredMedias, callback) {
				requiredMedias = utils.assureArray(requiredMedias);

				this.$mediator.off(utils.buildEventNames(requiredMedias), callback);
			},
			isActiveMedia: function(media) {
				return $.inArray(media, this.activeMedias) !== -1;
			}
		};

		var utils = {
			assureArray: function(arg) {
				// Rely on jQuery's isArray() method for backwards compatibility
				return $.isArray(arg) ? arg : [arg];
			},
			buildEventNames: function(events) {
				return 'mediaReady.' + events.join(' mediaReady.');
			},
			buildClassNames: function(medias) {
				// Helper string with all classnames to be able to remove all classes efficiently
				return $.map(medias, function(media) {
					return media.name;
				}).join(' ');
			}
		};

		/**
		 * public api
		 *
		 * methods:
		 *  init():
		 *      initializes the plugin, performs initial calculation of activeMedias
		 *
		 *  ready(requiredMedias, callback):
		 *      requiredMedias:     string or array of media names
		 *      callback:           function
		 *
		 *      fire callback if one of requiredMedias is currently active
		 *
		 *  besides(forbiddenMedias, callback):
		 *      forbiddenMedias:    string or array of media names
		 *      callback:           function
		 *
		 *      fire callback if none of forbiddenMedias is currently active
		 *
		 *  on(requiredMedias, callback, [initialize])
		 *      requiredMedias:     string or array of media names
		 *      callback:           function
		 *      initialize:         initially check requiredMedias (default: true)
		 *
		 *      fire callback every time one of requiredMedias becomes active
		 *
		 *  once(requiredMedias, callback, [initialize])
		 *      requiredMedias:     string or array of media names
		 *      callback:           function
		 *      initialize:         initially check requiredMedias (default: true)
		 *
		 *      fire callback once when any of requiredMedias becomes active
		 *
		 *  off(requiredMedias, [callback])
		 *      requiredMedias:     string or array of media names
		 *      callback:           function
		 *
		 *      remove any listeners for the requiredMedias (if set, only the one with the given callback)
		 */
		return {
			init: function(options) {
				plugin.init(options);
			},
			ready: function(requiredMedias, callback) {
				if (plugin.checkMedias(requiredMedias)) {
					callback.call();
				}
			},
			besides: function(forbiddenMedias, callback) {
				if (plugin.checkMedias(forbiddenMedias, true)) {
					callback.call();
				}
			},
			on: function(requiredMedias, callback, initialize) {
				initialize !== false && this.ready(requiredMedias, callback);
				plugin.addListener(requiredMedias, callback);
			},
			once: function(requiredMedias, callback) {
				if (plugin.checkMedias(requiredMedias)) {
					callback.call();
				} else {
					plugin.addListener(requiredMedias, callback, true);
				}
			},
			off: function(requiredMedias, callback) {
				plugin.removeListener(requiredMedias, callback);
			}
		};
	})();

	// mediaReady defaults
	// for more information, see http://github.com/pixlscript/mediaReady
	$.mediaReady.options = {
		body: 'body',               // body element which receives classes reflecting current media types
		mediator: $({}),            // object for event delegation (pass in your own object for deeper integration)
		appendClasses: true,        // whether to add classes to $.mediaReady.options.body reflecting active medias
		events: true,               // whether to publish events when medias change after initialisation
		resize: {                   // set to false to prevent listening to resize
			debouncedInterval: 300  // threshold for resize listening
		},
		// check for screen widths:
		// objects must provide a min and max screen width
		// as well as a name property for class & event management
		breakpoints: [
			{
				min: 1,
				max: 480,
				name: 'xss'
			},
			{
				min: 481,
				max: 767,
				name: 'xs'
			},
			{
				min: 768,
				max: 991,
				name: 'sm'
			},
			{
				min: 992,
				max: 1199,
				name: 'md'
			},
			{
				min: 1200,
				max: 9999,
				name: 'lg'
			}
		],
		// check for any property:
		// objects must provide a check method which returns true/false for passed/failed checks
		// as well as a name property for class & event management
		// these medias are not checked on window resize by default
		// you may set the onResize property to true to perform checks for this media on resize as well
		medias: [
			{
				check: function() {
					return ('ontouchstart' in window);
				},
				name: 'touch'
			}
		]
	};

}(jQuery, window));