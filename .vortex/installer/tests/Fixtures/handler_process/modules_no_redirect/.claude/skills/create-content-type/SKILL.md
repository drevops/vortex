@@ -105,10 +105,6 @@
 - Keep the `path` component on the content type's form display, so editors can override the alias.
 - Generate aliases for existing content: `ahoy drush pathauto:aliases-generate create canonical_entities:node`
 
-### Redirect
-
-- The Redirect module's `auto_redirect` setting is on by default, so changing a node's alias creates a redirect from the old one. Check it with `ahoy drush config:get redirect.settings auto_redirect`.
-
 ### XML sitemap
 
 - Decide with the developer whether the content type belongs in the sitemap, and set it at `/admin/config/search/xmlsitemap/settings/node/<type>`. It exports as `xmlsitemap.settings.node.<type>`.
