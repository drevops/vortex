# Local stack troubleshooting

Each section starts from what the developer sees. Run `ahoy doctor` before any of them: it names the layer that is broken. See [Troubleshooting](https://www.vortextemplate.com/docs/development/environment/troubleshooting) and [Doctor](https://www.vortextemplate.com/docs/development/environment/doctor) for detail.

## What Doctor checks

Doctor runs on the host and stops at the first check that fails. A check that passes prints an `[ OK ]` line.

| Check      | Fails when                                                          | Fix it prints                                                    |
|------------|---------------------------------------------------------------------|------------------------------------------------------------------|
| Tools      | `docker`, `pygmy` or `ahoy` is missing                              | `Command <name> is not available.`                               |
| Port       | A process other than Docker listens on port 80 (skipped on Linux)   | `Stop this service and run 'pygmy up'.`                          |
| Pygmy      | 1 of the 4 Pygmy services isn't running                             | `Run 'pygmy up' or 'pygmy restart' to fix.`                      |
| Containers | `cli`, `php`, `nginx` or `database` isn't running                   | `Run 'ahoy up'.` and `Run 'ahoy logs <service>' to see error logs.` |
| SSH        | The SSH key is loaded and mounted but still missing in `cli`        | `Run 'ahoy up --no-deps --force-recreate cli'.`                  |
| Webserver  | The local URL doesn't answer with 200 or 403                        | `Web server is not accessible at http://<url>.`                  |
| Bootstrap  | The home page HTML has no `charset=`                                | `Website is running, but cannot be bootstrapped.`                |

An SSH warning isn't a failure. Turn a check off for 1 run with its variable, for example `VORTEX_DOCTOR_CHECK_PORT=0 ahoy doctor`.

## Port 80 is in use

Pygmy's proxy listens on port 80, so another web server on the host stops it from starting.

1. Ask the developer to find the process: `lsof -i :80` shows what listens there, which is the same probe Doctor uses.
2. The developer stops that service, such as a local Apache, Nginx or MAMP.
3. Start Pygmy again: `pygmy up`, then `ahoy doctor`.

Only port 80 is fixed. The `database` container and the other services publish their ports on random host ports, which `ahoy info` prints, so a local MySQL on port 3306 doesn't block the stack.

## A container won't start

`ahoy up` runs `docker compose up --detach` and then exits with status 1 when `docker compose logs` contains the exact text `[Error]`. It doesn't print the matching line.

1. Run `ahoy doctor`. It names the first of `cli`, `php`, `nginx` and `database` that isn't running.
2. Read that container's log: `ahoy logs -- --tail=200 <service>`. Each line starts with the container name, so search the output for `[Error]`.
3. Fix the cause the log names, such as a bad value in `.env`, a broken file in `.docker/config/`, or a failed image build.
4. A container keeps its log until it's removed, so an old `[Error]` line keeps failing `ahoy up` after the fix. Recreate the container: `ahoy up -- --force-recreate <service>`.
5. Confirm that `ahoy up` exits with status 0 and that `ahoy doctor` prints `[ OK ] All containers are running.`

After a change to `docker-compose.yml`, a Dockerfile in `.docker/` or a `custom.ini`, rebuild the images: `ahoy up --build`.

## A service is still loading

Docker Compose starts `cli` only after the `wait-for-dependencies` container exits with code `0`. That container polls the service ports, such as `database:3306`, and gives up when a service isn't ready in time. On the first start of a stopped stack, a slow service can outlast it, and Docker Compose then reports that `wait-for-dependencies` didn't complete successfully.

The `clamav` container is the usual cause on its first start, because it can take several minutes to accept connections on port `3310`.

1. Read which service it waited for: `ahoy logs wait-for-dependencies`.
2. Run `ahoy up` once more, in the background. The slow service is usually ready by then.
3. When the second run fails the same way, read the log of the service it waited for, as the previous section describes.

## The database is unreachable

The symptoms are a Drupal error about the database connection, or a Drush error such as `Command <name> was not found. Drush was unable to query the database.`

1. Run `ahoy doctor`. When it reports the `database` container as not running, read its log: `ahoy logs -- --tail=100 database`.
2. Start it again with `ahoy up`, which keeps the data.
3. Check that Drush connects: `ahoy drush status --fields=db-status,bootstrap` prints `Connected` and `Successful`.
4. Only as a last resort, after asking, rebuild everything with `ahoy build`. It wipes the local database.

Inside the containers, the database host is `database` and the name, user and password are all `drupal`. These come from `docker-compose.yml`, and `ahoy info` prints them.

## The site doesn't load

- The URL is `http://<COMPOSE_PROJECT_NAME>.docker.amazee.io`, and the project name defaults to the project directory name. Read the exact URL from `ahoy info`. `LOCALDEV_URL` in `.env.local` overrides it.
- The URL doesn't resolve: Pygmy's DNS or proxy isn't running. Run `pygmy status`, then `pygmy up`.
- Drupal answers `The provided host name is not valid for this server.`: the request used a host name that isn't trusted. `web/sites/default/includes/providers/settings.container.php` trusts the host in `LOCALDEV_URL` and `nginx`, `settings.php` trusts `localhost`, and `settings.trusted_hosts.php` adds the domains in `DRUPAL_TRUSTED_HOSTS`. Use the URL from `ahoy info`.
- The page loads with an error: use the `debug-errors` skill.

## The database dump is stale

The local site lacks content or configuration that the hosting environment has, or `ahoy fetch-db` printed `Fetch will not proceed.`

1. Check the age of the dump: `ls -l .data`.
2. Fetch a new copy: `ahoy fetch-db --fresh`. On a hosting source, this also creates a new backup or dump at the source.
3. Load it, after asking: `VORTEX_PROVISION_OVERRIDE_DB=1 AHOY_CONFIRM_RESPONSE=y AHOY_CONFIRM_WAIT_SKIP=1 ahoy provision`. Without `VORTEX_PROVISION_OVERRIDE_DB=1`, provisioning keeps the installed site's database and prints `Site content will be preserved.`
4. Confirm that the provisioning output lists `Overwrite existing DB : Yes` and ends with `Finished site provisioning`.

A fresh dump from a hosting environment is sanitized during provisioning, so user email addresses and passwords differ from production. Log in with `ahoy login`.

## Dependencies are out of date

After a pull, a `Class "..." not found` error, an unknown Drush command or a module missing from the file system usually means the `vendor` directory or the `contrib` directories lag behind `composer.lock`.

1. Install from the lock file: `ahoy composer install`. Never run `composer update` to fix a pull, because it changes `composer.lock`.
2. When Composer hits the GitHub API rate limit or can't reach a private repository, set `PACKAGE_TOKEN` in `.env.local` to a GitHub token. `ahoy composer` registers it for every run.
3. When the new code ships update hooks, apply them with `ahoy provision`, or with `ahoy drush updatedb` for the updates alone.

When the root `package.json` or `package-lock.json` changed, run `ahoy fei`. It runs `npm ci`, which installs exactly what the lock file lists and fails when the lock file and `package.json` disagree. A failure there means the lock file in the commit is out of date, so tell the developer instead of regenerating it.

When files in `web/themes/custom/<theme>` changed, run `ahoy fei` for its npm packages and then `ahoy fe` to rebuild its assets. Unstyled pages after a pull usually mean the assets weren't rebuilt.

## A variable doesn't apply

- A variable passed in `docker-compose.yml` needs `ahoy up`, which recreates the container. `ahoy restart` keeps the old value.
- A variable that Drupal reads from `.env` but `docker-compose.yml` doesn't pass needs `ahoy restart`. PHP-FPM never reads `.env.local`.
- `ahoy cli` forwards only `TERM`, `LOCALDEV_URL` and variables that start with `COMPOSE_`, `GITHUB_`, `PACKAGE_`, `DOCKER_`, `DRUPAL_`, `VORTEX_` or `ENVIRONMENT_`, so a variable with another prefix never reaches a command in the `cli` container.
- Check the value a container holds: `ahoy cli printenv <VARIABLE>`.

## File ownership

The `cli` container runs as `root`, so on Linux the files it creates in the project, such as a new `vendor` directory, can end up owned by `root` on the host. Ask the developer to take ownership back with `sudo chown -R $USER:$USER .` from the project root. Never run `sudo` yourself.

## Start over

When nothing else works, and only after the developer agrees:

- `AHOY_CONFIRM_RESPONSE=y AHOY_CONFIRM_WAIT_SKIP=1 ahoy reset` removes the containers, their volumes and everything the build installed. The last line it prints is `[ OK ] Finished reset.` Then build again as `SKILL.md` describes.
- `ahoy reset --hard` also deletes every file that `.gitignore` matches and every uncommitted change, including `.env.local`, `.ahoy.local.yml`, `docker-compose.override.yml`, `settings.local.php` and the dumps in `.data`. Tell the developer what it will delete, and let them run it.
