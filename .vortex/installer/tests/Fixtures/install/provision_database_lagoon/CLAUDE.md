@@ -214,6 +214,8 @@
 
 - **GitHub Actions** for CI/CD
 
+- **Lagoon** for hosting
+
 - **Container Registry** deployments for containerized environments
 
 ### Manual Deployment Steps
@@ -221,8 +223,23 @@
 # Build deployment artifact
 ./scripts/vortex/deploy.sh
 
+# Deploy to Lagoon environment
+./scripts/vortex/deploy-lagoon.sh
+
 # Deploy via webhook
 ./scripts/vortex/deploy-webhook.sh
+```
+
+### Lagoon-Specific Commands
+```bash
+# Login to Lagoon
+lagoon login
+
+# Check environment status
+lagoon list projects
+
+# View deployment logs
+lagoon logs
 ```
 
 ## Common Tasks
