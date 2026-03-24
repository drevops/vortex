@@ -4,7 +4,7 @@
 // Discover js/ directories in custom modules, resolving symlinks to real
 // paths. Jest resolves symlinks internally, so roots must use real paths
 // for test files to be matched.
-const dirs = ['web/modules/custom'];
+const dirs = ['docroot/modules/custom'];
 const roots = [];
 
 dirs.forEach((dir) => {
