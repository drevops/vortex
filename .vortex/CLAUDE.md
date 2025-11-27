# Vortex Template Maintenance Guide

> **⚠️ MAINTENANCE MODE**: This file contains guidance for **maintaining the Vortex template itself**.
>
> For working with **Drupal projects created from this template**, see the main project guide: `../CLAUDE.md`

## Project Overview

**Vortex** is a Drupal project template by DrevOps that provides a comprehensive, production-ready Drupal development and deployment framework.

### Project Structure

```text
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

The `.vortex/` directory contains **three distinct subsystems**, each with its own purpose and technology stack:

### 1. .vortex/docs/ - Documentation Website

**Purpose**: Public-facing documentation website for Vortex users

**Technology Stack**:

- **Docusaurus** - Static site generator with React
- **MDX** - Markdown with React components
- **Jest** - Unit testing framework
- **ESLint/Prettier** - Code quality tools
- **cspell** - American English spellcheck validation

**Key Features**:

- Interactive documentation with custom React components
- Published to https://www.vortextemplate.com
- Comprehensive testing (unit tests, spellcheck, linting)
- Multi-format content system with enhanced UX

**Commands** (from `.vortex/docs/`):

```bash
yarn install    # Install dependencies
yarn start      # Development server
yarn build      # Production build
yarn test       # Run all tests
yarn spellcheck # Validate spelling
```

### 2. .vortex/installer/ - Template Installer

**Purpose**: Self-contained installation system that customizes the Vortex template based on user selections

**Technology Stack**:

- **Symfony Console** - Command-line application framework
- **PHP** - Core programming language
- **PHPUnit** - Testing framework for installer logic

**Key Features**:

- Interactive installation wizard
- Conditional token system for template customization
- Baseline + diff fixture architecture for testing
- Handles all user prompts and template modifications

**Architecture**:

- `src/` - Installer source code (handlers, prompts, utilities)
- `tests/Fixtures/` - Test fixtures with baseline + scenario diffs
- `tests/Functional/` - PHPUnit tests for installation scenarios

**Commands** (from `.vortex/installer/`):

```bash
composer install                    # Install dependencies
./vendor/bin/phpunit               # Run installer tests
UPDATE_FIXTURES=1 composer test    # Update test fixtures
```

### 3. .vortex/tests/ - Template Testing Harness

**Purpose**: Comprehensive testing of the Vortex template itself through functional workflows

**Technology Stack**:

- **PHPUnit** - Functional testing of complete Drupal project workflows
- **BATS** - Unit testing of individual shell scripts
- **Bash** - Shell script testing and execution

**Key Features**:

- End-to-end workflow testing (build, provision, deploy)
- Shell script unit testing with mocking capabilities
- Real Docker container testing environment
- Coverage reporting and performance testing

**Architecture**:

- `phpunit/` - Functional tests for complete workflows
- `bats/` - Unit tests for individual shell scripts
- `bats/fixtures/` - Test fixtures and mock data
- `manual/` - Manual integration tests with real external services

**Commands** (from `.vortex/`):

```bash
ahoy install                           # Install all dependencies
ahoy test-bats -- tests/bats/        # Run BATS shell script tests
cd tests && ./vendor/bin/phpunit      # Run PHPUnit workflow tests
```

## Testing Architecture Overview

Vortex uses **four independent testing systems**, each serving different parts of the codebase:

### 1. Documentation Tests (.vortex/docs/)

**Scope**: Testing the documentation website components and content

**Technology**: Jest + React Testing Library + cspell

**What it Tests**:

- React component functionality and interactions
- MDX content rendering and navigation
- American English spelling consistency
- Documentation build processes

**Test Types**:

- **Unit tests**: Component behavior (`tests/unit/`)
- **Spellcheck**: Content validation (`cspell.json`)
- **Coverage reporting**: Multiple formats (text, lcov, HTML, Cobertura)

### 2. Installer Tests (.vortex/installer/)

**Scope**: Testing the template installation and customization logic

**Technology**: PHPUnit + Fixture System

**What it Tests**:

- User prompt handling and validation
- Template file modifications and token replacement
- Installation scenario outcomes
- Baseline vs customized template differences

**Test Types**:

- **Functional tests**: Complete installation scenarios
- **Handler tests**: Individual prompt and modification logic
- **Fixture tests**: Expected vs actual template output

### 3. Template Tests (.vortex/tests/)

**Scope**: Testing the actual Drupal template functionality

**Technology**: PHPUnit + BATS

**What it Tests**:

- Complete Drupal project workflows (build, provision, deploy)
- Individual shell script functionality
- Docker container interactions
- Real-world usage scenarios

**Test Types**:

- **PHPUnit Functional**: End-to-end workflow testing
- **BATS Unit**: Individual shell script testing with mocking

### 4. Manual Integration Tests (.vortex/tests/manual/)

**Scope**: Manual verification of notification integrations with real external services

**Technology**: Bash scripts + Real service APIs (Slack, JIRA, etc.)

**What it Tests**:

- Actual notification message formatting in live services
- Real API authentication and authorization
- Service-specific rendering (Slack rich attachments, JIRA ADF)
- End-to-end integration workflows (comment posting, issue transitions)

**Test Types**:

- **Manual Slack Tests**: Branch and PR deployment notifications to real Slack channels
- **Manual JIRA Tests**: Comment creation, transitions, and assignments on real JIRA issues
- **Authentication Verification**: API token validation and permission checks

**Key Difference**: These tests use REAL services (not mocks) and require manual verification of output, complementing automated tests by validating actual service integration and message appearance.

## Template BATS Testing System (.vortex/tests/bats/)

### Overview

**BATS (Bash Automated Testing System)** provides unit testing for individual shell scripts with sophisticated mocking and assertion capabilities.

**Key Files**:

- `provision.bats` - Tests for provision.sh script
- `_helper.bash` - Test helper functions
- `fixtures/` - Test fixture files
- `unit/` - Individual script unit tests

### BATS Helpers System

The BATS tests use a sophisticated helper system located in `node_modules/bats-helpers/src/steps.bash` that provides:

**Step Types**:

1. **Command Mocking**: `@<command> [<args>] # <mock_status> [ # <mock_output> ]`
   - Mocks shell commands with specific exit codes and output
   - Example: `"@drush -y status --field=drupal-version # mocked_core_version"`

