# Change Log
All notable changes to this project will be documented in this file.
This file follows the standard of [Keep a CHANGELOG](http://keepachangelog.com/).
This project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased][unreleased]

## [1.5.1] - 2016-04-12
## Fixed
- directory traversal issue introduced with PR #18 fixed, do not remove globally alls "./" - just remove it when strings begins with it

## [1.5.0] - 2016-04-12
## Added
- supports grunt v1.0.0
- example to exclude files

### Changed
- import files on same folder without "./" prefix

## [1.4.0] - 2015-08-04
### Added
- Option to set a custom signature or to disable signature
- Example for a SCSS file which uses the import map files from the example grunt task

## [1.3.0] - 2015-04-14
### Added
- Destination file can be written within source path and won't reference itself

### Changed
- Typo fixes in usage examples
- Remove two examples and add a description how to use other CSS preprocessors for a better overview and readability

## [1.2.0] - 2015-03-06
### Added
- Description of supported CSS preprocessors and postprocessors

### Changed
- Generated files now use CSS compatible comment style

## [1.1.0] - 2015-02-09
### Added
- Allow single quotes for @import statements
- Check if partial and non-partial with the same name exist in the same folder
- Add a change log file

## [1.0.3] - 2015-01-27
### Fixed
- use replace() with global modifier to replace all backslashes with slashes

## [1.0.2] - 2015-01-26
### Fixed
- @import statements change from directory separator from operating system to slashes needed by Sass/libsass

## [1.0.1] - 2014-12-01
### Added
- Keywords for npm search

### Changed
- Example in documentation shows unclear usage example

## 1.0.0 - 2014-11-21
### Added
- First release of grunt-sass-globbing

[unreleased]: https://github.com/DennisBecker/grunt-sass-globbing/compare/v1.5.1...HEAD
[1.5.0]: https://github.com/DennisBecker/grunt-sass-globbing/compare/v1.5.0...v1.5.1
[1.5.0]: https://github.com/DennisBecker/grunt-sass-globbing/compare/v1.4.0...v1.5.0
[1.4.0]: https://github.com/DennisBecker/grunt-sass-globbing/compare/v1.3.0...v1.4.0
[1.3.0]: https://github.com/DennisBecker/grunt-sass-globbing/compare/v1.2.0...v1.3.0
[1.2.0]: https://github.com/DennisBecker/grunt-sass-globbing/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/DennisBecker/grunt-sass-globbing/compare/v1.0.3...v1.1.0
[1.0.3]: https://github.com/DennisBecker/grunt-sass-globbing/compare/v1.0.2...v1.0.3
[1.0.2]: https://github.com/DennisBecker/grunt-sass-globbing/compare/v1.0.1...v1.0.2
[1.0.1]: https://github.com/DennisBecker/grunt-sass-globbing/compare/v1.0.0...v1.0.1
