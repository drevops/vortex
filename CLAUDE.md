# Vortex Drupal Project - Development Guide

> **🚀 PROJECT MODE**: This guide helps with **developing Drupal projects** created from the Vortex template.
> 
> For **maintaining the Vortex template itself**, see the maintenance guide: `.vortex/CLAUDE.md`

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

## Development Workflows

### Local Development Commands (Ahoy)

```bash
# Environment management
ahoy up                    # Start Docker containers
ahoy down                  # Stop Docker containers
ahoy restart               # Restart containers
ahoy info                  # Show project information and URLs

# Site building and provisioning
ahoy build                 # Build site from scratch
ahoy provision             # Provision site (install/import DB)
ahoy reset                 # Reset to clean state

# Database operations
[//]: # (#;< !DB_DOWNLOAD_SOURCE_NONE)
ahoy download-db           # Download fresh database
[//]: # (#;> !DB_DOWNLOAD_SOURCE_NONE)
ahoy export-db             # Export current database
ahoy import-db             # Import database from file

# Development tools
ahoy drush [command]       # Run Drush commands
ahoy composer [command]    # Run Composer commands
ahoy phpcs                 # Run code style checks
ahoy phpcbf                # Fix code style issues
```

### Code Quality and Testing

```bash
# Linting and code standards
ahoy lint                  # Run all linting checks
ahoy lint-fix              # Fix automatically fixable issues

# Testing
ahoy test-unit             # Run PHPUnit tests
ahoy test-bdd              # Run Behat (BDD) tests
ahoy test                  # Run all tests
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
[//]: # (#;< DRUPAL_THEME)
│   ├── themes/custom/     # Custom themes
[//]: # (#;> DRUPAL_THEME)
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

## Custom Development

### Creating Custom Modules
```bash
# Generate module scaffold
ahoy drush generate:module [module_name]

# Enable custom module
ahoy drush pm:install [module_name]
```

[//]: # (#;< DRUPAL_THEME)

### Theme Development
```bash
# Navigate to custom theme
cd web/themes/custom/[theme_name]

# Install theme dependencies (if using npm/yarn)
npm install

# Build theme assets
npm run build

