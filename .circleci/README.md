# CircleCI Multiple Configuration Files

This directory contains CircleCI configuration split into multiple files, each representing a separate pipeline with specific triggers and purposes.

## Overview

Vortex uses CircleCI's **multiple configuration files** feature to organize CI/CD workflows logically. This improves maintainability and makes it easier to understand each pipeline's purpose.

## Configuration Files

### Main Project Pipelines

#### 1. `build-test-deploy.yml`
**Pipeline Name:** `Database, Build, Test and Deploy`

**Purpose:** Core build, test, and deployment workflow for the project.

**Triggers:**
- Push to any branch
- Push to any tag

**Jobs:**
- `database` - Download and cache database
- `build` - Build stack, lint code, run tests
- `deploy` - Deploy to specific branches
- `deploy-tags` - Deploy tagged releases

**Conditionals:**
- `database` job: Conditional on `!PROVISION_TYPE_PROFILE`
- `deploy` and `deploy-tags` jobs: Conditional on `DEPLOYMENT`

---

#### 2. `database-nightly.yml`
**Pipeline Name:** `Database - Nightly refresh`

**Purpose:** Overnight database refresh and caching for next-day builds.

**Triggers:**
- Schedule: `0 18 * * *` (6 PM UTC daily)
- Branch: `develop` only

**Jobs:**
- `database-nightly` - Fresh database download and cache

**Conditionals:**
- Entire file: Conditional on `!PROVISION_TYPE_PROFILE`

---

#### 3. `update-dependencies.yml`
**Pipeline Name:** `Update dependencies`

**Purpose:** Self-hosted Renovate for automated dependency updates.

**Triggers:**
- Schedule: `5 11,23 * * *` (11:05 AM and 11:05 PM UTC daily)
- Manual: Pipeline parameter `run_update_dependencies`
- Branch: `develop` only

**Jobs:**
- `update-dependencies` - Run Renovate bot

**Requirements:**
- `RENOVATE_TOKEN` environment variable
- `RENOVATE_REPOSITORIES` environment variable
- `RENOVATE_GIT_AUTHOR` environment variable

**Conditionals:**
- Entire file: Conditional on `DEPS_UPDATE_PROVIDER_CI`

---

### Vortex Development Pipelines

These pipelines are used for Vortex framework testing and will be removed during project installation.

#### 4. `vortex-test-postbuild.yml`
**Pipeline Name:** `Vortex - Test (Post-build)`

**Purpose:** Vortex framework validation after main build.

**Triggers:**
- Push to any branch
- Push to any tag

**Jobs:**
- `vortex-test-postbuild` - Run Vortex post-build tests

**Conditionals:**
- Entire file: Conditional on `VORTEX_DEV`

---

#### 5. `vortex-test-didi-fi.yml`
**Pipeline Name:** `Vortex - Test (DIDI from file)`

**Purpose:** Test database-in-image workflow with file source.

**Triggers:**
- Push to any branch (implicit)

**Jobs:**
- `vortex-test-didi-database-fi` - Create DB image from file
- `vortex-test-didi-build-fi` - Build site with DIDI image

**Conditionals:**
- Entire file: Conditional on `VORTEX_DEV`

---

#### 6. `vortex-test-didi-ii.yml`
**Pipeline Name:** `Vortex - Test (DIDI from registry)`

**Purpose:** Test database-in-image workflow with registry source.

**Triggers:**
- Push to any branch (implicit)

**Jobs:**
- `vortex-test-didi-database-ii` - Create DB image from registry
- `vortex-test-didi-build-ii` - Build site with DIDI image

**Conditionals:**
- Entire file: Conditional on `VORTEX_DEV`

---

## Setting Up Pipelines in CircleCI

**Prerequisites:**
- CircleCI account connected to GitHub
- **CircleCI GitHub App integration** (required for multiple pipelines)
- Existing project configured in CircleCI

### Configuration Steps

1. **Access CircleCI UI:**
   - Go to your project in CircleCI
   - Navigate to **Project Settings** → **Project Setup**

2. **Create Each Pipeline:**

   For each configuration file, click **Add Pipeline** and configure:

   | Config File | Pipeline Name | Config Path | Trigger |
   |-------------|--------------|-------------|---------|
   | build-test-deploy.yml | `Database, Build, Test and Deploy` | `.circleci/build-test-deploy.yml` | Push to all branches, all tags |
   | database-nightly.yml | `Database - Nightly refresh` | `.circleci/database-nightly.yml` | Schedule (`0 18 * * *` on `develop`) |
   | update-dependencies.yml | `Update dependencies` | `.circleci/update-dependencies.yml` | Schedule (`5 11,23 * * *` on `develop`) + Manual |
   | vortex-test-postbuild.yml | `Vortex - Test (Post-build)` | `.circleci/vortex-test-postbuild.yml` | Push to all branches, all tags |
   | vortex-test-didi-fi.yml | `Vortex - Test (DIDI from file)` | `.circleci/vortex-test-didi-fi.yml` | Push to all branches |
   | vortex-test-didi-ii.yml | `Vortex - Test (DIDI from registry)` | `.circleci/vortex-test-didi-ii.yml` | Push to all branches |

