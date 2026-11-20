---
name: set-up-environment
description: Use when setting up the local environment for the first time, starting, stopping or rebuilding the local stack, getting a fresh database, setting personal overrides in `.env.local`, or when `ahoy up`, `ahoy build` or `ahoy provision` fails, the stack won't start, or the local site is out of date after a pull or runs on a stale database.
---

# Set up the local environment

Get the local Docker Compose stack running, put a working database in it, and bring it back when it breaks. Every step goes through a command in the shipped `.ahoy.yml`. The installer leaves out the commands for features the project didn't select, so run `ahoy --help` to see the commands this project has before you rely on one.

## Rules

- Run every command from the project root, 1 command per Bash call. Never chain commands with `&&`, `;` or `|`.
- `ahoy build`, `ahoy down`, `ahoy reset`, `ahoy import-db`, `ahoy reload-db` and `ahoy provision` ask for confirmation through `ahoy confirm` and wait for a typed answer. Once the developer has agreed to the command, run it with both variables in front, for example `AHOY_CONFIRM_RESPONSE=y AHOY_CONFIRM_WAIT_SKIP=1 ahoy provision`. Don't write these variables into the developer's `.env.local`.
- `ahoy build`, `ahoy reset` and `ahoy down` remove the containers with their volumes, so the local database is lost. `ahoy import-db` and `ahoy provision` can replace it. Ask before each of them, and offer `ahoy export-db` first, which saves the current database as a timestamped dump in `.data/`.
- `ahoy build`, `ahoy fetch-db`, `ahoy provision` and the first `ahoy up` of a stopped stack can run for many minutes. Run them in the background and read the output when they finish. Silence isn't a failure.
- `ahoy drush` hands its arguments to a second shell inside the container, so escape the quotes of an argument that holds spaces with a backslash: `ahoy drush watchdog:show \"Access denied\"`. The comment above `cli` in `.ahoy.yml` describes this.
- Never edit the shipped scripts in `vendor/drevops/vortex-tooling/src/`. `AGENTS.md` sends changes to patches or to your own scripts in `scripts/`.
- Never use `drush php:eval`. When a check needs PHP, write it to a file under `.artifacts/tmp/` and run it with `ahoy drush php:script /app/.artifacts/tmp/<file>.php`. The project is mounted at `/app` in the `cli` container.

## Before the first build

1. The host needs Docker, Pygmy and Ahoy, as `README.md` lists. Shut down other local web servers and database servers, such as Apache, Nginx, MySQL or MAMP, because Pygmy needs port 80.
2. Start Pygmy once per system boot: `pygmy up`. It runs the shared proxy that routes `<project>.docker.amazee.io` to the `nginx` container, plus a DNS server, an SSH agent and a mail catcher.
3. Check the machine before any container exists: `VORTEX_DOCTOR_CHECK_PREFLIGHT=1 ahoy doctor`. It runs only the tools, port and Pygmy checks.

On a fresh clone, the first `ahoy` command runs `scripts/vortex-tooling.sh`, which installs the `drevops/vortex-tooling` package into `vendor/` with the host's Composer. Host-side commands such as `ahoy doctor` and `ahoy fetch-db` then work before `ahoy composer install` has run. When that step fails, it prints `Composer command failed.` with Composer's output above it.

## Personal overrides in `.env.local`

`.env` holds the values for the whole team and is committed. `.env.local` holds the values for 1 machine, overrides `.env`, and Git ignores it. A variable exported in the shell wins over both files, so a prefix such as `VORTEX_DEBUG=1 ahoy provision` changes 1 run only.

Create the file once, and never overwrite an existing one: `cp .env.local.example .env.local`. The example sets these values:

- `VORTEX_PROVISION_OVERRIDE_DB=1`, so `ahoy provision` replaces the local database with the dump on every run.
- `PACKAGE_TOKEN=`, a GitHub token that lifts the API rate limit and gives Composer access to private repositories.

- `VORTEX_FETCH_DB_FORCE=1`, so `ahoy fetch-db` always downloads a new dump, plus empty credential variables for the database source this project uses.

It also holds commented lines for `LOCALDEV_URL`, `VORTEX_DEBUG`, `AHOY_CONFIRM_RESPONSE` and `AHOY_CONFIRM_WAIT_SKIP`. Uncomment a line to use it.

How a changed value reaches the stack depends on who reads it:

