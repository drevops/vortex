@@ -3,6 +3,21 @@
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
 ## Project-specific configuration
 
 <!-- Add project-specific deployment configuration below -->
