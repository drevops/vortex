@@ -288,23 +288,6 @@
 
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
 ### Redis Caching Service
 
 ```bash
