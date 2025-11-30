@@ -318,19 +318,6 @@
 ahoy drush eval "\\Drupal::service('cache.backend.redis')->deleteAll();"
 ```
 
-### ClamAV Virus Scanning Service
-
-```bash
-# Test virus scanning functionality
-ahoy drush clamav:scan /path/to/test/file
-
-# Check ClamAV daemon status
-ahoy drush clamav:status
-
-# Update virus definitions
-ahoy drush clamav:update
-```
-
 ## Dependency Management
 
 ### Adding Drupal Modules
