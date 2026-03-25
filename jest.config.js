const fs = require('fs');
const path = require('path');

// Discover js/ directories in custom modules, resolving symlinks to real
// paths. Jest resolves symlinks internally, so roots must use real paths
// for test files to be matched.
const dirs = ['web/modules/custom'];
const roots = [];

dirs.forEach((dir) => {
  if (fs.existsSync(dir)) {
    fs.readdirSync(dir).forEach((name) => {
      const jsDir = path.resolve(dir, name, 'js');
      if (fs.existsSync(jsDir)) {
        roots.push(jsDir);
      }
    });
  }
});

module.exports = {
  testEnvironment: 'jest-environment-jsdom',
  roots: roots.length > 0 ? roots : [path.resolve('web/modules/custom')],
  testMatch: ['**/web/modules/custom/*/js/**/*.test.js'],
  testPathIgnorePatterns: ['/node_modules/', '/vendor/'],
  modulePathIgnorePatterns: [
    '<rootDir>/web/core/',
    '<rootDir>/web/modules/contrib/',
    '<rootDir>/web/themes/contrib/',
    '<rootDir>/web/themes/custom/',
  ],
};
