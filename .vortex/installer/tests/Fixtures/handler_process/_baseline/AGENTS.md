# star wars - Development Guide

## Daily Development Tasks

```bash
# Environment
ahoy up     # Start containers
ahoy down   # Stop containers
ahoy info   # Show URLs and status
ahoy login  # Get admin login URL

# Build & Database
ahoy download-db  # Download fresh database from remote
ahoy build        # Complete site rebuild
ahoy provision    # Re-provision (import DB + apply config)
ahoy import-db    # Import database from file without applying config
ahoy export-db    # Export current local database

# Drush commands
ahoy drush cr     # Clear cache
ahoy drush updb   # Run database updates
ahoy drush cex    # Export configuration to code
ahoy drush cim    # Import configuration from code
ahoy drush uli    # Get one-time login link
ahoy drush status # Check site status

# Composer
ahoy composer install
ahoy composer require drupal/[module_name]

# Code quality
ahoy lint     # Check code style
ahoy lint-fix # Auto-fix code style

# PHPUnit testing
ahoy test            # Run PHPUnit tests
ahoy test-unit       # Run PHPUnit Unit tests
ahoy test-kernel     # Run PHPUnit Kernel tests
ahoy test-functional # Run PHPUnit Functional tests
ahoy test -- --filter=TestClassName  # Run specific PHPUnit test class

# Behat testing
ahoy test-bdd # Run Behat tests
ahoy test-bdd -- --tags=@tagname  # Run Behat tests with specific tag
```

## Critical Rules

- **Never modify** `scripts/vortex/` - use `scripts/custom/` for your scripts
- **Never use** `ahoy drush php:eval` - use `ahoy drush php:script` instead
- **Always export config** after admin UI changes: `ahoy drush cex`

## Key Directories

- `web/modules/custom/` - Custom modules
- `web/themes/custom/` - Custom themes
- `config/default/` - Drupal configuration
- `scripts/custom/` - Project scripts
- `patches/` - Module patches

## Documentation

This project uses two documentation sources:

### Project-specific documentation (`docs/`)

The `docs/` directory contains **what** applies to this project:

- `docs/testing.md` - Testing conventions and agreements
- `docs/ci.md` - CI provider and configuration
- `docs/deployment.md` - Hosting provider and deployment rules
- `docs/releasing.md` - Version scheme and release process
- `docs/faqs.md` - Project-specific FAQs

**Always check these files first** to understand project-specific decisions.

### Vortex documentation (vortextemplate.com)

For **how** to perform operations, fetch from https://www.vortextemplate.com/docs.

Use the sitemap to discover available pages: https://www.vortextemplate.com/sitemap.xml

**Caching:** Save fetched docs to `.data/ai-artifacts/docs-[topic].md` with header
`<!-- Source: [URL] | Cached: [YYYY-MM-DD] -->`.
Re-fetch if user reports docs are outdated.
