module.exports = function (grunt) {
	grunt.config('watch', {
		scripts: {
			files: ['<%= files.includes.js.header %>', '<%= files.includes.js.footer %>'],
			tasks: ['uglify'],
			options: {
				spawn: false
			}
		},
		buster: {
			files: [grunt.config.data.files.cacheBuster.src + '*'],
			tasks: ['asset_cachebuster'],
			options: {
				// NOTE: This option is IMPORTANT since we need to change the asset_cachebuster config dynamically
				spawn: false
			}
		}
	});

	grunt.config('concurrent', {
		// Can be called with "concurrent:watch"
		watch: {
			// The Grunt watcher for scripts and the Compass watcher are started concurrently
			tasks: ['watch:scripts', 'compass:watch', 'watch:buster'],
			options: {
				logConcurrentOutput: true,
				limit: 5
			}
		}
	});

	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-concurrent');
};