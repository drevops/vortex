import js from '@eslint/js';

export default [
  // Apply recommended rules to all files
  js.configs.recommended,

  // Global configuration
  {
    languageOptions: {
      ecmaVersion: 2024,
      sourceType: 'module',
      globals: {
        // Browser globals
        window: 'readonly',
        document: 'readonly',
        console: 'readonly',
        setTimeout: 'readonly',
        clearTimeout: 'readonly',
        queueMicrotask: 'readonly',

        // Library globals
        Alpine: 'readonly',
        Joi: 'readonly',
        joi: 'readonly',

        // Global functions defined in installer.js
        switchTab: 'writable',
        showHelp: 'writable',
        closeHelpSidebar: 'writable',
        validateField: 'writable',
        validateAllFields: 'writable',
        installerData: 'writable',
      },
    },
    rules: {
      // Code quality
      'no-unused-vars': ['error', { argsIgnorePattern: '^_' }],
      'no-console': 'warn',
      'no-debugger': 'error',
      'no-alert': 'warn',

      // Best practices
      eqeqeq: ['error', 'always'],
      curly: ['error', 'all'],
      'no-eval': 'error',
      'no-implied-eval': 'error',
      'no-new-func': 'error',
      'prefer-const': 'error',
      'no-var': 'error',

      // Style (minimal since Prettier handles formatting)
      'arrow-spacing': 'error',
      'template-curly-spacing': 'error',
      'object-curly-spacing': ['error', 'always'],
      'array-bracket-spacing': ['error', 'never'],
    },
  },
  {
    files: ['tests/**/*.js'],
    languageOptions: {
      globals: {
        cy: 'readonly',
        Cypress: 'readonly',
        expect: 'readonly',
        describe: 'readonly',
        it: 'readonly',
        beforeEach: 'readonly',
        afterEach: 'readonly',
        before: 'readonly',
        after: 'readonly',
        context: 'readonly',
      },
    },
    rules: {
      // Relax some rules for test files
      'no-unused-expressions': 'off',
      'no-console': 'off',
    },
  },

  // Ignore patterns
  {
    ignores: [
      'dist/**',
      'node_modules/**',
      '**/*.min.js',
      'public/**/*.js',
      'cypress/videos/**',
      'cypress/screenshots/**',
      '.prettierrc.cjs',
      'vite.config.js',
    ],
  },
];
