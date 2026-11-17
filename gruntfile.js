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
						// Third-party libraries carry no translatable strings, and the
						// unminified DataTables source is large enough to exhaust the PHP
						// parser's memory limit - which fails the build rather than just
						// wasting time. Keep every vendored lib listed here.
						exclude: [
							'node_modules/*',
							'options/framework/*',
							'assets/js/datatables.js',
							'assets/js/datatables.min.js',
							'assets/js/select2.full.js',
							'assets/js/select2.full.min.js',
							'assets/css/datatables.css',
							'assets/css/datatables.min.css',
							'assets/css/select2.css',
							'assets/css/select2.min.css',
							'assets/css/jquery-ui.css'
						], // List of files or directories to ignore.
						mainFile: 'bp-user-todo-list.php', // Main project file.
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
				}
				// NOTE: there is no `admin` target. admin/assets/js/bptodo-admin.js was
				// removed in the card-panel migration; the admin panel loads
				// assets/js/admin.js directly. Do not re-add a target for a source that
				// does not exist - uglify/cssmin will happily emit an empty .min file
				// and ship it.
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
						'assets/css/bptodo-components.min.css': ['assets/css/bptodo-components.css'],
						'assets/css/bptodo-widget.min.css': ['assets/css/bptodo-widget.css']
					}
				}
				// NOTE: no `admin` target - see the uglify note above.
				// admin/assets/css/bptodo-admin.css was removed in the card-panel
				// migration, but this target survived and cssmin kept emitting a
				// 0-byte bptodo-admin.min.css that shipped in every zip.
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
				}
				// NOTE: no `admin` target - see the uglify note above.
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
						// Internal dev tooling / docs - never ship to WP.org.
						'!audit/**',
						'!.wbcom-i18n.json',
						'!docs/**',
						'!.claude/**',
						'!**/*.md',            // all markdown, at any depth (readme.txt/license.txt still ship).
						'!gruntfile.js',       // lowercase - the real filename (case-sensitive on Linux/SVN).
						'!Gruntfile.js',
						'!package.json',
						'!package-lock.json',
						'!composer.json',
						'!composer.lock',
						'!phpcs.xml',
						'!phpcs.xml.dist',
						'!phpunit.xml',
						'!phpunit.xml.dist',
						'!.editorconfig',
						'!.eslintrc*',
						'!.stylelintrc*',
						'!.DS_Store',
						'!*.zip',
						// WP.org serves screenshots from the SVN /assets dir, not the plugin zip.
						'!screenshot-*.png',
						'!**/*.map',
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
	// NOTE: build and release deliberately do NOT run makepot.
	//
	// The shipped POT is generated with `wp i18n make-pot` (see .wbcom-i18n.json
	// and the rest of the free series, which all do the same). grunt-wp-i18n finds
	// 49 fewer strings than wp-cli on this plugin - it misses the admin panel, the
	// settings views and every Discover description - so letting it run here would
	// silently replace a complete POT with an incomplete one, and those strings
	// would never reach a translator. The `makepot` target is kept for manual use.
	grunt.registerTask( 'default', [ 'checktextdomain' ] );
	grunt.registerTask( 'build', [ 'checktextdomain', 'uglify', 'cssmin', 'rtlcss' ] );
	grunt.registerTask( 'css', [ 'cssmin', 'rtlcss' ] );
	grunt.registerTask( 'js', [ 'uglify' ] );
	grunt.registerTask( 'release', [ 'checktextdomain', 'uglify', 'cssmin', 'rtlcss', 'clean:dist', 'clean:build', 'copy:build', 'compress:build', 'clean:build' ] );
};
