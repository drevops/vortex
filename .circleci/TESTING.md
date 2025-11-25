# Testing CircleCI Multiple Configuration Files

This document provides a comprehensive testing checklist for validating the split CircleCI configuration files.

## Pre-Testing Requirements

- [ ] CircleCI account connected to GitHub
- [ ] CircleCI GitHub App integration installed
- [ ] Test branch created (`feature/1571-split-circleci-config`)
- [ ] All config files committed to repository
- [ ] Access to CircleCI project settings

## Pipeline Configuration Testing

### 1. Configure Pipelines in CircleCI UI

For each pipeline, verify configuration:

#### build-test-deploy.yml
- [ ] Pipeline created with name: `Database, Build, Test and Deploy`
- [ ] Config path set to: `.circleci/build-test-deploy.yml`
- [ ] Trigger configured for: Push to all branches
- [ ] Trigger configured for: Push to all tags
- [ ] Environment variables accessible

#### database-nightly.yml
- [ ] Pipeline created with name: `Database - Nightly refresh`
- [ ] Config path set to: `.circleci/database-nightly.yml`
- [ ] Trigger configured for: Schedule `0 18 * * *`
- [ ] Target branch set to: `develop`
- [ ] SSH key for database downloads added

#### update-dependencies.yml
- [ ] Pipeline created with name: `Update dependencies`
- [ ] Config path set to: `.circleci/update-dependencies.yml`
- [ ] Trigger configured for: Schedule `5 11,23 * * *`
- [ ] Target branch set to: `develop`
- [ ] Manual trigger parameter configured
- [ ] RENOVATE_TOKEN environment variable set
- [ ] RENOVATE_REPOSITORIES environment variable set
- [ ] RENOVATE_GIT_AUTHOR environment variable set

#### vortex-test-postbuild.yml
- [ ] Pipeline created with name: `Vortex - Test (Post-build)`
- [ ] Config path set to: `.circleci/vortex-test-postbuild.yml`
- [ ] Trigger configured for: Push to all branches
- [ ] Trigger configured for: Push to all tags

#### vortex-test-didi-fi.yml
- [ ] Pipeline created with name: `Vortex - Test (DIDI from file)`
- [ ] Config path set to: `.circleci/vortex-test-didi-fi.yml`
- [ ] Trigger configured for: Push to all branches
- [ ] SSH key for database downloads added

#### vortex-test-didi-ii.yml
- [ ] Pipeline created with name: `Vortex - Test (DIDI from registry)`
- [ ] Config path set to: `.circleci/vortex-test-didi-ii.yml`
- [ ] Trigger configured for: Push to all branches
- [ ] SSH key for database downloads added

## Functional Testing

### 2. Test Main Pipeline (build-test-deploy.yml)

#### Push to Feature Branch
- [ ] Push commit to test branch
- [ ] Verify pipeline triggers automatically
- [ ] **database job:**
  - [ ] Starts automatically
  - [ ] Downloads database successfully
  - [ ] Caches database correctly
  - [ ] Exports database after download
- [ ] **build job:**
  - [ ] Waits for database job to complete
  - [ ] Validates Composer configuration
  - [ ] Restores database cache
  - [ ] Sets up remote Docker
  - [ ] Lints Dockerfiles with Hadolint
  - [ ] Lints Docker Compose with DCLint
  - [ ] Builds Docker stack
  - [ ] Installs dependencies
  - [ ] Validates Composer normalization
  - [ ] Runs all linters (PHPCS, PHPStan, Rector, PHPMD, Twig CS Fixer)
  - [ ] Provisions site successfully
  - [ ] Runs PHPUnit tests
  - [ ] Runs Behat tests
  - [ ] Stores test results
  - [ ] Stores artifacts
  - [ ] Uploads coverage to Codecov
- [ ] **deploy job:**
  - [ ] Does NOT run (feature branch not in allowed list)

