@@ -305,19 +305,6 @@
 ahoy drush search-api:server-status
 ```
 
-### Redis Caching Service
-
-```bash
-# Clear all caches (includes Redis)
-ahoy drush cache:rebuild
-
-# Check Redis connection status
-ahoy drush php:script -- redis_status
-
-# Flush Redis cache specifically
-ahoy drush eval "\\Drupal::service('cache.backend.redis')->deleteAll();"
-```
-
 ### ClamAV Virus Scanning Service
 
 ```bash
