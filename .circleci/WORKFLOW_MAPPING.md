# CircleCI Workflow Mapping

This document maps the original single-file workflows to the new multi-file structure.

## Original Workflows in config.yml

### 1. `commit` Workflow
- **Trigger:** Push to any branch, any tag
- **Jobs:**
  - `database` (conditional: !PROVISION_TYPE_PROFILE) → requires tags filter
  - `build` → depends on database, requires tags filter
  - `deploy` (conditional: DEPLOYMENT) → depends on build, specific branch filter, ignores tags
  - `deploy-tags` (conditional: DEPLOYMENT) → depends on build, specific tag filter, ignores branches
  - `vortex-dev-test-ci-postbuild` (conditional: VORTEX_DEV) → depends on build, requires tags filter

### 2. `vortex-dev-didi-fi` Workflow
- **Trigger:** Push to any branch (implicit)
- **Jobs:**
  - `vortex-dev-didi-database-fi`
  - `vortex-dev-didi-build-fi` → depends on vortex-dev-didi-database-fi

### 3. `vortex-dev-didi-ii` Workflow
- **Trigger:** Push to any branch (implicit)
- **Jobs:**
  - `vortex-dev-database-ii`
  - `vortex-dev-didi-build-ii` → depends on vortex-dev-database-ii

### 4. `nightly-db` Workflow
- **Trigger:** Schedule (cron: `0 18 * * *`) on `develop` branch
- **Jobs:**
  - `database-nightly`

### 5. `update-dependencies` Workflow
- **Trigger:** Schedule (cron: `5 11,23 * * *`) on `develop` branch
- **Jobs:**
  - `update-dependencies`

### 6. `update-dependencies-manual` Workflow
- **Trigger:** Pipeline parameter `run_update_dependencies`
- **Jobs:**
  - `update-dependencies`

## New Multi-File Structure

### build-test-deploy.yml
- **Workflows:** `commit` (main jobs only, excluding vortex-dev)
- **Jobs:** database, build, deploy, deploy-tags
- **Triggers:** Push to any branch, any tag

### database-nightly.yml
- **Workflows:** `database-nightly`
- **Jobs:** database-nightly
- **Triggers:** Schedule (cron: `0 18 * * *`) on `develop`

### update-dependencies.yml
- **Workflows:** `update-dependencies-scheduled`, `update-dependencies-manual`
- **Jobs:** update-dependencies
- **Triggers:** Schedule (cron: `5 11,23 * * *`) + manual parameter

### vortex-test-postbuild.yml
- **Workflows:** `vortex-test-postbuild`
- **Jobs:** vortex-dev-test-ci-postbuild (renamed to vortex-test-postbuild)
- **Triggers:** Push to any branch, any tag
- **Note:** This runs independently but logically after main build

### vortex-test-didi-fi.yml
- **Workflows:** `vortex-test-didi-fi`
- **Jobs:** vortex-dev-didi-database-fi, vortex-dev-didi-build-fi
- **Triggers:** Push to any branch

### vortex-test-didi-ii.yml
- **Workflows:** `vortex-test-didi-ii`
- **Jobs:** vortex-dev-database-ii, vortex-dev-didi-build-ii
- **Triggers:** Push to any branch

## Important Notes

1. **Job Dependencies Across Pipelines:**
   - In the original config, `vortex-dev-test-ci-postbuild` depends on `build`
   - With multiple pipelines, cross-pipeline dependencies are NOT supported
   - Solution: Each pipeline is independent; vortex tests run in parallel

2. **Shared Aliases:**
   - Each config file must include its own copy of required aliases
   - Common aliases: runner_config, ssh fingerprints, shared steps

3. **Conditional Blocks:**
   - All conditional markers (#;< #;>) must be preserved
   - Installer will process these markers in each file independently

4. **Environment Variables:**
   - All pipelines inherit project-level environment variables
   - No pipeline-specific variables needed (all use same secrets)