2. **Positive Assertions**: `"<substring>"`
   - Asserts that output CONTAINS the specified substring
   - Example: `"Fresh database detected. Performing additional example operations."`

3. **Negative Assertions**: `"- <substring>"`
   - Asserts that output does NOT contain the specified substring
   - Starts with '- ' (minus followed by space)
   - Example: `"-      Existing database detected. Performing additional example operations."`

**Usage Pattern**:

```bash
declare -a STEPS=(
  "@drush -y status # 0 # success"          # Mock command
  "Expected output string"                   # Should be present
  "-      Unwanted output string"            # Should NOT be present
)

mocks="$(run_steps "setup")"    # Setup phase
# ... run code under test ...
run_steps "assert" "${mocks}"   # Assert phase
```

## Running Tests by System

### 1. Documentation Tests (.vortex/docs/)

**Purpose**: Test documentation website functionality

```bash
cd .vortex/docs

# Install dependencies
yarn install

# Development workflow
yarn start              # Start dev server
yarn build              # Build documentation

# Testing workflow
yarn test               # Run all tests
yarn test:coverage      # Run with coverage
yarn test:watch         # Watch mode for development

# Quality assurance
yarn spellcheck         # American English validation
yarn lint               # Code quality checks
yarn lint-fix           # Auto-fix code quality issues
```

### 2. Installer Tests (.vortex/installer/)

**Purpose**: Test template installation scenarios

```bash
cd .vortex/installer

# Install dependencies
composer install

# Run all installer tests
./vendor/bin/phpunit

# Update test fixtures
UPDATE_FIXTURES=1 composer test

# Run specific scenarios
UPDATE_FIXTURES=1 ./vendor/bin/phpunit --filter "testInstall.*baseline"
UPDATE_FIXTURES=1 ./vendor/bin/phpunit --filter 'testInstall.*"services.*no.*clamav"'

# Run handler-specific tests
./vendor/bin/phpunit --filter "Handlers\\\\"
./vendor/bin/phpunit --filter "ServicesInstallTest"
```

### 3. Template Tests (.vortex/tests/)

**Purpose**: Test the actual Drupal template functionality

```bash
cd .vortex

# Install all dependencies (PHP, Node.js, BATS)
ahoy install

# PHPUnit functional tests (workflow testing)
cd tests && ./vendor/bin/phpunit

# BATS unit tests (shell script testing)
ahoy test-bats -- tests/bats/unit/notify.bats          # Specific test file
ahoy test-bats -- tests/bats/provision.bats            # Another test file
ahoy test-bats -- --verbose-run tests/bats/unit/       # Verbose output for directory
ahoy test-bats -- tests/bats/                          # All BATS tests

# Alternative: direct bats command (after ahoy install)
bats tests/bats/unit/notify.bats

# Individual test suites
./test.common.sh        # Common tests
./test.deployment.sh    # Deployment tests
./test.workflow.sh      # Workflow tests
./lint.scripts.sh       # Shell script linting
```

### 4. Manual Integration Tests (.vortex/tests/manual/)

**Purpose**: Manually verify notification integrations with real external services

**Technology**: Bash scripts that invoke actual notification endpoints

**What to Test**:

- Real Slack webhook message formatting and delivery
- JIRA comment creation, issue transitions, and assignments
- Actual service API authentication and responses

**Available Scripts**:

```bash
# Slack notifications (requires SLACK_WEBHOOK_URL)
export SLACK_WEBHOOK_URL="your-webhook-url"
.vortex/tests/manual/try-slack-notification.sh branch    # Test branch deployment
.vortex/tests/manual/try-slack-notification.sh pr        # Test PR deployment

# JIRA notifications (requires JIRA_TOKEN)
export JIRA_TOKEN="your-api-token"
export JIRA_USER="your-email@example.com"                 # Optional (default: alex@drevops.com)
export JIRA_ENDPOINT="https://your-domain.atlassian.net"  # Optional (default: https://drevops.atlassian.net)
export JIRA_ISSUE="PROJECT-123"                           # Optional (default: DEMO-2)
.vortex/tests/manual/try-jira-notification.sh branch      # Test branch deployment
.vortex/tests/manual/try-jira-notification.sh pr          # Test PR deployment

# JIRA authentication test (requires JIRA_TOKEN)
export JIRA_TOKEN="your-api-token"
.vortex/tests/manual/try-jira-auth.sh                     # Verify JIRA API access
```

**When to Use**:

- During notification script refactoring to verify real output formatting
- Before committing major notification changes
- To debug integration issues with external services
- After API updates to test compatibility

**Important Notes**:

- ⚠️ These send REAL notifications to live services (use test channels/projects)
- 🔐 Never commit credentials to version control
- 📚 See `.vortex/tests/manual/README.md` for comprehensive documentation
- 🧪 Manual tests complement automated BATS tests (real services vs mocks)

## Installer Fixture System

### Architecture

The installer uses a **baseline + diff** system for managing test fixtures:

1. **Baseline** (`_baseline/`): Complete template files
2. **Scenario Fixtures**: Diff files that modify the baseline

### Fixture Locations

