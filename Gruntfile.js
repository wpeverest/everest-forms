/* jshint node:true */
module.exports = function( grunt ){
	'use strict';

	grunt.initConfig({

		// Setting folder templates.
		dirs: {
			js: 'assets/js',
			css: 'assets/css'
		},

		// JavaScript linting with JSHint.
		jshint: {
			options: {
				jshintrc: '.jshintrc'
			},
			all: [
				'Gruntfile.js',
				'!<%= dirs.js %>/admin/*.js',
				'!<%= dirs.js %>/admin/*.min.js',
				'<%= dirs.js %>/frontend/*.js',
				'!<%= dirs.js %>/frontend/*.min.js'
			]
		},

		// Sass linting with Stylelint.
		stylelint: {
			options: {
				stylelintrc: '.stylelintrc'
			},
			all: [
				'<%= dirs.css %>/*.scss',
				'!<%= dirs.css %>/select2.scss'
			]
		},

		// Minify all .js files.
		uglify: {
			options: {
				ie8: true,
				parse: {
					strict: false
				},
				output: {
					comments: /@license|@preserve|^!/
				}
			},
			admin: {
				files: [{
					expand: true,
					cwd: '<%= dirs.js %>/admin/',
					src: [
						'*.js',
						'!*.min.js'
					],
					dest: '<%= dirs.js %>/admin/',
					ext: '.min.js'
				}]
			},
			frontend: {
				files: [{
					expand: true,
					cwd: '<%= dirs.js %>/frontend/',
					src: [
						'*.js',
						'!*.min.js'
					],
					dest: '<%= dirs.js %>/frontend/',
					ext: '.min.js'
				}]
			},
			vendor: {
				files: {
					'<%= dirs.js %>/inputmask/jquery.inputmask.bundle.min.js': [ '<%= dirs.js %>/inputmask/jquery.inputmask.bundle.js' ],
					'<%= dirs.js %>/jquery-blockui/jquery.blockUI.min.js': [ '<%= dirs.js %>/jquery-blockui/jquery.blockUI.js' ],
					'<%= dirs.js %>/jquery-confirm/jquery-confirm.min.js': [ '<%= dirs.js %>/jquery-confirm/jquery-confirm.js' ],
					'<%= dirs.js %>/jquery-validate/jquery.validate.min.js': [ '<%= dirs.js %>/jquery-validate/jquery.validate.js' ],
					'<%= dirs.js %>/flatpickr/flatpickr.min.js': ['<%= dirs.js %>/flatpickr/flatpickr.js'],
					'<%= dirs.js %>/mailcheck/mailcheck.min.js': ['<%= dirs.js %>/mailcheck/mailcheck.js'],
					'<%= dirs.js %>/perfect-scrollbar/perfect-scrollbar.min.js': ['<%= dirs.js %>/perfect-scrollbar/perfect-scrollbar.js'],
					'<%= dirs.js %>/selectWoo/selectWoo.min.js': ['<%= dirs.js %>/selectWoo/selectWoo.js'],
					'<%= dirs.js %>/tooltipster/tooltipster.bundle.min.js': [ '<%= dirs.js %>/tooltipster/tooltipster.bundle.js' ]
				}
			}
		},

		// Compile all .scss files.
		sass: {
			options: {
				sourcemap: 'none',
				implementation: require( 'node-sass' )
			},
			compile: {
				files: [{
					expand: true,
					cwd: '<%= dirs.css %>/',
					src: ['*.scss'],
					dest: '<%= dirs.css %>/',
					ext: '.css'
				}]
			}
		},

		// Generate all RTL .css files
		rtlcss: {
			generate: {
				expand: true,
				cwd: '<%= dirs.css %>',
				src: [
					'*.css',
					'!select2.css',
					'!flatpickr.css',
					'!*-rtl.css'
				],
				dest: '<%= dirs.css %>/',
				ext: '-rtl.css'
			}
		},

		// Minify all .css files.
		cssmin: {
			minify: {
				expand: true,
				cwd: '<%= dirs.css %>/',
				src: ['*.css'],
				dest: '<%= dirs.css %>/',
				ext: '.css'
			}
		},

		// Concatenate select2.css onto the admin.css files.
		concat: {
			admin: {
				files: {
					'<%= dirs.css %>/admin.css' : ['<%= dirs.css %>/select2.css', '<%= dirs.css %>/admin.css'],
					'<%= dirs.css %>/admin-rtl.css' : ['<%= dirs.css %>/select2.css', '<%= dirs.css %>/admin-rtl.css']
				}
			}
		},

		// Watch changes for assets.
		watch: {
			css: {
				files: [
					'<%= dirs.css %>/*.scss'
				],
				tasks: ['sass', 'rtlcss', 'postcss', 'cssmin', 'concat']
			},
			js: {
				files: [
					'<%= dirs.js %>/admin/*js',
					'<%= dirs.js %>/frontend/*js',
					'!<%= dirs.js %>/admin/*.min.js',
					'!<%= dirs.js %>/frontend/*.min.js'
				],
				tasks: ['jshint', 'uglify']
			}
		},

		// PHP Code Sniffer.
		phpcs: {
			options: {
				bin: 'vendor/bin/phpcs'
			},
			dist: {
				src:  [
					'**/*.php',               // Include all files
					'!includes/libraries/**', // Exclude libraries/
					'!node_modules/**',       // Exclude node_modules/
					'!vendor/**'              // Exclude vendor/
				]
			}
		},

		// Autoprefixer.
		postcss: {
			options: {
				processors: [
					require( 'autoprefixer' )({
						overrideBrowserslist: [
							'> 0.1%',
							'ie 8',
							'ie 9'
						]
					})
				]
			},
			dist: {
				src: [
					'<%= dirs.css %>/*.css'
				]
			}
		},

		// Compress files and folders.
		compress: {
			options: {
				archive: 'everest-forms.zip'
			},
			files: {
				src: [
					'**',
					'!.*',
					'!*.md',
					'!*.zip',
					'!.*/**',
					'!sass/**',
					'!vendor/**',
					'!phpcs.xml',
					'!Gruntfile.js',
					'!package.json',
					'!renovate.json',
					'!composer.json',
					'!composer.lock',
					'!node_modules/**',
					'!package-lock.json',
					'!webpack.config.js'
				],
				dest: 'everest-forms',
				expand: true
			}
		}
	});

	// Load NPM tasks to be used here
	grunt.loadNpmTasks( 'grunt-sass' );
	grunt.loadNpmTasks( 'grunt-phpcs' );
	grunt.loadNpmTasks( 'grunt-rtlcss' );
	grunt.loadNpmTasks( 'grunt-postcss' );
	grunt.loadNpmTasks( 'grunt-stylelint' );
	grunt.loadNpmTasks( 'grunt-contrib-jshint' );
	grunt.loadNpmTasks( 'grunt-contrib-uglify' );
	grunt.loadNpmTasks( 'grunt-contrib-cssmin' );
	grunt.loadNpmTasks( 'grunt-contrib-concat' );
	grunt.loadNpmTasks( 'grunt-contrib-watch' );
	grunt.loadNpmTasks( 'grunt-contrib-compress' );

	// Register tasks
	grunt.registerTask( 'default', [
		'js',
		'css'
	]);

	grunt.registerTask( 'js', [
		'uglify:admin',
		'uglify:frontend'
	]);

	grunt.registerTask( 'css', [
		'sass',
		'rtlcss',
		'postcss',
		'cssmin',
		'concat'
	]);

	// Only an alias to 'default' task.
	grunt.registerTask( 'dev', [
		'default'
	]);

	grunt.registerTask( 'zip', [
		'dev',
		'compress'
	]);
};
