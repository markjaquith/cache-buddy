module.exports = (grunt) ->
	# Regex, used twice
	readmeReplacements = [
		from: /^# (.*?)( #+)?$/mg
		to: '=== $1 ==='
	,
		from: /^## (.*?)( #+)?$/mg
		to: '== $1 =='
	,
		from: /^### (.*?)( #+)?$/mg
		to: '= $1 ='
	,
		from: /^Stable tag:\s*?[\w.-]+(\s*?)$/mi
		to: 'Stable tag: <%= pkg.version %>$1'
	]

	# Project configuration
	grunt.initConfig
		pkg: grunt.file.readJSON('package.json')

		coffee:
			options:
				join: yes
				sourceMap: yes
			default:
				files:
					'js/cache-buddy.js': 'js/cache-buddy.coffee'

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
					src: 'js/cache-buddy.js'
					dest: 'js/cache-buddy.min.js'
					sourceMapIn: 'js/cache-buddy.js.map'
				]

		compass:
			options:
				sassDir: 'css'
				cssDir: 'css'
				imagesDir: 'images'
				sourcemap: yes
				environment: 'production'

		phpunit:
			default: {}

		watch:
			php:
				files: [ '**/*.php' ]
				tasks: [ 'phpunit' ]
				options:
					debounceDelay: 5000
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

		wp_deploy:
			default:
				options:
					plugin_slug: '<%= pkg.name %>'
					build_dir: 'release/svn'
					assets_dir: 'assets'

		clean:
			release: [
				'release/<%= pkg.version %>'
			]
			js: [
				'js/*.js'
				'!js/*.min.js'
				'js/*.src.coffee'
				'js/*.js.map'
				'!js/*.min.js.map'
			]
			svn_readme_md: [
				'release/svn/readme.md'
			]

		copy:
			main:
				src: [
					'**'
					'!node_modules/**'
					'!release/**'
					'!assets/**'
					'!.git/**'
					'!.sass-cache/**'
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
			svn:
				cwd: 'release/<%= pkg.version %>/'
				expand: yes
				src: '**'
				dest: 'release/svn/'

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
					to: "$1const$2CSS_JS_VERSION$3=$4'<%= pkg.version %>';"
				]
			svn_readme:
				src: [ 'release/svn/readme.md' ]
				dest: 'release/svn/readme.txt'
				replacements: readmeReplacements

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
	grunt.loadNpmTasks 'grunt-phpunit'
	grunt.loadNpmTasks 'grunt-svn-checkout'
	grunt.loadNpmTasks 'grunt-push-svn'
	grunt.loadNpmTasks 'grunt-wp-deploy'

	# Default task
	grunt.registerTask 'default', [
		'replace:header'
		'replace:plugin'
		'coffeelint'
		'coffee'
		'jshint'
		'uglify'
		'compass'
		'clean:js'
	]

	# Build task
	grunt.registerTask 'build', [
		'default'
		'clean'
		'copy:main'
		# 'compress'
	]

	# Prepare a WordPress.org release
	grunt.registerTask 'release:prepare', [
		'build'
		'copy:svn'
		'replace:svn_readme'
		'clean:svn_readme_md'
	]

	# WordPress.org release task
	grunt.registerTask 'release', [
		'release:prepare'
		'wp_deploy'
	]

	grunt.util.linefeed = '\n'

