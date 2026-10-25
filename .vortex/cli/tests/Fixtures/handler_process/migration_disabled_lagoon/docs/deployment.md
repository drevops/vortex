@@ -3,6 +3,29 @@
 For information on how deployment works, see
 [Vortex Deployment Documentation](https://www.vortextemplate.com/docs/deployment).
 
+## Hosting provider
+
+This project is hosted on [Lagoon](https://www.amazee.io/lagoon).
+
+See [Lagoon hosting documentation](https://www.vortextemplate.com/docs/hosting/lagoon)
+for setup and configuration details.
+
+### Database refresh
+
+To refresh the database in an existing Lagoon environment with production data:
+
+```bash
+VORTEX_DEPLOY_BRANCH=<YOUR/BRANCH-NAME> VORTEX_DEPLOY_ACTION=deploy_override_db ahoy deploy
+```
+
+### Maintenance page
+
+To serve a static maintenance page in place of the site, set the
+`VORTEX_WEBSERVER_MAINTENANCE_MODE` variable to `1` on the environment and
+redeploy it. The `webserver` container then answers every request with the
+page in `.docker/config/nginx/maintenance.html`. See [Serve a maintenance
+page](https://www.vortextemplate.com/docs/hosting/lagoon#serve-a-maintenance-page).
+
 ## Repeatable deploy hooks
 
 Logic that must run on **every** deploy is a `DeployStep` plugin, not a
