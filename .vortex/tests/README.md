# Vortex Testing Framework

This directory contains the comprehensive testing system for the Vortex project
to ensure the quality and reliability of Vortex workflows and scripts.

## Overview

The Vortex testing framework is designed to validate:

- Core Vortex functionality and scripts
- Installation and deployment workflows
- Integration scenarios
- CI/CD pipeline components

## Testing Technologies

### PHPUnit

PHPUnit handles functional testing of Vortex user's **workflows**: processes
and commands are ran in the context of a Vortex installation, simulating
real-world scenarios.

### Bats (Bash Automated Testing System)

[Bats](https://github.com/bats-core/bats-core) is used for **unit** testing
shell scripts with coverage.

## Running Tests

### Prerequisites

- Docker and Docker Compose
- Node.js and Yarn
- PHP 8.2+
- Composer
- Git

### Setup

```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies for BATS testing (BATS is distributed via npm, but does not require Node.js to run)
yarn install
```

## Running PHPUnit Tests

```bash
# Run all PHPUnit tests
./vendor/bin/phpunit

# Run specific test class
./vendor/bin/phpunit phpunit/Functional/AhoyConfigTest.php
```

## Running BATS tests

```bash
# Run specific Bats test file
./node_modules/.bin/bats bats/helpers.bats

# Run with verbose output
./node_modules/.bin/bats --verbose-run bats/provision.bats
```

## Running Individual Test Suites

For parallel execution, tests can be run across multiple CI nodes using the
convenience script wrappers:

- [`test.common.sh]`(test.common.sh) - Common tests for all environments
- [`test.deployment.sh`](test.deployment.sh) - Deployment tests
- [`test.postbuild.sh`](test.postbuild.sh) - Post-build tests
- [`test.workflow.sh`](test.workflow.sh) - Workflow tests
- [`lint.scripts.sh`](lint.scripts.sh) - Linting for shell scripts
- [`lint.dockerfiles.sh`](lint.dockerfiles.sh) - Linting for Dockerfiles

These scripts are designed to run in CI environments (CircleCI, GitHub Actions)
and use the `TEST_NODE_INDEX` environment variable to distribute tests across
multiple runners.

## Environment Variables

The following environment variables can be added in the environment to
customize test execution:

- `TEST_VORTEX_DEBUG=1` - Enable debug output from tests
- `TEST_PACKAGE_TOKEN` - GitHub token used for integration tests
- `TEST_VORTEX_CONTAINER_REGISTRY_USER` and `TEST_VORTEX_CONTAINER_REGISTRY_PASS` - Container registry credentials used for integration tests

## File Structure

The PHPUnit testing system is organized into a clear hierarchy that separates functional tests from supporting utilities:

```text
phpunit/
├── Functional/                           # End-to-end functional tests
│   ├── FunctionalTestCase.php           # Base class for all functional tests
│   ├── AhoyConfigTest.php               # Tests Ahoy configuration functionality
│   ├── AhoyWorkflowTest.php             # Tests Ahoy workflows
│   ├── DockerComposeWorkflowTest.php    # Tests Docker Compose workflows
│   └── HelpersTest.php                  # Tests helper methods used in the testing framework. Tests for tests.
│
└── Traits/                              # Reusable test functionality
    ├── GitTrait.php                     # Helpers to work with Git repositories
    ├── HelpersTrait.php                 # File operations, backup/restore with hash-based prefixes, and Docker volume sync
    ├── ProcessTrait.php                 # Runs a test process and provides assertions for its output
    ├── SutTrait.php                     # Generic methods for setting up and testing SUT. Workflow-specific methods are within Subtest\*Trait traits
    └── Subtests/                        # Specialized test utilities
        ├── SubtestAhoyTrait.php         # Steps and assertions for testing Ahoy-based workflows
        └── SubtestDockerComposeTrait.php # Steps and assertions for Docker Compose-based workflows
```

