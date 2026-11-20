---
name: create-content-type
description: Use when adding or changing the site's content model, such as content types, fields, form and view displays, view modes, taxonomy vocabularies and the fields that reference them, URL alias patterns or a content model recipe, or when exporting, applying or checking such a change.
---

# Create or change the content model

The content model is configuration: content types, field storages, fields, form displays, view displays and vocabularies. Build it on the local site, check it, then ship it as exported configuration or as a recipe.

Run every command from the project root through `ahoy`, 1 command per Bash call. Never use `drush php:eval`: write ad-hoc PHP to a file under `.artifacts/tmp/create-content-type/` and run it with `ahoy drush php:script /app/.artifacts/tmp/create-content-type/<file>.php`. The project is mounted at `/app` in the `cli` container. Ready-made scripts are in [references/scripts.md](references/scripts.md), and the recipe format is in [references/recipes.md](references/recipes.md).

`ahoy drush` hands its arguments to a second shell, so escape the quotes of a value with spaces: `--field-label=\"Event date\"`.

## Rules

- Use the `manage-config` skill for every export and import. It owns the previews and the approval steps.
- Machine names are permanent. A content type, vocabulary or field can't be renamed, and a field can't change its type once it holds data. Agree on the machine names with the developer before you create anything.
- Prefix new field names with `field_`, as the Field UI does. Reuse an existing field storage, such as `body` or `field_tags`, on another bundle instead of creating a second field for the same data.
- Never delete a content type, vocabulary or field that the developer didn't name. Deleting a field deletes its data on every environment that imports the change.
- A change that has to transform existing content, such as copying values into a new field, needs an update or deploy hook in a custom module. Use the `create-module` skill for it.

## Inspect the current model

1. List the bundles and their fields: copy the listing script from [references/scripts.md](references/scripts.md) to `.artifacts/tmp/create-content-type/list-model.php`, then run `ahoy drush php:script /app/.artifacts/tmp/create-content-type/list-model.php`.
2. Read the fields of 1 bundle: `ahoy drush field:info node <type>`
3. Read 1 item: `ahoy drush config:get node.type.<type>`. An item that doesn't exist prints an error.
4. Check what is already exported: `ls config/default`

The site can hold content types that no file in the repository describes, because the database was provisioned from a dump or an installation profile.

## The shipped content model

The template ships its own content model as the Drupal recipe `recipes/page`:

- `recipe.yml` installs the `node`, `text` and `path` modules and sets `config: strict: false`.
- `recipes/page/config/` holds the `page` content type (`node.type.page`), the `body` field storage and field, the default form display and the default and teaser view displays.
- `scripts/provision-00-enable-demo-modules.sh` applies it with `./vendor/bin/dr recipe "$(pwd)/recipes/page" --no-interaction`, before it installs the site modules, because those modules attach behavior to the `page` type. Like the rest of that script, this runs only in the `local`, `ci`, `dev` and `stage` environments.
- `strict: false` makes the recipe create only the items the site doesn't have yet. On a site that already has the `page` type, applying the recipe changes nothing. Editing a file in `recipes/page/config/` therefore never updates an existing site: change the site, then export the configuration, or add a config action to `recipe.yml`.
- `.gitignore` ignores everything under `recipes/` except `recipes/page`.

- `sw_demo` lists `page` nodes at `/pages` through `views.view.sw_demo_pages`, so keep the type while the demo module is installed.

- `sw_search_deploy_add_editorial_workflow()` in `sw_search.deploy.php` attaches the editorial workflow to the `page` type.

## Choose how the change ships

- **Exported configuration in `config/default`.** This is the default for the project's own content model. Provisioning imports `config/default` on every environment, production included, so the model reaches every site.
- **A recipe in `recipes/<name>/`.** Use it for a content model that has to be applied to a site that doesn't import configuration, such as the demo site that provisioning builds before the project exports its configuration, or for a model several sites share. A recipe applies to production only when something runs it there.

The template ships `config/default` with no configuration in it, and provisioning skips the configuration import while it holds no `.yml` files. Ask the developer before the first export, and make that first export a full one. A `config/default` that holds only a few items makes the provisioning import fail, because `core.extension` is missing.

## Build the change on the local site

Get an administrator login link with `ahoy login`. The structure pages need the core Field UI module. When they return 404, ask before running `ahoy drush pm:install field_ui`, because the installed module becomes part of the next export.

| Task | Page |
|------|------|
| Add a content type | `/admin/structure/types/add` |
| Add, reuse or edit fields | `/admin/structure/types/manage/<type>/fields` |
| Arrange the edit form | `/admin/structure/types/manage/<type>/form-display` |
| Arrange the output | `/admin/structure/types/manage/<type>/display/default`, and `/display/teaser` for the teaser |
| Add a vocabulary | `/admin/structure/taxonomy/add` |
| Add fields to a vocabulary | `/admin/structure/taxonomy/manage/<vocabulary>/overview/fields` |
| Grant permissions per content type | `/admin/structure/types/manage/<type>/permissions` |

Fields can also be created from the command line. Pass every option, so the command asks nothing. `field:create` adds the field to the default form display and the default view display.

