#!/usr/bin/env bash
##
# Additional environment variables used in this project in CI.
# shellcheck disable=SC2034

# Skip all deployments.
VORTEX_DEPLOY_SKIP=

# Flag to allow skipping of a deployment using additional flags.
VORTEX_DEPLOY_ALLOW_SKIP=

# Pull request numbers to skip deployment for (single value or comma-separated list).
VORTEX_DEPLOY_SKIP_PRS=

# Branch names to skip deployment for (single value or comma-separated list).
VORTEX_DEPLOY_SKIP_BRANCHES=

# Proceed with container image deployment after it was exported.
VORTEX_EXPORT_DB_CONTAINER_REGISTRY_DEPLOY_PROCEED=

# Ignore Hadolint failures.
VORTEX_CI_HADOLINT_IGNORE_FAILURE=0

# Ignore DCLint failures.
VORTEX_CI_DCLINT_IGNORE_FAILURE=0

# Ignore `composer validate` failures.
VORTEX_CI_COMPOSER_VALIDATE_IGNORE_FAILURE=0

# Ignore `composer normalize` failures.
VORTEX_CI_COMPOSER_NORMALIZE_IGNORE_FAILURE=0

# Ignore `composer audit` failures.
VORTEX_CI_COMPOSER_AUDIT_IGNORE_FAILURE=0

# Ignore PHPCS failures.
VORTEX_CI_PHPCS_IGNORE_FAILURE=0

# Ignore PHPStan failures.
VORTEX_CI_PHPSTAN_IGNORE_FAILURE=0

# Ignore Rector failures.
VORTEX_CI_RECTOR_IGNORE_FAILURE=0

# Ignore PHPMD failures.
VORTEX_CI_PHPMD_IGNORE_FAILURE=0

# Ignore Twig CS Fixer failures.
VORTEX_CI_TWIG_CS_FIXER_IGNORE_FAILURE=0

# Ignore NodeJS linters failures.
VORTEX_CI_NODEJS_LINT_IGNORE_FAILURE=0

# Ignore Gherkin Lint failures.
VORTEX_CI_GHERKIN_LINT_IGNORE_FAILURE=0

# Ignore PHPUnit test failures.
VORTEX_CI_PHPUNIT_IGNORE_FAILURE=0

# Ignore Behat test failures.
VORTEX_CI_BEHAT_IGNORE_FAILURE=0

# Test Behat profile to use in CI. If not set, the `default` profile will be used.
VORTEX_CI_BEHAT_PROFILE=

# Directory to store test results in CI.
VORTEX_CI_TEST_RESULTS=/tmp/tests

# Directory to store test artifacts in CI.
VORTEX_CI_ARTIFACTS=/tmp/artifacts

# Self-hosted Renovate bot token.
# Create a GitHub token with a permission to write to a repository.
RENOVATE_TOKEN=

# Whether to enable self-hosted Renovate bot dashboard.
RENOVATE_DEPENDENCY_DASHBOARD=false

# Whether to allow self-hosted Renovate bot make changes to the repository.
RENOVATE_DRY_RUN=false

# Commit author for self-hosted Renovate bot.
RENOVATE_GIT_AUTHOR='Renovate Self Hosted <renovatebot@your-site-domain.example>'

# Renovate repositories to manage.
# Set as "organization/repository".
RENOVATE_REPOSITORIES=
