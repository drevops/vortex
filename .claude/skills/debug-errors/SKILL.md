---
name: debug-errors
description: Use when the local site shows an error message, a white screen or a 500, 502 or 503 response, a Drush command or `ahoy provision` fails, an error goes away after a cache rebuild, or when asked to read the Drupal log or the container logs, show PHP errors, or step through code with Xdebug.
---

# Debug errors

Find the error message and the line of code that raised it before changing anything. Most errors on the local site are already written to a log, so read the logs first, reproduce second, and fix last.

Run every command from the project root, 1 command per Bash call. The ad-hoc scripts this skill uses are in [references/probes.md](references/probes.md), and the provisioning failures are in [references/provisioning.md](references/provisioning.md).

## Rules

- Read the logs before running `ahoy drush cache:rebuild`. A rebuild can make an error disappear for a while without fixing its cause, and the evidence goes with it.
- Never use `drush php:eval`, and never pass PHP through standard input or a heredoc. Write the code to a file under `.artifacts/tmp/debug-errors/` and run it with `ahoy drush php:script /app/.artifacts/tmp/debug-errors/<file>.php`. The project is mounted at `/app` in the `cli` container. Delete the files when you finish.
- `ahoy drush` hands its arguments to a second shell inside the container, so escape the quotes of an argument that holds spaces with a backslash: `ahoy drush watchdog:show \"Call to a member function\"`.
- Work on the local site only. Never run a command against a remote site alias such as `@<alias>`.
- Fix the code that raised the error. Never edit Drupal core, contributed modules or `vendor/`; a fix there goes in a patch listed under `extra.patches` in `composer.json`, which `cweagans/composer-patches` applies.
- Hand off what this skill doesn't cover. A slow page, a cache that never hits, or content that stays stale after an edit goes to the `debug-performance` skill. A configuration import that fails goes to the `manage-config` skill. A stack that won't start goes to the `set-up-environment` skill.

## Capture the symptom

Write down the URL, the user or role, the time and the exact text before you investigate. Get the site URL from `ahoy info`.