3. **Configure Environment Variables:**

   Ensure the following project-level environment variables are set:

   **Required for all pipelines:**
   - `VORTEX_CONTAINER_REGISTRY_USER` - Docker registry username
   - `VORTEX_CONTAINER_REGISTRY_PASS` - Docker registry password

   **Required for database pipelines:**
   - Add SSH key for database downloads (fingerprint in config)

   **Required for deployment pipelines:**
   - Add SSH key for deployments (fingerprint in config)

   **Required for update-dependencies pipeline:**
   - `RENOVATE_TOKEN` - GitHub access token
   - `RENOVATE_REPOSITORIES` - Repository to run Renovate on
   - `RENOVATE_GIT_AUTHOR` - Author for Renovate commits

### Trigger Configuration Examples

**For scheduled pipelines:**
- In CircleCI UI, select **Trigger** → **Schedule**
- Set cron expression and target branch
- Example: `0 18 * * *` on branch `develop`

**For manual triggers:**
- Use pipeline parameters in config file
- Trigger via CircleCI UI or API

**For push triggers:**
- Select **Trigger** → **GitHub event**
- Choose appropriate events (push, tag, pull request)

---

## File Organization

```
.circleci/
├── README.md                         # This file
├── WORKFLOW_MAPPING.md              # Workflow mapping documentation
├── SHARED_COMPONENTS.md             # Shared component analysis
├── build-test-deploy.yml            # Main pipeline
├── database-nightly.yml             # Nightly DB refresh
├── update-dependencies.yml          # Dependency updates
├── vortex-test-postbuild.yml       # Vortex post-build tests
├── vortex-test-didi-fi.yml         # Vortex DIDI from file tests
└── vortex-test-didi-ii.yml         # Vortex DIDI from registry tests
```

---

## Naming Conventions

Files follow **GitHub Actions-style naming** for consistency:

- **Kebab-case:** All lowercase with hyphens (e.g., `build-test-deploy.yml`)
- **Descriptive names:** Action-oriented naming (e.g., `update-dependencies.yml`)
- **Vortex prefix:** Development workflows prefixed with `vortex-` (e.g., `vortex-test-*.yml`)

Pipeline names in CircleCI UI also follow this convention for easy identification.

---

## Shared Components

Each configuration file includes its own copy of required aliases and steps:

- `runner_config` - Shared runner container configuration
- `step_setup_remote_docker` - Setup remote Docker
- `step_process_codebase_for_ci` - Process docker-compose for CI
- `load_variables_from_dotenv` - Load environment variables

This duplication ensures each config file is **self-contained and independent**.

---

## Conditional Markers

Vortex uses conditional markers for installer processing:

```yaml
#;< MARKER_NAME
# Content included only if MARKER_NAME is true
#;> MARKER_NAME
```

Common markers:
- `!PROVISION_TYPE_PROFILE` - Exclude if profile-based provisioning
- `DEPLOYMENT` - Include deployment jobs
- `DEPS_UPDATE_PROVIDER_CI` - Include CI-based dependency updates
- `VORTEX_DEV` - Vortex development features (removed in consumer projects)
- `TOOL_*` - Include specific tool integrations (PHPCS, PHPStan, Behat, etc.)

---

## Consumer Project Installation

During Vortex installation:

1. **Vortex development files are removed:**
   - `vortex-test-postbuild.yml`
   - `vortex-test-didi-fi.yml`
   - `vortex-test-didi-ii.yml`

2. **Consumer project receives:**
   - `build-test-deploy.yml`
   - `database-nightly.yml` (if using database downloads)
   - `update-dependencies.yml` (if using CI-based dependency updates)

3. **Files are processed:**
   - Conditional markers are evaluated
   - Sections are included/excluded based on project configuration
   - Comments and markers are cleaned up

---

## Troubleshooting

### Pipeline Not Triggering

- **Check CircleCI UI:** Verify pipeline is configured correctly
- **Check triggers:** Ensure trigger matches your event (push, schedule, etc.)
- **Check branch filters:** Ensure your branch matches trigger configuration

### Pipeline Failing

- **Check environment variables:** Ensure all required variables are set
- **Check SSH keys:** Verify fingerprints match configured keys
- **Check logs:** Review CircleCI job logs for specific errors

### Cross-Pipeline Dependencies

- **Important:** CircleCI multiple pipelines **cannot depend on each other**
- Each pipeline runs independently
- If job dependencies are needed, keep jobs in the same config file

---

## Additional Resources

- **CircleCI Multiple Pipelines:** https://circleci.com/docs/set-up-multiple-configuration-files-for-a-project/
- **CircleCI GitHub App:** https://circleci.com/docs/github-apps-integration/
- **Vortex Documentation:** https://www.vortextemplate.com
- **GitHub Actions (reference):** `.github/workflows/` directory

---

## Maintenance

When updating configurations:

1. **Maintain consistency:** Keep shared components identical across files
2. **Update all files:** If changing runner version, update in all configs
3. **Test changes:** Push to test branch and verify all pipelines run correctly
4. **Document changes:** Update this README if adding/removing pipelines

---

## Custom Scripts

You may add custom scripts, which would run only in CI, to this directory and
reference them from configuration files.
