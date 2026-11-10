@@ -109,10 +109,6 @@
 
 - The Redirect module's `auto_redirect` setting is on by default, so changing a node's alias creates a redirect from the old one. Check it with `ahoy drush config:get redirect.settings auto_redirect`.
 
-### XML sitemap
-
-- Decide with the developer whether the content type belongs in the sitemap, and set it at `/admin/config/search/xmlsitemap/settings/node/<type>`. It exports as `xmlsitemap.settings.node.<type>`.
-
 ### Generated Content
 
 - To fill the new type with placeholder content on development sites, add a plugin to a custom module in `src/Plugin/GeneratedContent/Node/<Type>.php` with the `#[GeneratedContent]` attribute. See [Generated Content](https://www.vortextemplate.com/docs/development/modules/generated-content).
