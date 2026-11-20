@@ -38,9 +38,9 @@
 - `strict: false` makes the recipe create only the items the site doesn't have yet. On a site that already has the `page` type, applying the recipe changes nothing. Editing a file in `recipes/page/config/` therefore never updates an existing site: change the site, then export the configuration, or add a config action to `recipe.yml`.
 - `.gitignore` ignores everything under `recipes/` except `recipes/page`.
 
-- `sw_demo` lists `page` nodes at `/pages` through `views.view.sw_demo_pages`, so keep the type while the demo module is installed.
+- `the_force_demo` lists `page` nodes at `/pages` through `views.view.the_force_demo_pages`, so keep the type while the demo module is installed.
 
-- `sw_search_deploy_add_editorial_workflow()` in `sw_search.deploy.php` attaches the editorial workflow to the `page` type.
+- `the_force_search_deploy_add_editorial_workflow()` in `the_force_search.deploy.php` attaches the editorial workflow to the `page` type.
 
 ## Choose how the change ships
 
@@ -120,8 +120,8 @@
 
 ### Search and moderation
 
-- The `content` index in `web/modules/custom/sw_search/config/install/search_api.index.content.yml` indexes every node bundle, so the search indexes a new content type with no change to the index.
-- `sw_search` installs Content Moderation and attaches the editorial workflow only to the `page` type. To moderate a new type, add it at `/admin/config/workflow/workflows/manage/editorial`. A node of a moderated type takes the workflow's default moderation state, whatever its `status`, so code that creates published nodes sets `moderation_state` to `published`.
+- The `content` index in `web/modules/custom/the_force_search/config/install/search_api.index.content.yml` indexes every node bundle, so the search indexes a new content type with no change to the index.
+- `the_force_search` installs Content Moderation and attaches the editorial workflow only to the `page` type. To moderate a new type, add it at `/admin/config/workflow/workflows/manage/editorial`. A node of a moderated type takes the workflow's default moderation state, whatever its `status`, so code that creates published nodes sets `moderation_state` to `published`.
 
 - Use the `configure-search` skill to add the new fields to the index or tune their relevance.
 
