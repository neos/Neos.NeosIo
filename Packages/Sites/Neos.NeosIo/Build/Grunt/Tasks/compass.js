module.exports = function(grunt) {
	grunt.config('compass', {
		// This target compiles files once
		compile: {
			options: getCompassOptions(false)
		},
		// This target starts the compass watcher
		watch: {
			options: getCompassOptions(true)
		}
	});

	grunt.loadNpmTasks('grunt-contrib-compass');
};

function getCompassOptions(isWatching) {
	var options = {};

	// can be 'development' or 'production'
	options.environment = 'development';
	options.config = 'Resources/config/compass.rb';

	options.basePath = 'Resources';
	options.sassDir = 'Private/Scss';
	options.imagesDir = 'Public/Images';
	options.javascriptsDir = 'Public/Scripts';
	options.cssDir = '../.grunt_cache/css';
	options.watch = isWatching;

	return options;
}