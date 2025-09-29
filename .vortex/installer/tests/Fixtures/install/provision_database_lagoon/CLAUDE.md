# Vortex Drupal Project - Development Guide

<!-- CLAUDE_CONTEXT_START
This is a Drupal project built with the Vortex template by DrevOps.

CRITICAL UNDERSTANDING:
- This is a PRODUCTION-READY Drupal project template
- Uses Docker for local development
- Commands are executed via 'ahoy' (task runner)
- Configuration is exported/imported via Drupal's config management
- Testing includes both PHPUnit (unit) and Behat (BDD)
- Deployment is automated via CI/CD pipelines

KEY CONVENTIONS:
- All local commands use 'ahoy' prefix
- Test data must use [TEST] prefix
- Never use drush php:eval directly
- Always use scripts via drush php:script
- Configuration changes must be exported
- Database operations vary by project setup

CLAUDE_CONTEXT_END -->

## Project Overview

This is a **production-ready Drupal project** built with **Vortex** - a
comprehensive Drupal project template by DrevOps that provides:

- 🐳 **Docker-based development environment**
- 🔄 **Automated CI/CD deployment workflows**
- 🧪 **Comprehensive testing framework** (PHPUnit + Behat)
- ⚙️ **Configuration management** (exportable configs)
- 🚀 **Production hosting integration**

## Quick Start Commands

```bash
# STEP 1: Build the site locally (first time setup)
ahoy build

# STEP 2: Start development environment
ahoy up

# STEP 3: Get site information and URLs
ahoy info

# STEP 4: Get admin login link
ahoy login
```

## Core Development Workflow

### 1. Environment Management

```bash
# Start Docker containers (daily workflow)
ahoy up

# Stop Docker containers (end of day)
ahoy down

# Restart all containers (troubleshooting)
ahoy restart

# Show project URLs, container status, database info
ahoy info
```

### 2. Site Building & Database

```bash
# Complete site rebuild (nuclear option)
ahoy build

# Re-provision site (install/import fresh DB)
ahoy provision

# Reset to clean state (removes local changes)
ahoy reset
```

```bash
# Download fresh database from remote source
ahoy download-db

# Export current local database
ahoy export-db

# Import database from file
ahoy import-db [path/to/dump.sql]
```

### 3. Daily Development Tasks

```bash
# Run Drush commands (Drupal CLI)
ahoy drush [command]
# Examples:
ahoy drush status
ahoy drush cr # Clear cache
ahoy drush uli # Get login link

# Run Composer commands (PHP dependencies)
ahoy composer [command]
# Examples:
ahoy composer install
ahoy composer require drupal/admin_toolbar
```

## Code Quality & Testing

### Linting (Code Standards)

```bash
# Check code style issues (PHP, JS, CSS)
ahoy lint

# Automatically fix code style issues
ahoy lint-fix
```

### Testing Framework

```bash
# Run PHPUnit tests (unit/integration tests)
ahoy test-unit

# Run Behat tests (behavioral/BDD tests)
ahoy test-bdd

# Run ALL tests (unit + BDD)
ahoy test

# Run specific Behat feature
ahoy test-bdd tests/behat/features/homepage.feature
```

## Configuration Management (Critical for Drupal)

### Understanding Config Management

- **Structure changes** (content types, fields, views) = Configuration (exported
  to code)
- **Content data** (nodes, users, media) = Database (not exported)

### Export Configuration (After making admin changes)

```bash
# Export ALL configuration changes to code
ahoy drush config:export
# Short version:
ahoy drush cex

# Export with diff preview
ahoy drush config:export --diff
```

### Import Configuration (Deploy config changes)

```bash
# Import configuration from code
ahoy drush config:import
# Short version:
ahoy drush cim

# Import from specific environment
ahoy drush config:import --source=../config/stage
```

### Typical Config Workflow

1. Make changes in Drupal admin UI
2. Run `ahoy drush cex` to export to code
3. Commit the config files to git
4. Deploy code and run `ahoy drush cim` on target environment

## Project Structure (Critical Understanding)

```text
your-project/
├── .ahoy.yml                  # Ahoy task definitions
├── .env                       # Environment variables (local)
├── docker-compose.yml         # Local development containers
├── composer.json              # PHP dependencies
│
├── config/                    # Drupal configuration (version controlled)
│   ├── default/              # Base configuration (all environments)
│   ├── dev/                  # Development-specific overrides
│   ├── stage/                # Staging-specific overrides
│   └── ci/                   # CI-specific overrides
│
├── web/                      # Drupal webroot (document root)
│   ├── modules/custom/       # Your custom modules
│   ├── themes/custom/        # Your custom themes
│   ├── sites/default/        # Drupal site settings
│   └── index.php            # Drupal entry point
│
├── tests/
│   ├── behat/               # Behavioral tests (user scenarios)
│   │   ├── features/        # Test scenarios (.feature files)
│   │   └── behat.yml       # Behat configuration
│   └── phpunit/            # Unit/integration tests
│
└── scripts/
    ├── vortex/             # Core Vortex scripts (don't modify)
    └── custom/             # Project-specific scripts
```

