/**
 * @file
 * Grunt tasks.
 *
 * Run `grunt` for to process with dev settings.
 * Run `grunt prod` to process with prod settings.
 * Run `grunt watch` to start watching with dev settings.
 * phpcs:ignoreFile
 */

/* global module */

var librariesPaths = [];
var themeName = 'your_site_theme';
module.exports = function (grunt) {
  'use strict';
  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),
    eslint: {
      scan: {
        src: [
          'js/**/*.js',
          '!js/**/*.min.js',
        ],
      },
      fix: {
        options: {
          fix: true
        },
        src: [
          'js/**/*.js',
          '!js/**/*.min.js',
        ],
      },
      options: {
        config: '.eslintrc.json'
      }
    },
    stylelint: {
      scan: {
        src: ['scss/**/*.scss']
      },
      fix: {
        options: {
          fix: true
        },
        src: ['scss/**/*.scss']
      }
    },
    sass_globbing: {
      dev: {
        files: {
          ['scss/_components.scss']: 'scss/components/**/*.scss'
        },
        options: {
          useSingleQuotes: true,
          signature: '//\n// GENERATED FILE. DO NOT MODIFY DIRECTLY.\n//'
        }
      }
    },
    clean: ['build'],
    concat: {
      options: {
        separator: '\n\n'
      },
      dist: {
        src: [
          'js/**/*.js',
          '!js/' + themeName + '.min.js'
        ].concat(librariesPaths),
        dest: 'build/js/' + themeName + '.min.js'
      }
    },
    uglify: {
      prod: {
        options: {
          mangle: {
            reserved: ['jQuery', 'Drupal']
          },
          compress: {
            drop_console: true,
            module: false
          }
        },
        files: {
          ['build/js/' + themeName + '.min.js']: ['build/js/' + themeName + '.min.js']
        }
      }
    },
    sass: {
      dev: {
        files: {
          ['build/css/' + themeName + '.min.css']: 'scss/styles.scss'
        },
        options: {
          implementation: require('sass'),
          sourceMap: true,
          outputStyle: 'expanded'
        }
      },
      prod: {
        files: {
          ['build/css/' + themeName + '.min.css']: 'scss/styles.scss'
        },
        options: {
          implementation: require('sass'),
          sourceMap: false,
          outputStyle: 'compressed'
        }
      }
    },
    postcss: {
      options: {
        processors: [
          require('autoprefixer')()
        ]
      },
      dev: {
        map: true,
        src: 'build/css/' + themeName + '.min.css'
      },
      prod: {
        map: false,
        src: 'build/css/' + themeName + '.min.css'
      }
    },
    copy: {
      images: {
        expand: true,
        cwd: 'images/',
        src: '**',
        dest: 'build/images'
      },
      fonts: {
        expand: true,
        cwd: 'fonts/',
        src: '**',
        dest: 'build/fonts'
      }
    },
    watch: {
      scripts: {
        files: ['js/**/*.js'],
        tasks: ['concat'],
        options: {
          spawn: false
        }
      },
      styles: {
        files: [
          'scss/**/*.scss'
        ],
        tasks: ['sass_globbing', 'sass:dev', 'postcss:dev'],
        options: {
          spawn: false
        }
      }
    }
  });

  grunt.loadNpmTasks('@lodder/grunt-postcss');
  grunt.loadNpmTasks('grunt-sass-globbing');
  grunt.loadNpmTasks('grunt-contrib-concat');
  grunt.loadNpmTasks('grunt-contrib-copy');
  grunt.loadNpmTasks('grunt-contrib-clean');
  grunt.loadNpmTasks('gruntify-eslint');
  grunt.loadNpmTasks('grunt-contrib-uglify');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-sass');
  grunt.loadNpmTasks('grunt-stylelint');
  grunt.loadNpmTasks('grunt-exec');

  grunt.registerTask('lint', ['eslint', 'stylelint:scan']);
  grunt.registerTask('lint-fix', ['eslint:fix', 'stylelint:fix']);
  grunt.registerTask('prod', ['sass_globbing', 'clean', 'concat', 'uglify:prod', 'sass:prod', 'postcss:prod', 'copy']);
  grunt.registerTask('dev', ['sass_globbing', 'clean', 'concat', 'sass:dev', 'postcss:dev', 'copy']);
  grunt.registerTask('watch_message', function() {
    grunt.log.writeln('If using Chrome, make sure that "Disable cache (while DevTools is open)" setting is selected and DevTools are opened or browser will cache assets.');
  });
  grunt.registerTask('watch-dev', ['dev', 'watch_message', 'watch']);
  // By default, run grunt with prod settings.
  grunt.registerTask('default', ['prod']);
};
