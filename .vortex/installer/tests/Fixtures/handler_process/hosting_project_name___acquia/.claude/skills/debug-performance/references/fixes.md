@@ -12,7 +12,7 @@
 
 When Drupal renders a tree, each child's cacheability merges into its parents, up to the response: tags and contexts add up, and the lowest max-age wins. 1 element with `max-age` 0 or a `user` context deep in the tree makes the whole page uncacheable for Dynamic Page Cache, unless that element becomes a placeholder.
 
-The `required_cache_contexts` in `web/sites/default/services.yml` add `languages:language_interface`, `theme` and `user.permissions` to every render array, so never add those by hand.
+The `required_cache_contexts` in `docroot/sites/default/services.yml` add `languages:language_interface`, `theme` and `user.permissions` to every render array, so never add those by hand.
 
 ## Declare cacheability with the data
 