```text
.vortex/installer/tests/Fixtures/install/
├── _baseline/                  # Complete template files
├── services_no_clamav/         # Diff: removes ClamAV-related content
├── services_no_solr/           # Diff: removes Solr-related content
├── services_no_redis/          # Diff: removes Redis content
├── services_none/              # Diff: removes all services
├── hosting_acquia/             # Diff: Acquia-specific modifications
├── hosting_lagoon/             # Diff: Lagoon-specific modifications
└── [other scenarios]/          # Various configuration scenarios
```

### Updating Fixtures

**CRITICAL - Use the Unified Ahoy Command**:

The correct way to update fixtures is to use the unified `ahoy update-fixtures` command from the `.vortex` directory:

```bash
cd .vortex

# This is the CORRECT way to update all fixtures
ahoy update-fixtures
```

**What this command does**:

- Updates template test fixtures in `tests/` directory
- Updates installer test fixtures in `installer/` directory
- Handles baseline fixtures first
- Updates all scenario-specific fixtures
- Runs tests twice to properly handle fixture updates (first run may fail, second should pass)

**DO NOT manually run `UPDATE_FIXTURES=1` commands** - the `ahoy update-fixtures` command handles everything automatically.

### Alternative: Manual Fixture Updates (Advanced)

For specific fixture updates or debugging, you can use manual commands:

```bash
cd .vortex/installer

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

1. **Use ahoy update-fixtures**: This is the standard and recommended approach
2. **Alternative - Baseline First**: Update baseline fixtures manually if needed
3. **Alternative - Scenario Diffs**: Run individual scenario tests to update specific diffs
4. **Validation**: Verify tests pass without UPDATE_FIXTURES flag

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
3. **Update Installer Fixtures**: Run `ahoy update-fixtures` from `.vortex/` directory

### Provision Script BATS Test Logic

The provision example script (`scripts/custom/provision-10-example.sh`) uses conditional logic:

```bash
if [ "${VORTEX_PROVISION_OVERRIDE_DB:-0}" = "1" ]; then
  note "Fresh database detected. Performing additional example operations."
else
  note "Existing database detected. Performing additional example operations."
fi
```

**BATS Test Expectations**:

- **Fresh database scenarios**: Should have "Fresh database detected" and NOT have "Existing database detected"
  - `"Provision: DB; no site"`
  - `"Provision: DB; existing site; overwrite"`
  - `"Provision: DB; no site; configs"`
  - `"Provision: profile; no site"`
  - `"Provision: profile; existing site; overwrite"`

- **Existing database scenarios**: Should have "Existing database detected" and NOT have "Fresh database detected"
  - `"Provision: DB; existing site"`
  - `"Provision: profile; existing site"`

**Test Pattern**:

```bash
# Fresh database tests
"      Fresh database detected. Performing additional example operations."
"-      Existing database detected. Performing additional example operations."

# Existing database tests
"-       Fresh database detected. Performing additional example operations."
"Existing database detected. Performing additional example operations."
```

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

## Cross-System Test Dependencies

**Important**: Each system has independent dependencies and must be set up separately:

1. **Documentation** (`.vortex/docs/`): Requires Node.js/Yarn
2. **Installer** (`.vortex/installer/`): Requires PHP/Composer
3. **Template** (`.vortex/tests/`): Requires PHP/Composer + Node.js + BATS

**Full Setup** (from `.vortex/`):

```bash
ahoy install        # Installs dependencies for all three systems
```

## Unified Testing Commands

For convenience, you can run tests across all systems:

```bash
# From .vortex/ root
ahoy install        # Install all dependencies (docs, installer, template)
ahoy lint           # Code linting across all systems
ahoy test           # Run all template tests

# Individual system commands
cd docs && yarn test                    # Documentation tests only
cd installer && composer test           # Installer tests only
cd tests && ./vendor/bin/phpunit       # Template PHPUnit tests only
ahoy test-bats -- tests/bats/          # Template BATS tests only
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
- `SERVICE_REDIS` - Redis caching

**CI Providers**:

- `CI_PROVIDER_GHA` - GitHub Actions
- `CI_PROVIDER_CIRCLECI` - CircleCI
- `CI_PROVIDER_ANY` - Any CI provider selected

**Hosting Providers**:

- `HOSTING_LAGOON` - Lagoon hosting
- `HOSTING_ACQUIA` - Acquia hosting

**Deployment Types**:

- `DEPLOY_TYPES_CONTAINER_REGISTRY` - Container registry deployments
- `DEPLOY_TYPES_WEBHOOK` - Webhook deployments
- `DEPLOY_TYPES_ARTIFACT` - Artifact deployments

**Dependencies**:

- `DEPS_UPDATE_PROVIDER` - Automated dependency updates (RenovateBot)

**Database**:

- `!DB_DOWNLOAD_SOURCE_NONE` - Negated token, removes content when NO database download source is selected

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

## Directory Structure Summary

### Template Structure (Outside .vortex/) - The Actual Drupal Project

```text
├── scripts/
│   ├── vortex/                 # Core Vortex scripts
│   └── custom/                 # Custom project scripts
│       └── provision-10-example.sh  # Example provision script
├── tests/
│   ├── behat/                  # Behat tests for the template
│   └── phpunit/                # PHPUnit tests for the template
├── config/                     # Drupal configuration
├── web/                        # Drupal webroot
├── docker-compose.yml          # Docker development environment
├── .ahoy.yml                   # Ahoy task definitions
├── composer.json               # PHP dependencies for Drupal project
└── [other template files]      # Complete Drupal project structure
```

### Test Harness (.vortex/) - Three Separate Systems

**Critical Understanding**: The `.vortex/` directory contains three **completely independent subsystems**:

1. **`.vortex/docs/`** - Docusaurus website (Node.js/React)
2. **`.vortex/installer/`** - PHP installer application (Symfony Console)
3. **`.vortex/tests/`** - Template testing harness (PHPUnit + BATS)

Each system:

- Has its own dependencies and package managers
- Serves a different purpose in the Vortex ecosystem
- Can be developed and tested independently
- Has its own command structure and workflows

### Test Harness (.vortex/) - Three Independent Systems

```text
├── docs/                       # 1. DOCUMENTATION WEBSITE
│   ├── src/components/         # React components (VerticalTabs, etc.)
│   ├── tests/unit/             # Jest tests for React components
│   ├── content/                # MDX documentation content
│   ├── jest.config.js          # Jest test configuration
│   ├── cspell.json             # Spellcheck configuration
│   ├── package.json            # Node.js dependencies
│   └── yarn.lock               # Lockfile for docs dependencies
│
├── installer/                  # 2. TEMPLATE INSTALLER
│   ├── src/                    # Installer source code (PHP)
│   │   ├── Prompts/Handlers/   # Installation prompt handlers
│   │   └── Utilities/          # Helper classes and utilities
│   ├── tests/Fixtures/         # Installation test fixtures
│   │   ├── _baseline/          # Base template files
│   │   └── [scenarios]/        # Scenario-specific diffs
│   ├── tests/Functional/       # PHPUnit installer tests
│   ├── composer.json           # PHP dependencies for installer
│   └── installer.php           # Main installer entry point
│
└── tests/                      # 3. TEMPLATE TESTING
    ├── bats/                   # Shell script unit tests
    │   ├── unit/               # Individual script tests
    │   ├── fixtures/           # Test fixtures for BATS
    │   └── provision.bats      # Main provision script tests
    ├── phpunit/                # Workflow functional tests
    │   ├── Functional/         # End-to-end workflow tests
    │   └── Traits/             # Shared test functionality
    ├── manual/                 # Manual integration tests
    │   ├── try-slack-notification.sh   # Slack webhook testing
    │   ├── try-jira-notification.sh    # JIRA API testing
    │   ├── try-jira-auth.sh            # JIRA authentication testing
    │   └── README.md                   # Manual testing documentation
    ├── composer.json           # PHP dependencies for template tests
    └── [test scripts]          # Individual test executables
```

## System-Specific Maintenance Guidelines

### 1. Documentation System (.vortex/docs/)

**Common Tasks**:

- Update React components and test with Jest
- Maintain American English spelling consistency
- Keep MDX content synchronized with code changes
- Ensure responsive design across device types

**Best Practices**:

- Run spellcheck before committing content changes
- Test interactive components in both development and production builds
- Maintain consistent terminology across all documentation
- **Use sentence case for all headings** - only the first letter is capitalized, except for proper nouns (Vortex, GitHub, Drupal, Docker Compose, CircleCI, etc.) and acronyms (CI/CD, SSH, API, BDD, PHPUnit, etc.)

### 2. Installer System (.vortex/installer/)

**Fixture Updates**:

- **Use `ahoy update-fixtures`** from `.vortex/` directory - this is the standard approach
- The unified command updates all fixtures automatically
- Runs tests twice to handle fixture updates properly (first run may fail, second should pass)
- Be patient - full test suite can take several minutes
- For debugging specific scenarios, manual `UPDATE_FIXTURES=1` commands can be used

**Handler Development**:

- Queue operations in handlers, execute centrally in PromptManager
- Use wrapper methods for common file operations
- Test each handler type (token removal, string replacement, custom transformation)
- Maintain execution order dependencies

### 3. Template Testing System (.vortex/tests/)

**Script Changes Require Multi-Level Updates**:

1. **Main script** (template level)
2. **BATS test assertions** (unit test level)
3. **Installer fixtures** (integration test level)

**Output Formatter Consistency**:

- Always use the standard formatter functions
- Maintain consistent output patterns across all scripts
- Test changes with both BATS and installer fixture tests

**PHPUnit Helper Usage**:

- Use `cmd()` for successful commands with output assertions
- Use `cmdFail()` for expected failures
- Follow prefix rules: all-or-nothing for output assertions
- Prefer named arguments for complex parameters

**Manual Integration Tests**:

- Use manual scripts in `.vortex/tests/manual/` when refactoring notification integrations
- Always test with real services before updating automated BATS tests
- Verify message formatting in actual Slack/JIRA UI, not just script output
- Keep credentials in environment variables, never commit to version control
- Use test channels/projects to avoid cluttering production systems

## System-Specific Troubleshooting

### 1. Documentation Issues (.vortex/docs/)

**Common Problems**:

```bash
# Build failures
yarn build --verbose           # Check for detailed build errors

# Spellcheck failures
yarn spellcheck                # Review American English violations
npx cspell "content/**/*.md"   # Check specific files

# Component test failures
yarn test --verbose            # Detailed Jest output
yarn test --updateSnapshot     # Update component snapshots
```

### 2. Installer Issues (.vortex/installer/)

**Fixture Update Issues**:

```bash
# RECOMMENDED: Use the unified command
cd .vortex
ahoy update-fixtures

# ALTERNATIVE: Manual updates for specific scenarios
cd .vortex/installer
UPDATE_FIXTURES=1 ./vendor/bin/phpunit --filter "testInstall.*baseline"
UPDATE_FIXTURES=1 ./vendor/bin/phpunit --filter 'testInstall.*"scenario_name"'

# Check for test timeouts - increase if needed
./vendor/bin/phpunit --timeout=600
```

**Handler Development Issues**:

- Verify execution order (handlers queue, PromptManager executes)
- Check namespace imports for `ExtendedSplFileInfo`
- Ensure complex logic is preserved in callback signatures