- A new field: `ahoy drush field:create node event --field-name=field_event_date --field-label=\"Event date\" --field-type=datetime --field-widget=datetime_default --is-required=0 --cardinality=1`
- A reference to a vocabulary: `ahoy drush field:create node event --field-name=field_tags --field-label=Tags --field-type=entity_reference --target-type=taxonomy_term --target-bundle=tags --field-widget=entity_reference_autocomplete_tags --is-required=0 --cardinality=-1`
- An existing field storage on another bundle: `ahoy drush field:create node event --existing --existing-field-name=body --field-label=Body --is-required=0`

The field type's module has to be installed: `datetime` for date fields, `link` for links, `image` for images. Drush has no command that creates a content type, so use the admin page or the script in [references/scripts.md](references/scripts.md).

A new content type adds permissions such as `create <type> content`. Granting them to a role changes that role's `user.role.<role>` configuration, so export it with the rest.

## Export the change

1. Run `ahoy drush cr`, then `ahoy drush config:status` to list the changed items.
2. Export with the `manage-config` skill, previewing first.
3. Check that the export holds every item the change created, and nothing the developer didn't change. A new content type usually brings `node.type.<type>`, `field.storage.node.<field>`, `field.field.node.<type>.<field>`, `core.entity_form_display.node.<type>.default`, `core.entity_view_display.node.<type>.default` and `core.entity_view_display.node.<type>.teaser`.

## Ship it as a recipe

1. Create `recipes/<name>/recipe.yml` and `recipes/<name>/config/`. [references/recipes.md](references/recipes.md) has the file format.
2. Create the scratch directory's parent, which Drush requires to exist: `mkdir -p .artifacts/tmp/create-content-type`
3. Export the site's configuration to the scratch directory, which leaves `config/default` untouched: `ahoy drush config:export --destination=/app/.artifacts/tmp/create-content-type/export -y`
4. Copy each item of the change from `.artifacts/tmp/create-content-type/export/` to `recipes/<name>/config/`.
5. In each copy, remove the `uuid:` line and the `_core:` block, so the recipe creates the items with new identifiers on each site.
6. List every module the items depend on under `install` in `recipe.yml`. Their names are in the `dependencies.module` key of each item.
7. Add `!recipes/<name>` below `recipes/*` in `.gitignore`. Git ignores everything else under `recipes/`, because Composer installs contributed recipes there.
8. Validate the recipe: `ahoy cli ./vendor/bin/dr recipe:info /app/recipes/<name>`
9. Apply it: `ahoy cli ./vendor/bin/dr recipe /app/recipes/<name> --no-interaction`

To apply the recipe during provisioning, add the same command to a provision script, before anything that depends on the content model. Each provision script checks its own environment, and the template's demo script skips production.

With `strict: false`, a recipe never changes an item that already exists. To change existing items, such as placing a field on an existing display or granting a permission, add config actions to `recipe.yml`.

## Shipped modules that work per content type

Some modules keep settings per content type. List the enabled modules with `ahoy drush pm:list --type=module --status=enabled`, and configure the new type in each one the site uses.

### Pathauto

- Add a URL alias pattern for the content type at `/admin/config/search/path/patterns/add`. It exports as `pathauto.pattern.<id>`.
- Keep the `path` component on the content type's form display, so editors can override the alias.
- Generate aliases for existing content: `ahoy drush pathauto:aliases-generate create canonical_entities:node`

### Redirect

- The Redirect module's `auto_redirect` setting is on by default, so changing a node's alias creates a redirect from the old one. Check it with `ahoy drush config:get redirect.settings auto_redirect`.

### XML sitemap

- Decide with the developer whether the content type belongs in the sitemap, and set it at `/admin/config/search/xmlsitemap/settings/node/<type>`. It exports as `xmlsitemap.settings.node.<type>`.

### Generated Content

- To fill the new type with placeholder content on development sites, add a plugin to a custom module in `src/Plugin/GeneratedContent/Node/<Type>.php` with the `#[GeneratedContent]` attribute. See [Generated Content](https://www.vortextemplate.com/docs/development/modules/generated-content).
- Generate the content: `ahoy drush generated-content:create-content node <type> 10`

### Search and moderation

- The `content` index in `web/modules/custom/sw_search/config/install/search_api.index.content.yml` indexes every node bundle, so the search indexes a new content type with no change to the index.
- `sw_search` installs Content Moderation and attaches the editorial workflow only to the `page` type. To moderate a new type, add it at `/admin/config/workflow/workflows/manage/editorial`. A node of a moderated type takes the workflow's default moderation state, whatever its `status`, so code that creates published nodes sets `moderation_state` to `published`.

- Use the `configure-search` skill to add the new fields to the index or tune their relevance.

## Check the result

1. Rebuild caches: `ahoy drush cr`
2. Confirm the fields: `ahoy drush field:info node <type>`
3. Open `/node/add/<type>` as an administrator, create a node titled `[TEST] <type>` with every field filled in, and check the edit form and the page against the form and view displays you set.
4. Log in as a user with the editor role, if the site has one, and check that the form shows the fields that role needs.
5. Check for errors: `ahoy drush watchdog:show --count=20`
6. Delete the `[TEST]` node.
7. Confirm that `ahoy drush config:status` reports no differences after the export, or that the recipe applies with no error on a site that already has the items.

To cover the content type with end-to-end scenarios, follow the content type testing process in `docs/testing.md` and use the `write-tests` skill.
