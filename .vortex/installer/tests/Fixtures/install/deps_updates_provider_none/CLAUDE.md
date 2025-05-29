@@ -239,17 +239,6 @@
 cd web/themes/custom/[theme] && npm install [package]
 ```
 
-### Dependency Management
-Dependencies are automatically updated via RenovateBot:
-- **Composer dependencies**: Updated automatically with compatibility checks
-- **Node.js dependencies**: Updated in theme directories
-- **Docker images**: Base image updates for containers
-
-To manually check for updates:
-```bash
-ahoy composer outdated
-```
-
 ### Debugging
 ```bash
 # Enable development modules
