@@ -8,8 +8,6 @@
 - Uses Docker for local development
 - Commands are executed via 'ahoy' (task runner)
 - Configuration is exported/imported via Drupal's config management
-- Testing includes PHPUnit
-- Testing includes Behat (BDD)
 - Deployment is automated via CI/CD pipelines
 
 KEY CONVENTIONS:
@@ -122,19 +120,6 @@
 
 ### Testing Framework
 
-```bash
-# Run PHPUnit tests (unit/integration tests)
-ahoy test-unit
-```
-
-```bash
-# Run Behat tests (behavioral/BDD tests)
-ahoy test-bdd
-
-# Run specific Behat feature
-ahoy test-bdd tests/behat/features/homepage.feature
-```
-
 ## Configuration Management (Critical for Drupal)
 
 ### Understanding Config Management
@@ -196,10 +181,6 @@
 │   └── index.php            # Drupal entry point
 │
 ├── tests/
-│   ├── behat/               # Behavioral tests (user scenarios)
-│   │   ├── features/        # Test scenarios (.feature files)
-│   │   └── behat.yml       # Behat configuration
-│   └── phpunit/            # Unit/integration tests
 │
 └── scripts/
     ├── vortex/             # Core Vortex scripts (don't modify)
@@ -617,97 +598,6 @@
 
 ## Testing Best Practices
 
-### Writing Behat Tests (BDD)
-
-#### User Story Format (Required)
-
-All Behat features MUST follow this format:
-
-```gherkin
-Feature: [Feature name]
-
-  As a [user type]
-  I want to [action/goal]
-  So that [benefit/outcome]
-```
-
-#### Standard User Types
-
-```gherkin
-As a site visitor          # Anonymous users
-As a site administrator    # Admin users
-As a content editor        # Content management users
-As a authenticated user    # Logged-in users
-```
-
-#### Test Data Conventions
-
-- **Always prefix test content**: `[TEST] Page Title`
-- **Use numbered patterns**: `[TEST] Topic 1`, `[TEST] Topic 2`
-- **Avoid real names**: Don't use "Workshop" or "Training"
-- **Be descriptive**: `[TEST] Event with All Fields`
-
-#### Example Feature File
-
-```gherkin
-Feature: Homepage
-
-  As a site visitor
-  I want to access the homepage
-  So that I can view the main landing page and navigate the site
-
-  Scenario: View homepage content
-    Given I am on the homepage
-    Then I should see "[TEST] Welcome Message"
-    And I should see "About Us" in the "navigation" region
-```
-
-#### Discovering Available Step Definitions
-
-```bash
-# Generate step definitions reference (run once)
-ahoy test-bdd -- --definitions=l >.claude/artifacts/behat-steps.txt
-```
-
-Use the cached file for reference, don't regenerate unless asked.
-
-### Content Type Testing Process
-
-When creating comprehensive tests for content types:
-
-1. **Analyze Configuration First**
-
-   - Check `config/default/field.field.node.[type].*.yml`
-   - Review `core.entity_view_display.node.[type].default.yml`
-   - Identify visible vs hidden fields
-
-1. **Create Supporting Entities**
-
-```gherkin
-Background:
-  Given "tags" terms:
-    | name              |
-    | [TEST] Topic 1    |
-    | [TEST] Topic 2    |
-
-  And the following media "image" exist:
-    | name                    |
-    | [TEST] Featured Image 1 |
-```
-
-1. **Test All Visible Fields**
-
-```gherkin
-Scenario: View complete content with all fields
-  Given "page" content:
-    | title                     | body                          | field_tags         |
-    | [TEST] Complete Page Test | [TEST] This is the body text. | [TEST] Topic 1     |
-  When I visit "[TEST] Complete Page Test"
-  Then I should see "[TEST] Complete Page Test"
-  And I should see "[TEST] This is the body text."
-  And I should see "[TEST] Topic 1"
-```
-
 ## Debugging & Troubleshooting
 
 ### Development Tools
@@ -796,7 +686,7 @@
 
 This project includes automated deployment via:
 
-- **GitHub Actions** - See `.github/workflows/`
+- **CircleCI** - See `.circleci/config.yml`
 
 ### Hosting Platforms
 
@@ -881,7 +771,6 @@
 - Always use ahoy prefix for commands
 - Never use drush php:eval directly
 - Test data must use [TEST] prefix
-- Behat tests need proper user story format
 
 The project uses Docker locally but deploys to various hosting platforms with automated CI/CD.
 CLAUDE_CONTEXT_SUMMARY -->
