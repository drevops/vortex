'use strict';

var grunt = require('grunt'),
  path = require('path'),
  exec = require('child_process').exec,
  execOptions = {
    cwd: path.join(__dirname, '..')
  };

/*
  ======== A Handy Little Nodeunit Reference ========
  https://github.com/caolan/nodeunit

  Test methods:
    test.expect(numAssertions)
    test.done()
  Test assertions:
    test.ok(value, [message])
    test.equal(actual, expected, [message])
    test.notEqual(actual, expected, [message])
    test.deepEqual(actual, expected, [message])
    test.notDeepEqual(actual, expected, [message])
    test.strictEqual(actual, expected, [message])
    test.notStrictEqual(actual, expected, [message])
    test.throws(block, [error], [message])
    test.doesNotThrow(block, [error], [message])
    test.ifError(value)
*/

exports.sass_globbing = {
  setUp: function(done) {
    // setup here if necessary
    done();
  },
  defult_options: function(test) {
    test.expect(1);

    exec('grunt sass_globbing:default_options', execOptions, function(error, stdout) {
      var actual = grunt.file.read('tmp/partials.scss');
      var expected = grunt.file.read('test/expected/partials.scss');
      test.equal(actual, expected, 'generated partials.scss is correct');

      test.done();
    });
  },
  exclude_file: function(test) {
    test.expect(1);

    exec('grunt sass_globbing:exclude_file', execOptions, function(error, stdout) {
      var actual = grunt.file.read('tmp/partials.scss');
      var expected = grunt.file.read('test/expected/_partials.exclude_file.scss');
      test.equal(actual, expected, 'generated partials.scss is correct');

      test.done();
    });
  },
  single_quotes: function(test) {
    test.expect(1);

    exec('grunt sass_globbing:single_quotes', execOptions, function(error, stdout) {
      var actual = grunt.file.read('tmp/other-single.scss');
      var expected = grunt.file.read('test/expected/other-single.scss');
      test.equal(actual, expected, 'generated other.scss is correct');

      test.done();
    });
  },
  multi_files: function(test) {
    test.expect(2);

    exec('grunt sass_globbing:multi_files', execOptions, function(error, stdout) {
      var actual = grunt.file.read('tmp/partials.scss');
      var expected = grunt.file.read('test/expected/partials.scss');
      test.equal(actual, expected, 'generated partials.scss is correct');

      actual = grunt.file.read('tmp/other.scss');
      expected = grunt.file.read('test/expected/other.scss');
      test.equal(actual, expected, 'generated other.scss is correct');

      test.done();
    });
  },
  partial_and_non_partial_files: function(test) {
    test.expect(1);

    exec('grunt sass_globbing:partial_and_non_partial_files', execOptions, function(error, stdout) {
      test.equal(
        stdout.indexOf('There is also a partial next to file "tmp/bad_import/colors.scss" - merge partial _colors.scss and colors.scss to solve this issue') > -1,
        true,
        'found partial and non-partial files named same'
      );
      test.done();
    });
  },
  globbed_target_inside_globbed_folder: function(test) {
    test.expect(1);

    exec('grunt sass_globbing:globbed_target_inside_globbed_folder', execOptions, function(error, stdout) {
      var actual = grunt.file.read('tmp/partials/_partials.scss');
      var expected = grunt.file.read('test/expected/_partials.scss');
      test.equal(actual, expected, 'generated partials/partials.scss is correct');

      test.done();
    });
  },
  custom_signature: function(test) {
    test.expect(1);

    exec('grunt sass_globbing:custom_signature', execOptions, function(error, stdout) {
      var actual = grunt.file.read('tmp/partials/_partials.scss');
      var expected = grunt.file.read('test/expected/_partials.custom_signature.scss');
      test.equal(actual, expected, 'generated partials/partials.scss is correct');

      test.done();
    });
  },
  no_signature: function(test) {
    test.expect(1);

    exec('grunt sass_globbing:no_signature', execOptions, function(error, stdout) {
      var actual = grunt.file.read('tmp/partials/_partials.scss');
      var expected = grunt.file.read('test/expected/_partials.no_signature.scss');
      test.equal(actual, expected, 'generated partials/partials.scss is correct');

      test.done();
    });
  },
  correct_directory_traversel: function(test) {
    test.expect(1);

    exec('grunt sass_globbing:correct_directory_traversel', execOptions, function(error, stdout) {
      var actual = grunt.file.read('tmp/scss/_components.scss');
      var expected = grunt.file.read('test/expected/_correct_directory_traversal.scss');
      test.equal(actual, expected, 'generated components.scss is correct');

      test.done();
    });
  }
};
