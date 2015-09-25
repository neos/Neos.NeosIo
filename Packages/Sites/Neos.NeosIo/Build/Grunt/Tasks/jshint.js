module.exports = function(grunt) {
	grunt.config('jshint', {
		all: ['Gruntfile.js', 'Build/Grunt/Tasks/**/*.js', '<%= files.src.js %>']
	});

	grunt.loadNpmTasks('grunt-contrib-jshint');
};