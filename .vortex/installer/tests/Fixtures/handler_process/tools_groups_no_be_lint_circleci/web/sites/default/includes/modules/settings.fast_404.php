@@ -55,6 +55,5 @@
   // the module passes an array where an exception is expected. Patch it with
   // https://www.drupal.org/files/issues/2023-08-24/fast_404_3x-3194034-10.patch
   // @see https://www.drupal.org/project/fast_404/issues/3194034
-  // @phpstan-ignore-next-line
   fast404_preboot($settings);
 }
