@@ -5,8 +5,8 @@
 
 ## Repeatable deploy hooks
 
-Logic that must run on **every** deploy lives in the `sw_deploy` module
-(`web/modules/custom/sw_deploy`), not in run-once hooks. Drupal and Drush
+Logic that must run on **every** deploy lives in the `the_force_deploy` module
+(`web/modules/custom/the_force_deploy`), not in run-once hooks. Drupal and Drush
 run-once hooks (`hook_update_N()`, `hook_post_update_NAME()`,
 `hook_deploy_NAME()`) are recorded as completed and never run again, so they
 cannot express "run on every deploy".
@@ -13,7 +13,7 @@
 
 A project has two deploy-time layers:
 
-- **Drupal-level "every deploy"** - the Drush command hooks in `sw_deploy`. They
+- **Drupal-level "every deploy"** - the Drush command hooks in `the_force_deploy`. They
   run wherever `drush deploy:hook` runs (CI, local, and production hosting after
   rollout). Add idempotent steps to `preDeploySteps()` or `postDeploySteps()` in
   `DeployCommands`.