### 3. Template Testing Issues (.vortex/tests/)

**BATS Test Failures**:

- Check output formatting matches script changes
- Verify mock commands and assertions align
- Ensure test fixtures are updated after script modifications

**PHPUnit Workflow Failures**:

- Verify Docker containers are running properly
- Check that cmd() prefix rules are followed correctly
- Ensure test environments are properly isolated

**Performance Characteristics**:

- **BATS tests**: Fast (unit level, ~seconds)
- **PHPUnit workflow tests**: Slower (integration level, ~minutes)
- **Installer tests**: Slowest (full installation simulation, ~minutes)

### 4. Manual Integration Test Issues (.vortex/tests/manual/)

**Missing Credentials**:

```bash
# Verify environment variables are set
echo $SLACK_WEBHOOK_URL
echo $JIRA_TOKEN

# If not set, export them
export SLACK_WEBHOOK_URL="your-webhook-url"
export JIRA_TOKEN="your-api-token"
```

**Authentication Failures**:

- Use try-jira-auth.sh to diagnose JIRA authentication issues
- Verify API token has required permissions (comment, transition, assign)
- Check endpoint URL format (https://domain.atlassian.net)
- Ensure token hasn't expired or been revoked

**Message Format Issues**:

- Compare actual output in Slack/JIRA with expected format
- Check for JSON encoding issues in JIRA ADF format
- Verify URL formatting (links vs inline cards)
- Test both branch and PR scenarios to catch conditional logic issues

**Integration Failures**:

- Confirm services are accessible (not blocked by firewall/VPN)
- Verify webhook URLs are valid and not revoked
- Check issue keys exist and are accessible by user
- Review service-specific rate limits or quotas

## Shell Script Development Patterns

### Script Structure Best Practices

Vortex shell scripts follow a consistent structure for maintainability and clarity:

**Standard Script Structure**:

1. **Shebang and header comments** - Script purpose and requirements
2. **Environment loading** - Load `.env` and `.env.local` files
3. **Shell options** - Set `set -eu` and optional debug mode
4. **Variable declarations** - All variables with defaults in one section
5. **Helper functions** - Output formatters and utility functions
6. **Pre-flight checks** - Verify required commands are available
7. **Argument parsing** - Parse command-line arguments (modifies variables)
8. **Main execution** - Core script logic

**Example Structure**:

```bash
#!/usr/bin/env bash
##
# Script purpose.
#
# shellcheck disable=SC1090,SC1091

# Environment loading.
t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "${t}" && rm "${t}" && unset t

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# Variable declarations with defaults.
VARIABLE_ONE="${VARIABLE_ONE:-default_value}"
VARIABLE_TWO="${VARIABLE_TWO:-0}"

# ------------------------------------------------------------------------------

# Helper functions.
info() { printf "[INFO] %s\n" "${1}"; }
fail() { printf "[FAIL] %s\n" "${1}"; }

# Pre-flight checks.
for cmd in required_cmd1 required_cmd2; do command -v "${cmd}" >/dev/null || {
  fail "Command ${cmd} is not available"
  exit 1
}; done

# Parse arguments.
for arg in "$@"; do
  if [ "${arg}" = "--flag" ]; then
    VARIABLE_TWO=1
  else
    VARIABLE_ONE="${arg}"
  fi
done

# ------------------------------------------------------------------------------

# Main execution.
# ... script logic here ...
```

**Key Principles**:

- **Keep variable section clean**: Declare all variables with defaults together, don't mix with argument parsing
- **Separate concerns**: Variable declarations → Pre-flight checks → Argument parsing → Execution
- **Consistent ordering**: Maintain the same section order across all scripts
- **Clear boundaries**: Use separator lines (`# ----...`) between major sections

### Script Development Workflow

When creating or modifying shell scripts, follow this workflow to ensure code quality and documentation consistency:

1. **Create/Modify Script**: Make changes to the script in `scripts/vortex/` or `scripts/custom/`
2. **Lint Scripts**: Run `ahoy lint-scripts` from the `.vortex/` directory to check shell script quality
3. **Update Documentation**: Run `ahoy update-docs` from the `.vortex/` directory to regenerate documentation from script variables
4. **Lint Documentation**: Run `ahoy lint-docs` from the `.vortex/` directory to ensure documentation formatting is correct
5. **Lint Markdown Files**: Run `ahoy lint-markdown` from the `.vortex/` directory to check all markdown files for formatting issues

**Example Workflow**:

```bash
# After modifying a script, navigate to .vortex directory
cd .vortex

# Run the quality checks
ahoy lint-scripts        # Lint all shell scripts
ahoy update-docs         # Update documentation from script variables
ahoy lint-docs           # Lint documentation files
ahoy lint-markdown       # Lint markdown files (or use ahoy lint-markdown-fix to auto-fix)
```

**Important Notes**:

- **Commands must be run from `.vortex/` directory**: All commands (`lint-scripts`, `update-docs`, `lint-docs`, `lint-markdown`) must be executed from the `.vortex/` directory
- **Scripts must be linted** before committing to ensure they follow shell script best practices
- **Documentation must be updated** whenever script variables or structure changes
- **Documentation must be linted** to maintain consistent formatting across all docs
- **Markdown auto-fix available**: Use `ahoy lint-markdown-fix` to automatically fix markdown formatting issues

### BATS Testing with Interactive Scripts

**Critical Understanding**: BATS tests mock shell commands - they don't actually execute them.

When testing scripts that would normally require user interaction (e.g., running with `--no-interaction` flag omitted):

**How Mocking Works**:

```bash
# In BATS test:
create_global_command_wrapper "php"

# Later in test:
"@php installer.php --uri=https://example.com # 0"
```

This creates a mock that:

- Intercepts calls to `php` command
- Returns immediately with exit code 0
- Never actually executes the PHP script
- **Does not hang waiting for user input**

**Implication**: Scripts with interactive modes can be safely tested without hanging, because the test mocks prevent actual execution.

### Simplicity Over Complexity

**Lesson**: When implementing new features, start with the simplest solution that works.

**Example from `update-vortex.sh`**:

- ✅ **Simple**: Two conditional branches with explicit commands
- ❌ **Complex**: Array building, argument iteration, dynamic construction

**Why Simpler is Better**:

- Easier to read and understand
- More predictable behavior
- Better test alignment (argument order is explicit)
- Fewer edge cases to handle
- Faster debugging when issues occur

**When to Use Complexity**: Only when you need to handle many permutations or truly dynamic argument sets. For simple binary choices (interactive vs non-interactive), conditional execution is clearer.

## Installer Development Patterns

### Code Refactoring and Performance Optimization

**Batch Processing Pattern**: The installer uses batch processing for file operations to improve performance. All file modifications are queued and executed in a single pass through the directory tree.

**Key Pattern**:

```php
// Before: Individual operations (slow)
File::replaceContentInFile($file, 'old', 'new');
File::removeTokenInDir($dir, 'TOKEN');

// After: Batched operations (fast)
File::replaceContentAsync('old', 'new');
File::replaceTokenAsync('TOKEN');
// Execute all at once: File::runTaskDirectory($dir);
```

### Handler Architecture Best Practices

**File Utility Wrapper Methods**: Use static wrapper methods to reduce code duplication across handlers:

```php
// Unified API for content replacement
File::replaceContentAsync([
    'search1' => 'replace1',
    'search2' => 'replace2',
]);

// Or single replacement
File::replaceContentAsync('search', 'replace');

// Or custom transformation
File::replaceContentAsync(function(string $content, ExtendedSplFileInfo $file): string {
    // Complex logic here
    return $content;
});
```

**Central Execution**: All handlers should only QUEUE operations, not execute them. Execution happens centrally in `PromptManager.php`:

```php
// In handlers: Queue operations only
File::replaceContentAsync('old', 'new');
File::replaceTokenAsync('TOKEN');

// In PromptManager: Execute all queued operations once
File::runTaskDirectory($this->config->get(Config::TMP));
```

### Refactoring Workflow

1. **Create Wrappers**: Add static methods to `File` class for common patterns
1. **Replace Usage**: Update handlers to use wrapper methods
1. **Test Systematically**:
   - Run baseline test first to verify core functionality
   - Run individual test scenarios to catch edge cases
   - Use `UPDATE_FIXTURES=1` to regenerate expected outputs when needed

### Performance Insights

**Critical Success Factors**:

- Maintain execution order (handlers queue, PromptManager executes)
- Preserve complex logic in callbacks for edge cases (e.g., empty line processing exclusions)
- Test each handler type (token removal, string replacement, custom transformation)

### Common Pitfalls

1. **Execution in Handlers**: Don't call `File::runTaskDirectory()` in individual handlers
2. **Import Namespace Issues**: Use `AlexSkrypnyk\File\Internal\ExtendedSplFileInfo` not the root namespace
3. **Complex Logic Loss**: Don't oversimplify complex transformations - use callback signature when needed
4. **Test Order Dependencies**: Some tests depend on specific file/directory states from previous handlers

## Installer Test Architecture

### Handler-Specific Test Classes

The installer tests have been refactored to use a modular, handler-focused architecture that improves maintainability and test execution flexibility.

**Abstract Base Class**: `AbstractInstallTestCase` provides shared test logic for all installer test scenarios, including:

- Common setup and teardown procedures
- Core `testInstall()` method with data provider integration
- Fixture management and assertion helpers
- Version replacement utilities

**Handler Test Organization**: Each installer handler has its own dedicated test class in the `Handlers/` namespace that extends the abstract base class. This approach provides:

- **Focused Testing**: Each test class covers scenarios specific to one handler or feature area
- **Better Maintainability**: Smaller, focused data providers that are easier to understand and modify
- **Improved Filtering**: Granular test execution capabilities using PHPUnit filters
- **Scalable Architecture**: Easy to add new handler tests following established patterns

**Key Benefits**:

- Run all handler tests: `--filter "Handlers\\\\"`
- Run specific handler: `--filter "HandlerNameInstallTest"`
- Run specific scenarios: `--filter "HandlerNameInstallTest.*scenario_pattern"`
- Consistent structure across all handler test classes
- Clear separation between test logic (in base class) and test data (in handler classes)

**Usage with Fixture Updates**: The `UPDATE_FIXTURES=1` mechanism works seamlessly with the new architecture, allowing systematic fixture updates across all handler test scenarios.

### PHPUnit Test Organization Best Practices

**File Structure Convention**: PHPUnit test files should follow a consistent organization pattern for maintainability and readability:

1. **Test methods first** - The actual test methods using `#[DataProvider]` attributes
2. **Data providers next** - Static methods that provide test data
3. **Protected helper methods last** - Helper methods at the bottom of the file

**Example Structure**:

```php
class ExampleTest extends TestCase {
  // 1. Test Methods First
  #[DataProvider('providerValidInput')]
  public function testValidInput(string $input, string $expected): void {
    // Test implementation
  }

  #[DataProvider('providerInvalidInput')]
  public function testInvalidInput(string $input, string $expectedMessage): void {
    // Test implementation with exception handling
  }

  // 2. Data Providers Next
  public static function providerValidInput(): array {
    return [
      'case 1' => ['input' => 'value1', 'expected' => 'result1'],
      'case 2' => ['input' => 'value2', 'expected' => 'result2'],
    ];
  }

  public static function providerInvalidInput(): array {
    return [
      'invalid case' => ['input' => 'bad', 'expectedMessage' => 'Error'],
    ];
  }

  // 3. Protected Helper Methods Last
  protected function createTestFixture(): string {
    // Helper implementation
  }
}
```

### Data Provider Patterns

**Consolidating Success and Error Cases**: Use conditional exception handling to merge success and error scenarios into a single test method, significantly reducing code duplication:

```php
public static function providerTestCases(): array {
  return [
    'success case' => [
      'input' => 'valid',
      'expectedResult' => 'success',
      'expectedException' => NULL,
      'expectedMessage' => NULL,
    ],
    'error case' => [
      'input' => 'invalid',
      'expectedResult' => NULL,
      'expectedException' => \RuntimeException::class,
      'expectedMessage' => 'Invalid input',
    ],
  ];
}

#[DataProvider('providerTestCases')]
public function testCases(string $input, ?string $expectedResult, ?string $expectedException, ?string $expectedMessage): void {
  if ($expectedException !== NULL) {
    /** @var class-string<\Throwable> $expectedException */
    $this->expectException($expectedException);
    $this->expectExceptionMessage($expectedMessage);
  }

  $result = $this->systemUnderTest->process($input);

  if ($expectedResult !== NULL) {
    $this->assertEquals($expectedResult, $result);
  }
}
```

**Benefits of This Pattern**:

- Eliminates duplicate test setup code
- Makes test scenarios easier to understand and compare
- Reduces overall test file length by 50-85%
- Maintains clear test output with descriptive scenario names
- Simplifies adding new test cases

**When to Use**:

- When multiple tests share identical setup but differ only in input/output
- When testing both success and failure paths of the same method
- When test scenarios can be parameterized effectively

**When Not to Use**:

- When tests require significantly different setup logic
- When tests are testing fundamentally different behaviors
- When conditional logic would make tests harder to understand

### Cross-Platform File Operations in Tests

**Critical**: Always use the `AlexSkrypnyk\File\File` class for file system operations in tests instead of native PHP functions. This ensures cross-platform compatibility across Windows, Linux, and macOS.

**Required Import**:

```php
use AlexSkrypnyk\File\File;
```

**Test Temporary Directory**:

All tests extending `UnitTestCase` have access to `self::$tmp` - a temporary directory that is automatically created before tests and cleaned up after. Always use this for test file operations instead of creating your own temporary directories.

**File Operations Mapping**:

| Native PHP Function | File Class Method | Purpose |
|---------------------|-------------------|---------|
| `mkdir($path)` | `File::mkdir($path)` | Create directory (with parents if needed) |
| `file_put_contents($path, $content)` | `File::dump($path, $content)` | Write content to file |
| `unlink($file)` | `File::remove($file)` | Remove file |
| `rmdir($dir)` recursively | `File::rmdir($dir)` | Remove directory recursively |

**Example Usage**:

```php
class MyTest extends UnitTestCase {
  public function testSomething(): void {
    // ✅ CORRECT - Cross-platform compatible using self::$tmp
    $test_dir = self::$tmp . '/subdir_' . uniqid();
    File::mkdir($test_dir);
    File::dump($test_dir . '/file.txt', 'content');
    $content = file_get_contents($test_dir . '/file.txt');
    File::remove($test_dir . '/file.txt');
    // No need to clean up - self::$tmp is auto-cleaned by UnitTestCase

    // ❌ INCORRECT - Platform-dependent native PHP functions
    $test_dir = sys_get_temp_dir() . '/test_' . uniqid();
    mkdir($test_dir);
    file_put_contents($test_dir . '/file.txt', 'content');
    unlink($test_dir . '/file.txt');
    rmdir($test_dir);
  }
}
```

**Benefits**:

- **Cross-platform compatibility**: Handles path separators and permissions correctly on all OS
- **Consistent error handling**: Throws `FileException` with clear messages
- **Automatic cleanup**: `self::$tmp` is automatically cleaned up by UnitTestCase tearDown
- **Better abstractions**: File class methods handle edge cases automatically

**Reference**: Full File class documentation available at `.vortex/installer/vendor/alexskrypnyk/file/README.md`

## PHPUnit Helper Functions

### cmd() Function

The `FunctionalTestCase` provides a convenient `cmd()` function that combines `processRun()` + `assertProcessSuccessful()` + output assertions:

```php
public function cmd(
  string $cmd,
  array|string|null $out = NULL,
  ?string $txt = NULL,
  array $arg = [],
  array $inp = [],
  array $env = [],
  int $tio = 60,
  int $ito = 60,
): ?Process
```

**Basic Usage:**

```php
// Simple command without output checks
$this->cmd('ahoy drush cr');

// Command with single output assertion
$this->cmd('ahoy doctor info', 'OPERATING SYSTEM');

// Command with multiple output assertions
$this->cmd('ahoy info', [
  'Project name                : star_wars',
  'Docker Compose project name : star_wars'
]);

// Command with named parameters
$this->cmd('ahoy reset', inp: ['y'], tio: 5 * 60);
```

### cmdFail() Function

For commands expected to fail, use `cmdFail()` which calls `assertProcessFailed()`:

```php
$this->cmdFail('ahoy lint-be', tio: 120, ito: 90);
```

### Output Assertion Prefixes

**CRITICAL RULE**: When using ANY prefixed strings, **ALL strings must have prefixes**. You cannot mix prefixed and non-prefixed strings.

#### Prefix Types

- **`+`** - Exact match present (entire output must equal this string)
- **`*`** - Substring present (string must be found within output)
- **`-`** - Exact match absent (entire output must NOT equal this string)
- **`!`** - Substring absent (string must NOT be found within output)

#### Two Operating Modes

**1. Shortcut Mode** (No prefixes - all treated as substring present):

```php
$this->cmd('ahoy info', ['Docker', 'Compose']);  // Both must be found in output
```

**2. Mixed Mode** (Any prefix present - ALL must have prefixes):

```php
// ✅ CORRECT - all strings have prefixes
$this->cmd('ahoy info', [
  '* Xdebug',      // Must contain "Xdebug"
  '* Disabled',    // Must contain "Disabled"
  '! Enabled'      // Must NOT contain "Enabled"
]);

// ❌ INCORRECT - mixing prefixed and non-prefixed
$this->cmd('ahoy info', [
  'Xdebug',        // No prefix
  '! Enabled'      // Has prefix - this will throw RuntimeException
]);
```

**Example Conversions:**

Before:

```php
$this->processRun('ahoy export-db', $args);
$this->assertProcessSuccessful();
$this->assertProcessOutputNotContains('Containers are not running.');
```

After:

```php
$this->cmd('ahoy export-db', '! Containers are not running.', arg: $args);
```

### When to Use cmd() vs processRun()

**Use `cmd()`:**

- Commands that should succeed (`assertProcessSuccessful()`)
- Simple output assertions (`assertProcessOutputContains/NotContains`)
- Most common test scenarios

**Use `processRun()`:**

- Commands with complex error output assertions (`assertProcessErrorOutputContains`)
- Commands requiring custom logic between execution and assertions
- Special cases like conditional retry patterns

## System-Specific Resources

### Documentation System

- **Live Site**: https://www.vortextemplate.com
- **Docusaurus Docs**: https://docusaurus.io/docs
- **React Testing Library**: https://testing-library.com/docs/react-testing-library/intro/
- **Jest Documentation**: https://jestjs.io/docs/getting-started
- **MDX Documentation**: https://mdxjs.com/docs/

### Installer System

- **Symfony Console**: https://symfony.com/doc/current/console.html
- **PHPUnit Documentation**: https://phpunit.de/documentation.html
- **Composer Documentation**: https://getcomposer.org/doc/

### Template Testing System

- **BATS Documentation**: https://github.com/bats-core/bats-core
- **PHPUnit Helpers**: https://github.com/AlexSkrypnyk/phpunit-helpers
- **Docker Compose**: https://docs.docker.com/compose/

### General

- **Issue Tracking**: https://github.com/drevops/vortex/issues
- **Main Repository**: https://github.com/drevops/vortex

## Important AI Assistant Guidelines

### CRITICAL: Commands That Require User Permission

**NEVER run these commands without explicit user permission:**

- `ahoy update-fixtures` - Updates all test fixtures (can take 10-15 minutes, runs full test suite twice)
- `UPDATE_FIXTURES=1 ./vendor/bin/phpunit` - Updates installer test fixtures
- `UPDATE_FIXTURES=1 composer test` - Updates any test fixtures
- Any command with `UPDATE_FIXTURES=1` environment variable

**Why**: These commands:

- Modify many files across the codebase
- Take significant time to complete (10-15 minutes)
- Run full test suites multiple times
- Should only be run when user explicitly requests fixture updates

**When user needs fixture updates**:

- Explain what the command does and how long it will take
- Ask for explicit permission before running
- Let the user run it themselves if they prefer

### System-Specific Restrictions

**Documentation System** (`.vortex/docs/`):

- Maintain American English spelling throughout
- Test React components thoroughly before committing
- Preserve responsive design patterns

**Installer System** (`.vortex/installer/`):

- **CRITICAL**: NEVER directly modify files under `.vortex/installer/tests/Fixtures/`
- These are test fixtures that must be updated via `ahoy update-fixtures` command from `.vortex/` directory
- The unified `ahoy update-fixtures` command handles all fixture updates automatically
- For debugging, manual `UPDATE_FIXTURES=1` commands can be used from `.vortex/installer/`
- Always test with baseline scenario first, then individual scenarios
- Preserve handler execution order and batching patterns

**Template Testing System** (`.vortex/tests/`):

- Follow PHPUnit helper function patterns (`cmd()`, `cmdFail()`)
- Maintain BATS test assertion alignment with script output
- Preserve Docker container isolation between tests
- Use appropriate test types for different validation levels

### General Coding Standards

**Line Length**:

- **Code**: Keep code on single lines as much as possible. There is no character limit per line for code.
- **Comments**: Follow standard line length limits (typically 80-120 characters) for comments and documentation.

**Rationale**: Code readability is enhanced by keeping logical units on single lines, while comments should be wrapped for readability.

**Examples**:

```php
// ✅ GOOD - Single-line code
throw new \RuntimeException(sprintf('Failed to download archive from: %s - %s', $url, $e->getMessage()));

// ❌ AVOID - Multi-line code when not necessary
throw new \RuntimeException(sprintf(
  'Failed to download archive from: %s - %s',
  $url,
  $e->getMessage()
));

// ✅ GOOD - Wrapped comment for readability
// This is a long comment that explains the reasoning behind the implementation
// and should be wrapped at a reasonable character limit for easier reading
// in various editor configurations.
```

**Exceptions**: Multi-line code is acceptable when:

- Function signatures have many parameters
- Array definitions with multiple elements
- Chained method calls for fluent interfaces
- Complex conditional expressions that benefit from line breaks

### Cross-System Considerations

- Each system can be modified independently
- Changes to template (outside `.vortex/`) may require updates across all three systems
- Always run system-specific tests after making changes
- Consider impact on user workflows when modifying any system

---

*This knowledge base should be updated whenever significant changes are made to any of the three Vortex subsystems or their maintenance procedures.*
