@@ -28,20 +28,6 @@
 
 The site can hold content types that no file in the repository describes, because the database was provisioned from a dump or an installation profile.
 
-## The shipped content model
-
-The template ships its own content model as the Drupal recipe `recipes/page`:
-
-- `recipe.yml` installs the `node`, `text` and `path` modules and sets `config: strict: false`.
-- `recipes/page/config/` holds the `page` content type (`node.type.page`), the `body` field storage and field, the default form display and the default and teaser view displays.
-- `scripts/provision-00-enable-demo-modules.sh` applies it with `./vendor/bin/dr recipe "$(pwd)/recipes/page" --no-interaction`, before it installs the site modules, because those modules attach behavior to the `page` type. Like the rest of that script, this runs only in the `local`, `ci`, `dev` and `stage` environments.
-- `strict: false` makes the recipe create only the items the site doesn't have yet. On a site that already has the `page` type, applying the recipe changes nothing. Editing a file in `recipes/page/config/` therefore never updates an existing site: change the site, then export the configuration, or add a config action to `recipe.yml`.
-- `.gitignore` ignores everything under `recipes/` except `recipes/page`.
-
-- `sw_demo` lists `page` nodes at `/pages` through `views.view.sw_demo_pages`, so keep the type while the demo module is installed.
-
-- `sw_search_deploy_add_editorial_workflow()` in `sw_search.deploy.php` attaches the editorial workflow to the `page` type.
-
 ## Choose how the change ships
 
 - **Exported configuration in `config/default`.** This is the default for the project's own content model. Provisioning imports `config/default` on every environment, production included, so the model reaches every site.
@@ -117,13 +103,6 @@
 
 - To fill the new type with placeholder content on development sites, add a plugin to a custom module in `src/Plugin/GeneratedContent/Node/<Type>.php` with the `#[GeneratedContent]` attribute. See [Generated Content](https://www.vortextemplate.com/docs/development/modules/generated-content).
 - Generate the content: `ahoy drush generated-content:create-content node <type> 10`
-
-### Search and moderation
-
-- The `content` index in `web/modules/custom/sw_search/config/install/search_api.index.content.yml` indexes every node bundle, so the search indexes a new content type with no change to the index.
-- `sw_search` installs Content Moderation and attaches the editorial workflow only to the `page` type. To moderate a new type, add it at `/admin/config/workflow/workflows/manage/editorial`. A node of a moderated type takes the workflow's default moderation state, whatever its `status`, so code that creates published nodes sets `moderation_state` to `published`.
-
-- Use the `configure-search` skill to add the new fields to the index or tune their relevance.
 
 ## Check the result
 
