@@ -73,7 +73,7 @@
 ├── scripts/
 │   ├── vortex/            # Core Vortex deployment scripts
 │   └── custom/            # Project-specific custom scripts
-├── web/                   # Drupal webroot
+├── docroot/                   # Drupal webroot
 │   ├── modules/custom/    # Custom modules
 │   ├── themes/custom/     # Custom themes
 │   └── sites/default/     # Drupal site configuration
@@ -118,7 +118,7 @@
 ### Theme Development
 ```bash
 # Navigate to custom theme
-cd web/themes/custom/[theme_name]
+cd docroot/themes/custom/[theme_name]
 
 # Install theme dependencies (if using npm/yarn)
 npm install
@@ -214,6 +214,8 @@
 
 - **GitHub Actions** for CI/CD
 
+- **Acquia** for hosting
+
 - **Container Registry** deployments for containerized environments
 
 ### Manual Deployment Steps
@@ -221,10 +223,25 @@
 # Build deployment artifact
 ./scripts/vortex/deploy.sh
 
+# Deploy to Acquia environment
+./scripts/vortex/deploy-acquia.sh
+
 # Deploy via webhook
 ./scripts/vortex/deploy-webhook.sh
 ```
 
+### Acquia-Specific Commands
+```bash
+# Download database from Acquia
+ahoy download-db-acquia
+
+# Copy files from Acquia
+ahoy copy-files-acquia
+
+# Deploy code to Acquia
+ahoy deploy-acquia
+```
+
 ## Common Tasks
 
 ### Adding Dependencies
@@ -236,7 +253,7 @@
 ahoy composer require --dev drupal/devel
 
 # Add theme build tools
-cd web/themes/custom/[theme] && npm install [package]
+cd docroot/themes/custom/[theme] && npm install [package]
 ```
 
 ### Dependency Management
