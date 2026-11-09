---
name: secure-code
description: Use when writing or changing code in the site's custom modules or custom theme that handles user input, routes, access checks, database queries, rendered output, forms, file uploads, outbound HTTP requests, credentials, API keys or logging, or when asked to make Drupal code secure or fix a security issue in this project.
---

# Write secure code

Write custom code so that it is safe by construction. This skill covers writing code. To review a finished change, use the `review-code` skill: its security items live in `.claude/skills/review-code/checklist.md`, and the last step below runs the change against them.

Read `AGENTS.md` first. Run every command from the project root, through `ahoy`, 1 command per call. Code examples for each section are in [references/examples.md](references/examples.md).

## Map the trust boundary

Before writing, list for the change:

- **Inputs**: route parameters, query strings, request bodies, form values, cookies, headers, uploaded files, field values that editors can change, configuration that is editable in the UI, and responses from external services. Treat all of them as untrusted.
- **Sinks**: HTML (render arrays, Twig), SQL, URLs and redirects, outbound HTTP requests, file paths, shell commands, logs, emails and `drupalSettings`.
- **Audience**: who can reach the code. Anonymous users, any authenticated user, a role, or the owner of an entity.

Every input that reaches a sink needs the matching control from the sections below.

## Access control

- Give every route in a `*.routing.yml` file an explicit requirement. Drupal denies a route with no requirement, so a missing requirement is a bug, and an over-broad one is a hole.
- Pick the narrowest requirement that fits:
  - `_permission` for a capability. A comma between permissions means all of them, and `+` means any of them.
  - `_entity_access` such as `node.update` for a route that acts on 1 entity from a route parameter.
  - `_custom_access` pointing at a method that returns an `AccessResult` when the rule depends on data.
  - `_access: 'TRUE'` only for a route that is public on purpose.
- Avoid `_role`. Roles change names and get merged; permissions describe the capability.
- Declare new permissions in the module's `*.permissions.yml`. Add `restrict access: true` to a permission that grants control over the site or other users' data.
- Add cacheability to every `AccessResult`: `cachePerPermissions()`, `cachePerUser()` or `addCacheableDependency($entity)`. An access result without it gets cached for the first user who hit it and served to everyone else.
- Call `accessCheck()` on every entity query. Use `accessCheck(TRUE)` for anything shown to or acted on by a user. Use `accessCheck(FALSE)` only in system work such as cron, queues, deploy hooks and migrations, and then check `$entity->access('view')` before rendering any result.
- Check entity access when the code loads an entity from an ID in the request: `$entity->access('view', $account, TRUE)` returns an `AccessResult` with cacheability.
- Check field access with `$entity->get('field_name')->access('view')` when output picks individual fields, as in a JSON response.
- Add the `node_access` tag to a database select query that returns nodes, so node grant modules apply. Entity queries with `accessCheck(TRUE)` do this already.
- Controllers that return data outside the render system, such as a `JsonResponse`, do no access checks of their own beyond the route requirement. Check access to each item they return.
- After granting a permission to a role in the UI, export the configuration with `ahoy drush cex`.

## Injection

- Build SQL through the database API: `select()` with `condition()`, or `query()` with named placeholders such as `:nid`. Use a `:name[]` placeholder for a list of values.
- Pass user input through `$connection->escapeLike()` before adding `%` wildcards to a `LIKE` condition.
- Never take a table name, column name, sort field or sort direction from input. Map the input onto an allowlist in code.
- Prefer an entity query over SQL for entity data, so access checks and field storage stay correct.
- Never pass input to `unserialize()`. Decode JSON with `json_decode()` and the `JSON_THROW_ON_ERROR` flag.
- Never call `eval()`, `exec()`, `shell_exec()`, `system()`, `passthru()` or `proc_open()` with input. When the code must run a binary, use `Symfony\Component\Process\Process` with an array of arguments.
- Never build a file system path from input. Write to a fixed stream wrapper location such as `private://sw_base/` and let the file system service create the file name.
- Make outbound requests through the injected `http_client` service with a fixed base URL and a `timeout` option. When a URL comes from input, parse it and check the scheme and host against an allowlist before the request, so the site can't be used to reach internal services.
- Redirect only to internal paths built with `Url::fromUserInput()` or `Url::fromRoute()`. Use `TrustedRedirectResponse` only for a host on an allowlist in code.

