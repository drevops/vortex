@@ -82,8 +82,6 @@
   // PHP version upgrade sets - modernizes syntax to PHP 8.4.
   // Includes all rules from PHP 5.3 through 8.4.
   ->withPhpSets(php84: TRUE)
-  // Behat attribute sets - converts annotations to PHP 8 attributes.
-  ->withAttributesSets(behat: TRUE)
   // Code quality improvement sets.
   ->withPreparedSets(
     codeQuality: TRUE,
