@@ -219,25 +219,6 @@
 ahoy drush pm:install [module_name]
 ```
 
-### Theme Development
-
-```bash
-# Navigate to custom theme
-cd web/themes/custom/[theme_name]
-
-# Install theme dependencies
-yarn install
-
-# Build theme assets (CSS/JS)
-yarn run build
-
-# Watch for changes during development
-yarn run watch
-
-# Build for production
-yarn run build:prod
-```
-
 ## PHP Script Execution (IMPORTANT)
 
 ### ✅ Correct Way: Use PHP Scripts
@@ -597,22 +578,6 @@
 
 ```bash
 ahoy composer require vendor/library-name
-```
-
-### Theme Dependencies
-
-```bash
-# Navigate to theme directory
-cd web/themes/custom/[theme_name]
-
-# Add frontend dependencies
-yarn add [package-name]
-
-# Example: Add Bootstrap
-yarn add bootstrap
-
-# Install dev dependencies
-yarn add --dev sass webpack
 ```
 
 ## Testing Best Practices
