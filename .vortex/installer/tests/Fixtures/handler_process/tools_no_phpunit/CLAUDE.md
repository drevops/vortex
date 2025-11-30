@@ -8,7 +8,6 @@
 - Uses Docker for local development
 - Commands are executed via 'ahoy' (task runner)
 - Configuration is exported/imported via Drupal's config management
-- Testing includes PHPUnit
 - Testing includes Behat (BDD)
 - Deployment is automated via CI/CD pipelines
 
@@ -123,11 +122,6 @@
 ### Testing Framework
 
 ```bash
-# Run PHPUnit tests (unit/integration tests)
-ahoy test-unit
-```
-
-```bash
 # Run Behat tests (behavioral/BDD tests)
 ahoy test-bdd
 
@@ -199,7 +193,6 @@
 │   ├── behat/               # Behavioral tests (user scenarios)
 │   │   ├── features/        # Test scenarios (.feature files)
 │   │   └── behat.yml       # Behat configuration
-│   └── phpunit/            # Unit/integration tests
 │
 └── scripts/
     ├── vortex/             # Core Vortex scripts (don't modify)
