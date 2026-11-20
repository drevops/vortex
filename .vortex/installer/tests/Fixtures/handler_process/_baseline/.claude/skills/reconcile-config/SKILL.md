---
name: reconcile-config
description: Use when configuration on a deployed environment has drifted from the configuration in code, when `drush config:status` reports differences against a database fetched from a hosting environment, before a deployment that would import configuration over changes made on the site, or when asked which side should win for each configuration item.
---

# Reconcile configuration

Walk the differences between a deployed environment's database and the configuration in code, 1 item at a time. For each item, recommend Import, Export or Skip with the reason, and let the developer decide.

This skill writes only local files: the local database, files under `config/`, and scratch files under `.artifacts/tmp/reconcile-config/`. It never writes to a hosting environment, and it never commits or pushes.

Run every command from the project root, 1 command per Bash call. Never use `drush php:eval`: write PHP to a file under `.artifacts/tmp/reconcile-config/` and run it with `ahoy drush php:script`. The project is mounted at `/app` in the `cli` container, so pass container paths that start with `/app`.

## The 3 decisions

- **Import** (code wins): keep the file as it is. The next deployment's configuration import writes it to the environment.
- **Export** (database wins): write the environment's value into the file, so the next deployment keeps it.
- **Skip**: change nothing, and list the item in the report.

## 1. Prepare

1. Check the tree with `git status --short config/`. When it isn't clean, ask the developer to commit or stash first, so exported values don't mix with unfinished work.
2. Find the default branch with `git symbolic-ref --short refs/remotes/origin/HEAD`, then list the items this branch changed: `git diff --name-only origin/<default-branch>...HEAD -- config/`. The comparison runs against the code checked out now, so these items differ by design.
3. Save the local database so you can restore it later: `ahoy export-db`. Note the file name it prints, such as `.data/export_db_20260101_120000.sql`.

## 2. Pick the environment

Read `VORTEX_FETCH_DB_SOURCE` and `VORTEX_FETCH_DB_ENVIRONMENT` in `.env`, and in `.env.local` when it exists, because `.env.local` wins. The hosting sources copy the environment that `VORTEX_FETCH_DB_ENVIRONMENT` names, `prod` in the shipped `.env`. The `url`, `ftp` and `s3` sources download whatever file they point at, so ask the developer which environment that dump was taken from, and when. With `none`, the developer puts the dump at `.data/db.sql`. When `.ahoy.yml` has no `fetch-db` command, the project installs from a profile, so ask the developer for a dump.

