# Vortex Template Maintenance Guide

> **⚠️ MAINTENANCE MODE**: This file contains guidance for **maintaining the Vortex template itself**.
> 
> For working with **Drupal projects created from this template**, see the main project guide: `../CLAUDE.md`

## Project Overview

**Vortex** is a Drupal project template by DrevOps that provides a comprehensive, production-ready Drupal development and deployment framework.

### Project Structure

```
vortex/
├── .vortex/                    # Test harness and development tools
│   ├── docs/                   # Documentation for Vortex
│   ├── installer/              # Self-contained Symfony console installer
│   ├── tests/                  # Unit and functional tests
│   └── CLAUDE.md              # This maintenance guide
└── [root files]                # The actual Drupal template
    └── CLAUDE.md               # Drupal development guide
```

**Key Principle**: Everything outside `.vortex/` is the **actual template** that gets installed for users. Everything inside `.vortex/` is the **test harness** used to test and maintain the template.

## .vortex Directory Structure

### .vortex/docs/
- Documentation published to https://vortex.drevops.com
- Contains comprehensive project documentation

### .vortex/installer/
- Self-contained Symfony console application
- Handles Vortex installation and customization
- **Fixture System**: Uses baseline + diff architecture

### .vortex/tests/
- Unit and functional tests for Vortex
- Uses both **PHPUnit** (functional workflows) and **BATS** (shell script unit tests)

## Testing Framework

### PHPUnit Tests (.vortex/tests/phpunit/)
- **Purpose**: Functional testing of Vortex user workflows
- **Scope**: Processes and commands in context of Vortex installation
- **Location**: `.vortex/tests/phpunit/`
- **Key Files**:
  - `Functional/WorkflowTest.php` - Main workflow testing
  - `Functional/FunctionalTestCase.php` - Base test case