#### Push to develop Branch
- [ ] Push commit to develop branch (or merge PR)
- [ ] Verify pipeline triggers automatically
- [ ] **database job:** Runs and completes
- [ ] **build job:** Runs and completes
- [ ] **deploy job:**
  - [ ] Starts after build completes
  - [ ] Checks deployment should not be skipped
  - [ ] Runs deployment script
  - [ ] Stores artifacts

#### Create and Push Tag
- [ ] Create semver tag (e.g., `1.0.0`)
- [ ] Push tag to repository
- [ ] Verify pipeline triggers for tag
- [ ] **database job:** Runs and completes
- [ ] **build job:** Runs and completes
- [ ] **deploy job:** Does NOT run (ignores branches)
- [ ] **deploy-tags job:**
  - [ ] Starts after build completes
  - [ ] Runs tag deployment
  - [ ] Stores artifacts

### 3. Test Database Nightly Pipeline (database-nightly.yml)

#### Manual Trigger Test
- [ ] Manually trigger pipeline (if supported)
- [ ] OR wait for scheduled run at 18:00 UTC
- [ ] **database-nightly job:**
  - [ ] Runs on develop branch only
  - [ ] Downloads fresh database (ignores fallback)
  - [ ] Uses fresh base image
  - [ ] Exports to container registry
  - [ ] Skips frontend build
  - [ ] Caches database correctly

### 4. Test Update Dependencies Pipeline (update-dependencies.yml)

#### Scheduled Trigger Test
- [ ] Wait for scheduled run at 11:05 or 23:05 UTC
- [ ] OR manually trigger via parameter
- [ ] **update-dependencies job:**
  - [ ] Checks RENOVATE_TOKEN is set
  - [ ] Checks RENOVATE_REPOSITORIES is set
  - [ ] Checks RENOVATE_GIT_AUTHOR is set
  - [ ] Validates Renovate configuration
  - [ ] Runs Renovate successfully

#### Manual Trigger Test
- [ ] Trigger pipeline with parameter `run_update_dependencies=true`
- [ ] Verify pipeline runs immediately
- [ ] Verify job completes successfully

### 5. Test Vortex Post-Build Pipeline (vortex-test-postbuild.yml)

#### Push to Any Branch
- [ ] Push commit to test branch
- [ ] Verify pipeline triggers
- [ ] **vortex-test-postbuild job:**
  - [ ] Installs Ahoy
  - [ ] Installs test dependencies
  - [ ] Runs post-build tests
  - [ ] Stores test results
  - [ ] Stores artifacts

### 6. Test Vortex DIDI-FI Pipeline (vortex-test-didi-fi.yml)

#### Push to Any Branch
- [ ] Push commit to test branch
- [ ] Verify pipeline triggers
- [ ] **vortex-test-didi-database-fi job:**
  - [ ] Downloads DB from URL
  - [ ] Creates DB image
  - [ ] Pushes image to registry
  - [ ] Uses custom cache key
- [ ] **vortex-test-didi-build-fi job:**
  - [ ] Waits for database job
  - [ ] Restores cache with custom key
  - [ ] Builds site with DIDI image
  - [ ] Provisions successfully
  - [ ] Stores results

### 7. Test Vortex DIDI-II Pipeline (vortex-test-didi-ii.yml)

#### Push to Any Branch
- [ ] Push commit to test branch
- [ ] Verify pipeline triggers
- [ ] **vortex-test-didi-database-ii job:**
  - [ ] Downloads DB from container registry
  - [ ] Creates DB image
  - [ ] Pushes image to registry
  - [ ] Uses custom cache key
- [ ] **vortex-test-didi-build-ii job:**
  - [ ] Waits for database job
  - [ ] Restores cache with custom key
  - [ ] Builds site with DIDI image
  - [ ] Provisions successfully
  - [ ] Stores results

## Regression Testing

### 8. Compare Against Original config.yml

#### Job Behavior
- [ ] All jobs from original config run correctly
- [ ] Job dependencies are preserved
- [ ] Conditional logic works as expected
- [ ] Environment variables are accessible

