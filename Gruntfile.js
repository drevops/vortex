/**
 * @file
 * Grunt tasks.
 *
 * Run `grunt` for to process with dev settings.
 * Run `grunt prod` to process with prod settings.
 * Run `grunt watch` to start watching with dev settings.
 */

/* global module */
var bootstrapPath = 'vendor/twbs/bootstrap/dist/js/bootstrap.js';
var themePath = 'docroot/themes/custom/your_site_theme/';
var themeName = 'your_site_theme';
module.exports = function (grunt) {
  'use strict';
  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),
    eslint: {
      src: [
        'docroot/profiles/custom/**/*.js',
        '!docroot/profiles/custom/**/*.min.js',
        'docroot/modules/custom/**/*.js',
        '!docroot/modules/custom/**/*.min.js',
        'docroot/themes/custom/**/*.js',
        '!docroot/themes/custom/**/*.min.js'
      ],
      options: {
        config: '.eslintrc.json',
        format: 'codeframe'
      }
    },
    sasslint: {
      options: {
        configFile: '.sass-lint.yml',
        formatter: 'codeframe'
      },
      target: [
        'docroot/themes/custom/**/*.scss',
        'docroot/modules/custom/**/*.scss'
      ]
    },
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
    clean: [themePath + 'build'],
    concat: {
      options: {
        separator: '\n\n'
      },
      dist: {
        src: [
          bootstrapPath,
          themePath + 'js/**/*.js',
          '!' + themePath + 'js/' + themeName + '.min.js'
        ],
        dest: themePath + 'build/js/' + themeName + '.min.js'
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
          [themePath + 'build/js/' + themeName + '.min.js']: [themePath + 'build/js/' + themeName + '.min.js']
        }
      }
    },
    sass: {
      dev: {
        files: {
          [themePath + 'build/css/' + themeName + '.min.css']: themePath + 'scss/style.scss'
        },
        options: {
          implementation: require('node-sass'),
          sourceMap: true,
          outputStyle: 'expanded'
        }
      },
      prod: {
        files: {
          [themePath + 'build/css/' + themeName + '.min.css']: themePath + 'scss/style.scss'
        },
        options: {
          implementation: require('node-sass'),
          sourceMap: false,
          outputStyle: 'compressed'
        }
      }
    },
    postcss: {
      options: {
        processors: [
          require('autoprefixer')({browsers: ['last 2 versions', 'not ie <= 8', 'iOS >= 7']})
        ]
      },
      dev: {
        map: true,
        src: themePath + 'build/css/' + themeName + '.min.css'
      },
      prod: {
        map: false,
        src: themePath + 'build/css/' + themeName + '.min.css'
      }
    },
    copy: {
      images: {
        expand: true,
        cwd: themePath + 'images/',
        src: '**',
        dest: themePath + 'build/images'
      },
      fonts: {
        expand: true,
        cwd: themePath + 'fonts/',
        src: '**',
        dest: themePath + 'build/fonts'
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
        files: [
          themePath + 'scss/**/*.scss'
        ],
        tasks: ['sass_globbing', 'sass:dev', 'postcss:dev'],
        options: {
          livereload: true,
          spawn: false
        }
      }
    }
  });

  grunt.loadNpmTasks('grunt-postcss');
  grunt.loadNpmTasks('grunt-sass-globbing');
  grunt.loadNpmTasks('grunt-contrib-concat');
  grunt.loadNpmTasks('grunt-contrib-copy');
  grunt.loadNpmTasks('grunt-contrib-clean');
  grunt.loadNpmTasks('gruntify-eslint');
  grunt.loadNpmTasks('grunt-contrib-uglify');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-sass');
  grunt.loadNpmTasks('grunt-sass-lint');
  grunt.loadNpmTasks('grunt-exec');

  grunt.registerTask('lint', ['eslint', 'sasslint']);
  grunt.registerTask('prod', ['lint', 'sass_globbing', 'clean', 'concat', 'uglify:prod', 'sass:prod', 'postcss:prod', 'copy']);
  grunt.registerTask('dev', ['sass_globbing', 'clean', 'concat', 'sass:dev', 'postcss:dev', 'copy']);
  // By default, run grunt with prod settings.
  grunt.registerTask('default', ['prod']);
};
