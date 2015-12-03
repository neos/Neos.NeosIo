module.exports = function(grunt) {
	grunt.config.init({
		pkg: grunt.file.readJSON('package.json'),

		files: {
			// All our own sources for linting and code style checking
			src: {
				js: 'Resources/Public/Scripts/Custom/*.js'
			},
			cacheBuster: {
				src: '.grunt_cache/css/',
				dest: 'Resources/Public/Css/'
			},
			// Individual include definitions for JS and CSS
			includes: {
				js: {
					// Ordered list of scripts for header inclusion
					header: [
						// 'Resources/Public/Scripts/Vendor/modernizr.custom.js'
					],
					// Ordered list of scripts for footer inclusion
					footer: [
						// jQuery will be loaded from a CDN

						// Bower resources
						// 'Resources/Bower/flexslider/jquery.flexslider.js',
						// 'Resources/Bower/fastclick/lib/fastclick.js',
						// 'Resources/Bower/jquery-mediaready/dist/jquery.mediaready.js'

						// Custom scripts
					]
				}
			}
		}
	});

	grunt.loadTasks('Build/Grunt/Tasks');

	grunt.registerTask('default', ['compass:compile', 'cacheBust', 'compress']);
	grunt.registerTask('compress', ['uglify']);
	grunt.registerTask('checkstyle', ['jshint', 'jscs']);
	grunt.registerTask('dowatch', ['default', 'browserSync', 'concurrent:watch']);
};
