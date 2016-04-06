module.exports = function(grunt) {

	// Project configuration.
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		uglify: {
			jquery: {
				files: {
					'Resources/Public/Javascripts/vendor/jquery.min.js': ['Resources/Public/Javascripts/vendor/jquery.js']
				}
			},
			zepto: {
				files: {
					'Resources/Public/Javascripts/vendor/zepto.min.js': ['Resources/Public/Javascripts/vendor/zepto.js']
				}
			},
			app: {
				options: {
					sourceMap: 'Resources/Public/Javascripts/source-map.js',
					sourceMapPrefix: 3,
					sourceMappingURL: 'source-map.js'
				},
				files: {
					'Resources/Public/Javascripts/app.min.js': [
						'Resources/Public/Javascripts/foundation/foundation.js',
						'Resources/Public/Javascripts/foundation/foundation.orbit.js',
						'Resources/Public/Javascripts/foundation/foundation.reveal.js',
						'Resources/Public/Javascripts/vendor/zepto.smoothScroll.js',
						'Resources/Public/Javascripts/vendor/imagesloaded.pkgd.min.js',
						'Resources/Public/Javascripts/vendor/wookmark.js',
						'Resources/Public/Javascripts/vendor/stupidtable.min.js',
						'Resources/Public/Javascripts/vendor/wookmark.min.js',
						'Resources/Public/Javascripts/custommagellan.js',
						'Resources/Public/Javascripts/customtopbar.js',
						'Resources/Public/Javascripts/app.js'
					]
				}
			}
		},
		compass: {
			dist: {
				options: {
					config: 'config.rb'
				}
			}
		},
		watch: {
			scripts: {
				files: ['Resources/**/*.js'],
				tasks: ['uglify'],
				options: {
					spawn: false
				}
			},
			stylesheets: {
				files: ['Resources/**/*.scss', 'config.rb'],
				tasks: ['compass'],
				options: {
					spawn: false
				}
			}
		}
	});

	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-contrib-compass');
	grunt.loadNpmTasks('grunt-contrib-watch');

	// Default task(s).
	grunt.registerTask('default', ['compass', 'uglify']);
};
