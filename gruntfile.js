'use strict';
module.exports = function ( grunt ) {

	// load all grunt tasks matching the `grunt-*` pattern
	// Ref. https://npmjs.org/package/load-grunt-tasks
	require( 'load-grunt-tasks' )( grunt );
	grunt.initConfig(
		{
			pkg: grunt.file.readJSON('package.json'),

			// Check text domain
			checktextdomain: {
				options: {
					text_domain: [ 'wb-todo', 'buddypress' ], // Specify allowed domain(s)
					keywords: [ // List keyword specifications
						'__:1,2d',
						'_e:1,2d',
						'_x:1,2c,3d',
						'esc_html__:1,2d',
						'esc_html_e:1,2d',
						'esc_html_x:1,2c,3d',
						'esc_attr__:1,2d',
						'esc_attr_e:1,2d',
						'esc_attr_x:1,2c,3d',
						'_ex:1,2c,3d',
						'_n:1,2,4d',
						'_nx:1,2,4c,5d',
						'_n_noop:1,2,3d',
						'_nx_noop:1,2,3c,4d'
					]
				},
				target: {
					files: [ {
						src: [
						'*.php',
						'**/*.php',
						'!node_modules/**',
						'!options/framework/**',
						'!tests/**'
							], // all php
						expand: true
					} ]
				}
			},
			// make po files
			makepot: {
				target: {
					options: {
						cwd: '.', // Directory of files to internationalize.
						domainPath: 'languages/', // Where to save the POT file.
						exclude: [ 'node_modules/*', 'options/framework/*' ], // List of files or directories to ignore.
						mainFile: 'index.php', // Main project file.
						potFilename: 'wb-todo.pot', // Name of the POT file.
						potHeaders: { // Headers to add to the generated POT file.
							poedit: true, // Includes common Poedit headers.
							'Last-Translator': 'Varun Dubey',
							'Language-Team': 'Wbcom Designs',
							'report-msgid-bugs-to': '',
							'x-poedit-keywordslist': true // Include a list of all possible gettext functions.
						},
						type: 'wp-plugin', // Type of project (wp-plugin or wp-theme).
						updateTimestamp: true // Whether the POT-Creation-Date should be updated without other changes.
					}
				}
			},

			// Minify JavaScript files
			uglify: {
				options: {
					mangle: false,
					compress: {
						drop_console: false
					}
				},
				frontend: {
					files: {
						'assets/js/bptodo-front.min.js': ['assets/js/bptodo-front.js'],
						'assets/js/bptodo-modern.min.js': ['assets/js/bptodo-modern.js']
					}
				},
				admin: {
					files: {
						'admin/assets/js/bptodo-admin.min.js': ['admin/assets/js/bptodo-admin.js']
					}
				}
			},

			// Minify CSS files
			cssmin: {
				options: {
					shorthandCompacting: false,
					roundingPrecision: -1
				},
				frontend: {
					files: {
						'assets/css/bptodo-front.min.css': ['assets/css/bptodo-front.css'],
						'assets/css/bptodo-design-system.min.css': ['assets/css/bptodo-design-system.css'],
						'assets/css/bptodo-components.min.css': ['assets/css/bptodo-components.css']
					}
				},
				admin: {
					files: {
						'admin/assets/css/bptodo-admin.min.css': ['admin/assets/css/bptodo-admin.css']
					}
				}
			},

			// Generate RTL CSS
			rtlcss: {
				options: {
					opts: {
						clean: false
					},
					saveUnmodified: false
				},
				frontend: {
					expand: true,
					cwd: 'assets/css/',
					src: ['bptodo-front.css', 'bptodo-design-system.css', 'bptodo-components.css'],
					dest: 'assets/css/',
					ext: '-rtl.css'
				},
				admin: {
					expand: true,
					cwd: 'admin/assets/css/',
					src: ['bptodo-admin.css'],
					dest: 'admin/assets/css/',
					ext: '-rtl.css'
				}
			},

			// Watch for changes
			watch: {
				css: {
					files: ['assets/css/*.css', 'admin/assets/css/*.css', '!**/*-rtl.css', '!**/*.min.css'],
					tasks: ['cssmin', 'rtlcss']
				},
				js: {
					files: ['assets/js/*.js', 'admin/assets/js/*.js', '!**/*.min.js'],
					tasks: ['uglify']
				}
			},

			// Clean build directory
			clean: {
				build: ['build'],
				zip: ['*.zip'],
				dist: ['dist']
			},

			// Copy files to build directory
			copy: {
				build: {
					expand: true,
					src: [
						'**',
						'!node_modules/**',
						'!build/**',
						'!dist/**',
						'!.git/**',
						'!.github/**',
						'!.gitignore',
						'!.gitattributes',
						'!docs/**',
						'!*.md',
						'!Gruntfile.js',
						'!package.json',
						'!package-lock.json',
						'!composer.json',
						'!composer.lock',
						'!phpcs.xml',
						'!phpunit.xml',
						'!.editorconfig',
						'!.DS_Store',
						'!*.zip',
						'!tests/**',
						'!bin/**',
						'!vendor/bin/**'
					],
					dest: 'build/buddypress-user-todo-list/'
				}
			},

			// Create zip file
			compress: {
				build: {
					options: {
						mode: 'zip',
						archive: 'dist/buddypress-user-todo-list-<%= pkg.version %>.zip'
					},
					expand: true,
					cwd: 'build/',
					src: ['**/*'],
					dest: '/'
				}
			}
		}
	);

	// register tasks
	grunt.registerTask( 'default', [ 'checktextdomain', 'makepot' ] );
	grunt.registerTask( 'build', [ 'checktextdomain', 'makepot', 'uglify', 'cssmin', 'rtlcss' ] );
	grunt.registerTask( 'css', [ 'cssmin', 'rtlcss' ] );
	grunt.registerTask( 'js', [ 'uglify' ] );
	grunt.registerTask( 'release', [ 'checktextdomain', 'makepot', 'uglify', 'cssmin', 'rtlcss', 'clean:dist', 'clean:build', 'copy:build', 'compress:build', 'clean:build' ] );
};