Then work out the environment type that the environment runs as, `prod`, `stage` or `dev`. Step 4 runs the comparison with it. See [Environment type detection](https://www.vortextemplate.com/docs/development/settings#environment-type-detection).

## 3. Fetch and import the database

1. `ahoy fetch-db` keeps an existing `.data/db.sql` whatever its age, so the comparison could run against old data. Download a new copy with `VORTEX_FETCH_DB_FORCE=1 ahoy fetch-db`. Confirm that the output ends with `Finished database fetch.`
2. `ahoy fetch-db --fresh` also makes the hosting platform create a new backup or dump before the download. That creates something on the hosting side, so use it only when the developer asks for it.
3. Tell the developer that the import replaces the local database. After they agree, run `AHOY_CONFIRM_RESPONSE=y AHOY_CONFIRM_WAIT_SKIP=1 ahoy import-db`, because `ahoy` waits for a typed answer otherwise. Confirm that the output ends with `Finished database import.`

Never use `ahoy provision` here. It imports the configuration from code, which erases the drift you're about to review. See [Fetch the database](https://www.vortextemplate.com/docs/development/provision#fetch-the-database) and [Refresh the database](https://www.vortextemplate.com/docs/development/provision#refresh-the-database).

## 4. Compare with the environment's type

The local stack runs as the `local` environment type, which turns on settings that the deployed environment doesn't have. Prefix every Drush command from here on with the type from step 2, for example `ENVIRONMENT_TYPE=prod ahoy drush config:status`. `ahoy cli` passes `ENVIRONMENT_*` variables into the container, and `settings.php` replaces the detected type with `ENVIRONMENT_TYPE`. See [Override the environment type](https://www.vortextemplate.com/docs/development/settings#override-the-environment-type).

Confirm the type: copy the script from [references/verify.md](references/verify.md) to `.artifacts/tmp/reconcile-config/verify.php` and run it without names, `ENVIRONMENT_TYPE=prod ahoy drush php:script /app/.artifacts/tmp/reconcile-config/verify.php`. The first line reads `Environment type: prod`.

Under the `local` type, `web/sites/default/includes/modules/settings.config_split.php` turns on the `local` split. The comparison then applies that split, so its modules and items show up as `Only in sync dir`, and `core.extension` as `Different`, although the environment never had them. Under the environment's type, the comparison applies the split the environment uses: `stage` or `dev`, and none for `prod`.

Check a split with `ENVIRONMENT_TYPE=prod ahoy drush config:get config_split.config_split.local status --include-overridden`, which reports `false` when the split is off. A split whose definition file says `status: true` is on under every type. Tell the developer, because the environment applies it too.

List the differences: `ENVIRONMENT_TYPE=prod ahoy drush config:status`. When it prints `No differences between DB and sync directory.`, report that the environment and the code match, then go to step 7. Otherwise, make a todo list with 1 entry per item.

## 5. Walk each item

Take the `Only in DB` items first, then `Only in sync dir`, then `Different`. For each item:

1. Find the file: `find config -name "*<name>.yml"`. Most items live in `config/default`. An item in another directory under `config/` belongs to a configuration split, so its file stays in that directory.
2. Capture the database value. Create the directory once with `mkdir -p .artifacts/tmp/reconcile-config/db`, then run `ENVIRONMENT_TYPE=prod ahoy drush config:get <name> > .artifacts/tmp/reconcile-config/db/<name>.yml`. The error `Config <name> does not exist` means the database lacks the item.
3. Show the diff with the code on the left and the database on the right, where `<file>` is the path from item 1, usually `config/default/<name>.yml`: `git diff --no-index -- <file> .artifacts/tmp/reconcile-config/db/<name>.yml`. Exit code 1 only means the files differ. Ignore 2 kinds of noise from `config:get`: multi-line strings in quoted style instead of `|` blocks, and a second newline at the end. For an item on 1 side only, show that side's file in full.
4. Recommend a decision with the rules below, and give the reason in 1 sentence that points at the evidence in the diff.
5. Ask the developer: Import, Export or Skip. Wait for the answer before you act.
6. Carry out the decision, as [Act on the decision](#act-on-the-decision) describes.
7. Record the item, its state, the decision and the reason for the report.

### Recommend a decision

Apply the first rule that matches. [references/decision-rules.md](references/decision-rules.md) explains each rule and its exceptions.

| Rule | Evidence                                                                                  | Recommend | Reason                                                                                     |
|------|-------------------------------------------------------------------------------------------|-----------|--------------------------------------------------------------------------------------------|
| 1    | An excluded module or a configuration split owns the item                                 | Skip      | The difference is environment-specific by design                                           |
| 2    | `Only in sync dir`, but `config:get` prints the item, or the `uuid` lines differ          | Export    | An import deletes the entity and creates it again, and a field storage loses its data     |
| 3    | Only the `_core` key differs                                                              | Skip      | `_core` records where the item was installed from and changes no behavior                 |
| 4    | The branch changed the file (step 1)                                                      | Import    | The change in code is intended and waits for deployment                                    |
| 5    | `Only in DB`                                                                              | Export    | Someone created the item on the environment, and an import would delete it                |
| 6    | `Only in sync dir`, and the database lacks the item                                       | Import    | The next deployment creates it                                                              |
| 7    | `Different`, and the values are environment-specific, such as hosts, keys or addresses    | Skip      | The value belongs in a settings override, not in shared configuration                     |
| 8    | `Different`, and the database holds edits made on the site                                | Export    | The environment's edits are the newer intent                                               |
| 9    | Anything else                                                                             | Skip      | Present both sides and let the developer decide                                            |

### Act on the decision

- **Export**: copy the script from the `manage-config` skill's [references/export-items.md](../manage-config/references/export-items.md) to `.artifacts/tmp/reconcile-config/export-items.php` once, then run `ENVIRONMENT_TYPE=prod ahoy drush php:script /app/.artifacts/tmp/reconcile-config/export-items.php <name>`. It writes the file in the format of a full export. A `SKIPPED` line means the item lives outside `config/default`: report it for the developer to handle, and don't write the file by hand.
- **Export of an item the database lacks**: the environment deleted it, so exporting means deleting the file. Confirm with the developer, then run `rm config/default/<name>.yml`.
- **Import**: change nothing.
- **Import of an `Only in DB` item**: the next deployment's configuration import deletes the item from the environment, together with anything that depends on it. Warn the developer and ask for confirmation. Then ask a second time, naming the item and the environment, for example "Delete `views.view.archive` from `prod` on the next deployment?" Without 2 clear yes answers, Skip.
- **Skip**: change nothing.

Never write a `config:get` capture into `config/`, and never edit an exported file by hand to merge both sides. When the developer wants part of each side, make the change on the local site in the admin UI, then export the item.

## 6. Verify

Run the verify script with every item you decided on:

```shell
ENVIRONMENT_TYPE=prod ahoy drush php:script /app/.artifacts/tmp/reconcile-config/verify.php views.view.archive system.site
```

It reads the code side through the same import transformation that a deployment applies. Expect this result for each decision:

| Decision                       | Expected result                         |
|--------------------------------|-----------------------------------------|
| Export                         | `MATCH`                                 |
| Export of an item the database lacks | `ABSENT`                          |
| Import                         | A result that starts with `CODE WINS`   |
| Import of an `Only in DB` item | `DELETED ON DEPLOY`                     |
| Skip                           | The same result as before your decision |

Go back to any item whose result doesn't match, and report it when a second attempt doesn't help.

## 7. Report and restore

Print a table with 1 row per item: name, state, decision, reason and result. Below it, show `git status --short config/` and `git diff --stat config/`. Don't commit or push. Remind the developer that Import items reach the environment only when this code deploys and provisioning runs the configuration import.

Then offer to restore the local site. After the developer agrees, run `AHOY_CONFIRM_RESPONSE=y AHOY_CONFIRM_WAIT_SKIP=1 ahoy import-db .data/export_db_<timestamp>.sql` with the file from step 1, or `AHOY_CONFIRM_RESPONSE=y ahoy provision` for a fresh, fully configured site. Finally, remove the scratch files with `rm -rf .artifacts/tmp/reconcile-config`.
