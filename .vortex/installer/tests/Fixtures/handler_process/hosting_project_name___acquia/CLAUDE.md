@@ -189,7 +189,7 @@
 │   ├── stage/                # Staging-specific overrides
 │   └── ci/                   # CI-specific overrides
 │
-├── web/                      # Drupal webroot (document root)
+├── docroot/                      # Drupal webroot (document root)
 │   ├── modules/custom/       # Your custom modules
 │   ├── themes/custom/        # Your custom themes
 │   ├── sites/default/        # Drupal site settings
@@ -214,7 +214,7 @@
 # Generate custom module scaffold
 ahoy drush generate:module
 
-# Location: web/modules/custom/[module_name]/
+# Location: docroot/modules/custom/[module_name]/
 # Enable module:
 ahoy drush pm:install [module_name]
 ```
@@ -223,7 +223,7 @@
 
 ```bash
 # Navigate to custom theme
-cd web/themes/custom/[theme_name]
+cd docroot/themes/custom/[theme_name]
 
 # Install theme dependencies
 yarn install
@@ -603,7 +603,7 @@
 
 ```bash
 # Navigate to theme directory
-cd web/themes/custom/[theme_name]
+cd docroot/themes/custom/[theme_name]
 
 # Add frontend dependencies
 yarn add [package-name]
@@ -799,6 +799,8 @@
 - **GitHub Actions** - See `.github/workflows/`
 
 ### Hosting Platforms
+
+- **Acquia Cloud** - Enterprise Drupal hosting
 
 - **Container Registry** - Docker-based deployments
 