## Custom Code Development

### Creating Custom Modules

```bash
# Generate custom module scaffold
ahoy drush generate:module

# Location: web/modules/custom/[module_name]/
# Enable module:
ahoy drush pm:install [module_name]
```

### Theme Development

```bash
# Navigate to custom theme
cd web/themes/custom/[theme_name]

# Install theme dependencies
yarn install

# Build theme assets (CSS/JS)
yarn run build

# Watch for changes during development
yarn run watch

# Build for production
yarn run build:prod
```

## PHP Script Execution (IMPORTANT)

### ✅ Correct Way: Use PHP Scripts

```bash
# Run PHP script with full Drupal bootstrap
ahoy drush php:script script_name

# List available scripts
ahoy drush php:script

# Run with custom script path
ahoy drush php:script script_name --script-path=scripts/custom

# Pass arguments to script (note the -- separator)
ahoy drush php:script -- script_name --arg1=value1 --arg2=value2
```

### ❌ NEVER Do This

```bash
# DANGEROUS - Never evaluate PHP directly!
ahoy drush php:eval "dangerous_code_here"
```

### Creating PHP Scripts

Create scripts in `scripts/custom/` directory:

```php
<?php
/**
 * @file
 * Example custom script.
 */

use Drupal\node\Entity\Node;

// Your Drupal code here
$nodes = \\Drupal::entityTypeManager()
  ->getStorage('node')
  ->loadByProperties(['type' => 'page']);

foreach ($nodes as $node) {
  print $node->getTitle();
}
```

## Service Integrations

### Solr Search Service

```bash
# Check Solr search status
ahoy drush search-api:status

# Index all content to Solr
ahoy drush search-api:index

# Clear and rebuild Solr index
ahoy drush search-api:clear
ahoy drush search-api:index

# Check Solr server connection
ahoy drush search-api:server-status
```

### Redis Caching Service

```bash
# Clear all caches (includes Redis)
ahoy drush cache:rebuild

# Check Redis connection status
ahoy drush php:script -- redis_status

# Flush Redis cache specifically
ahoy drush eval "\\Drupal::service('cache.backend.redis')->deleteAll();"
```

### ClamAV Virus Scanning Service

```bash
# Test virus scanning functionality
ahoy drush clamav:scan /path/to/test/file

# Check ClamAV daemon status
ahoy drush clamav:status

# Update virus definitions
ahoy drush clamav:update
```

## Dependency Management

### Adding Drupal Modules

```bash
# Add contributed modules
ahoy composer require drupal/admin_toolbar
ahoy composer require drupal/pathauto

# Add development-only modules
ahoy composer require --dev drupal/devel

# Enable installed modules
ahoy drush pm:install admin_toolbar pathauto
```

### Patching Contributed Modules

When contributed modules need fixes or customizations, use the proper patching workflow with `cweagans/composer-patches`.

#### Prerequisites for Patching

- Project uses `cweagans/composer-patches` package
- Git is available for version control
- Access to Drupal.org git repositories

#### Patch Storage and Configuration

Patches are defined in `composer.json` under the `extra.patches` section:

```json
{
  "extra": {
    "patches": {
      "drupal/module_name": {
        "Description of fix": "patches/module-name-description.patch",
        "Another fix": "https://www.drupal.org/files/issues/external-patch.patch"
      }
    }
  }
}
```

- **Local patches**: Store in `patches/` directory in project root
- **External patches**: Reference URLs directly in composer.json
- **Naming convention**: Use descriptive names like `module-name-description.patch`

#### Creating Module Patches

Step 1: Identify Module Version

Always work with the exact version and state used in your project:

```bash
# Check installed version
ahoy composer show drupal/module_name

# Verify version in composer.lock
grep -A 20 "drupal/module_name" composer.lock
```

Step 2: Clone Module from Git

**CRITICAL**: Always use the official Drupal git repository, not tarball downloads:

```bash
# Create working directory
cd /tmp && mkdir module_patch_work && cd module_patch_work

# Clone the module
git clone https://git.drupalcode.org/project/module_name.git

# Navigate to module directory
cd module_name

# Checkout the exact version/tag used in project
git checkout 1.2.3  # Replace with actual version

# Create working branch
git checkout -b fix-description
```

Step 3: Apply Existing Patches

If your project already has patches for this module, apply them first to match the current state:

```bash
# Apply each existing patch in order
curl -s https://example.com/patch1.patch | patch -p1
curl -s https://example.com/patch2.patch | patch -p1

# Commit the patches to establish baseline
git add .
git commit -m "Apply existing patches to match project state"
```

