module.exports = (grunt) ->
	# Project configuration
	grunt.initConfig
		pkg: grunt.file.readJSON('package.json')

		coffee:
			options:
				join: yes
				sourceMap: yes
			default:
				files:
					'js/cache_buddy.js': 'js/cache_buddy.coffee'

		coffeelint:
			default: [ 'js/*.coffee' ]
			options:
				no_tabs:
					level: 'ignore'
				max_line_length:
					level: 'warn'
				indentation:
					level: 'ignore'

		jshint:
			default: []
			options:
				curly: yes
				eqeqeq: yes
				immed: yes
				latedef: yes
				newcap: yes
				noarg: yes
				sub: yes
				undef: yes
				boss: yes
				eqnull: yes
				globals:
					exports: yes
					module: no

		uglify:
			options:
				sourceMap: yes
				mangle:
						except: [ 'jQuery' ]
			default:
				files: [
					src: 'js/cache_buddy.js'
					dest: 'js/cache_buddy.min.js'
					sourceMapIn: 'js/cache_buddy.js.map'
				]

		compass:
			options:
				sassDir: 'css'
				cssDir: 'css'
				imagesDir: 'images'
				sourcemap: yes
				environment: 'production'

		watch:
			sass:
				files: [ 'css/*.sass' ]
				tasks: [ 'compass' ]
				options:
					debounceDelay: 500
			scripts:
				files: [
					'js/**/*.coffee'
					'js/vendor/**/*.js'
				]
				tasks: [
					'coffeelint'
					'coffee'
					'jshint'
					'uglify'
					'clean:js'
				]
				options:
					debounceDelay: 500

		clean:
			release: [ 'release/<%= pkg.version %>' ]
			js: [
				'js/*.js'
				'!js/*.min.js'
				'js/*.src.coffee'
				'js/*.js.map'
				'!js/*.min.js.map'
			]

		copy:
			main:
				src: [
					'**'
					'!node_modules/**'
					'!release/**'
					'!.git/**'
					'!.sass-cache/**'
					'!css/**/*.sass'
					'!js/**/*.coffee'
					'!img/src/**'
					'!Gruntfile.*'
					'!package.json'
					'!.gitignore'
					'!.gitmodules'
					'!tests/**'
					'!bin/**'
					'!.travis.yml'
					'!phpunit.xml'
				]
				dest: 'release/<%= pkg.version %>/'

		replace:
			header:
				src: [ '<%= pkg.name %>.php' ]
				overwrite: yes
				replacements: [
					from: /^Version:(\s*?)[\w.-]+$/m
					to: 'Version: <%= pkg.version %>'
				]
			plugin:
				src: [ 'classes/plugin.php' ]
				overwrite: yes
				replacements: [
					from: /^(\s*?)const(\s+?)VERSION(\s*?)=(\s+?)'[^']+';/m
					to: "$1const$2VERSION$3=$4'<%= pkg.version %>';"
				,
					from: /^(\s*?)const(\s+?)CSS_JS_VERSION(\s*?)=(\s+?)'[^']+';/m
					to: "$1const$2CSS_JS_VERSION$3=$4'<%= pkg.version %>-release';"
				]

		compress:
			default:
				options:
					mode: 'zip'
					archive: './release/<%= pkg.name %>.<%= pkg.version %>.zip'
				expand: yes
				cwd: 'release/<%= pkg.version %>/'
				src: [ '**/*' ]
				dest: '<%= pkg.name %>/'

	# Load other tasks
	grunt.loadNpmTasks 'grunt-contrib-jshint'
	grunt.loadNpmTasks 'grunt-contrib-concat'
	grunt.loadNpmTasks 'grunt-contrib-coffee'
	grunt.loadNpmTasks 'grunt-coffeelint'
	grunt.loadNpmTasks 'grunt-contrib-uglify'
	grunt.loadNpmTasks 'grunt-contrib-compass'
	grunt.loadNpmTasks 'grunt-contrib-watch'
	grunt.loadNpmTasks 'grunt-contrib-clean'
	grunt.loadNpmTasks 'grunt-contrib-copy'
	grunt.loadNpmTasks 'grunt-contrib-compress'
	grunt.loadNpmTasks 'grunt-text-replace'

	# Default task
	grunt.registerTask 'default', [
		'coffeelint'
		'coffee'
		'jshint'
		'uglify'
		'compass'
		'clean:js'
	]

	# Build task
	grunt.registerTask 'build', [
		'replace'
		'default'
		'clean'
		'copy'
		'compress'
	]

	grunt.util.linefeed = '\n'

