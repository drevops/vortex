@@ -4,7 +4,7 @@
 // Discover js/ directories in custom modules, resolving symlinks to real
 // paths. Jest resolves symlinks internally, so roots must use real paths
 // for test files to be matched.
-const dirs = ['web/modules/custom'];
+const dirs = ['docroot/modules/custom'];
 const roots = [];
 
 dirs.forEach((dir) => {
@@ -20,8 +20,8 @@
 
 module.exports = {
   testEnvironment: 'jest-environment-jsdom',
-  roots: roots.length > 0 ? roots : ['web/modules/custom'],
+  roots: roots.length > 0 ? roots : ['docroot/modules/custom'],
   testMatch: ['**/*.test.js'],
   testPathIgnorePatterns: ['/node_modules/', '/vendor/'],
-  modulePathIgnorePatterns: ['web/core/', 'web/modules/contrib/', 'web/themes/contrib/'],
+  modulePathIgnorePatterns: ['docroot/core/', 'docroot/modules/contrib/', 'docroot/themes/contrib/'],
 };
