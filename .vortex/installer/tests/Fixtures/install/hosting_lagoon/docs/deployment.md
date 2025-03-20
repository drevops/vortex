@@ -17,3 +17,12 @@
 
 Once PR is closed, the environment will be automatically removed.
 
+## Database refresh in Lagoon environments
+
+To fresh the database in the existing Lagoon environment with the database from
+production environment, run:
+
+```
+VORTEX_DEPLOY_BRANCH=<YOUR/BRANCH-NAME> VORTEX_DEPLOY_ACTION=deploy_override_db ahoy deploy
+```
+
