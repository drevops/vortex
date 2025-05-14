@@ -17,3 +17,22 @@
 
 Once PR is closed, the environment will be automatically removed.
 
+## Database refresh in Lagoon environments
+
+To fresh the database in the existing Lagoon environment with the database from
+production environment, run:
+
+```bash
+VORTEX_DEPLOY_BRANCH=<YOUR/BRANCH-NAME> VORTEX_DEPLOY_ACTION=deploy_override_db ahoy deploy
+```
+
+## Skipping deployments
+
+You can completely skip deployments by setting the `VORTEX_DEPLOY_SKIP` environment variable to `1`. This can be useful in CI/CD pipelines where you want to run the build and tests but skip the deployment step.
+
+```bash
+VORTEX_DEPLOY_SKIP=1 ahoy deploy
+```
+
+In CI, you can set the repository variable `VORTEX_DEPLOY_SKIP` to `1` to skip all deployments temporarily.
+
