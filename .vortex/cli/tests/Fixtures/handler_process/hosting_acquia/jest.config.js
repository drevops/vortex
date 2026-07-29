@@ -4,7 +4,7 @@
 // Discover js/ directories in custom modules, resolving symlinks to real
 // paths. Jest resolves symlinks internally, so roots must use real paths
 // for test files to be matched.
-const dirs = ['web/modules/custom'];
+const dirs = ['docroot/modules/custom'];
 const roots = [];
 
 dirs.forEach((dir) => {
@@ -20,16 +20,16 @@
 
 module.exports = {
   testEnvironment: 'jest-environment-jsdom',
-  roots: roots.length > 0 ? roots : [path.resolve('web/modules/custom')],
-  testRegex: 'web/modules/custom/.+\\.test\\.js$',
+  roots: roots.length > 0 ? roots : [path.resolve('docroot/modules/custom')],
+  testRegex: 'docroot/modules/custom/.+\\.test\\.js$',
   testPathIgnorePatterns: ['/node_modules/', '/vendor/'],
   modulePathIgnorePatterns: [
-    '<rootDir>/web/core/',
-    '<rootDir>/web/modules/contrib/',
-    '<rootDir>/web/themes/',
+    '<rootDir>/docroot/core/',
+    '<rootDir>/docroot/modules/contrib/',
+    '<rootDir>/docroot/themes/',
     '<rootDir>/.vortex/',
   ],
-  collectCoverageFrom: ['web/modules/custom/**/js/**/*.js', '!**/*.test.js'],
+  collectCoverageFrom: ['docroot/modules/custom/**/js/**/*.js', '!**/*.test.js'],
   coverageReporters: ['text', 'lcov', 'html', ['cobertura', { file: 'cobertura.xml' }]],
   coverageDirectory: '.logs/coverage/jest',
 };
