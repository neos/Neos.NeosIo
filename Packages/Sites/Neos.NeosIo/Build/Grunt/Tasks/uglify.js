module.exports = function(grunt) {
	grunt.config('uglify', {
		options: {
			mangle: {
				except: ['jQuery', 'Backbone', 'Modernizr']
			},
			beautify: false
		},
		//header: {
		//	options: {
		//		sourceMap: true
		//	},
		//	files: {
		//		'Resources/Public/Scripts/header.min.js': [
		//			'<%= files.includes.js.header %>'
		//		]
		//	}
		//},
		//footer: {
		//	options: {
		//		sourceMap: true
		//	},
		//	files: {
		//		'Resources/Public/Scripts/footer.min.js': [
		//			'<%= files.includes.js.footer %>'
		//		]
		//	}
		//}
	});

	grunt.loadNpmTasks('grunt-contrib-uglify');
};