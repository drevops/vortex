@@ -175,15 +175,6 @@
 ahoy drush php-eval "\Drupal\redis\Client\ClientInterface::flushAll();"
 ```
 
-### ClamAV Virus Scanning
-```bash
-# Test virus scanning functionality
-ahoy drush clamav:scan
-
-# Check ClamAV status
-ahoy drush clamav:status
-```
-
 ## Environment Configuration
 
 ### Environment Variables (.env)