- A variable that `docker-compose.yml` passes to the containers, such as `DRUPAL_THEME` or `XDEBUG_ENABLE`: run `ahoy up`, which recreates the containers that changed. A plain restart keeps the old value.
- A variable that Drupal reads but `docker-compose.yml` doesn't pass: PHP-FPM loads it from `.env` when its container starts and never reads `.env.local`, so run `ahoy restart` after changing `.env`. To set such a variable on 1 machine only, pass it to the `php` service in `docker-compose.override.yml` and set its value in `.env.local`.
- A variable that only the `vendor/bin/vortex-*` scripts read, such as `VORTEX_PROVISION_OVERRIDE_DB`: it applies on the next run, because each script reads both files when it starts.

Confirm a container has the new value with `ahoy cli printenv <VARIABLE>`. See [Environment variables](https://www.vortextemplate.com/docs/development/environment#environment-variables).

## Build the project for the first time

Fetch the database dump first, because `ahoy build` doesn't fetch it: `ahoy fetch-db`. The output ends with `Finished database fetch.` and `.data/db.sql` exists. Without a dump, provisioning stops with `Unable to import database from file.`

Then ask the developer, and run `AHOY_CONFIRM_RESPONSE=y AHOY_CONFIRM_WAIT_SKIP=1 ahoy build` in the background. It runs these steps in order and stops at the first one that fails:

- `ahoy reset`: runs `ahoy down`, then deletes `vendor`, every `node_modules` directory, `web/core` and the `contrib` directories.
- `ahoy up --build --force-recreate`: builds the images and starts the stack.
- `ahoy composer install`: installs the Composer dependencies.

- `ahoy fei`: installs the npm packages of the root `package.json` with `npm ci`.

- `ahoy fei` installs the theme's npm packages with `npm ci` in `web/themes/custom/<theme>`, and `ahoy fe` builds the theme assets.

- `ahoy provision`: imports the database or installs the site, then runs database updates, the configuration import, cache rebuilds, deploy hooks, sanitization and the `scripts/provision-*.sh` scripts.
- `ahoy info`: prints the project details and a one-time login link.

The build worked when the provisioning output ends with `Finished site provisioning`, `ahoy info` prints the `Site local URL`, and `ahoy doctor` ends with `[ OK ] All required checks have passed.`

`ahoy build` starts with a reset, so after a failure, fix the cause and run the failed step and the ones after it on their own. Don't run the whole build again.

## Start, stop and check the stack

| Command        | What it does                                                                           | Local database |
|----------------|----------------------------------------------------------------------------------------|----------------|
| `ahoy up`      | Creates or updates the containers and starts them                                      | Kept           |
| `ahoy stop`    | Stops the running containers                                                           | Kept           |
| `ahoy start`   | Starts the stopped containers                                                          | Kept           |
| `ahoy restart` | Restarts the containers, so PHP-FPM reads `.env` again                                 | Kept           |
| `ahoy down`    | Removes the containers, volumes and networks after a confirmation                      | Lost           |
| `ahoy info`    | Prints the site URL, the database port on the host, the Xdebug state and the Mailhog URL | Kept         |
| `ahoy login`   | Unblocks user 1 and prints a one-time login link                                       | Kept           |
| `ahoy doctor`  | Checks the tools, Pygmy, the containers and the site, and stops at the first failure   | Kept           |

- Stop work for the day with `ahoy stop`, not `ahoy down`.
- `ahoy up` exits with status 1 when any container log contains `[Error]`, and it doesn't print the line. See [references/troubleshooting.md](references/troubleshooting.md).
- Docker assigns the database port on the host when the container starts, so read it from `ahoy info` after every restart.
- A one-time login link works once. Run `ahoy login` again for a new one.
- `ahoy doctor info` prints a system report for a support request. Read it before sharing it, because it redacts only some values.

## Get a database

### Fetch the dump

`ahoy fetch-db` downloads a dump into `.data/`, from the source that `VORTEX_FETCH_DB_SOURCE` in `.env` names. An unsupported value stops it with an error that lists the supported sources.

- When `.data/` already holds the dump, the fetch keeps it, whatever its age, and prints `Fetch will not proceed.` Set `VORTEX_FETCH_DB_FORCE=1` to download a new copy, as `.env.local.example` does.
- `ahoy fetch-db --fresh` sets both `VORTEX_FETCH_DB_FORCE=1` and `VORTEX_FETCH_DB_FRESH=1`. On hosting sources, the second variable creates a new backup or dump instead of reusing an earlier one.
- To use a dump from somewhere else, name it `db.sql` and put it in `.data/`.

| Variable                      | Default        | Purpose                                                            |
|-------------------------------|----------------|--------------------------------------------------------------------|
| `VORTEX_FETCH_DB_SOURCE`      | `url`          | Where the dump comes from                                          |
| `VORTEX_FETCH_DB_ENVIRONMENT` | `prod`         | The hosting environment to copy, for the hosting sources           |
| `VORTEX_DB_DIR`               | `./.data`      | The directory for dumps                                            |
| `VORTEX_DB_FILE`              | `db.sql`       | The dump file name                                                 |
| `VORTEX_FETCH_DB_FORCE`       | empty          | `1` replaces an existing dump                                      |
| `VORTEX_FETCH_DB_FRESH`       | empty          | `1` asks the hosting source for a new backup                       |
| `VORTEX_FETCH_DB_PROCEED`     | `1`            | Anything other than `1` skips the fetch                            |

Each source also reads its own `VORTEX_FETCH_DB_*` variables from `.env`. Put credentials in `.env.local`, under the names `.env.local.example` lists.

### Load it

- `ahoy import-db` drops the local database and loads `.data/db.sql` as it is, with no database updates and no configuration import. Pass a path to load another dump: `ahoy import-db .data/<file>.sql`. It ends with `Finished database import.`
- `ahoy provision` runs the whole provisioning flow and ends with `Finished site provisioning`, followed by a login link. It replaces the database only when no site is installed or `VORTEX_PROVISION_OVERRIDE_DB=1`. The `Overwrite existing DB` line near the top of its output shows which applies.
- To apply updates and configuration while keeping the local database, set the variable for 1 run: `VORTEX_PROVISION_OVERRIDE_DB=0 AHOY_CONFIRM_RESPONSE=y AHOY_CONFIRM_WAIT_SKIP=1 ahoy provision`. Set it to `1` the same way to load the dump again.
- `ahoy export-db` saves the local database as `.data/export_db_<timestamp>.sql`. Load it later with `ahoy import-db .data/<file>.sql`.

When `ahoy provision` fails, use the `debug-errors` skill to read its output. See [Provision](https://www.vortextemplate.com/docs/development/provision) for every variable that steers the flow.

## After a pull or a branch switch

Skip the full rebuild. Read what changed with `git diff --stat ORIG_HEAD HEAD` or the branch diff, and run only the matching commands:

- `composer.json` or `composer.lock` changed: `ahoy composer install`.

- `package.json` or `package-lock.json` in the project root changed: `ahoy fei`.

- The theme's `package.json` changed: `ahoy fei`. Any other file in `web/themes/custom/<theme>` changed: `ahoy fe`, or `ahoy fed` for a development build.

- `docker-compose.yml` or a file in `.docker/` changed: `ahoy up --build`, which rebuilds the images and recreates the containers.
- `.env` changed: `ahoy up`, or `ahoy restart` for a variable that only Drupal reads, as the overrides section above describes.
- Update hooks, deploy hooks or files in `config/default` changed: `ahoy provision`, after asking. To see what a configuration import would change first, use the `manage-config` skill.

Confirm the result with `ahoy doctor`, then open the URL from `ahoy info`.

## When something fails

Run `ahoy doctor` first. It checks each layer in order, stops at the first broken one and prints the fix for most failures. Then find the symptom here and read its section in [references/troubleshooting.md](references/troubleshooting.md):

| Symptom                                                                        | Section                         |
|--------------------------------------------------------------------------------|---------------------------------|
| `Port 80 is occupied by a service other than Docker.`                          | Port 80 is in use               |
| `ahoy up` exits with status 1 and prints nothing, or a container keeps stopping | A container won't start         |
| The first `ahoy up` fails on `wait-for-dependencies`                           | A service is still loading      |
| Drupal or Drush can't connect to the database                                  | The database is unreachable     |
| The site doesn't load at its URL, or rejects the host name                     | The site doesn't load           |
| Content or configuration is older than on the hosting environment              | The database dump is stale      |
| A class or package is missing after a pull, or `ahoy composer install` fails   | Dependencies are out of date    |
| A changed variable has no effect                                               | A variable doesn't apply        |
| Files in the project end up owned by `root`                                    | File ownership                  |

For an error that the site itself shows, such as a white screen, a 500 response or a failed Drush command, use the `debug-errors` skill.

## Finish

Report what you ran and its result: the last line of `ahoy doctor`, the site URL from `ahoy info`, and the database you loaded with the date of its dump. When the developer has to act on the host, such as stopping a service on port 80 or adding an SSH key to Pygmy, say exactly which command to run.
