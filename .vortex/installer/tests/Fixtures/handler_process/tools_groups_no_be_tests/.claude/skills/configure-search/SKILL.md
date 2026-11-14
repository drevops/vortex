@@ -45,8 +45,6 @@
 
 In this project the server ID is `solr` and the index ID is `content`. Use them wherever this skill says `<server_id>` and `<index_id>`.
 
-`tests/behat/features/search.feature` indexes 3 pages and searches them through `/search`. Run it after a change with `ahoy test-bdd -- --tags=@search`.
-
 ## 2. Change configuration the safe way
 
 1. Make the change in the administration UI at `/admin/config/search/search-api`, or edit the exported YAML in `config/default/` and run `ahoy drush cim`. Get a login link with `ahoy login`.