- The status code: `curl -k -s -o /dev/null -w "%{http_code}\n" "SITE_URL/path"`
- The page body: `curl -k -s "SITE_URL/path"`. Drupal's generic error page reads `The website encountered an unexpected error. Try again later.`, and the details are in the log.
- A page that needs a login: get a link with `ahoy drush user:login --uid=<uid> --no-browser`, then follow the cookie steps on [Debugging](https://www.vortextemplate.com/docs/development/environment/debugging#request-authenticated-pages-with-curl) and add `-b .data/cookies.txt` to the commands above.
- A failing Drush command: run it again with `--debug`, which prints Drush's own log messages, such as `ahoy drush cache:rebuild --debug`.

## Read the Drupal log

Drupal writes PHP errors, uncaught exceptions and module messages to the watchdog table, and Drush reads it. Reproduce the error once, then read the newest entries:

```shell
ahoy drush watchdog:show --count=20 --severity-min=Error
```

These are the options of the Drush 13 commands:

| Command or option                     | What it does                                                                                     |
|---------------------------------------|--------------------------------------------------------------------------------------------------|
| `watchdog:show`                       | Lists the 10 newest messages, with the ID, date, type, severity and message                     |
| `--count=50`                          | Lists that many messages                                                                         |
| `--severity-min=Error`                | Lists that severity and the more severe ones                                                    |
| `--severity=Warning`                  | Lists that severity only. It can't be combined with `--severity-min`                            |
| `--type=php`                          | Lists 1 message type. `php` holds PHP errors and uncaught exceptions                            |
| `\"<text>\"` as an argument           | Lists messages that contain the text                                                             |
| `--extended`                          | Prints each message in full instead of its first 188 characters                                 |
| `--format=json`                       | Prints machine-readable output; `yaml` and `csv` work too                                       |
| `watchdog:show-one <id>`              | Prints 1 message in full, with the page URL (`location`), the referrer, the host name and the user |
| `watchdog:tail --severity-min=Warning` | Prints new messages as they arrive, until it's stopped                                         |
| `watchdog:delete all`                 | Deletes every message, so the next reproduction stands alone                                    |

- Without `--extended`, each message is cut at 188 characters. A `php` message ends with the backtrace, so read it with `ahoy drush watchdog:show-one <id>`, taking the ID from the first column.
- Severity names are capitalized: `Emergency`, `Alert`, `Critical`, `Error`, `Warning`, `Notice`, `Info` and `Debug`. Numbers work too, from `0` for `Emergency` to `7` for `Debug`.
- `--type` accepts only a type that is already in the log. Any other value fails with `Unrecognized message type` and lists the types the log holds.
- After a database import, the older entries come from the environment the dump was taken on. Compare the dates with the time you reproduced the error.
- Ask before `watchdog:delete all`, even on the local site. Drush confirms on its own when no terminal is attached.
- `watchdog:tail` doesn't stop by itself, so prefer `watchdog:show` after each reproduction.
- The watchdog commands need the core Database Logging module, `dblog`. When they report that it isn't enabled, check with `ahoy drush config:get core.extension module.dblog`, and use the container logs instead.

## Read the container logs

`ahoy logs` runs `docker compose logs` with the arguments you add. Put `--` before the first option, as in [Logs](https://www.vortextemplate.com/docs/development/environment/logs):

| Command                                    | Shows                                                                                       |
|--------------------------------------------|---------------------------------------------------------------------------------------------|
| `ahoy logs -- --tail=100 php`              | PHP-FPM output, including fatal errors and memory exhaustion that never reach the watchdog log |
| `ahoy logs -- --tail=100 nginx`            | The web server's requests and errors, such as a `502` when `php` doesn't answer             |
| `ahoy logs -- --tail=100 cli`              | The command-line container                                                                  |
| `ahoy logs -- --tail=100 database`         | The database server, such as a crash or a failed start                                      |
| `ahoy logs -- --since=10m --timestamps php` | The last 10 minutes, with a timestamp on each line                                         |
| `ahoy logs -- -f php`                      | New lines as they arrive, until it's stopped                                                |

The `services` section of `docker-compose.yml` lists the names you can pass: `cli`, `nginx`, `php`, `database`, `chrome` and `wait-for-dependencies`, plus any optional service the project selected. Drupal also writes every fatal error to PHP's own error log, so a fatal error shows up in the `php` log even when the database is down and the watchdog log can't record it.

## Check what the site shows

`$config['system.logging']['error_level']` decides how much of an error the page shows:

| Value     | The page shows                                 |
|-----------|------------------------------------------------|
| `hide`    | No error messages                              |
| `some`    | Errors and warnings, without notices           |
| `all`     | Every message                                  |
| `verbose` | Every message, with a backtrace                |

- `web/sites/default/includes/modules/settings.system.php` sets `all` in the `local` and `ci` environment types.
- A `web/sites/default/settings.local.php` copied from `example.settings.local.php` sets `verbose`. `settings.php` loads it last, so its value wins.
- Both are configuration overrides, so the setting on `/admin/config/development/logging` changes nothing locally. Read the value the site uses with `ahoy drush config:get system.logging error_level --include-overridden`.
- To get backtraces on the page, the developer copies `example.settings.local.php` to `settings.local.php` and `example.services.local.yml` to `services.local.yml` in `web/sites/default`. Git ignores both. Ask first, because these files also bypass the render, page and dynamic page caches, turn on Twig debugging and allow `/core/rebuild.php`.
- The settings probe in `references/probes.md` prints the environment type, both error levels, the loaded service files and the cache backends at once.

## White screens and error responses

Work down this list and stop at the first step that names the cause:

1. Read the log: `ahoy drush watchdog:show --count=20 --severity-min=Error`, then `ahoy drush watchdog:show-one <id>` for the newest matching entry.
2. A blank page with nothing new in the log is usually a fatal PHP error. Read `ahoy logs -- --tail=100 php` and look for `PHP Fatal error`, `Allowed memory size` or `Uncaught`.
3. When Drush fails too, the site doesn't bootstrap. Check `ahoy drush status --fields=db-status,bootstrap`. Without `Connected`, the database is down, which the `set-up-environment` skill covers.
4. A `502 Bad Gateway` comes from `nginx` when the `php` container doesn't answer. Read `ahoy logs -- --tail=50 php`, then run `ahoy up`.
5. A `503` page from Drupal means maintenance mode, which a failed provisioning leaves on. `ahoy drush maint:get` prints `1`; turn it off with `ahoy drush maint:set 0`. A `503` without any `X-Drupal-` response header comes from the web server's maintenance page, which `VORTEX_WEBSERVER_MAINTENANCE_MODE=1` turns on.
6. `The provided host name is not valid for this server.` means the request used a host that isn't in the trusted host patterns. Use the URL from `ahoy info`.
7. To get the exception with its full trace without a browser, run the request probe from `references/probes.md`. It sends the request through Drupal's HTTP kernel with error handling off, so the original exception reaches the output.
8. `Allowed memory size` in the `php` log is the web limit, which the Lagoon image sets from `PHP_MEMORY_LIMIT`. For Drush, `drush/php-ini/drush.ini` sets `memory_limit = 1G`. Find what uses the memory before raising either.

## Failed provisioning

`ahoy provision` prints a summary block first, then `[TASK]`, `[ OK ]` and `[FAIL]` lines for each step. The custom scripts in `scripts/` print their own lines with `==>`, `>`, `+` and `!` prefixes.

1. Read the summary block. `DB dump file path` shows whether the dump is `present` or `absent`, `Existing site found` whether a site was installed, and `Overwrite existing DB` whether the dump replaces it.
2. Find the last `[TASK]` line without a matching `[ OK ]` line. That's the step that failed, and the Drush or PHP output right above `[FAIL]` is the error.
3. Match the message with the table in `references/provisioning.md`.
4. To see every command the script ran, provision again with `VORTEX_DEBUG=1` in front of the command.
5. Fix the cause and rerun the failed step on its own when the reference gives a command for it. Run the whole `ahoy provision` again only after asking, because it can replace the local database.

A failed provisioning leaves the site in maintenance mode. Turn it off with `ahoy drush maint:set 0` once the cause is fixed.

## An error that a cache rebuild hides

- Record the error first: the watchdog ID, the page and the time.
- When `ahoy drush cache:rebuild` itself fails, its output is the real error. It's usually a service container that can't be built, such as a broken `*.services.yml` file or a class that doesn't exist. Run it with `--debug` for the full message.
- When Drush can't bootstrap at all, `/core/rebuild.php` rebuilds the caches from the browser, but only while `settings.local.php` sets `$settings['rebuild_access'] = TRUE`, as `example.settings.local.php` does.
- When the error disappears after a rebuild, find out whether it comes back. Request the page right after the rebuild and again a few times. An error only on the first request after a rebuild comes from code that runs while a cache is built, such as plugin discovery or a hook that alters definitions. An error only on later requests comes from something cached in a broken state, which the `debug-performance` skill helps trace.
- An error that a rebuild clears for good, after a code change to services, routes, plugins, hooks or templates, came from definitions cached before the change. Name that in the report instead of a code fix.
- `settings.local.php` from the example sends the render, page and dynamic page caches to `cache.backend.null`. An error that appears only on a hosting environment can therefore live in cached render output that never exists locally. Comment out those 3 lines in `settings.local.php` to reproduce it, and restore them afterwards.

## Step through the code with Xdebug

Xdebug is off by default. See [Xdebug](https://www.vortextemplate.com/docs/development/environment/debugging#xdebug) for the IDE setup.

- Turn it on with `ahoy debug`. It recreates the `cli`, `php` and `nginx` containers with `XDEBUG_ENABLE=true` and prints `Enabled debug configuration. Use 'ahoy up' to disable.`
- Turn it off with `ahoy up`. The `Xdebug` line of `ahoy info` suggests `ahoy up cli`, but that recreates only `cli`, and web requests keep Xdebug in `php`.
- `XDEBUG_ENABLE=true` in `.env` or `.env.local` keeps Xdebug on for every `ahoy up`, so remove the line to turn it off for good.
- Xdebug connects to port `9003` on `host.docker.internal`. The IDE listens on `9003` and maps the project root to `/app`.
- Web requests start a session with the `XDEBUG_SESSION` cookie, which an Xdebug browser extension sets, or with `?XDEBUG_SESSION_START=1` in the URL.
- In the `cli` container, every PHP script starts a session while Xdebug is on. Drush is the exception: `vendor/bin/drush` turns Xdebug off unless it gets `--xdebug`, as in `ahoy drush --xdebug php:script /app/.artifacts/tmp/debug-errors/request.php`.
- You can't operate the developer's IDE. Turn Xdebug on, name the file and line for the breakpoint, and ask the developer to start listening. Turn Xdebug off when the session ends, because it slows every request.

## Finish

1. Reproduce the original request and confirm the status code and the page.
2. Run `ahoy drush watchdog:show --count=10 --severity-min=Error` and confirm that no new entry appeared since the fix.
3. Turn Xdebug off with `ahoy up` if you turned it on, restore any local settings you changed, and delete the files in `.artifacts/tmp/debug-errors/`.
4. Report the symptom, the log entry that named the error, the cause with its `path:line`, the fix, and the check that proved it.