Step 4: Make Required Changes

Edit the necessary files to implement your fix:

```bash
# Make your changes using your preferred editor
vim path/to/file.php

# Or use automated tools if applicable
sed -i 's/old_code/new_code/' path/to/file.php
```

Step 5: Generate Clean Patch

Create a proper git-based patch:

```bash
# Stage your changes
git add .

# Generate patch from staged changes
git diff --cached > /path/to/project/patches/module-name-fix-description.patch
```

Step 6: Test Patch Application

**ALWAYS test that your patch applies cleanly**:

```bash
# Reset to test patch application
git reset --hard HEAD

# Test patch applies without conflicts
git apply /path/to/project/patches/module-name-fix-description.patch

# Verify changes were applied correctly
git status
git diff
```

Step 7: Integrate into Project

Add the patch to your project's composer configuration and test:

```bash
# Update the specific module to apply patch
ahoy composer require drupal/module_name

# Verify no patch application errors
# Check that functionality works as expected
```

Step 8: Clean Up

Remove temporary working directory:

```bash
rm -rf /tmp/module_patch_work
```

#### Patching Best Practices

**✅ Do This:**

- Use descriptive patch names that explain what they fix
- Keep patches focused - one fix per patch when possible
- Follow Drupal coding standards in your changes
- Test locally before committing patches
- Document the issue being fixed in composer.json description
- Include issue URLs when available from drupal.org

**❌ Avoid These Mistakes:**

- Working with tarball downloads instead of git repositories
- Creating patches from modified project files
- Skipping patch application testing
- Creating patches without applying existing patches first
- Assuming patches work across different module versions

#### Troubleshooting Patch Issues

**Patch Won't Apply:**

1. Check module version matches between patch creation and application
2. Verify existing patches are applied in correct order
3. Check for whitespace issues in patch file
4. Ensure patch paths are correct (usually relative to module root)

**Patch Conflicts:**

1. Identify conflicting patches by applying them individually
2. Update patch order in composer.json if needed
3. Recreate patches against the current patched state
4. Merge patches if they modify the same areas

**Performance Issues:**

1. Minimize external patch URLs to reduce download time
2. Store frequently used patches locally in patches directory
3. Keep patch files small and focused
4. Remove obsolete patches when updating modules

### Adding JavaScript/CSS Libraries

For npm packages that need to be Drupal libraries, define them as inline
Composer packages:

1. **Add to composer.json repositories section:**

```json
{
  "repositories": [
    {
      "type": "package",
      "package": {
        "name": "vendor/library-name",
        "type": "drupal-library",
        "version": "1.0.0",
        "source": {
          "type": "git",
          "url": "https://github.com/vendor/library-name",
          "reference": "1.0.0"
        }
      }
    }
  ]
}
```

1. **Install via Composer:**

```bash
ahoy composer require vendor/library-name
```

### Theme Dependencies

```bash
# Navigate to theme directory
cd web/themes/custom/[theme_name]

# Add frontend dependencies
yarn add [package-name]

# Example: Add Bootstrap
yarn add bootstrap

# Install dev dependencies
yarn add --dev sass webpack
```

## Testing Best Practices

### Writing Behat Tests (BDD)

#### User Story Format (Required)

All Behat features MUST follow this format:

```gherkin
Feature: [Feature name]

  As a [user type]
  I want to [action/goal]
  So that [benefit/outcome]
```

#### Standard User Types

```gherkin
As a site visitor          # Anonymous users
As a site administrator    # Admin users
As a content editor        # Content management users
As a authenticated user    # Logged-in users
```

#### Test Data Conventions

- **Always prefix test content**: `[TEST] Page Title`
- **Use numbered patterns**: `[TEST] Topic 1`, `[TEST] Topic 2`
- **Avoid real names**: Don't use "Workshop" or "Training"
- **Be descriptive**: `[TEST] Event with All Fields`

#### Example Feature File

```gherkin
Feature: Homepage

  As a site visitor
  I want to access the homepage
  So that I can view the main landing page and navigate the site

  Scenario: View homepage content
    Given I am on the homepage
    Then I should see "[TEST] Welcome Message"
    And I should see "About Us" in the "navigation" region
```

#### Discovering Available Step Definitions

```bash
# Generate step definitions reference (run once)
ahoy test-bdd -- --definitions=l >.claude/artifacts/behat-steps.txt
```

Use the cached file for reference, don't regenerate unless asked.

### Content Type Testing Process

When creating comprehensive tests for content types:

1. **Analyze Configuration First**

   - Check `config/default/field.field.node.[type].*.yml`
   - Review `core.entity_view_display.node.[type].default.yml`
   - Identify visible vs hidden fields

