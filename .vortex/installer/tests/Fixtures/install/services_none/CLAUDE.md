@@ -154,36 +154,6 @@
 
 ## Services Integration
 
-### Solr Search
-```bash
-# Check Solr status
-ahoy drush search-api:status
-
-# Index content
-ahoy drush search-api:index
-
-# Clear Solr index
-ahoy drush search-api:clear
-```
-
-### Valkey (Redis-compatible caching)
-```bash
-# Check cache status
-ahoy drush cache:rebuild
-
-# Clear Redis/Valkey cache
-ahoy drush php-eval "\Drupal\redis\Client\ClientInterface::flushAll();"
-```
-
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
