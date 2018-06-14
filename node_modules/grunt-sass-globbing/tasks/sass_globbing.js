/*
 * grunt-sass-globbing
 * https://github.com/DennisBecker/grunt-sass-globbing
 *
 * Copyright (c) 2014 Dennis Becker
 * Licensed under the MIT license.
 */

'use strict';


module.exports = function(grunt) {

  var fs = require('fs');
  var path = require('path');

  grunt.registerMultiTask('sass_globbing', 'Create file with @import from a configured path', function() {

    var importFiles = [];

    // Merge task-specific and/or target-specific options with these defaults.
    var options = this.options({
      useSingleQuotes: false,
      signature: '/* generated with grunt-sass-globbing */'
    });

    if(typeof options.signature === 'string' && options.signature !== ''){
      options.signature = options.signature + '\n\n';
    } else if (options.signature === false) {
      options.signature = '';
    }

    var quoteSymbol = '"';
    if (typeof options.useSingleQuotes !== 'undefined' && options.useSingleQuotes === true) {
      quoteSymbol = '\'';
    }

    this.files.forEach(function(f) {

      var importStatement = '';
      var importStatements = [];
      if (!(f.dest in importFiles)) {
        importFiles[f.dest] = options.signature;
      }

      f.src.forEach(function(filePath) {

        if (filePath === f.dest) {
          return;
          // if the current filePath is the same as the file.dest then skip this loop
        }

        var importPath = path.dirname(path.relative(path.dirname(f.dest), filePath));
        var fileName = path.basename(filePath);
        fileName = fileName.replace(/^_/, '');
        importPath += path.sep + fileName.replace(path.extname(fileName), '');

        importStatement = '@import ' + quoteSymbol + importPath.replace(/\\/g, '/').replace(/^\.\//, '') + quoteSymbol + ';\n';

        if (importStatements.indexOf(importStatement) > -1) {
          throw new Error('There is also a partial next to file "'+ filePath + '" - merge partial _' + fileName + ' and ' + fileName + ' to solve this issue');
          //grunt.fail.warn('There is also a partial next to file "'+ filePath + '" - merge partial _' + fileName + ' and ' + fileName + ' to solve this issue' + "\n");
        }

        importStatements.push(importStatement);
        importFiles[f.dest] += importStatement;
      });
    });

    grunt.log.debug(importFiles);

    for (var index in importFiles) {
      grunt.file.write(index, importFiles[index]);
      grunt.verbose.ok(importFiles[index]);
    }
  });

};