### BATS Tests (.vortex/tests/bats/)
- **Purpose**: Unit testing of shell scripts with coverage
- **Technology**: [Bats (Bash Automated Testing System)](https://github.com/bats-core/bats-core)
- **Location**: `.vortex/tests/bats/`
- **Key Files**:
  - `provision.bats` - Tests for provision.sh script
  - `_helper.bash` - Test helper functions
  - `fixtures/` - Test fixture files

### Running Tests

```bash
cd .vortex/tests

# PHP dependencies
composer install

# Node.js dependencies (for BATS)
yarn install

# Run PHPUnit tests
./vendor/bin/phpunit

# Run BATS tests
bats bats/provision.bats

# Run with verbose output
bats --verbose-run bats/provision.bats
```

## Installer Fixture System

### Architecture
The installer uses a **baseline + diff** system for managing test fixtures:

1. **Baseline** (`_baseline/`): Complete template files
2. **Scenario Fixtures**: Diff files that modify the baseline

### Fixture Locations
```
.vortex/installer/tests/Fixtures/install/
├── _baseline/                  # Complete template files
├── services_no_clamav/         # Diff: removes ClamAV-related content
├── services_no_solr/           # Diff: removes Solr-related content  
├── services_no_valkey/         # Diff: removes Redis/Valkey content
├── services_none/              # Diff: removes all services
├── hosting_acquia/             # Diff: Acquia-specific modifications
├── hosting_lagoon/             # Diff: Lagoon-specific modifications
└── [other scenarios]/          # Various configuration scenarios
```

### Updating Fixtures

**Critical**: Use the proper fixture update mechanism:

```bash
cd .vortex/installer

# Update all fixtures
UPDATE_FIXTURES=1 composer test

# Update specific test fixtures
UPDATE_FIXTURES=1 ./vendor/bin/phpunit --filter "testInstall.*baseline"
UPDATE_FIXTURES=1 ./vendor/bin/phpunit --filter 'testInstall.*"services.*no.*clamav"'
```

**How it works**:
1. Tests run and compare actual output vs expected fixtures
2. When `UPDATE_FIXTURES=1` is set, differences automatically update fixtures
3. Baseline changes propagate to all scenario diffs
4. Each scenario maintains only its differences from baseline

### Fixture Update Process
1. **Baseline First**: Update baseline fixtures first
2. **Scenario Diffs**: Run individual scenario tests to update their diffs
3. **Validation**: Verify tests pass without UPDATE_FIXTURES flag

## Script Output Formatters

### Standard Output Formatters
Vortex uses consistent output formatting across all scripts:

```bash
# Define in each script
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }

# For provision scripts, also include:
task() { printf "    > %s\n" "${1}"; }
note() { printf "      %s\n" "${1}"; }
```

### Usage Guidelines
- **info()**: Main section headers and completion messages
- **pass()**: Success confirmations
- **fail()**: Error messages  
- **task()**: Step-by-step operations
- **note()**: Conditional messages, details, hints

### Example Usage
```bash
info "Executing example operations in non-production environment."
task "Setting site name."
task "Installing contrib modules."
note "Fresh database detected. Performing additional example operations."
info "Finished executing example operations in non-production environment."
```

## Test Maintenance Workflow

### When Updating Scripts with Output Formatters

1. **Update Main Script**: Modify the script in the template (outside .vortex/)
2. **Update BATS Tests**: Update test assertions in `.vortex/tests/bats/`
3. **Update Installer Fixtures**: Use `UPDATE_FIXTURES=1` process

### BATS Test Updates
When script output changes, update corresponding test files:

```bash
# Example: provision.bats for provision.sh changes
# Update assertions to match new formatter output:
"  ==> Executing example operations in non-production environment."
"    > Setting site name."
"    > Installing contrib modules."
"      Fresh database detected. Performing additional example operations."
```

### Common Test Commands

```bash
# From .vortex/tests/
composer install
composer lint       # Code linting
composer test       # Run all tests

# Individual test suites
./test.common.sh     # Common tests
./test.deployment.sh # Deployment tests  
./test.workflow.sh   # Workflow tests
./lint.scripts.sh    # Shell script linting
```

## Environment Variables

### Testing
- `TEST_VORTEX_DEBUG=1` - Enable debug output
- `TEST_NODE_INDEX` - CI runner index for parallel execution
- `VORTEX_DEV_TEST_COVERAGE_DIR` - Coverage output directory
- `UPDATE_FIXTURES=1` - Enable fixture updates during tests

### Development
- `VORTEX_DEBUG=1` - Enable debug mode in scripts

## Conditional Token System

### Overview
Vortex uses conditional tokens to strip out content from template files based on user selections during installation. This allows the same template to generate customized projects with only relevant features.

### Token Patterns

**Markdown files** (like `README.dist.md`, `CLAUDE.md`):
```markdown
[//]: # (#;< TOKEN_NAME)
Content that gets removed if user doesn't select this feature
[//]: # (#;> TOKEN_NAME)
```

**Shell/YAML files** (like `docker-compose.yml`, shell scripts):
```bash
#;< TOKEN_NAME
Content that gets removed if user doesn't select this feature
#;> TOKEN_NAME
```

### Available Tokens

**Theme-related**:
- `DRUPAL_THEME` - Custom theme functionality

**Services**:
- `SERVICE_CLAMAV` - ClamAV virus scanning
- `SERVICE_SOLR` - Solr search engine
- `SERVICE_VALKEY` - Valkey/Redis caching

**CI Providers**:
- `CI_PROVIDER_GHA` - GitHub Actions
- `CI_PROVIDER_CIRCLECI` - CircleCI
- `CI_PROVIDER_ANY` - Any CI provider selected

**Hosting Providers**:
- `HOSTING_LAGOON` - Lagoon hosting
- `HOSTING_ACQUIA` - Acquia hosting

**Deployment Types**:
- `DEPLOY_TYPE_CONTAINER_REGISTRY` - Container registry deployments
- `DEPLOY_TYPE_WEBHOOK` - Webhook deployments
- `DEPLOY_TYPE_ARTIFACT` - Artifact deployments

**Dependencies**:
- `DEPS_UPDATE_PROVIDER` - Automated dependency updates (RenovateBot)

**Database**:
- `!DB_DOWNLOAD_SOURCE_NONE` - Negated token, removes content when NO database download source is selected

**Documentation**:
- `DOCS_ONBOARDING` - Onboarding documentation sections

**Provisioning**:
- `!PROVISION_TYPE_PROFILE` - Negated token for non-profile provision types

### Token Implementation Locations

**Handler Classes** (`.vortex/installer/src/Prompts/Handlers/`):
- `CiProvider.php` - Defines CI_PROVIDER_* tokens
- `HostingProvider.php` - Defines HOSTING_* tokens  
- `Services.php` - Defines SERVICE_* tokens
- `Theme.php` - Defines DRUPAL_THEME token
- `DependencyUpdatesProvider.php` - Defines DEPS_UPDATE_PROVIDER token

**Token Processing** (`.vortex/installer/src/Prompts/Handlers/Internal.php:29`):
```php
// Remove all conditional tokens during installation
File::removeTokenInDir($this->tmpDir);
```

### Using Conditional Tokens

When creating or updating template files:

1. **Identify optional features** - What content should only appear for certain configurations?
2. **Wrap with appropriate tokens** - Use the token patterns above
3. **Test with installer** - Verify tokens are properly removed/kept based on user selections
4. **Update both root and fixture files** - Ensure installer tests reflect token usage

### Example Usage in Template Files

**Root CLAUDE.md** - Wrapping theme-specific content:
```markdown
[//]: # (#;< DRUPAL_THEME)

### Theme Development
Commands and workflows for custom theme development...

[//]: # (#;> DRUPAL_THEME)
```

**docker-compose.yml** - Service-specific containers:
```yaml
#;< SERVICE_SOLR
  solr:
    image: solr:8
#;> SERVICE_SOLR
```

### Token Discovery for New Features

When adding new conditional features:

1. **Check existing handlers** in `.vortex/installer/src/Prompts/Handlers/`
2. **Look at fixture directories** in `.vortex/installer/tests/Fixtures/install/`
3. **Examine baseline vs scenario diffs** to understand token usage patterns
4. **Follow existing token naming conventions** (UPPERCASE_UNDERSCORE format)

### Token Testing

Conditional tokens are tested through the installer fixture system:
- **Baseline fixtures** contain all possible content
- **Scenario fixtures** show what gets removed for specific configurations
- Use `UPDATE_FIXTURES=1` mechanism to regenerate after token changes

## Key Directories and Files

### Template Structure (Outside .vortex/)
```
├── scripts/
│   ├── vortex/                 # Core Vortex scripts
│   └── custom/                 # Custom project scripts
│       └── provision-10-example.sh  # Example provision script
├── tests/
│   ├── behat/                  # Behat tests for the template
│   └── phpunit/                # PHPUnit tests for the template
├── config/                     # Drupal configuration
├── web/                        # Drupal webroot
└── [other template files]
```

### Test Harness (.vortex/)
```
├── docs/                       # Vortex documentation
├── installer/
│   ├── src/                    # Installer source code
│   ├── tests/Fixtures/         # Installation test fixtures
│   └── tests/Functional/       # Installer functional tests
└── tests/
    ├── bats/                   # Shell script unit tests
    └── phpunit/                # Workflow functional tests
```

## Maintenance Tips

### Fixture Updates Can Be Finicky
- The `UPDATE_FIXTURES=1` mechanism can have defects
- Try updating baseline first, then individual scenarios
- Use filtered test runs for specific scenarios
- Be patient - full test suite can take several minutes

### Script Changes Require Multi-Level Updates
1. **Main script** (template level)
2. **BATS test assertions** (unit test level)  
3. **Installer fixtures** (integration test level)

### Output Formatter Consistency
- Always use the standard formatter functions
- Maintain consistent output patterns across all scripts
- Test changes with both BATS and installer fixture tests

## Troubleshooting

### Fixture Update Issues
```bash
# Try baseline first
UPDATE_FIXTURES=1 ./vendor/bin/phpunit --filter "testInstall.*baseline"

# Then individual scenarios
UPDATE_FIXTURES=1 ./vendor/bin/phpunit --filter 'testInstall.*"scenario_name"'

# Check for test timeouts - increase if needed
./vendor/bin/phpunit --timeout=600
```

### Test Failures
- Check BATS test output formatting matches script changes
- Verify installer fixtures are properly updated
- Ensure output formatters are defined in all scripts

### Performance
- BATS tests are fast (unit level)
- PHPUnit workflow tests are slower (integration level)
- Installer tests are slowest (full installation simulation)

## Resources

- **Documentation**: `.vortex/docs/` and https://vortex.drevops.com
- **BATS Documentation**: https://github.com/bats-core/bats-core
- **Issue Tracking**: https://github.com/drevops/vortex/issues

---

*This knowledge base should be updated whenever significant changes are made to the Vortex testing or maintenance procedures.*