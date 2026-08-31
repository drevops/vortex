// Mirrors the Drupal core configuration from
// https://www.drupal.org/project/drupal/issues/3440225, adapted to lint the
// theme's own sources. The theme carries its own front-end tooling because it
// can be moved into a separate repository.
import { defineConfig } from 'eslint/config';
import { fixupPluginRules } from '@eslint/compat';
import importPlugin from 'eslint-plugin-import';
import noJQuery from 'eslint-plugin-no-jquery';
import prettierConfig from 'eslint-plugin-prettier/recommended';
import ymlConfig from 'eslint-plugin-yml';
import jsdoc from 'eslint-plugin-jsdoc';
import js from '@eslint/js';
import globals from 'globals';

export default defineConfig(
  {
    ignores: ['node_modules/**/*', 'build/**/*', '**/*.min.js'],
  },
  js.configs.recommended,
  importPlugin.flatConfigs.recommended,
  jsdoc.configs['flat/recommended'],
  prettierConfig,
  ymlConfig.configs['flat/recommended'],
  {
    plugins: {
      'no-jquery': fixupPluginRules(noJQuery),
    },
    languageOptions: {
      ecmaVersion: 2020,
      globals: {
        ...globals.browser,
        ...globals.node,

        Drupal: 'readonly',
        drupalSettings: 'readonly',
        drupalTranslations: 'readonly',
        jQuery: 'readonly',
        _: 'readonly',
        Cookies: 'readonly',
        Backbone: 'readonly',
        htmx: 'readonly',
        loadjs: 'readonly',
        Shepherd: 'readonly',
        Sortable: 'readonly',
        once: 'readonly',
        CKEditor5: 'readonly',
        CKEDITOR: 'readonly',
        tabbable: 'readonly',
        transliterate: 'readonly',
        bodyScrollLock: 'readonly',
        FloatingUIDOM: 'readonly',
      },
    },
    rules: {
      'prettier/prettier': 'error',
      'no-unexpected-multiline': ['off'],
      'consistent-return': ['off'],
      'no-underscore-dangle': ['off'],
      'max-nested-callbacks': ['warn', 3],
      'import/no-mutable-exports': ['warn'],
      'no-plusplus': [
        'warn',
        {
          allowForLoopAfterthoughts: true,
        },
      ],
      'no-param-reassign': ['off'],
      'no-prototype-builtins': ['off'],
      'no-unused-vars': ['warn'],
      // Kept from the previous ruleset: console calls left in shipped code
      // print to every visitor's browser.
      'no-console': 'error',
      'operator-linebreak': [
        'error',
        'after',
        {
          overrides: {
            '?': 'ignore',
            ':': 'ignore',
          },
        },
      ],
      'yml/indent': ['error', 2],
      // The Drupal docblock style separates the description from the first tag
      // with a blank line, which the preset forbids by default.
      'jsdoc/tag-lines': ['warn', 'any', { startLines: 1 }],
      // Drupal front-end code documents contracts, not every function.
      'jsdoc/require-jsdoc': ['off'],
      ...noJQuery.configs.all.rules,
    },
    settings: {
      jsdoc: {
        tagNamePreference: {
          returns: 'return',
          property: 'prop',
        },
      },
    },
  },
);
