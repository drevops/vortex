/*
 * grunt-sass-globbing
 * https://github.com/DennisBecker/grunt-sass-globbing
 *
 * Copyright (c) 2014 Dennis Becker
 * Licensed under the MIT license.
 */

'use strict';

module.exports = function(grunt) {

  // Project configuration.
  grunt.initConfig({
    jshint: {
      all: [
        'Gruntfile.js',
        'tasks/*.js',
        '<%= nodeunit.tests %>'
      ],
      options: {
        jshintrc: '.jshintrc'
      }
    },

    // Before generating any new files, remove any previously-created files.
    clean: {
      tests: ['tmp']
    },

    copy: {
      test: {
        files: [{
          expand: true,
          cwd: 'test/fixtures',
          src: [
            '**/*'
          ],
          dest: 'tmp'
        }]
      }
    },

    // Configuration to be run (and then tested).
    sass_globbing: {
      default_options: {
        files: {
          'tmp/partials.scss': 'tmp/partials/**/*.scss'
        }
      },
      exclude_file: {
        files: {
          'tmp/partials.scss': ['tmp/partials/**/*.scss', '!tmp/**/_post.scss']
        }
      },
      single_quotes: {
        files: {
          'tmp/other-single.scss': 'tmp/other/**/*.scss'
        },
        options: {
          useSingleQuotes: true
        }
      },
      multi_files: {
        files: {
          'tmp/partials.scss': 'tmp/partials/**/*.scss',
          'tmp/other.scss': 'tmp/other/**/*.scss'
        }
      },
      partial_and_non_partial_files: {
        files: {
          'tmp/all.scss': 'tmp/bad_import/**/*.scss'
        }
      },
      globbed_target_inside_globbed_folder: {
        files: {
          'tmp/partials/_partials.scss': 'tmp/partials/**/*.scss'
        }
      },
      custom_signature: {
        files: {
          'tmp/partials/_partials.scss': 'tmp/partials/**/*.scss'
        },
        options: {
          signature: '// Hello, World!'
        }
      },
      no_signature: {
        files: {
          'tmp/partials/_partials.scss': 'tmp/partials/**/*.scss'
        },
        options: {
          signature: false
        }
      },
      correct_directory_traversel: {
          files: {
            'tmp/scss/_components.scss': 'tmp/components/**/*.scss'
          }
      }
    },

    // Unit tests.
    nodeunit: {
      tests: ['test/*_test.js']
    }

  });

  // Actually load this plugin's task(s).
  grunt.loadTasks('tasks');

  // These plugins provide necessary tasks.
  grunt.loadNpmTasks('grunt-contrib-jshint');
  grunt.loadNpmTasks('grunt-contrib-clean');
  grunt.loadNpmTasks('grunt-contrib-copy');
  grunt.loadNpmTasks('grunt-contrib-nodeunit');

  // Whenever the "test" task is run, first clean the "tmp" dir, then run this
  // plugin's task(s), then test the result.
  grunt.registerTask('test', ['clean', 'copy', 'nodeunit']);

  // By default, lint and run all tests.
  grunt.registerTask('default', ['jshint', 'test']);

};
