@@ -34,8 +34,6 @@
 - `VORTEX_PROVISION_OVERRIDE_DB=1`, so `ahoy provision` replaces the local database with the dump on every run.
 - `PACKAGE_TOKEN=`, a GitHub token that lifts the API rate limit and gives Composer access to private repositories.
 
-- `VORTEX_FETCH_DB_FORCE=1`, so `ahoy fetch-db` always downloads a new dump, plus empty credential variables for the database source this project uses.
-
 It also holds commented lines for `LOCALDEV_URL`, `VORTEX_DEBUG`, `AHOY_CONFIRM_RESPONSE` and `AHOY_CONFIRM_WAIT_SKIP`. Uncomment a line to use it.
 
 How a changed value reaches the stack depends on who reads it:
@@ -48,8 +46,6 @@
 
 ## Build the project for the first time
 
-Fetch the database dump first, because `ahoy build` doesn't fetch it: `ahoy fetch-db`. The output ends with `Finished database fetch.` and `.data/db.sql` exists. Without a dump, provisioning stops with `Unable to import database from file.`
-
 Then ask the developer, and run `AHOY_CONFIRM_RESPONSE=y AHOY_CONFIRM_WAIT_SKIP=1 ahoy build` in the background. It runs these steps in order and stops at the first one that fails:
 
 - `ahoy reset`: runs `ahoy down`, then deletes `vendor`, every `node_modules` directory, `web/core` and the `contrib` directories.
@@ -88,25 +84,7 @@
 
 ## Get a database
 
-### Fetch the dump
-
-`ahoy fetch-db` downloads a dump into `.data/`, from the source that `VORTEX_FETCH_DB_SOURCE` in `.env` names. An unsupported value stops it with an error that lists the supported sources.
-
-- When `.data/` already holds the dump, the fetch keeps it, whatever its age, and prints `Fetch will not proceed.` Set `VORTEX_FETCH_DB_FORCE=1` to download a new copy, as `.env.local.example` does.
-- `ahoy fetch-db --fresh` sets both `VORTEX_FETCH_DB_FORCE=1` and `VORTEX_FETCH_DB_FRESH=1`. On hosting sources, the second variable creates a new backup or dump instead of reusing an earlier one.
-- To use a dump from somewhere else, name it `db.sql` and put it in `.data/`.
-
-| Variable                      | Default        | Purpose                                                            |
-|-------------------------------|----------------|--------------------------------------------------------------------|
-| `VORTEX_FETCH_DB_SOURCE`      | `url`          | Where the dump comes from                                          |
-| `VORTEX_FETCH_DB_ENVIRONMENT` | `prod`         | The hosting environment to copy, for the hosting sources           |
-| `VORTEX_DB_DIR`               | `./.data`      | The directory for dumps                                            |
-| `VORTEX_DB_FILE`              | `db.sql`       | The dump file name                                                 |
-| `VORTEX_FETCH_DB_FORCE`       | empty          | `1` replaces an existing dump                                      |
-| `VORTEX_FETCH_DB_FRESH`       | empty          | `1` asks the hosting source for a new backup                       |
-| `VORTEX_FETCH_DB_PROCEED`     | `1`            | Anything other than `1` skips the fetch                            |
-
-Each source also reads its own `VORTEX_FETCH_DB_*` variables from `.env`. Put credentials in `.env.local`, under the names `.env.local.example` lists.
+This project installs Drupal from the `DRUPAL_PROFILE` profile instead of importing a dump, because `.env` sets `VORTEX_PROVISION_TYPE=profile`. `ahoy provision` installs a site when none exists, using the files in `config/default` when there are any, and reinstalls it when `VORTEX_PROVISION_OVERRIDE_DB=1`.
 
 ### Load it
 
