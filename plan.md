# Plan: Split CircleCI Config into Multiple Files

## Overview

This plan addresses [#1571](https://github.com/drevops/vortex/issues/1571) by leveraging CircleCI's native multiple configuration files feature to split the large 763-line `config.yml` into smaller, more manageable and maintainable files organized by functional concerns.

## Context

**Current State:**
- Single `.circleci/config.yml` file with 763 lines
- Contains all jobs, workflows, and configuration mixed together
- Difficult to navigate and maintain
- Includes both consumer project configuration and Vortex development testing

**CircleCI Multiple Configuration Files Feature:**
- Requires **CircleCI GitHub App integration** (prerequisite)
- Each configuration file must be complete with all necessary elements
- Files can be stored anywhere in the repository
- Each config file is associated with a separate pipeline
- Pipelines are triggered by specific GitHub events
- Configured via CircleCI UI: Project Settings → Project Setup → Add Pipeline

## Proposed File Structure

Split the configuration into logical, purpose-driven files using GitHub Actions-style naming conventions for consistency:

```
.circleci/
├── build-test-deploy.yml           # Main pipeline (commit workflow)
├── database-nightly.yml            # Nightly database refresh pipeline
├── update-dependencies.yml         # Renovate/dependency updates pipeline
├── vortex-test-postbuild.yml      # Vortex post-build validation tests
├── vortex-test-didi-fi.yml        # Database-in-image from file tests
└── vortex-test-didi-ii.yml        # Database-in-image from registry tests
```

**Naming Conventions (aligned with GitHub Actions):**
- Use kebab-case (lowercase with hyphens)
- Descriptive, action-oriented names (build-test-deploy, update-dependencies)
- Prefix Vortex development workflows with `vortex-` (e.g., `vortex-test-*`)
- Match GitHub Actions equivalents where they exist:
  - `.github/workflows/build-test-deploy.yml` → `.circleci/build-test-deploy.yml`
  - `.github/workflows/update-dependencies.yml` → `.circleci/update-dependencies.yml`
  - `.github/workflows/vortex-test-*.yml` → `.circleci/vortex-test-*.yml`

## File Breakdown

### 1. **build-test-deploy.yml** - Main Build Pipeline
**Purpose:** Core build, test, and deployment workflow
**Trigger:** Push to any branch, tag creation
**GitHub Actions Equivalent:** `.github/workflows/build-test-deploy.yml`
**Workflow Name:** `Database, Build, Test and Deploy` (matches GHA)

**Contents:**
- Aliases (SSH fingerprints, runner config, shared steps)
- Jobs:
  - `database` (conditional: !PROVISION_TYPE_PROFILE)
  - `build`
  - `deploy`
  - `deploy-tags`
- Workflow: `build-test-deploy`

**Size:** ~470 lines

---

### 2. **database-nightly.yml** - Nightly Database Pipeline
**Purpose:** Overnight database refresh and caching
**Trigger:** Scheduled cron (`0 18 * * *`) on `develop` branch
**Workflow Name:** `Database - Nightly refresh`

**Contents:**
- Shared aliases (SSH fingerprints, runner config, db cache config)
- Jobs:
  - `database-nightly`
- Workflow: `database-nightly`

**Size:** ~200 lines (includes shared aliases)

**Note:** In GitHub Actions, this is part of `build-test-deploy.yml` with a schedule trigger. CircleCI requires separate file for clarity.

---

### 3. **update-dependencies.yml** - Dependency Updates Pipeline
**Purpose:** Self-hosted Renovate for automated dependency updates
**Trigger:**
  - Scheduled cron (`5 11,23 * * *`) on `develop` branch
  - Manual trigger via pipeline parameter
**GitHub Actions Equivalent:** `.github/workflows/update-dependencies.yml`
**Workflow Name:** `Update dependencies` (matches GHA)

**Contents:**
- Parameters (run_update_dependencies)
- Jobs:
  - `update-dependencies`
- Workflows:
  - `update-dependencies-scheduled`
  - `update-dependencies-manual`

**Size:** ~60 lines

---

### 4. **vortex-test-postbuild.yml** - Post-Build Tests
**Purpose:** Vortex framework validation after main build
**Trigger:** Push to any branch (runs after main pipeline)
**Workflow Name:** `Vortex - Test (Post-build)`

**Contents:**
- Shared aliases (runner config)
- Jobs:
  - `vortex-test-postbuild`
- Workflow: `vortex-test-postbuild`

**Size:** ~80 lines

**Note:** Follows same naming pattern as `.github/workflows/vortex-test-*.yml` files.

---

### 5. **vortex-test-didi-fi.yml** - DIDI File-to-Image Tests
**Purpose:** Test database-in-image workflow with file source
**Trigger:** Push to any branch
**Workflow Name:** `Vortex - Test (DIDI from file)`

**Contents:**
- Shared aliases (runner config, db config)
- Jobs:
  - `vortex-test-didi-database-fi` (creates image from DB file)
  - `vortex-test-didi-build-fi` (builds site with DIDI image)
- Workflow: `vortex-test-didi-fi`

**Size:** ~250 lines (includes shared job definitions)

---

### 6. **vortex-test-didi-ii.yml** - DIDI Image-to-Image Tests
**Purpose:** Test database-in-image workflow with registry source
**Trigger:** Push to any branch
**Workflow Name:** `Vortex - Test (DIDI from registry)`

**Contents:**
- Shared aliases (runner config, db config)
- Jobs:
  - `vortex-test-didi-database-ii` (creates image from registry)
  - `vortex-test-didi-build-ii` (builds site with DIDI image)
- Workflow: `vortex-test-didi-ii`

**Size:** ~250 lines (includes shared job definitions)

## Implementation Strategy

### Phase 1: Preparation and Validation
1. **Document current behavior:**
   - Map all workflows to their triggers
   - Identify job dependencies across workflows
   - Document environment variables per workflow

2. **Create reference documentation:**
   - CircleCI pipeline configuration requirements
   - Trigger event mappings for each workflow
   - Variable inheritance and scoping rules

3. **Validate prerequisites:**
   - Confirm CircleCI GitHub App is installed
   - Verify permissions for pipeline management

### Phase 2: Extract Shared Components
1. **Identify common elements:**
   - Aliases used across multiple jobs (runner_config, ssh fingerprints)
   - Shared steps (setup_remote_docker, process_codebase_for_ci)
   - Common parameters and environment variables

2. **Create extraction strategy:**
   - Determine which aliases need duplication vs. reference
   - Document which shared components belong in each file
   - Plan for maintaining consistency across files

### Phase 3: Split Configuration Files
1. **Create individual config files:**
   - Start with simplest workflows first (update-dependencies)
   - Move to isolated workflows (nightly-db)
   - Handle complex workflows last (vortex-dev tests)

2. **For each file:**
   - Copy necessary CircleCI structure (`version: '2.1'`)
   - Include all required shared components (aliases, steps)
   - Preserve Vortex comment markers (`#;<`, `#;>`)
   - Maintain conditional logic for installer
   - Ensure all environment variables are accessible

3. **Handle Vortex-specific concerns:**
   - Preserve installer token markers
   - Maintain comment structure for template processing
   - Ensure conditional includes work correctly

### Phase 4: Configure CircleCI Pipelines
1. **Access CircleCI UI:**
   - Navigate to Project Settings → Project Setup

2. **Create pipelines (in order):**

   **Note:** Pipeline names in CircleCI UI follow GitHub Actions style for consistency.

   - **Build, Test and Deploy Pipeline**
     - Name: `Database, Build, Test and Deploy`
     - Config: `.circleci/build-test-deploy.yml`
     - Trigger: Push to all branches, all tags
     - _Matches:_ `.github/workflows/build-test-deploy.yml`

   - **Nightly Database Pipeline**
     - Name: `Database - Nightly refresh`
     - Config: `.circleci/database-nightly.yml`
     - Trigger: Schedule (`0 18 * * *` on `develop`)
     - _Note:_ GHA combines this with main workflow; CircleCI separates for clarity

   - **Update Dependencies Pipeline**
     - Name: `Update dependencies`
     - Config: `.circleci/update-dependencies.yml`
     - Trigger: Schedule (`5 11,23 * * *` on `develop`) + Manual
     - _Matches:_ `.github/workflows/update-dependencies.yml`

   - **Vortex Post-Build Tests Pipeline**
     - Name: `Vortex - Test (Post-build)`
     - Config: `.circleci/vortex-test-postbuild.yml`
     - Trigger: Push to all branches
     - _Follows pattern:_ `.github/workflows/vortex-test-*.yml`

   - **Vortex DIDI-FI Tests Pipeline**
     - Name: `Vortex - Test (DIDI from file)`
     - Config: `.circleci/vortex-test-didi-fi.yml`
     - Trigger: Push to all branches
     - _Follows pattern:_ `.github/workflows/vortex-test-*.yml`

   - **Vortex DIDI-II Tests Pipeline**
     - Name: `Vortex - Test (DIDI from registry)`
     - Config: `.circleci/vortex-test-didi-ii.yml`
     - Trigger: Push to all branches
     - _Follows pattern:_ `.github/workflows/vortex-test-*.yml`

3. **Configure environment variables:**
   - Ensure all pipelines inherit project-level variables
   - Document any pipeline-specific variables needed

### Phase 5: Testing and Validation
1. **Create test branch:**
   - Create `feature/1571-split-circleci-config` branch
   - Push with split configuration

2. **Validate each pipeline:**
   - **Commit workflow:** Push to test branch, verify build runs
   - **Nightly DB:** Manually trigger or wait for schedule
   - **Update dependencies:** Trigger manually via parameter
   - **Vortex dev tests:** Verify all three DIDI workflows run

3. **Test scenarios:**
   - Push to feature branch (should trigger main + vortex-dev pipelines)
   - Push to `develop` (should trigger all applicable workflows)
   - Create tag (should trigger deploy-tags job)
   - Schedule triggers (wait for or manually trigger)

4. **Verify job dependencies:**
   - Ensure `build` waits for `database` in main pipeline
   - Ensure `deploy` waits for `build`
   - Verify Vortex DIDI build jobs wait for database jobs

5. **Check for regressions:**
   - Compare job outputs with previous runs
   - Verify all tests pass
   - Ensure deployments work correctly
   - Validate caching behavior

### Phase 6: Documentation Updates
1. **Update project documentation:**
   - `docs/ci.md` - Explain new multi-pipeline structure
   - `README.md` - Update CircleCI setup instructions
   - `.circleci/README.md` (create) - Document pipeline organization

2. **Document for consumer projects:**
   - How installer handles multiple files
   - Which files are removed/kept for consumer projects
   - How to configure their own pipelines post-install

3. **Create maintenance guide:**
   - How to add new workflows
   - How to modify shared components
   - Testing guidelines for CI changes

### Phase 7: Installer Integration
1. **Update Vortex installer:**
   - Modify installer to handle multiple CircleCI files
   - Ensure Vortex-dev files are removed during installation
   - Preserve conditional markers and processing logic

2. **Test installer behavior:**
   - Run installer with various configurations
   - Verify correct files are generated for consumer projects
   - Ensure conditional blocks work across all files

3. **Update installer tests:**
   - Add test cases for multi-file scenarios
   - Verify file generation logic
   - Test Vortex-dev file removal

## Benefits

### Maintainability
- **Smaller files:** Each file focuses on single responsibility
- **Easier navigation:** Find relevant configuration quickly
- **Reduced conflicts:** Changes to different workflows don't collide

### Clarity
- **Purpose-driven organization:** File name indicates purpose
- **Separated concerns:** Consumer vs. Vortex development clearly split
- **Better documentation:** Each file documents its own trigger and purpose

### Flexibility
- **Independent triggers:** Each pipeline runs on appropriate events
- **Isolated testing:** Test workflows without affecting main pipeline
- **Easier debugging:** Identify which pipeline failed at a glance

### Consumer Experience
- **Cleaner projects:** Consumer projects don't receive Vortex-dev configs
- **Simpler customization:** Modify specific pipelines without touching others
- **Clear examples:** Each config file serves as standalone example

## Risks and Mitigation

### Risk 1: CircleCI GitHub App Requirement
**Impact:** Consumer projects must use GitHub App integration
**Mitigation:**
- Document requirement clearly in installation guide
- Provide migration guide for existing projects
- Consider maintaining single-file option as fallback (legacy mode)

### Risk 2: Increased Complexity
**Impact:** More files to manage and configure
**Mitigation:**
- Comprehensive documentation
- Clear naming conventions
- Automated validation in CI

### Risk 3: Shared Component Duplication
**Impact:** Aliases and steps may need duplication across files
**Mitigation:**
- Accept reasonable duplication for independence
- Document canonical definitions
- Use comments to track which components are shared

### Risk 4: Installer Compatibility
**Impact:** Installer must handle multiple files correctly
**Mitigation:**
- Thorough testing of installer changes
- Maintain backward compatibility during transition
- Provide migration scripts if needed

### Risk 5: Breaking Consumer Projects
**Impact:** Existing projects might break after update
**Mitigation:**
- Mark as breaking change in release notes
- Provide migration guide with manual steps
- Consider phased rollout or opt-in mechanism

## Success Criteria

1. **Functionality:**
   - All workflows continue to work as before
   - Job dependencies are preserved
   - Caching behavior remains consistent
   - Deployments succeed without changes

2. **Code Quality:**
   - Each config file is under 300 lines
   - Clear separation of concerns
   - No duplicate job definitions (shared via references)
   - Preserved Vortex template processing markers

3. **Documentation:**
   - Pipeline structure documented
   - Trigger events clearly explained
   - Consumer migration guide available
   - Maintenance guide created

4. **Testing:**
   - All existing tests pass
   - New pipeline-specific tests added
   - Installer tests validate multi-file handling

5. **Consumer Impact:**
   - Installation process documented
   - Consumer projects receive only relevant configs
   - Migration path for existing projects

## Open Questions

1. **Should we provide a single-file fallback for projects not using GitHub App?**
   - Consider maintaining both structures
   - Or require GitHub App as prerequisite

2. **How should shared aliases be handled?**
   - Duplicate in each file (independence)
   - Or create a shared config via CircleCI orb

3. **Should pipeline names be configurable?**
   - Allow consumers to customize pipeline names
   - Or enforce standard naming convention

4. **How to handle Vortex version upgrades?**
   - Update all config files in sync
   - Or version each file independently

5. **Should we extract orbs for common functionality?**
   - Create Vortex CircleCI orb for shared commands
   - Or keep everything in config files

## Timeline Estimate

- **Phase 1 (Preparation):** 2-4 hours
- **Phase 2 (Extract Shared):** 2-3 hours
- **Phase 3 (Split Files):** 4-6 hours
- **Phase 4 (Configure Pipelines):** 2-3 hours
- **Phase 5 (Testing):** 4-6 hours
- **Phase 6 (Documentation):** 3-4 hours
- **Phase 7 (Installer):** 4-6 hours

**Total Estimated Time:** 21-32 hours

## Next Steps

1. Review and approve this plan
2. Answer open questions
3. Create subtasks in GitHub issue
4. Begin Phase 1 implementation
5. Regular checkpoints after each phase

---

**Issue:** #1571
**Branch:** `feature/1571-split-circleci-config`
**Milestone:** 25.11.0
