@@ -166,15 +166,6 @@
 ahoy drush search-api:clear
 ```
 
-### Valkey (Redis-compatible caching)
-```bash
-# Check cache status
-ahoy drush cache:rebuild
-
-# Clear Redis/Valkey cache
-ahoy drush php-eval "\Drupal\redis\Client\ClientInterface::flushAll();"
-```
-
 ### ClamAV Virus Scanning
 ```bash
 # Test virus scanning functionality
