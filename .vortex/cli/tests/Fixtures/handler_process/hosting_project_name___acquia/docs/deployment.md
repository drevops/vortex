@@ -3,6 +3,35 @@
 To learn how deployment works, see the [Vortex deployment
 documentation](https://www.vortextemplate.com/docs/deployment).
 
+## Hosting provider
+
+This project is hosted on [Acquia
+Cloud](https://www.acquia.com/products/drupal-cloud).
+
+For setup and configuration details, see the [Acquia hosting
+documentation](https://www.vortextemplate.com/docs/hosting/acquia).
+
+### Deployment workflow
+
+1. Code is pushed to GitHub (source repository).
+2. CI builds and tests the code.
+3. On success, CI builds an artifact and pushes it to Acquia Cloud
+   (destination repository).
+4. Acquia Cloud runs deployment hooks.
+
+### Branch naming on Acquia Cloud
+
+- Feature branches (`feature/ABC-123`) → same name on Acquia
+- Release tags (`__VERSION__`) → `deployment/__VERSION__` branch on Acquia
+
+### Important rules
+
+- Never push directly to the Acquia Cloud repository.
+- Only the Technical Lead and the Deployer user should have access to the
+  Acquia repository.
+- The Technical Lead should regularly clean up `feature/*` and `bugfix/*`
+  branches.
+
 ## Repeatable deploy hooks
 
 Put logic that must run on **every** deploy in a `DeployStep` plugin.
