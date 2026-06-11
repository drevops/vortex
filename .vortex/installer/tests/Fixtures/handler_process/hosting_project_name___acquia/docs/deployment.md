@@ -3,6 +3,32 @@
 For information on how deployment works, see
 [Vortex Deployment Documentation](https://www.vortextemplate.com/docs/deployment).
 
+## Hosting provider
+
+This project is hosted on [Acquia Cloud](https://www.acquia.com/products/drupal-cloud).
+
+See [Acquia hosting documentation](https://www.vortextemplate.com/docs/hosting/acquia)
+for setup and configuration details.
+
+### Deployment workflow
+
+1. Code is pushed to GitHub (source repository).
+2. CI builds and tests the code.
+3. On success, CI builds an artifact and pushes to Acquia Cloud (destination
+   repository).
+4. Acquia Cloud runs deployment hooks.
+
+### Branch naming on Acquia Cloud
+
+- Feature branches (`feature/ABC-123`) → same name on Acquia
+- Release tags (`__VERSION__`) → `deployment/__VERSION__` branch on Acquia
+
+### Important rules
+
+- No direct pushes to Acquia Cloud repository.
+- Only Technical Lead and Deployer user should have access to Acquia repository.
+- Technical Lead should regularly clean up `feature/*` and `bugfix/*` branches.
+
 ## Repeatable deploy hooks
 
 Logic that must run on **every** deploy is a `PersistentDeploy` plugin, not a
@@ -10,7 +36,7 @@
 `hook_post_update_NAME()`, `hook_deploy_NAME()`) are recorded as completed and
 never run again, so they cannot express "run on every deploy".
 
-The `persistent_deploy` module (`web/modules/custom/persistent_deploy`) owns a
+The `persistent_deploy` module (`docroot/modules/custom/persistent_deploy`) owns a
 single pair of Drush `pre-command` / `post-command` hooks on `deploy:hook` and,
 on every deploy, discovers every `PersistentDeploy` plugin from every enabled
 module, groups them by phase, orders each phase by weight, asks each plugin's
