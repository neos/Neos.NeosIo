module.exports = function (grunt) {
    // We need to register a custom task around the default asset_cachebuster here, because we need grunt.task.requires!
    // This way we wait for a successful first run of compass:compile before we invoke the cachebuster for the first time.
    grunt.registerTask('cacheBust', 'Cachbuster task - depending on compass:compile', function() {
        grunt.task.requires('compass:compile');
    	grunt.config('asset_cachebuster', {        
    		options: {
    			buster: Date.now()
    		},

    		build: {
    			files: filesToBust(grunt.config.data.files.cacheBuster.src, grunt.config.data.files.cacheBuster.dest)
    		}
    	});

    	grunt.loadNpmTasks('grunt-asset-cachebuster');
        grunt.task.run('asset_cachebuster');
    });
    
	grunt.config('asset_cachebuster', {        
		options: {
			buster: Date.now()
		}
	});
    
    /**
     * This listens for the watch event to bust files one by one as they change
     */
    grunt.event.on('watch', function(action, filepath, target) {
        if(target === 'buster') {
            // Build the destination string
            var n = filepath.lastIndexOf('/');
                dest = grunt.config.data.files.cacheBuster.dest + filepath.substring(n + 1);

            // Dynamicly change the configuration before this task is executed!
            grunt.config.data.asset_cachebuster.build = {};
            grunt.config.data.asset_cachebuster.build.files = {};
            grunt.config.data.asset_cachebuster.build.files[dest] = filepath;
        }
    });

	grunt.loadNpmTasks('grunt-asset-cachebuster');
};

function filesToBust(srcDir, destDir) {
    var fs = require('fs'),
        fileNameArray = fs.readdirSync(srcDir),
        fileNameObject = {};
        
    for(var i = 0; i < fileNameArray.length; i++) {
        var currentFile = fileNameArray[i];
        fileNameObject[destDir + currentFile] = [srcDir + currentFile];
    }
    
    return fileNameObject;
}