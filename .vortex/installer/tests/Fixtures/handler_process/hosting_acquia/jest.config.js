@@ -4,7 +4,7 @@
 // Discover js/ directories in custom modules, resolving symlinks to real
 // paths. Jest resolves symlinks internally, so roots must use real paths
 // for test files to be matched.
-const dirs = ['web/modules/custom'];
+const dirs = ['docroot/modules/custom'];
 const roots = [];
 
 dirs.forEach((dir) => {
@@ -20,13 +20,13 @@
 
 module.exports = {
   testEnvironment: 'jest-environment-jsdom',
-  roots: roots.length > 0 ? roots : [path.resolve('web/modules/custom')],
-  testMatch: ['**/web/modules/custom/*/js/**/*.test.js'],
+  roots: roots.length > 0 ? roots : [path.resolve('docroot/modules/custom')],
+  testMatch: ['**/docroot/modules/custom/*/js/**/*.test.js'],
   testPathIgnorePatterns: ['/node_modules/', '/vendor/'],
   modulePathIgnorePatterns: [
-    '<rootDir>/web/core/',
-    '<rootDir>/web/modules/contrib/',
-    '<rootDir>/web/themes/contrib/',
-    '<rootDir>/web/themes/custom/',
+    '<rootDir>/docroot/core/',
+    '<rootDir>/docroot/modules/contrib/',
+    '<rootDir>/docroot/themes/contrib/',
+    '<rootDir>/docroot/themes/custom/',
   ],
 };
