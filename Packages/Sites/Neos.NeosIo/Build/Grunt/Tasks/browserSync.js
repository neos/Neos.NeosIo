module.exports = function(grunt) {
	grunt.config('browserSync', {
		default_options: {
			bsFiles: {
				src : ['Resources/**/*.css', 'Resources/**/*.html']
			},
			options: {
				watchTask: true, // < VERY important
				proxy: buildPath(),
				open: false
			},
			// If you don't want browserSync to sync your scrolling
			ghostMode: false
		}
	});

	grunt.loadNpmTasks('grunt-browser-sync');
};

var buildPath = function() {
	var workingDirectory = __dirname,
	 	folder = workingDirectory.split('/'),
		os = require('os');

	// check if we're working local or on typokeeper
	if(os.hostname().match(/local$/)) {
		// construct the base url from parts of our working directory
		//				   customer name
		return 'http://' + folder[4] + '.flow.dev';
	} else {
		// check if we are in the users directory
		if (workingDirectory.indexOf('vhosts')) {
			// construct the base url from parts of our working directory
			//				   customer name	 username
			return 'http://' + folder[4] + '.' + folder[2] + '.cms.dev.interner-server.de/';
		} else {
			// construct the base url from parts of the working directory
			//				   customer name is this time on the second position, because we are not in a user dir
			return 'http://' + folder[2] + '.dev.interner-server.de/';
		}
	}
};

