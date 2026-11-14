# Content model recipes

A recipe is a directory with a `recipe.yml` file and an optional `config/` directory. Applying it installs modules, creates the configuration in `config/` and runs config actions. Core keeps no list of applied recipes, so a recipe can be applied again.

## Layout

```text
recipes/event/
├── recipe.yml
└── config/
    ├── core.entity_form_display.node.event.default.yml
    ├── core.entity_view_display.node.event.default.yml
    ├── core.entity_view_display.node.event.teaser.yml
    ├── field.field.node.event.body.yml
    ├── field.field.node.event.field_event_date.yml
    ├── field.storage.node.field_event_date.yml
    └── node.type.event.yml
```

A recipe that adds a bundle ships the storage of each field it creates. It also ships the storage of a shared field, such as `field.storage.node.body.yml`, when the site might not have that field yet.

[//]: # (#;< CONTENT_MODEL)

`recipes/page` follows this layout. Read its files as a working example.

[//]: # (#;> CONTENT_MODEL)

## recipe.yml

```yaml
name: 'Event'
description: 'Creates the Event content type with a date field and default form and view displays.'
type: 'Content type'
recipes:
  - core/recipes/tags_taxonomy
install:
  - node
  - text
  - datetime
  - path
config:
  strict: false
  actions:
    core.entity_form_display.node.event.default:
      setComponent:
        name: field_tags
        options:
          type: entity_reference_autocomplete_tags
          weight: 3
          region: content
    ?user.role.content_editor:
      grantPermissions:
        - 'create event content'
        - 'edit own event content'
```

The keys are:

- `name`, `description` and `type`: labels that `recipe:info` prints. `name` is required.
- `recipes`: other recipes to apply first. A bare name such as `tags` points to a sibling directory, `recipes/tags`. A path with a slash, such as `core/recipes/tags_taxonomy`, is resolved from the `web/` directory. Core ships its recipes in `web/core/recipes/`.
- `install`: modules to install before the configuration is created. List every module that an item in `config/` names under `dependencies.module`.
- `config.strict`: `false` creates only the items the site doesn't have yet and leaves existing items untouched. With `true`, the default, the recipe fails when an existing item differs from its file.
- `config.import`: configuration to take from an installed module's own `config/install` directory, either `'*'` or a list of names, for example `taxonomy: '*'`.
- `config.actions`: changes to items that already exist, keyed by the item name. A name that starts with `?` makes the action optional, so the recipe skips it when the item doesn't exist.

Core actions that a content model recipe uses most:

| Action | Applies to | Effect |
|--------|------------|--------|
| `setComponent` | a form or view display | Places a field with the given widget or formatter options |
| `hideComponent` | a form or view display | Hides a field |
| `grantPermissions` | a user role | Adds permissions to the role |
| `simpleConfigUpdate` | simple configuration such as `system.site` | Sets keys to values |
| `createIfNotExists` | any configuration entity | Creates the entity from the given values when it doesn't exist |
| `setThirdPartySetting` | any configuration entity | Sets 1 setting of another module on the entity |

## Prepare the config files

The items come from the scratch export that the skill describes, in `.artifacts/tmp/create-content-type/export/`.

- Remove the `uuid:` line and the `_core:` block from each file.
- Keep `langcode`, `status` and `dependencies` as they are.
- Keep the file names unchanged, because the name is the item's identifier.
- Don't copy items that the recipe doesn't own, such as `core.extension` or a role. Change those through a config action.

## Validate and apply

```shell
ahoy cli ./vendor/bin/dr recipe:info /app/recipes/event
ahoy cli ./vendor/bin/dr recipe /app/recipes/event --no-interaction
```

`recipe:info` reads and validates `recipe.yml` and prints the description, so it catches a missing module or a malformed action before anything changes. Run each command in its own Bash call.

To test the recipe on a site that doesn't have the content type yet, apply it after the developer agrees to provision a fresh local database. Applying it to a site that already has the items tests only that it doesn't fail.
