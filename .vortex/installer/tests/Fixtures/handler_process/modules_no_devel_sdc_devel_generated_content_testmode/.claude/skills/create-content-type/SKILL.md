@@ -113,11 +113,6 @@
 
 - Decide with the developer whether the content type belongs in the sitemap, and set it at `/admin/config/search/xmlsitemap/settings/node/<type>`. It exports as `xmlsitemap.settings.node.<type>`.
 
-### Generated Content
-
-- To fill the new type with placeholder content on development sites, add a plugin to a custom module in `src/Plugin/GeneratedContent/Node/<Type>.php` with the `#[GeneratedContent]` attribute. See [Generated Content](https://www.vortextemplate.com/docs/development/modules/generated-content).
-- Generate the content: `ahoy drush generated-content:create-content node <type> 10`
-
 ### Search and moderation
 
 - The `content` index in `web/modules/custom/sw_search/config/install/search_api.index.content.yml` indexes every node bundle, so the search indexes a new content type with no change to the index.