## Output and cross-site scripting

- Let Twig escape. `{{ value }}` is escaped automatically. Never add `|raw` to a value that a user, an editor or an external service can change.
- Pass markup from PHP to Twig as a render array, not as a string.
- In render arrays, use `#plain_text` for user-supplied text. `#markup` runs its value through `Xss::filterAdmin()`, which still allows links, images and most formatting tags, so it is for markup the code owns.
- Render user HTML that belongs to a text format as `'#type' => 'processed_text'` with `#text` and `#format`, so the format's filters run.
- Use `Xss::filter()` with an explicit tag list for limited HTML that has no text format. Use `Html::escape()` for a value concatenated into a markup string.
- Call `Markup::create()` only on strings the code assembles from escaped parts. It marks the string as safe and disables all later escaping.
- Never pass a variable as the first argument of `t()` or `$this->t()`. Use `@name` for plain text, `%name` for emphasized text and `:name` for a URL in an attribute, which also strips dangerous protocols.
- Build links with `Url` and `Link` objects. For a URL from input that must stay a string, run it through `UrlHelper::stripDangerousProtocols()`.
- Send data to JavaScript through `#attached['drupalSettings']`, never by printing it into an inline script.
- In JavaScript, write text with `textContent`, not `innerHTML`. When building an HTML string, escape values with `Drupal.checkPlain()`.

## Cross-site request forgery

- Change state only in a form built with the Form API or in a route that declares `_csrf_token: 'TRUE'`. The Form API adds a token for authenticated users; never set `$form['#token'] = FALSE` on a form that changes data.
- Generate links to a `_csrf_token` route with `Url::fromRoute()`, which adds the `token` query parameter. A hard-coded path has no token and gets a 403.
- For a JavaScript endpoint that changes state, generate a token with the `csrf_token` service and validate it in the controller.
- Put destructive actions behind a `ConfirmFormBase` form instead of a single link.
- Never change data in a `GET` request without a token, including in a controller that looks read-only.

## File uploads and private files

- Accept uploads through the `managed_file` element or the file upload service, with `#upload_validators` that list `FileExtension` and `FileSizeLimit`. Never allow executable or script extensions, and leave the `allow_insecure_uploads` setting off.
- Treat SVG as active content. Accept SVG uploads only from trusted roles.
- Store anything that is not meant for the public in the `private://` scheme through `#upload_location`. **Vortex** sets the private path from the `DRUPAL_PRIVATE_FILES` variable, `sites/default/files/private` by default, in `web/sites/default/settings.php`.
- Serve private files through Drupal's `/system/files/` route. For a file referenced by a file field, Drupal checks access to the entity that references it. For a file the code manages on its own, implement `hook_file_download()` and return `-1` for users without access.
- The default private path sits inside the public files directory, so check that the web server refuses a direct request: fetch `/sites/default/files/private/<file>` and expect a 403 or 404, then fetch `/system/files/<file>` as a user with and without access.
- Never write uploaded bytes straight to disk with `file_put_contents()` or `move_uploaded_file()`. That skips every upload validator.

- **Vortex** ships the ClamAV integration: `web/sites/default/includes/modules/settings.clamav.php` points the module at the `clamav` container in daemon mode when `DRUPAL_CLAMAV_ENABLED` is `1`, which the shipped `.env` sets. The scan runs as an upload validator, so route every upload through Drupal's file validation, or the files skip the virus scan too.

## Secrets and credentials

