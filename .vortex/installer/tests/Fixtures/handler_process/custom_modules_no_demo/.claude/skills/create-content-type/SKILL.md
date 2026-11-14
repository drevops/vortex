@@ -38,8 +38,6 @@
 - `strict: false` makes the recipe create only the items the site doesn't have yet. On a site that already has the `page` type, applying the recipe changes nothing. Editing a file in `recipes/page/config/` therefore never updates an existing site: change the site, then export the configuration, or add a config action to `recipe.yml`.
 - `.gitignore` ignores everything under `recipes/` except `recipes/page`.
 
-- `sw_demo` lists `page` nodes at `/pages` through `views.view.sw_demo_pages`, so keep the type while the demo module is installed.
-
 - `sw_search_deploy_add_editorial_workflow()` in `sw_search.deploy.php` attaches the editorial workflow to the `page` type.
 
 ## Choose how the change ships
