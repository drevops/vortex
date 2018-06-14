# grunt-sass-globbing

[![Build Status](https://travis-ci.org/DennisBecker/grunt-sass-globbing.svg?branch=master)](https://travis-ci.org/DennisBecker/grunt-sass-globbing)

> Create an import map file with @import from a configured path

> This plugin can be used with libsass, Ruby Sass, PostCSS or Less

> Initially it's intend was to offer an alternative for Ruby Gem sass-globbing used with Ruby SASS

## Supported CSS preprocessors

### Ruby Sass

This plugin can be used with Ruby Sass as an alternative for the Ruby Gem `sass-globbing`. It might increase speed on compile time but there is no comparison yet.

### libsass

libsass (and Ruby Sass) do not support globbing out-of-the-box. This plugin helps you migrating existing projects from Ruby Sass to libsass.

### PostCSS

With the release of v1.2.0 the generated @import map file is compatible with PostCSS.

### Less

As for the other CSS preprocessors, Less also supports the same type of @import statements, so you can configure this plugin to use it with Less.


## Getting Started

This plugin requires Grunt `~0.4.5`

If you haven't used [Grunt](http://gruntjs.com/) before, be sure to check out the [Getting Started](http://gruntjs.com/getting-started) guide, as it explains how to create a [Gruntfile](http://gruntjs.com/sample-gruntfile) as well as install and use Grunt plugins. Once you're familiar with that process, you may install this plugin with this command:

```shell
npm install grunt-sass-globbing --save-dev
```

Once the plugin has been installed, it may be enabled inside your Gruntfile with this line of JavaScript:

```js
grunt.loadNpmTasks('grunt-sass-globbing');
```

## The "sass_globbing" task

### Overview

In your project's Gruntfile, add a section named `sass_globbing` to the data object passed into `grunt.initConfig()`.

### Usage Example

In this example, an import map from a defined path will be created. You can also ignore files, you will find details about the syntax at [node-glob](https://github.com/isaacs/node-glob).

You should exclude the generated file from your version control system.

#### Usage with all options

```js
grunt.initConfig({
  sass_globbing: {
    your_target: {
      files: {
        'src/_importMap.scss': 'src/partials/**/*.scss',
        'src/_variablesMap.scss': ['src/variables/**/*.scss', '!src/variables/foo.css'],
      },
      options: {
        useSingleQuotes: false,
        signature: '// Hello, World!'
      }
    }
  }
});
```

#### Usage with CSS prepocessors other than Sass engines

If you want to use an other CSS preprocessor, just change the file extension from `.scss` to the file extension supported by the preprocessor.

### Options

#### useSingleQuotes
Type: `Boolean`
Default: `false`

Determines whether single or double quotes are used around import statements.

* `false` - Double quotes are used.
* `true` - Single quotes are used.

#### signature
Type: `string`
Default: `/* generated with grunt-sass-globbing */\n\n`

Sets the signature for the map files.

* `false` - Disables adding of signature.

### Usage in SCSS file

In this example, your file is located in src/screen.scss. This file imports the generated map files
described iin the example Gruntfile above.

```scss
@import "importMap";
@import "variablesMap";

// more imports or rules
```

## Contributing

In lieu of a formal styleguide, take care to maintain the existing coding style. Add unit tests for any new or changed functionality. Lint and test your code using [Grunt](http://gruntjs.com/).

## Release History

For detailed release information have a look at the [change log](CHANGELOG.md)
