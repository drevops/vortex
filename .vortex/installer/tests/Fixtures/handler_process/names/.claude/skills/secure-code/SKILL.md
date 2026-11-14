@@ -45,7 +45,7 @@
 - Prefer an entity query over SQL for entity data, so access checks and field storage stay correct.
 - Never pass input to `unserialize()`. Decode JSON with `json_decode()` and the `JSON_THROW_ON_ERROR` flag.
 - Never call `eval()`, `exec()`, `shell_exec()`, `system()`, `passthru()` or `proc_open()` with input. When the code must run a binary, use `Symfony\Component\Process\Process` with an array of arguments.
-- Never build a file system path from input. Write to a fixed stream wrapper location such as `private://sw_base/` and let the file system service create the file name.
+- Never build a file system path from input. Write to a fixed stream wrapper location such as `private://the_force_base/` and let the file system service create the file name.
 - Make outbound requests through the injected `http_client` service with a fixed base URL and a `timeout` option. When a URL comes from input, parse it and check the scheme and host against an allowlist before the request, so the site can't be used to reach internal services.
 - Redirect only to internal paths built with `Url::fromUserInput()` or `Url::fromRoute()`. Use `TrustedRedirectResponse` only for a host on an allowlist in code.
 
@@ -84,7 +84,7 @@
 ## Secrets and credentials
 
 - Never put a password, token, API key or private key in code, in exported configuration under `config/default`, in the committed `.env` file, in test fixtures or in a commit message.
-- Read each secret from an environment variable with the `DRUPAL_` prefix, in a per-module override file such as `web/sites/default/includes/modules/settings.sw_base.php`. Store it in `$settings`, guarded with `!empty()`, and read it with `Settings::get()` through the injected `settings` service. The settings guidelines on [Settings](https://www.vortextemplate.com/docs/development/settings#guidelines) cover the file layout.
+- Read each secret from an environment variable with the `DRUPAL_` prefix, in a per-module override file such as `web/sites/default/includes/modules/settings.the_force_base.php`. Store it in `$settings`, guarded with `!empty()`, and read it with `Settings::get()` through the injected `settings` service. The settings guidelines on [Settings](https://www.vortextemplate.com/docs/development/settings#guidelines) cover the file layout.
 - Keep secrets out of `$config` overrides and configuration forms. A form saves whatever it shows into active configuration, and `ahoy drush cex` then writes it to `config/default`.
 - Fail closed: when the variable is empty, skip the integration and log that it isn't configured, without the value.
 - Locally, put the value in `.env.local`, which Git ignores. `ahoy drush` and `ahoy cli` forward `DRUPAL_*` variables from it. Web requests don't read `.env.local`, so also pass the variable to the `php` service in `docker-compose.override.yml` and run `ahoy up`, as [Environment variables](https://www.vortextemplate.com/docs/development/environment#environment-variables) describes.
@@ -99,7 +99,7 @@
 
 ## Logging without leaking data
 
-- Log through an injected logger channel named after the module, such as one from `logger.factory` with `get('sw_base')`.
+- Log through an injected logger channel named after the module, such as one from `logger.factory` with `get('the_force_base')`.
 - Pass values as placeholders in the context array. Never concatenate input into the message string, which is also the translatable format string.
 - Never log passwords, tokens, API keys, session IDs, full request or response bodies, or personal data such as email addresses. Log an entity ID instead.
 - Log exceptions with `Error::logException($this->logger, $exception)`, after checking that the exception message can't hold a secret.
