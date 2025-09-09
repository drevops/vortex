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

### .vortex/docs/

- **Docusaurus-based documentation** published to https://www.vortextemplate.com
- **MDX content system** with interactive components
- **Multi-layered testing**: Jest (unit), E2E, and spellcheck validation
- **Quality tools**: ESLint, Prettier, and American English standardization
- **Custom React components** for enhanced documentation UX

### .vortex/installer/

- Self-contained Symfony console application
- Handles Vortex installation and customization
- **Fixture System**: Uses baseline + diff architecture

### .vortex/tests/

- Unit and functional tests for Vortex
- Uses both **PHPUnit** (functional workflows) and **BATS** (shell script unit tests)

## Testing Framework

### Documentation Testing (.vortex/docs/)

- **Jest-based testing** with jsdom and React Testing Library
- **Unit tests**: React component functionality and interactions
- **Spellcheck**: cspell validation for American English consistency
- **Coverage reporting**: Text, lcov, HTML, and Cobertura formats
- **Location**: `tests/unit/`

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

#### BATS Helpers System

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

### Running Tests

**Documentation Tests** (`.vortex/docs/`):

```bash
cd .vortex/docs

# Install dependencies
yarn install

# Run tests
yarn test

# Run with coverage
yarn test:coverage

# Run in watch mode
yarn test:watch

# Spellcheck validation
yarn spellcheck

# Code quality checks
yarn lint
yarn lint-fix
```

**Template Tests** (`.vortex/`):

```bash
cd .vortex

# Install all dependencies (PHP, Node.js, BATS)
ahoy install

# Run PHPUnit tests
cd tests && ./vendor/bin/phpunit

# Run BATS tests - use ahoy from .vortex/ directory
ahoy test-bats -- tests/bats/unit/notify.bats          # Specific test file
ahoy test-bats -- tests/bats/provision.bats            # Another test file
ahoy test-bats -- --verbose-run tests/bats/unit/       # Verbose output for directory
ahoy test-bats -- tests/bats/                          # All BATS tests

# Alternative: direct bats command (after ahoy install)
bats tests/bats/unit/notify.bats
```

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

### Common Test Commands

**Documentation Workflow** (`.vortex/docs/`):

```bash
# Development workflow
yarn start          # Start dev server
yarn build          # Build documentation
yarn spellcheck     # American English validation
yarn lint-fix       # Auto-fix code quality issues

# Testing workflow
yarn test           # Run all tests
yarn test:watch     # Watch mode for development
```

**Template Testing** (`.vortex/`):

```bash
# From .vortex/
ahoy install        # Install all dependencies
ahoy lint           # Code linting
ahoy test           # Run all tests

# Individual test suites
cd tests
./test.common.sh     # Common tests
./test.deployment.sh # Deployment tests
./test.workflow.sh   # Workflow tests
./lint.scripts.sh    # Shell script linting

# BATS testing (from .vortex/)
ahoy test-bats -- tests/bats/unit/notify.bats    # Specific test
ahoy test-bats -- tests/bats/                    # All BATS tests
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

- `DEPLOY_TYPE_CONTAINER_REGISTRY` - Container registry deployments
- `DEPLOY_TYPE_WEBHOOK` - Webhook deployments
- `DEPLOY_TYPE_ARTIFACT` - Artifact deployments

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

## Key Directories and Files

### Template Structure (Outside .vortex/)

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
└── [other template files]
```

### Test Harness (.vortex/)

```text
├── docs/                       # Vortex documentation (Docusaurus)
│   ├── src/components/         # React components (VerticalTabs, etc.)
│   ├── tests/unit/             # Jest tests
│   ├── content/                # MDX documentation content
│   ├── jest.config.js          # Test configuration
│   └── cspell.json             # Spellcheck configuration
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

## Resources

- **Documentation**: `.vortex/docs/` and https://www.vortextemplate.com
- **BATS Documentation**: https://github.com/bats-core/bats-core
- **Issue Tracking**: https://github.com/drevops/vortex/issues

## Important AI Assistant Guidelines

**CRITICAL**: NEVER directly modify files under `.vortex/installer/tests/Fixtures/`. These are test fixtures that must be updated by the user **MANUALLY**.

---

*This knowledge base should be updated whenever significant changes are made to the Vortex testing or maintenance procedures.*
