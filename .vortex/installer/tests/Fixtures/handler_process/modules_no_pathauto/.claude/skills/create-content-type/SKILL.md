@@ -99,12 +99,6 @@
 
 Some modules keep settings per content type. List the enabled modules with `ahoy drush pm:list --type=module --status=enabled`, and configure the new type in each one the site uses.
 
-### Pathauto
-
-- Add a URL alias pattern for the content type at `/admin/config/search/path/patterns/add`. It exports as `pathauto.pattern.<id>`.
-- Keep the `path` component on the content type's form display, so editors can override the alias.
-- Generate aliases for existing content: `ahoy drush pathauto:aliases-generate create canonical_entities:node`
-
 ### Redirect
 
 - The Redirect module's `auto_redirect` setting is on by default, so changing a node's alias creates a redirect from the old one. Check it with `ahoy drush config:get redirect.settings auto_redirect`.
