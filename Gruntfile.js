/**
 * @file
 * Grunt tasks.
 *
 * Run `grunt` for to process with dev settings.
 * Run `grunt prod` to process with prod settings.
 * Run `grunt watch` to start watching with dev settings.
 */

/* global module */
var themePath = 'docroot/themes/custom/mysitetheme/';
var libraryPath = 'docroot/libraries/';
module.exports = function (grunt) {
  'use strict';
  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),
    sass_globbing: {
      dev: {
        files: {
          [themePath + 'scss/_components.scss']: themePath + 'scss/components/**/*.scss'
        },
        options: {
          useSingleQuotes: true,
          signature: '//\n// GENERATED FILE. DO NOT MODIFY DIRECTLY.\n//'
        }
      }
    },
    concat: {
      options: {
        separator: '\n\n'
      },
      dist: {
        src: [
          // Uncomment below to include libraries.
          // libraryPath + 'bootstrap/assets/javascripts/bootstrap.js',
          themePath + 'js/**/*.js',
          '!' + themePath + 'js/mysitetheme.min.js'
        ],
        dest: themePath + 'js/mysitetheme.min.js'
      }
    },
    uglify: {
      prod: {
        options: {
          mangle: {
            reserved: ['jQuery', 'Drupal']
          },
          compress: {
            drop_console: true
          }
        },
        files: {
          [themePath + 'js/mysitetheme.min.js']: [themePath + 'js/mysitetheme.min.js']
        }
      }
    },
    sass: {
      dev: {
        files: {
          [themePath + 'css/mysitetheme.min.css']: themePath + 'scss/style.scss'
        },
        options: {
          sourceMap: true,
          outputStyle: 'expanded'
        }
      },
      prod: {
        files: {
          [themePath + 'css/mysitetheme.min.css']: themePath + 'scss/style.scss'
        },
        options: {
          sourceMap: false,
          outputStyle: 'compressed'
        }
      }
    },
    watch: {
      scripts: {
        files: [themePath + 'js/**/*.js'],
        tasks: ['concat'],
        options: {
          spawn: false
        }
      },
      styles: {
        files: [themePath + 'scss/**/*.scss'],
        tasks: ['sass_globbing', 'sass:dev'],
        options: {
          livereload: true,
          spawn: false
        }
      }
    }
  });

  grunt.loadNpmTasks('grunt-sass-globbing');
  grunt.loadNpmTasks('grunt-contrib-concat');
  grunt.loadNpmTasks('grunt-contrib-uglify');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-sass');
  grunt.loadNpmTasks('grunt-exec');

  grunt.registerTask('prod', ['sass_globbing', 'concat', 'uglify:prod', 'sass:prod']);
  // By default, run grunt with dev settings.
  grunt.registerTask('default', ['sass_globbing', 'concat', 'sass:dev']);
};
