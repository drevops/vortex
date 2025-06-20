# Vortex Drupal Project - Development Guide

## Project Overview

This is a Drupal project built with **Vortex** - a comprehensive Drupal project template by DrevOps that provides production-ready development and deployment workflows.

## Quick Start

```bash
# Build the site locally
ahoy build

# Start development environment
ahoy up

# Access the site
ahoy info
```

## Local Development Commands (Ahoy)

### Environment management

```bash
ahoy up                    # Start Docker containers
ahoy down                  # Stop Docker containers
ahoy restart               # Restart containers
ahoy info                  # Show project information and URLs
```

### Site building and provisioning

```bash
ahoy build                 # Build site from scratch
ahoy provision             # Provision site (install/import DB)
ahoy reset                 # Reset to clean state
```

### Database operations

```bash
ahoy download-db           # Download fresh database
ahoy export-db             # Export current database
ahoy import-db             # Import database from file
```

### Development tools

```bash
ahoy drush [command]       # Run Drush commands
ahoy composer [command]    # Run Composer commands
ahoy phpcs                 # Run code style checks
ahoy phpcbf                # Fix code style issues
```

### Code Quality

```bash
ahoy lint                  # Run all linting checks
ahoy lint-fix              # Fix automatically fixable issues
```

### Testing

```bash
ahoy test-unit             # Run PHPUnit tests
ahoy test-bdd              # Run Behat (BDD) tests
ahoy test                  # Run all tests
```

#### Behat Feature Writing Standards

When creating or updating Behat feature files, follow these conventions:

- **User Story Format**: All features must use the standard user story format:
  ```gherkin
  As a [user type]
  I want to [action]
  So that [benefit]
  ```

- **User Types**: Use consistent user types across features:
  - `site visitor` - for anonymous users and general site access
  - `site administrator` - for users with administrative privileges
  - `content editor` - for users managing content

- **No Punctuation**: Do not use commas or periods in user story statements

- **Example**:
  ```gherkin
  Feature: Homepage

    As a site visitor
    I want to access the homepage
    So that I can view the main landing page and navigate the site
  ```

## Project Structure

```
├── config/                # Drupal configuration (exported)
│   ├── default/           # Default configuration
│   ├── dev/               # Development-specific config
│   ├── stage/             # Staging-specific config
│   └── ci/                # CI-specific config
├── scripts/
│   ├── vortex/            # Core Vortex deployment scripts
│   └── custom/            # Project-specific custom scripts
├── web/                   # Drupal webroot
│   ├── modules/custom/    # Custom modules
│   ├── themes/custom/     # Custom themes
│   └── sites/default/     # Drupal site configuration
├── tests/
│   ├── behat/             # Behavioral tests (BDD)
│   └── phpunit/           # Unit/integration tests
├── docker-compose.yml     # Local development environment
└── .env                   # Environment configuration
```

## Configuration Management

### Exporting Configuration

```bash
# Export all configuration changes
ahoy drush config:export

# Export specific configuration
ahoy drush config:export --diff
```

### Importing Configuration

```bash
# Import configuration (usually part of deployment)
ahoy drush config:import

# Import with specific source
ahoy drush config:import --source=../config/stage
```

### Theme Development

```bash
# Navigate to custom theme
cd web/themes/custom/star_wars

# Install theme dependencies (if using npm/yarn)
yarn install

# Build theme assets
yarn run build

# Watch for changes during development
yarn run watch
```

## Database and Content

### Content Management

- Use Configuration Management for structure (content types, fields, views)
- Use database imports for content in non-production environments
- Use migration modules for structured content imports in production

## Services Integration

### Solr Search

```bash
# Check Solr status
ahoy drush search-api:status

# Index content
ahoy drush search-api:index

# Clear Solr index
ahoy drush search-api:clear
```

### Valkey (Redis-compatible caching)

```bash
# Check cache status
ahoy drush cache:rebuild

# Clear Redis/Valkey cache
ahoy drush php-eval "\Drupal\redis\Client\ClientInterface::flushAll();"
```

### ClamAV Virus Scanning

```bash
# Test virus scanning functionality
ahoy drush clamav:scan

# Check ClamAV status
ahoy drush clamav:status
```

## Deployment

### CI/CD Pipeline
The project includes automated deployment via:

- **GitHub Actions** for CI/CD

- **Container Registry** deployments for containerized environments

## Common Tasks

### Adding Dependencies

```bash
# Add Drupal modules
ahoy composer require drupal/module_name

# Add development dependencies
ahoy composer require --dev drupal/devel
```

### Add theme build tools

```bash
cd web/themes/custom/star_wars && npm install [package]
```

### Dependency Management

Dependencies are automatically updated via RenovateBot:
- **Composer dependencies**: Updated automatically with compatibility checks
- **Node.js dependencies**: Updated in theme directories
- **Docker images**: Base image updates for containers

To manually check for updates:
```bash
ahoy composer outdated
```

### Debugging

```bash
# Enable development modules
ahoy drush pm:install devel webprofiler

# View logs
ahoy drush watchdog:show

# Clear caches
ahoy drush cache:rebuild
```

### Performance

```bash
# Enable caching
ahoy drush config:set system.performance css.preprocess 1
ahoy drush config:set system.performance js.preprocess 1

# Clear specific caches
ahoy drush cache:rebuild-external
```

## Troubleshooting

### Common Issues

**Site not loading locally:**
```bash
ahoy down && ahoy up
ahoy info  # Check URLs and container status
```

**Database connection issues:**
```bash
# Check database container
docker-compose ps
ahoy reset  # Rebuild if needed
```

## Resources

- **Vortex Documentation**: https://www.vortextemplate.com
- **Drupal Documentation**: https://www.drupal.org/docs
- **Drush Documentation**: https://www.drush.org
- **Ahoy Documentation**: https://github.com/ahoy-cli/ahoy

## Getting Help

- Check `ahoy --help` for available commands
- Use `ahoy [command] --help` for specific command help
- Review project-specific documentation in `/docs` (if available)
- Check environment logs: `ahoy logs`

---

*This guide covers the essentials for working with your Vortex-powered Drupal project. As your project grows, consider expanding this guide with project-specific workflows and conventions.*