- Never put a password, token, API key or private key in code, in exported configuration under `config/default`, in the committed `.env` file, in test fixtures or in a commit message.
- Read each secret from an environment variable with the `DRUPAL_` prefix, in a per-module override file such as `web/sites/default/includes/modules/settings.sw_base.php`. Store it in `$settings`, guarded with `!empty()`, and read it with `Settings::get()` through the injected `settings` service. The settings guidelines on [Settings](https://www.vortextemplate.com/docs/development/settings#guidelines) cover the file layout.
- Keep secrets out of `$config` overrides and configuration forms. A form saves whatever it shows into active configuration, and `ahoy drush cex` then writes it to `config/default`.
- Fail closed: when the variable is empty, skip the integration and log that it isn't configured, without the value.
- Locally, put the value in `.env.local`, which Git ignores. `ahoy drush` and `ahoy cli` forward `DRUPAL_*` variables from it. Web requests don't read `.env.local`, so also pass the variable to the `php` service in `docker-compose.override.yml` and run `ahoy up`, as [Environment variables](https://www.vortextemplate.com/docs/development/environment#environment-variables) describes.
- In hosted environments, set the variable as a platform variable or secret in each environment. `docs/deployment.md` names this project's hosting.
- Each hosted environment needs its own long, random `DRUPAL_HASH_SALT`. The fallback salt derived from the database host is for local and CI use only; see [Hash salt](https://www.vortextemplate.com/docs/development/settings#hash-salt).
- Send API keys in request headers, not in query strings. HTTP client exceptions include the request URL in their message, so a key in the URL ends up in the logs.
- Never expose a secret through `drupalSettings`, a render array, an error message or command output.

- The Shield credentials come from `DRUPAL_SHIELD_USER` and `DRUPAL_SHIELD_PASS`, set per environment, never in `.env`. Shield hides `dev` and `stage` from the public; it is not access control for the site, so never rely on it to protect a route.

- CI scans the code for committed secrets. When a real secret reaches a commit, rotate it with the service that issued it; deleting the line or rewriting history leaves it exposed. Use the `gitleaks:allow` comment only for values that are not secrets. See [Secret scanning](https://www.vortextemplate.com/docs/development/security/secret-scanning).

## Logging without leaking data

- Log through an injected logger channel named after the module, such as one from `logger.factory` with `get('sw_base')`.
- Pass values as placeholders in the context array. Never concatenate input into the message string, which is also the translatable format string.
- Never log passwords, tokens, API keys, session IDs, full request or response bodies, or personal data such as email addresses. Log an entity ID instead.
- Log exceptions with `Error::logException($this->logger, $exception)`, after checking that the exception message can't hold a secret.
- Show users a generic message through the messenger service and keep the details in the log.
- Leave error display as the environment sets it. **Vortex** shows all errors only in `local` and `ci`, in `web/sites/default/includes/modules/settings.system.php`.
- Check what the code logs with `ahoy drush watchdog:show --count=20`.

## Security headers

The Security Kit module ships with the project but provisioning doesn't install it. `web/sites/default/includes/modules/settings.seckit.php` turns off its Content Security Policy locally and in CI, where the site isn't served over HTTPS. Write front-end code that works under a strict policy whether or not the module is on yet: no inline `<script>` blocks, no inline event handlers such as `onclick`, and JavaScript attached through a library and `Drupal.behaviors`. See [Security Kit](https://www.vortextemplate.com/docs/development/modules/contributed-modules#security-kit).

## Dependencies

- Add packages with `ahoy composer require <vendor/package>`, then run `ahoy composer audit`.
- The project's Composer policy in `composer.json` doesn't block installs on advisories but fails the audit, so a vulnerable package installs quietly. Run the audit after every dependency change.
- Ignore an advisory only after assessing it: add its ID under `config.policy.advisories.ignore-id` in `composer.json` with the reason, and repeat the reason in the commit message. See [Dependency audit](https://www.vortextemplate.com/docs/development/security/dependency-audit).

- Audit the root JavaScript packages with `ahoy cli "npm audit"`.

- Audit the custom theme's packages with `ahoy cli "npm audit --prefix=\${WEBROOT}/themes/custom/\${DRUPAL_THEME}"`.

- Apply an upstream security fix to a contributed package as a patch through `cweagans/composer-patches`, with the patch file in `patches/`. Never edit files under `vendor/` or `web/modules/contrib/` directly.

## Finish

1. Re-read the change against the **Security** section of `.claude/skills/review-code/checklist.md` and fix every item it flags.
2. Add tests for the security behavior: the route returns 403 for an anonymous user and for a user without the permission, and user input renders escaped. Follow `docs/testing.md` for where tests go.
3. Run `ahoy lint` and fix what it reports.
4. Run `ahoy composer audit` when the change touched dependencies.
5. Report to the developer which inputs the change accepts and which control covers each one.
