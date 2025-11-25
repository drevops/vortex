# Shared Components Across CircleCI Configs

This document identifies shared components and their usage across the split configuration files.

## Aliases (All Files Need These)

### 1. **db_ssh_fingerprint** (lines 16-20)
- **Used by:** build-test-deploy.yml, database-nightly.yml, vortex-test-didi-*.yml
- **Conditional:** !PROVISION_TYPE_PROFILE
- **Purpose:** SSH key for database downloads
```yaml
- &db_ssh_fingerprint "SHA256:6d+U5QubT0eAWz+4N2wt+WM2qx6o4cvyvQ6xILETJ84"
```

### 2. **deploy_ssh_fingerprint** (lines 22-24)
- **Used by:** build-test-deploy.yml
- **Purpose:** SSH key for deployments
```yaml
- &deploy_ssh_fingerprint "SHA256:6d+U5QubT0eAWz+4N2wt+WM2qx6o4cvyvQ6xILETJ84"
```

### 3. **nightly_db_schedule** (lines 26-29)
- **Used by:** database-nightly.yml
- **Conditional:** !PROVISION_TYPE_PROFILE
- **Purpose:** Cron schedule for nightly DB refresh
```yaml
- &nightly_db_schedule "0 18 * * *"
```

### 4. **runner_config** (lines 31-83)
- **Used by:** ALL config files (universal)
- **Purpose:** Shared runner container configuration
- **Contains:**
  - working_directory alias
  - Environment variables (DB cache, test results, artifacts)
  - Docker image and auth
  - Resource class

### 5. **test_results** anchor (line 69)
- **Used by:** build-test-deploy.yml, vortex-test-*.yml
- **Purpose:** Test results directory path
- **Embedded in:** runner_config environment

### 6. **artifacts** anchor (line 71)
- **Used by:** build-test-deploy.yml, vortex-test-*.yml
- **Purpose:** Artifacts directory path
- **Embedded in:** runner_config environment

### 7. **working_directory** anchor (line 33)
- **Used by:** ALL config files
- **Purpose:** Project working directory
- **Embedded in:** runner_config

### 8. **step_setup_remote_docker** (lines 85-91)
- **Used by:** build-test-deploy.yml, database-nightly.yml, vortex-test-*.yml
- **Purpose:** Setup remote docker with layer caching

### 9. **step_process_codebase_for_ci** (lines 93-98)
- **Used by:** build-test-deploy.yml, database-nightly.yml, vortex-test-postbuild.yml
- **Purpose:** Process docker-compose.yml for CI

### 10. **load_variables_from_dotenv** (lines 100-104)
- **Used by:** build-test-deploy.yml, database-nightly.yml, vortex-test-postbuild.yml
- **Purpose:** Load .env variables into bash environment

## Parameters

### run_update_dependencies (lines 110-113)
- **Used by:** update-dependencies.yml only
- **Purpose:** Manual trigger for dependency updates

## File-Specific Component Mapping

### build-test-deploy.yml
**Needs:**
- db_ssh_fingerprint (conditional)
- deploy_ssh_fingerprint
- runner_config (with all embedded anchors)
- step_setup_remote_docker
- step_process_codebase_for_ci
- load_variables_from_dotenv

### database-nightly.yml
**Needs:**
- db_ssh_fingerprint
- nightly_db_schedule
- runner_config (with all embedded anchors)
- step_setup_remote_docker
- step_process_codebase_for_ci
- load_variables_from_dotenv

### update-dependencies.yml
**Needs:**
- parameters: run_update_dependencies
- NO other shared components (uses different docker image)

### vortex-test-postbuild.yml
**Needs:**
- runner_config (with all embedded anchors)
- step_setup_remote_docker
- step_process_codebase_for_ci
- load_variables_from_dotenv

### vortex-test-didi-fi.yml
**Needs:**
- db_ssh_fingerprint (for database job)
- runner_config (with all embedded anchors)
- step_setup_remote_docker
- step_process_codebase_for_ci
- load_variables_from_dotenv

### vortex-test-didi-ii.yml
**Needs:**
- db_ssh_fingerprint (for database job)
- runner_config (with all embedded anchors)
- step_setup_remote_docker
- step_process_codebase_for_ci
- load_variables_from_dotenv

## Strategy

Each config file will include its own copy of required aliases. This approach:
- ✅ Maintains file independence
- ✅ No cross-file dependencies
- ✅ Each file is self-contained and complete
- ✅ Easier to understand and modify
- ⚠️ Some duplication (acceptable trade-off)

## Consistency Notes

1. All aliases must be identical across files
2. runner_config version (drevops/ci-runner:25.10.0) must match
3. Conditional markers (#;<, #;>) must be preserved in all files
4. Comments should be consistent across files
