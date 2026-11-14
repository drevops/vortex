@@ -99,25 +99,6 @@
 
 Some modules keep settings per content type. List the enabled modules with `ahoy drush pm:list --type=module --status=enabled`, and configure the new type in each one the site uses.
 
-### Pathauto
-
-- Add a URL alias pattern for the content type at `/admin/config/search/path/patterns/add`. It exports as `pathauto.pattern.<id>`.
-- Keep the `path` component on the content type's form display, so editors can override the alias.
-- Generate aliases for existing content: `ahoy drush pathauto:aliases-generate create canonical_entities:node`
-
-### Redirect
-
-- The Redirect module's `auto_redirect` setting is on by default, so changing a node's alias creates a redirect from the old one. Check it with `ahoy drush config:get redirect.settings auto_redirect`.
-
-### XML sitemap
-
-- Decide with the developer whether the content type belongs in the sitemap, and set it at `/admin/config/search/xmlsitemap/settings/node/<type>`. It exports as `xmlsitemap.settings.node.<type>`.
-
-### Generated Content
-
-- To fill the new type with placeholder content on development sites, add a plugin to a custom module in `src/Plugin/GeneratedContent/Node/<Type>.php` with the `#[GeneratedContent]` attribute. See [Generated Content](https://www.vortextemplate.com/docs/development/modules/generated-content).
-- Generate the content: `ahoy drush generated-content:create-content node <type> 10`
-
 ### Search and moderation
 
 - The `content` index in `web/modules/custom/sw_search/config/install/search_api.index.content.yml` indexes every node bundle, so the search indexes a new content type with no change to the index.
