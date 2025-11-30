@@ -288,49 +288,6 @@
 
 ## Service Integrations
 
-### Solr Search Service
-
-```bash
-# Check Solr search status
-ahoy drush search-api:status
-
-# Index all content to Solr
-ahoy drush search-api:index
-
-# Clear and rebuild Solr index
-ahoy drush search-api:clear
-ahoy drush search-api:index
-
-# Check Solr server connection
-ahoy drush search-api:server-status
-```
-
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