#### Caching
- [ ] Database cache keys match original
- [ ] Cache restore works correctly
- [ ] Cache save works correctly
- [ ] Fallback caching works

#### Artifacts
- [ ] Test results stored correctly
- [ ] Artifacts stored correctly
- [ ] Coverage reports uploaded

#### Timing
- [ ] Build times are similar to original
- [ ] No unexpected delays
- [ ] Parallelism works correctly

### 9. Cross-Pipeline Independence

- [ ] Pipelines run independently
- [ ] No cross-pipeline dependencies
- [ ] Multiple pipelines can run simultaneously
- [ ] Failures in one pipeline don't affect others

## Conditional Marker Testing

### 10. Verify Installer Processing

These tests verify that conditional markers will work correctly during installation:

#### !PROVISION_TYPE_PROFILE
- [ ] Lines/sections marked are present in files
- [ ] Will be correctly removed if profile provisioning is used

#### DEPLOYMENT
- [ ] Deploy jobs are present in build-test-deploy.yml
- [ ] Will be correctly removed if deployments are disabled

#### DEPS_UPDATE_PROVIDER_CI
- [ ] update-dependencies.yml exists
- [ ] Will be correctly removed if not using CI-based updates

#### VORTEX_DEV
- [ ] vortex-test-*.yml files exist
- [ ] Will be correctly removed during installation

#### TOOL_* Markers
- [ ] PHPCS linting steps present
- [ ] PHPStan linting steps present
- [ ] Rector linting steps present
- [ ] PHPMD linting steps present
- [ ] Behat test steps present
- [ ] PHPUnit test steps present

## Edge Cases and Error Handling

### 11. Error Scenarios

#### Missing Environment Variables
- [ ] Pipeline fails gracefully if RENOVATE_TOKEN missing
- [ ] Appropriate error messages displayed

#### SSH Key Issues
- [ ] Pipeline fails if SSH key not configured
- [ ] Error messages indicate missing key

#### Cache Miss
- [ ] Pipeline handles missing cache gracefully
- [ ] Fallback caching works correctly

#### Failed Tests
- [ ] Build continues even if linters fail (with ignore flags)
- [ ] Tests can be retried (Behat rerun)

## Performance Testing

### 12. Performance Validation

- [ ] Build times within acceptable range
- [ ] Parallel jobs utilize parallelism correctly
- [ ] Caching reduces build time significantly
- [ ] Multiple pipelines don't slow each other down

## Documentation Validation

### 13. Documentation Accuracy

- [ ] README.md accurately describes all pipelines
- [ ] Pipeline names match documentation
- [ ] Trigger configurations match documentation
- [ ] Environment variables list is complete
- [ ] Configuration steps are accurate

## Final Validation

### 14. Production Readiness

- [ ] All pipelines tested successfully
- [ ] No regressions from original config
- [ ] Documentation is complete and accurate
- [ ] Conditional markers preserved correctly
- [ ] Installer compatibility verified (Phase 7)

---

## Test Results Summary

| Test Section | Status | Notes |
|--------------|--------|-------|
| 1. Pipeline Configuration | ⬜ Pending | |
| 2. Main Pipeline | ⬜ Pending | |
| 3. Database Nightly | ⬜ Pending | |
| 4. Update Dependencies | ⬜ Pending | |
| 5. Vortex Post-Build | ⬜ Pending | |
| 6. Vortex DIDI-FI | ⬜ Pending | |
| 7. Vortex DIDI-II | ⬜ Pending | |
| 8. Regression Testing | ⬜ Pending | |
| 9. Cross-Pipeline | ⬜ Pending | |
| 10. Conditional Markers | ⬜ Pending | |
| 11. Error Scenarios | ⬜ Pending | |
| 12. Performance | ⬜ Pending | |
| 13. Documentation | ⬜ Pending | |
| 14. Production Readiness | ⬜ Pending | |

---

## Notes and Issues

Use this section to document any issues encountered during testing:

```
Date: _______________
Tester: _____________

Issues Found:
1.
2.
3.

Resolution:
1.
2.
3.
```
