@@ -75,7 +75,6 @@
 │   └── custom/            # Project-specific custom scripts
 ├── web/                   # Drupal webroot
 │   ├── modules/custom/    # Custom modules
-│   ├── themes/custom/     # Custom themes
 │   └── sites/default/     # Drupal site configuration
 ├── tests/
 │   ├── behat/             # Behavioral tests (BDD)
@@ -115,21 +114,6 @@
 ahoy drush pm:install [module_name]
 ```
 
-### Theme Development
-```bash
-# Navigate to custom theme
-cd web/themes/custom/[theme_name]
-
-# Install theme dependencies (if using npm/yarn)
-npm install
-
-# Build theme assets
-npm run build
-
-# Watch for changes during development
-npm run watch
-```
-
 ## Database and Content
 
 ### Database Operations
@@ -235,8 +219,6 @@
 # Add development dependencies  
 ahoy composer require --dev drupal/devel
 
-# Add theme build tools
-cd web/themes/custom/[theme] && npm install [package]
 ```
 
 ### Dependency Management