1. **Create Supporting Entities**

```gherkin
Background:
  Given "tags" terms:
    | name              |
    | [TEST] Topic 1    |
    | [TEST] Topic 2    |

  And the following media "image" exist:
    | name                    |
    | [TEST] Featured Image 1 |
```

1. **Test All Visible Fields**

```gherkin
Scenario: View complete content with all fields
  Given "page" content:
    | title                     | body                          | field_tags         |
    | [TEST] Complete Page Test | [TEST] This is the body text. | [TEST] Topic 1     |
  When I visit "[TEST] Complete Page Test"
  Then I should see "[TEST] Complete Page Test"
  And I should see "[TEST] This is the body text."
  And I should see "[TEST] Topic 1"
```

## Debugging & Troubleshooting

### Development Tools

```bash
# Enable development modules
ahoy drush pm:install devel webprofiler stage_file_proxy

# Get admin login URL
ahoy login

# View recent log entries
ahoy drush watchdog:show

# Clear all caches
ahoy drush cache:rebuild

# Check system status
ahoy drush status
```

### Performance Optimization

```bash
# Enable CSS/JS aggregation
ahoy drush config:set system.performance css.preprocess 1
ahoy drush config:set system.performance js.preprocess 1

# Clear render cache
ahoy drush cache:rebuild-external

# Check database updates needed
ahoy drush updatedb:status
```

### Container Debugging

```bash
# Check container status
docker-compose ps

# View container logs
docker-compose logs [service_name]
# Examples:
docker-compose logs web
docker-compose logs db

# Access container shell
docker-compose exec web bash
docker-compose exec db mysql -u drupal -p drupal
```

### Common Issues & Solutions

**Site not loading:**

```bash
ahoy down && ahoy up
ahoy info # Verify URLs and ports
```

**Database connection errors:**

```bash
docker-compose ps      # Check if database container is running
ahoy reset             # Nuclear option: rebuild everything
```

**Permission issues:**

```bash
# Fix file permissions (Linux/Mac)
sudo chown -R $USER:$USER .
```

**Memory issues during composer install:**

```bash
# Increase PHP memory temporarily
ahoy composer install --no-dev --optimize-autoloader
```

## CI/CD & Deployment

### Automated Deployment

This project includes automated deployment via:

- **GitHub Actions** - See `.github/workflows/`

### Hosting Platforms

- **Lagoon** - Container-based hosting platform

- **Container Registry** - Docker-based deployments

### Manual Deployment Commands

```bash
# Export configuration before deployment
ahoy drush config:export

# Run database updates
ahoy drush updatedb

# Import configuration
ahoy drush config:import

# Clear caches
ahoy drush cache:rebuild

# Full deployment sequence
ahoy drush updatedb && ahoy drush config:import && ahoy drush cache:rebuild
```

## Getting Help & Resources

### Command Help

```bash
# List all available ahoy commands
ahoy --help

# Get help for specific command
ahoy [command] --help

# Examples:
ahoy build --help
ahoy test-bdd --help
```

### Log Files & Debugging

```bash
# View ahoy logs
ahoy logs

# Check container logs
docker-compose logs --tail=50 web

# View Drupal watchdog logs
ahoy drush watchdog:show --count=20
```

### Documentation Resources

- **Vortex Documentation**: https://www.vortextemplate.com
- **Drupal Documentation**: https://www.drupal.org/docs
- **Drush Documentation**: https://www.drush.org
- **Ahoy Documentation**: https://github.com/ahoy-cli/ahoy
- **Docker Compose**: https://docs.docker.com/compose/

### Project-Specific Help

- Check `/docs` directory for additional project documentation
- Review `README.md` in project root
- Check `.ahoy.yml` for custom commands
- Review `composer.json` for installed packages and scripts

---

<!-- CLAUDE_CONTEXT_SUMMARY
This is a comprehensive guide for a Vortex Drupal project that provides:

IMMEDIATE ACTIONS available:
- ahoy build (first setup)
- ahoy up/down (start/stop)
- ahoy info (get project info)
- ahoy login (get admin access)

DAILY WORKFLOW:
- ahoy drush [cmd] for Drupal operations
- ahoy composer [cmd] for dependencies
- ahoy lint/test for quality checks
- ahoy drush cex/cim for config management

CRITICAL CONCEPTS:
- Configuration = exported to code (structure)
- Content = stays in database (data)
- Always use ahoy prefix for commands
- Never use drush php:eval directly
- Test data must use [TEST] prefix
- Behat tests need proper user story format

The project uses Docker locally but deploys to various hosting platforms with automated CI/CD.
CLAUDE_CONTEXT_SUMMARY -->

*This guide covers the complete development workflow for your Vortex-powered
Drupal project. Keep this guide updated as your project grows and add
project-specific conventions below.*
