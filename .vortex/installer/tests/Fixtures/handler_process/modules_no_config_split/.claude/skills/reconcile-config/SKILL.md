@@ -43,10 +43,6 @@
 
 Confirm the type: copy the script from [references/verify.md](references/verify.md) to `.artifacts/tmp/reconcile-config/verify.php` and run it without names, `ENVIRONMENT_TYPE=prod ahoy drush php:script /app/.artifacts/tmp/reconcile-config/verify.php`. The first line reads `Environment type: prod`.
 
-Under the `local` type, `web/sites/default/includes/modules/settings.config_split.php` turns on the `local` split. The comparison then applies that split, so its modules and items show up as `Only in sync dir`, and `core.extension` as `Different`, although the environment never had them. Under the environment's type, the comparison applies the split the environment uses: `stage` or `dev`, and none for `prod`.
-
-Check a split with `ENVIRONMENT_TYPE=prod ahoy drush config:get config_split.config_split.local status --include-overridden`, which reports `false` when the split is off. A split whose definition file says `status: true` is on under every type. Tell the developer, because the environment applies it too.
-
 List the differences: `ENVIRONMENT_TYPE=prod ahoy drush config:status`. When it prints `No differences between DB and sync directory.`, report that the environment and the code match, then go to step 7. Otherwise, make a todo list with 1 entry per item.
 
 ## 5. Walk each item
