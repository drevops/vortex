# Review checklist

The team owns this file. Add, change or remove items to match how the
project works. `AGENTS.md` and `docs/` override anything here.

## Security

- User input reaches SQL only through placeholders or the query builder,
  never through string concatenation.
- Render arrays use `#plain_text` or a template for values that are not
  trusted, not `#markup` with variables.
- Twig templates do not apply `|raw` to values that users can change.
- `t()` and `$this->t()` pass user values through `@` or `%`
  placeholders.
- Every new route declares `requirements` such as `_permission`. A route
  with `_access: 'TRUE'` is intentionally public.
- Entity queries call `accessCheck()` explicitly. `accessCheck(FALSE)`
  appears only where the code checks access itself.
- State-changing actions use Form API forms or a route with
  `_csrf_token: 'TRUE'`.
- Secrets come from environment variables, not from code, config or
  settings files.

## Drupal APIs

- Classes receive services through dependency injection. `\Drupal::`
  static calls appear only in procedural code such as `.module` and
  `.install` files.
- Code loads entities with `loadMultiple()` instead of `load()` inside a
  loop.
- New hooks follow the style the module already uses.

## Caching

- Output that varies by user, role, route or query parameter declares the
  matching `#cache` contexts.
- Output built from entities or config declares their cache tags.
- Code sets `max-age` to `0` only with a comment that explains why.

## Configuration and updates

- Configuration changes are exported to `config/default`.
- Custom modules that add configuration ship a schema in
  `config/schema/`.
- Changes that need existing data or configuration transformed ship a
  `hook_update_N()`, `hook_post_update_NAME()` or a deploy hook in
  `*.deploy.php`.
- Settings for a contributed module go in
  `web/sites/default/includes/modules/`.

## Tests

- Changed behavior has a test: PHPUnit in the module's `tests/`
  directory, a Behat scenario in `tests/behat/features/`, or a test in
  the suite that already covers the changed code.
- Behat scenarios wait for conditions, not for a fixed number of seconds.

## Front end

- JavaScript attaches through `Drupal.behaviors` and uses `once()`.
- Libraries are attached where they are used, not globally.
- Twig marks user-facing strings for translation with `|t` or
  `{% trans %}`.