# Watch for changes during development
npm run watch
```

[//]: # (#;> DRUPAL_THEME)

## Database and Content

### Database Operations
```bash
[//]: # (#;< !DB_DOWNLOAD_SOURCE_NONE)
# Download latest database
ahoy download-db
[//]: # (#;> !DB_DOWNLOAD_SOURCE_NONE)

# Import database from file
ahoy import-db path/to/database.sql

# Create database snapshot
ahoy export-db-file

# Reset to fresh install
ahoy provision --override-db
```

### Content Management
- Use Configuration Management for structure (content types, fields, views)
- Use database imports for content in non-production environments
- Use migration modules for structured content imports in production

## Services Integration

[//]: # (#;< SERVICE_SOLR)

### Solr Search
```bash
# Check Solr status
ahoy drush search-api:status

# Index content
ahoy drush search-api:index

# Clear Solr index
ahoy drush search-api:clear
```

[//]: # (#;> SERVICE_SOLR)

[//]: # (#;< SERVICE_VALKEY)

### Valkey (Redis-compatible caching)
```bash
# Check cache status
ahoy drush cache:rebuild

# Clear Redis/Valkey cache
ahoy drush php-eval "\Drupal\redis\Client\ClientInterface::flushAll();"
```

[//]: # (#;> SERVICE_VALKEY)

[//]: # (#;< SERVICE_CLAMAV)

### ClamAV Virus Scanning
```bash
# Test virus scanning functionality
ahoy drush clamav:scan

# Check ClamAV status
ahoy drush clamav:status
```

[//]: # (#;> SERVICE_CLAMAV)

## Environment Configuration

### Environment Variables (.env)
Key variables for local development:
```bash
# Project identification
VORTEX_PROJECT=your_project_name

# Database configuration
DRUPAL_DATABASE_NAME=drupal
DRUPAL_DATABASE_USERNAME=drupal  
DRUPAL_DATABASE_PASSWORD=drupal

# Development settings
VORTEX_DEV_MODE=1
VORTEX_DEBUG=1
```

### Environment-Specific Settings
- **Development**: Full error reporting, development modules enabled
- **Staging**: Production-like but with debug capabilities
- **Production**: Error logging, caching enabled, development modules disabled

## Deployment

### CI/CD Pipeline
The project includes automated deployment via:

[//]: # (#;< CI_PROVIDER_GHA)
- **GitHub Actions** for CI/CD
[//]: # (#;> CI_PROVIDER_GHA)
[//]: # (#;< CI_PROVIDER_CIRCLECI)
- **CircleCI** for CI/CD
[//]: # (#;> CI_PROVIDER_CIRCLECI)

[//]: # (#;< HOSTING_LAGOON)
- **Lagoon** for hosting
[//]: # (#;> HOSTING_LAGOON)

[//]: # (#;< HOSTING_ACQUIA)
- **Acquia** for hosting
[//]: # (#;> HOSTING_ACQUIA)

[//]: # (#;< DEPLOY_TYPE_CONTAINER_REGISTRY)
- **Container Registry** deployments for containerized environments
[//]: # (#;> DEPLOY_TYPE_CONTAINER_REGISTRY)

### Manual Deployment Steps
```bash
# Build deployment artifact
./scripts/vortex/deploy.sh

[//]: # (#;< HOSTING_LAGOON)
# Deploy to Lagoon environment
./scripts/vortex/deploy-lagoon.sh
[//]: # (#;> HOSTING_LAGOON)

[//]: # (#;< HOSTING_ACQUIA)
# Deploy to Acquia environment
./scripts/vortex/deploy-acquia.sh
[//]: # (#;> HOSTING_ACQUIA)

[//]: # (#;< DEPLOY_TYPE_WEBHOOK)
# Deploy via webhook
./scripts/vortex/deploy-webhook.sh
[//]: # (#;> DEPLOY_TYPE_WEBHOOK)
```

[//]: # (#;< HOSTING_LAGOON)

### Lagoon-Specific Commands
```bash
# Login to Lagoon
lagoon login

# Check environment status
lagoon list projects

# View deployment logs
lagoon logs
```

[//]: # (#;> HOSTING_LAGOON)

[//]: # (#;< HOSTING_ACQUIA)

### Acquia-Specific Commands
```bash
# Download database from Acquia
ahoy download-db-acquia

# Copy files from Acquia
ahoy copy-files-acquia

# Deploy code to Acquia
ahoy deploy-acquia
```

[//]: # (#;> HOSTING_ACQUIA)

## Common Tasks

### Adding Dependencies
```bash
# Add Drupal modules
ahoy composer require drupal/module_name

# Add development dependencies  
ahoy composer require --dev drupal/devel

[//]: # (#;< DRUPAL_THEME)
# Add theme build tools
cd web/themes/custom/[theme] && npm install [package]
[//]: # (#;> DRUPAL_THEME)
```

[//]: # (#;< DEPS_UPDATE_PROVIDER)

### Dependency Management
Dependencies are automatically updated via RenovateBot:
- **Composer dependencies**: Updated automatically with compatibility checks
- **Node.js dependencies**: Updated in theme directories
- **Docker images**: Base image updates for containers

To manually check for updates:
```bash
ahoy composer outdated
```

[//]: # (#;> DEPS_UPDATE_PROVIDER)

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

**Permission issues:**
```bash
# Fix file permissions
ahoy fix-permissions
```

**Memory issues:**
```bash
# Increase PHP memory limit in docker-compose.yml
# Or use ahoy with more memory
ahoy drush --memory-limit=512M [command]
```

## Resources

- **Vortex Documentation**: https://vortex.drevops.com
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