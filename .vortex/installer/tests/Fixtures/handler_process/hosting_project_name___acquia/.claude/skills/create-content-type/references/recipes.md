@@ -53,7 +53,7 @@
 The keys are:
 
 - `name`, `description` and `type`: labels that `recipe:info` prints. `name` is required.
-- `recipes`: other recipes to apply first. A bare name such as `tags` points to a sibling directory, `recipes/tags`. A path with a slash, such as `core/recipes/tags_taxonomy`, is resolved from the `web/` directory. Core ships its recipes in `web/core/recipes/`.
+- `recipes`: other recipes to apply first. A bare name such as `tags` points to a sibling directory, `recipes/tags`. A path with a slash, such as `core/recipes/tags_taxonomy`, is resolved from the `docroot/` directory. Core ships its recipes in `docroot/core/recipes/`.
 - `install`: modules to install before the configuration is created. List every module that an item in `config/` names under `dependencies.module`.
 - `config.strict`: `false` creates only the items the site doesn't have yet and leaves existing items untouched. With `true`, the default, the recipe fails when an existing item differs from its file.
 - `config.import`: configuration to take from an installed module's own `config/install` directory, either `'*'` or a list of names, for example `taxonomy: '*'`.
