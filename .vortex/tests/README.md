# Vortex Testing Framework

This directory holds the PHPUnit functional tests for the Vortex project.

## Overview

PHPUnit tests the **workflows** of a Vortex user. Each test runs processes
and commands inside a Vortex installation, the same way a real project
does.

## Running Tests

### Prerequisites

- Docker and Docker Compose
- Node.js and Yarn
- PHP 8.3+
- Composer
- Git

### Setup

```bash
composer install
```

## Running PHPUnit Tests

```bash
# Run all PHPUnit tests
./vendor/bin/phpunit

# Run specific test class
./vendor/bin/phpunit phpunit/Functional/AhoyConfigTest.php
```

## Running Individual Test Suites

CI splits the functional tests across parallel nodes. Each test belongs
to a PHPUnit group named `p0` to `p5`, and each CI node runs
`--group="p${TEST_NODE_INDEX}"` for its own index.

The linters run from standalone script wrappers:

- [`lint.scripts.sh`](lint.scripts.sh) - Linting for shell scripts
- [`lint.dockerfiles.sh`](lint.dockerfiles.sh) - Linting for Dockerfiles

## Environment Variables

Set these environment variables to customize test execution:

- `TEST_VORTEX_DEBUG=1` - Enable debug output from tests
- `TEST_PACKAGE_TOKEN` - GitHub token used for integration tests
- `TEST_VORTEX_CONTAINER_REGISTRY_USER` and
  `TEST_VORTEX_CONTAINER_REGISTRY_PASS` - Container registry credentials
  used for integration tests

## File Structure

Functional tests sit apart from the traits that support them:

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
