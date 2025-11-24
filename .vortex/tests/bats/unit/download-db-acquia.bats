#!/usr/bin/env bats
#
# Unit tests for download-db-acquia.sh
#
# shellcheck disable=SC2030,SC2031,SC2034

load ../_helper.bash

@test "download-db-acquia: Download database successfully" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Clean up any existing test files first to force full download workflow
  rm -rf .data
  mkdir -p .data

  declare -a STEPS=(
    # Assert initial message
    "[INFO] Started database dump download from Acquia."

    # Mock authentication token curl call with its message
    "Retrieving authentication token."
    '@curl -s -L https://accounts.acquia.com/api/auth/oauth/token --data-urlencode client_id=test-key --data-urlencode client_secret=test-secret --data-urlencode grant_type=client_credentials # {"access_token":"test-token", "expires_in":3600}'

    # Mock application UUID curl call with its message
    "Retrieving testapp application UUID."
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications?filter=name%3Dtestapp # {"_embedded":{"items":[{"uuid":"app-uuid-123","name":"testapp"}]}}'

    # Mock environment ID curl call with its message
    "Retrieving prod environment ID."
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Dprod # {"_embedded":{"items":[{"id":"env-id-456","name":"prod"}]}}'

    # Mock backups curl call with its message
    "Discovering latest backup ID for DB testdb."
    '@curl --progress-bar -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/environments/env-id-456/databases/testdb/backups?sort=created # {"_embedded":{"items":[{"id":"backup-id-789","completed":"2024-01-01T00:00:00+00:00"}]}}'

    # Mock backup URL curl call with its message
    "Discovering backup URL."
    '@curl --progress-bar -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/environments/env-id-456/databases/testdb/backups/backup-id-789/actions/download # {"url":"https://backup.example.com/db.sql.gz","expires":"2024-01-01T01:00:00+00:00"}'

    # Mock file download curl call with its message and side effect to create zipped archive
    "Downloading DB dump into file .data/testdb_backup_backup-id-789.sql.gz."
    '@curl --progress-bar -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://backup.example.com/db.sql.gz -o .data/testdb_backup_backup-id-789.sql.gz # 0 #  # echo "CREATE TABLE test (id INT);" | gzip > .data/testdb_backup_backup-id-789.sql.gz'

    # Mock gunzip operations with their message
    "Expanding DB file .data/testdb_backup_backup-id-789.sql.gz into .data/testdb_backup_backup-id-789.sql."
    "@gunzip -t .data/testdb_backup_backup-id-789.sql.gz # 0"
    "@gunzip -c .data/testdb_backup_backup-id-789.sql.gz # 0 # CREATE TABLE test (id INT);"

    # Mock mv operation with its message and side effect to create final file
    'Renaming file ".data/testdb_backup_backup-id-789.sql" to ".data/db.sql".'
    '@mv .data/testdb_backup_backup-id-789.sql .data/db.sql # 0 #  # echo "CREATE TABLE test (id INT);" > .data/db.sql'

    # Assert final success message
    "[ OK ] Finished database dump download from Acquia."
  )

  export VORTEX_ACQUIA_KEY="test-key"
  export VORTEX_ACQUIA_SECRET="test-secret"
  export VORTEX_ACQUIA_APP_NAME="testapp"
  export VORTEX_DB_DOWNLOAD_ENVIRONMENT="prod"
  export VORTEX_DB_DOWNLOAD_ACQUIA_DB_NAME="testdb"
  export VORTEX_DB_DIR=".data"
  export VORTEX_DB_FILE="db.sql"

  mocks="$(run_steps "setup")"
  run scripts/vortex/download-db-acquia.sh
  run_steps "assert" "${mocks}"

  assert_success

  # Verify the final database file exists and has content
  assert_file_exists ".data/db.sql"
  assert_file_contains ".data/db.sql" "CREATE TABLE test"

  # Clean up
  rm -rf .data

  popd >/dev/null
}

@test "download-db-acquia: Use cached uncompressed file when available" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Create existing uncompressed file
  mkdir -p .data
  echo "cached database content" >.data/testdb_backup_backup-id-123.sql

  declare -a STEPS=(
    # Assert initial message
    "[INFO] Started database dump download from Acquia."

    # Mock authentication token curl call with its message
    "Retrieving authentication token."
    '@curl -s -L https://accounts.acquia.com/api/auth/oauth/token --data-urlencode client_id=test-key --data-urlencode client_secret=test-secret --data-urlencode grant_type=client_credentials # {"access_token":"test-token","expires_in":3600}'

    # Mock application UUID curl call with its message
    "Retrieving testapp application UUID."
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications?filter=name%3Dtestapp # {"_embedded":{"items":[{"uuid":"app-uuid-123","name":"testapp"}]}}'

    # Mock environment ID curl call with its message
    "Retrieving prod environment ID."
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Dprod # {"_embedded":{"items":[{"id":"env-id-456","name":"prod"}]}}'

    # Mock backups curl call with its message
    "Discovering latest backup ID for DB testdb."
    '@curl --progress-bar -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/environments/env-id-456/databases/testdb/backups?sort=created # {"_embedded":{"items":[{"id":"backup-id-123","completed":"2024-01-01T00:00:00+00:00"}]}}'

    # Assert cached file found message
    'Found existing cached DB file ".data/testdb_backup_backup-id-123.sql" for DB "testdb".'

    # Mock mv operation with its message and side effect to create final file
    'Renaming file ".data/testdb_backup_backup-id-123.sql" to ".data/db.sql".'
    '@mv .data/testdb_backup_backup-id-123.sql .data/db.sql # 0 #  # echo "cached database content" > .data/db.sql'

    # Assert final success message
    "[ OK ] Finished database dump download from Acquia."
  )

  export VORTEX_ACQUIA_KEY="test-key"
  export VORTEX_ACQUIA_SECRET="test-secret"
  export VORTEX_ACQUIA_APP_NAME="testapp"
  export VORTEX_DB_DOWNLOAD_ENVIRONMENT="prod"
  export VORTEX_DB_DOWNLOAD_ACQUIA_DB_NAME="testdb"
  export VORTEX_DB_DIR=".data"
  export VORTEX_DB_FILE="db.sql"

  mocks="$(run_steps "setup")"
  run scripts/vortex/download-db-acquia.sh
  run_steps "assert" "${mocks}"

  assert_success

  # Clean up
  rm -f .data/testdb_backup_backup-id-123.sql .data/db.sql

  popd >/dev/null
}

@test "download-db-acquia: Use cached compressed file when available" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Create existing compressed file
  mkdir -p .data
  echo "compressed database content" | gzip >.data/testdb_backup_backup-id-456.sql.gz

  declare -a STEPS=(
    # Assert initial message
    "[INFO] Started database dump download from Acquia."

    # Mock authentication token curl call with its message
    "Retrieving authentication token."
    '@curl -s -L https://accounts.acquia.com/api/auth/oauth/token --data-urlencode client_id=test-key --data-urlencode client_secret=test-secret --data-urlencode grant_type=client_credentials # {"access_token":"test-token"}'

    # Mock application UUID curl call with its message
    "Retrieving testapp application UUID."
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications?filter=name%3Dtestapp # {"_embedded":{"items":[{"uuid":"app-uuid-123"}]}}'

    # Mock environment ID curl call with its message
    "Retrieving prod environment ID."
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Dprod # {"_embedded":{"items":[{"id":"env-id-456"}]}}'

    # Mock backups curl call with its message
    "Discovering latest backup ID for DB testdb."
    '@curl --progress-bar -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/environments/env-id-456/databases/testdb/backups?sort=created # {"_embedded":{"items":[{"id":"backup-id-456"}]}}'

    # Assert cached compressed file found message
    "[ OK ] Found existing cached gzipped DB file .data/testdb_backup_backup-id-456.sql.gz for DB testdb."

    # Mock gunzip operations with their message
    "Expanding DB file .data/testdb_backup_backup-id-456.sql.gz into .data/testdb_backup_backup-id-456.sql."
    "@gunzip -t .data/testdb_backup_backup-id-456.sql.gz # 0"
    "@gunzip -c .data/testdb_backup_backup-id-456.sql.gz # 0 # decompressed database content"

    # Mock mv operation with its message and side effect to create final file
    'Renaming file ".data/testdb_backup_backup-id-456.sql" to ".data/db.sql".'
    '@mv .data/testdb_backup_backup-id-456.sql .data/db.sql # 0 #  # echo "decompressed database content" > .data/db.sql'

    # Assert final success message
    "[ OK ] Finished database dump download from Acquia."
  )

  export VORTEX_ACQUIA_KEY="test-key"
  export VORTEX_ACQUIA_SECRET="test-secret"
  export VORTEX_ACQUIA_APP_NAME="testapp"
  export VORTEX_DB_DOWNLOAD_ENVIRONMENT="prod"
  export VORTEX_DB_DOWNLOAD_ACQUIA_DB_NAME="testdb"
  export VORTEX_DB_DIR=".data"
  export VORTEX_DB_FILE="db.sql"

  mocks="$(run_steps "setup")"
  run scripts/vortex/download-db-acquia.sh
  run_steps "assert" "${mocks}"

  assert_success

  # Clean up
  rm -f .data/testdb_backup_backup-id-456.sql .data/db.sql

  popd >/dev/null
}

@test "download-db-acquia: Use default values for optional variables" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    # Assert initial message
    "[INFO] Started database dump download from Acquia."

    # Mock authentication token curl call with its message
    "Retrieving authentication token."
    '@curl -s -L https://accounts.acquia.com/api/auth/oauth/token --data-urlencode client_id=test-key --data-urlencode client_secret=test-secret --data-urlencode grant_type=client_credentials # {"access_token":"test-token"}'

    # Mock application UUID curl call with its message
    "Retrieving testapp application UUID."
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications?filter=name%3Dtestapp # {"_embedded":{"items":[{"uuid":"app-uuid-123"}]}}'

    # Mock environment ID curl call with its message
    "Retrieving prod environment ID."
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Dprod # {"_embedded":{"items":[{"id":"env-id-456"}]}}'

    # Mock backups curl call with its message
    "Discovering latest backup ID for DB testdb."
    '@curl --progress-bar -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/environments/env-id-456/databases/testdb/backups?sort=created # {"_embedded":{"items":[{"id":"backup-id-789"}]}}'

    # Mock backup URL curl call with its message
    "Discovering backup URL."
    '@curl --progress-bar -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/environments/env-id-456/databases/testdb/backups/backup-id-789/actions/download # {"url":"https://backup.example.com/db.sql.gz"}'

    # Mock file download curl call with its message and side effect to create zipped archive
    "Downloading DB dump into file ./.data/testdb_backup_backup-id-789.sql.gz."
    '@curl --progress-bar -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://backup.example.com/db.sql.gz -o ./.data/testdb_backup_backup-id-789.sql.gz # 0 #  # mkdir -p ./.data && echo "database content" | gzip > ./.data/testdb_backup_backup-id-789.sql.gz'

    # Mock gunzip operations with their message
    "Expanding DB file ./.data/testdb_backup_backup-id-789.sql.gz into ./.data/testdb_backup_backup-id-789.sql."
    "@gunzip -t ./.data/testdb_backup_backup-id-789.sql.gz # 0"
    "@gunzip -c ./.data/testdb_backup_backup-id-789.sql.gz # 0 # database content"

    # Mock mv operation with its message and side effect to create final file
    'Renaming file "./.data/testdb_backup_backup-id-789.sql" to "./.data/db.sql".'
    '@mv ./.data/testdb_backup_backup-id-789.sql ./.data/db.sql # 0 #  # echo "database content" > ./.data/db.sql'

    # Assert final success message
    "[ OK ] Finished database dump download from Acquia."
  )

  export VORTEX_ACQUIA_KEY="test-key"
  export VORTEX_ACQUIA_SECRET="test-secret"
  export VORTEX_ACQUIA_APP_NAME="testapp"
  export VORTEX_DB_DOWNLOAD_ENVIRONMENT="prod"
  export VORTEX_DB_DOWNLOAD_ACQUIA_DB_NAME="testdb"
  # Don't set VORTEX_DB_DIR and VORTEX_DB_FILE to test defaults
  unset VORTEX_DB_DIR VORTEX_DB_FILE

  mocks="$(run_steps "setup")"
  run scripts/vortex/download-db-acquia.sh
  run_steps "assert" "${mocks}"

  assert_success

  popd >/dev/null
}

@test "download-db-acquia: Authentication failure with error response" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    # Assert initial message
    "[INFO] Started database dump download from Acquia."

    # Mock authentication token curl call with its message and error response
    "Retrieving authentication token."
    '@curl -s -L https://accounts.acquia.com/api/auth/oauth/token --data-urlencode client_id=invalid-key --data-urlencode client_secret=invalid-secret --data-urlencode grant_type=client_credentials # {"error":"invalid_client","error_description":"Client authentication failed"}'

    # Assert authentication failure message
    "[FAIL] Authentication failed. Check VORTEX_ACQUIA_KEY and VORTEX_ACQUIA_SECRET."
  )

  export VORTEX_ACQUIA_KEY="invalid-key"
  export VORTEX_ACQUIA_SECRET="invalid-secret"
  export VORTEX_ACQUIA_APP_NAME="testapp"
  export VORTEX_DB_DOWNLOAD_ENVIRONMENT="prod"
  export VORTEX_DB_DOWNLOAD_ACQUIA_DB_NAME="testdb"

  mocks="$(run_steps "setup")"
  run scripts/vortex/download-db-acquia.sh
  run_steps "assert" "${mocks}"

  assert_failure

  popd >/dev/null
}

@test "download-db-acquia: Application not found" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    # Assert initial message
    "[INFO] Started database dump download from Acquia."

    # Mock authentication token curl call with its message
    "Retrieving authentication token."
    '@curl -s -L https://accounts.acquia.com/api/auth/oauth/token --data-urlencode client_id=test-key --data-urlencode client_secret=test-secret --data-urlencode grant_type=client_credentials # {"access_token":"test-token","expires_in":3600}'

    # Mock application UUID curl call with its message and empty response
    "Retrieving nonexistent-app application UUID."
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications?filter=name%3Dnonexistent-app # {"_embedded":{"items":[]}}'

    # Assert application not found failure message
    "[FAIL] Application 'nonexistent-app' not found. Check application name and access permissions."
  )

  export VORTEX_ACQUIA_KEY="test-key"
  export VORTEX_ACQUIA_SECRET="test-secret"
  export VORTEX_ACQUIA_APP_NAME="nonexistent-app"
  export VORTEX_DB_DOWNLOAD_ENVIRONMENT="prod"
  export VORTEX_DB_DOWNLOAD_ACQUIA_DB_NAME="testdb"

  mocks="$(run_steps "setup")"
  run scripts/vortex/download-db-acquia.sh
  run_steps "assert" "${mocks}"

  assert_failure

  popd >/dev/null
}

@test "download-db-acquia: Environment not found" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    # Assert initial message
    "[INFO] Started database dump download from Acquia."

    # Mock authentication token curl call with its message
    "Retrieving authentication token."
    '@curl -s -L https://accounts.acquia.com/api/auth/oauth/token --data-urlencode client_id=test-key --data-urlencode client_secret=test-secret --data-urlencode grant_type=client_credentials # {"access_token":"test-token","expires_in":3600}'

    # Mock application UUID curl call with its message
    "Retrieving testapp application UUID."
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications?filter=name%3Dtestapp # {"_embedded":{"items":[{"uuid":"app-uuid-123","name":"testapp"}]}}'

    # Mock environment ID curl call with its message and empty response
    "Retrieving nonexistent-env environment ID."
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Dnonexistent-env # {"_embedded":{"items":[]}}'

    # Assert environment not found failure message
    "[FAIL] Environment 'nonexistent-env' not found in application 'testapp'. Check environment name."
  )

  export VORTEX_ACQUIA_KEY="test-key"
  export VORTEX_ACQUIA_SECRET="test-secret"
  export VORTEX_ACQUIA_APP_NAME="testapp"
  export VORTEX_DB_DOWNLOAD_ENVIRONMENT="nonexistent-env"
  export VORTEX_DB_DOWNLOAD_ACQUIA_DB_NAME="testdb"

  mocks="$(run_steps "setup")"
  run scripts/vortex/download-db-acquia.sh
  run_steps "assert" "${mocks}"

  assert_failure

  popd >/dev/null
}

@test "download-db-acquia: Database not found" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    # Assert initial message
    "[INFO] Started database dump download from Acquia."

    # Mock authentication token curl call with its message
    "Retrieving authentication token."
    '@curl -s -L https://accounts.acquia.com/api/auth/oauth/token --data-urlencode client_id=test-key --data-urlencode client_secret=test-secret --data-urlencode grant_type=client_credentials # {"access_token":"test-token","expires_in":3600}'

    # Mock application UUID curl call with its message
    "Retrieving testapp application UUID."
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications?filter=name%3Dtestapp # {"_embedded":{"items":[{"uuid":"app-uuid-123","name":"testapp"}]}}'

    # Mock environment ID curl call with its message
    "Retrieving prod environment ID."
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Dprod # {"_embedded":{"items":[{"id":"env-id-456","name":"prod"}]}}'

    # Mock backups curl call with its message and error response
    "Discovering latest backup ID for DB nonexistent-db."
    '@curl --progress-bar -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/environments/env-id-456/databases/nonexistent-db/backups?sort=created # {"error":"Database not found","message":"The specified database does not exist"}'

    # Assert database not found failure message
    "[FAIL] Database 'nonexistent-db' not found in environment 'prod'. Check database name."
  )

  export VORTEX_ACQUIA_KEY="test-key"
  export VORTEX_ACQUIA_SECRET="test-secret"
  export VORTEX_ACQUIA_APP_NAME="testapp"
  export VORTEX_DB_DOWNLOAD_ENVIRONMENT="prod"
  export VORTEX_DB_DOWNLOAD_ACQUIA_DB_NAME="nonexistent-db"

  mocks="$(run_steps "setup")"
  run scripts/vortex/download-db-acquia.sh
  run_steps "assert" "${mocks}"

  assert_failure

  popd >/dev/null
}

@test "download-db-acquia: No backups found for database" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    # Assert initial message
    "[INFO] Started database dump download from Acquia."

    # Mock authentication token curl call with its message
    "Retrieving authentication token."
    '@curl -s -L https://accounts.acquia.com/api/auth/oauth/token --data-urlencode client_id=test-key --data-urlencode client_secret=test-secret --data-urlencode grant_type=client_credentials # {"access_token":"test-token","expires_in":3600}'

    # Mock application UUID curl call with its message
    "Retrieving testapp application UUID."
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications?filter=name%3Dtestapp # {"_embedded":{"items":[{"uuid":"app-uuid-123","name":"testapp"}]}}'

    # Mock environment ID curl call with its message
    "Retrieving prod environment ID."
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Dprod # {"_embedded":{"items":[{"id":"env-id-456","name":"prod"}]}}'

    # Mock backups curl call with its message and empty response
    "Discovering latest backup ID for DB testdb."
    '@curl --progress-bar -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/environments/env-id-456/databases/testdb/backups?sort=created # {"_embedded":{"items":[]}}'

    # Assert no backups found failure message
    "[FAIL] No backups found for database 'testdb' in environment 'prod'. Try creating a backup first."
  )

  export VORTEX_ACQUIA_KEY="test-key"
  export VORTEX_ACQUIA_SECRET="test-secret"
  export VORTEX_ACQUIA_APP_NAME="testapp"
  export VORTEX_DB_DOWNLOAD_ENVIRONMENT="prod"
  export VORTEX_DB_DOWNLOAD_ACQUIA_DB_NAME="testdb"

  mocks="$(run_steps "setup")"
  run scripts/vortex/download-db-acquia.sh
  run_steps "assert" "${mocks}"

  assert_failure

  popd >/dev/null
}

@test "download-db-acquia: Create fresh backup when requested" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  # Clean up any existing test files
  rm -rf .data
  mkdir -p .data

  # Create .env.local with the fresh flag
  echo "VORTEX_DB_DOWNLOAD_FRESH=1" >.env.local

  declare -a STEPS=(
    "[INFO] Started database dump download from Acquia."

    # Authentication
    "[TASK] Retrieving authentication token."
    '@curl -s -L https://accounts.acquia.com/api/auth/oauth/token --data-urlencode client_id=test-key --data-urlencode client_secret=test-secret --data-urlencode grant_type=client_credentials # {"access_token":"test-token","expires_in":3600}'

    # Application UUID
    "[TASK] Retrieving testapp application UUID."
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications?filter=name%3Dtestapp # {"_embedded":{"items":[{"uuid":"app-uuid-123","name":"testapp"}]}}'

    # Environment ID
    "[TASK] Retrieving prod environment ID."
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Dprod # {"_embedded":{"items":[{"id":"env-id-456","name":"prod"}]}}'

    # Create backup
    "[TASK] Creating new database backup for testdb."
    '@curl -s -L -X POST -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/environments/env-id-456/databases/testdb/backups # {"_links":{"notification":{"href":"https://cloud.acquia.com/api/notifications/notification-uuid-123"}}}'

    # Wait for backup - mock status checks
    "[TASK] Waiting for backup to complete."
    '@sleep 10 # 0'
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/notifications/notification-uuid-123 # {"status":"in-progress"}'
    "       Backup in progress (10s elapsed)..."
    '@sleep 10 # 0'
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/notifications/notification-uuid-123 # {"status":"completed"}'
    "[ OK ] Backup completed successfully."
    "       Fresh backup will be downloaded."

    # Continue with normal download flow
    "[TASK] Discovering latest backup ID for DB testdb."
    '@curl --progress-bar -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/environments/env-id-456/databases/testdb/backups?sort=created # {"_embedded":{"items":[{"id":"backup-id-new-123","completed":"2024-01-02T00:00:00+00:00"}]}}'

    # Rest of download steps...
    "[TASK] Discovering backup URL."
    '@curl --progress-bar -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/environments/env-id-456/databases/testdb/backups/backup-id-new-123/actions/download # {"url":"https://backup.example.com/db-fresh.sql.gz"}'

    "[TASK] Downloading DB dump into file .data/testdb_backup_backup-id-new-123.sql.gz."
    '@curl --progress-bar -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://backup.example.com/db-fresh.sql.gz -o .data/testdb_backup_backup-id-new-123.sql.gz # 0 #  # echo "CREATE TABLE fresh (id INT);" | gzip > .data/testdb_backup_backup-id-new-123.sql.gz'

    "[TASK] Expanding DB file .data/testdb_backup_backup-id-new-123.sql.gz into .data/testdb_backup_backup-id-new-123.sql."
    "@gunzip -t .data/testdb_backup_backup-id-new-123.sql.gz # 0"
    "@gunzip -c .data/testdb_backup_backup-id-new-123.sql.gz # 0 # CREATE TABLE fresh (id INT);"

    '[TASK] Renaming file ".data/testdb_backup_backup-id-new-123.sql" to ".data/db.sql".'
    '@mv .data/testdb_backup_backup-id-new-123.sql .data/db.sql # 0 #  # echo "CREATE TABLE fresh (id INT);" > .data/db.sql'

    "[ OK ] Finished database dump download from Acquia."
  )

  export VORTEX_ACQUIA_KEY="test-key"
  export VORTEX_ACQUIA_SECRET="test-secret"
  export VORTEX_ACQUIA_APP_NAME="testapp"
  export VORTEX_DB_DOWNLOAD_ENVIRONMENT="prod"
  export VORTEX_DB_DOWNLOAD_ACQUIA_DB_NAME="testdb"
  export VORTEX_DB_DIR=".data"
  export VORTEX_DB_FILE="db.sql"
  export VORTEX_DB_DOWNLOAD_FRESH="1"

  mocks="$(run_steps "setup")"
  run scripts/vortex/download-db-acquia.sh
  run_steps "assert" "${mocks}"

  assert_success
  assert_file_exists ".data/db.sql"
  assert_file_contains ".data/db.sql" "CREATE TABLE fresh"

  rm -rf .data
  popd >/dev/null
}

@test "download-db-acquia: Fresh backup creation fails with API error" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    "[INFO] Started database dump download from Acquia."

    # Authentication
    "[TASK] Retrieving authentication token."
    '@curl -s -L https://accounts.acquia.com/api/auth/oauth/token --data-urlencode client_id=test-key --data-urlencode client_secret=test-secret --data-urlencode grant_type=client_credentials # {"access_token":"test-token","expires_in":3600}'

    # Application UUID
    "[TASK] Retrieving testapp application UUID."
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications?filter=name%3Dtestapp # {"_embedded":{"items":[{"uuid":"app-uuid-123","name":"testapp"}]}}'

    # Environment ID
    "[TASK] Retrieving prod environment ID."
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Dprod # {"_embedded":{"items":[{"id":"env-id-456","name":"prod"}]}}'

    # Create backup fails with error
    "[TASK] Creating new database backup for testdb."
    '@curl -s -L -X POST -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/environments/env-id-456/databases/testdb/backups # {"error":"insufficient_permissions","message":"Insufficient permissions to create backup"}'

    # Assert failure message
    "[FAIL] Failed to create backup for database 'testdb'."
  )

  export VORTEX_ACQUIA_KEY="test-key"
  export VORTEX_ACQUIA_SECRET="test-secret"
  export VORTEX_ACQUIA_APP_NAME="testapp"
  export VORTEX_DB_DOWNLOAD_ENVIRONMENT="prod"
  export VORTEX_DB_DOWNLOAD_ACQUIA_DB_NAME="testdb"
  export VORTEX_DB_DIR=".data"
  export VORTEX_DB_FILE="db.sql"
  export VORTEX_DB_DOWNLOAD_FRESH="1"

  mocks="$(run_steps "setup")"
  run scripts/vortex/download-db-acquia.sh
  run_steps "assert" "${mocks}"

  assert_failure

  popd >/dev/null
}

@test "download-db-acquia: Fresh backup creation fails - missing notification URL" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    "[INFO] Started database dump download from Acquia."

    # Authentication
    "[TASK] Retrieving authentication token."
    '@curl -s -L https://accounts.acquia.com/api/auth/oauth/token --data-urlencode client_id=test-key --data-urlencode client_secret=test-secret --data-urlencode grant_type=client_credentials # {"access_token":"test-token","expires_in":3600}'

    # Application UUID
    "[TASK] Retrieving testapp application UUID."
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications?filter=name%3Dtestapp # {"_embedded":{"items":[{"uuid":"app-uuid-123","name":"testapp"}]}}'

    # Environment ID
    "[TASK] Retrieving prod environment ID."
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Dprod # {"_embedded":{"items":[{"id":"env-id-456","name":"prod"}]}}'

    # Create backup succeeds but notification URL is empty
    "[TASK] Creating new database backup for testdb."
    '@curl -s -L -X POST -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/environments/env-id-456/databases/testdb/backups # {"_links":{"notification":{"href":""}}}'

    # Assert failure message
    "[FAIL] Unable to get notification URL for backup creation."
  )

  export VORTEX_ACQUIA_KEY="test-key"
  export VORTEX_ACQUIA_SECRET="test-secret"
  export VORTEX_ACQUIA_APP_NAME="testapp"
  export VORTEX_DB_DOWNLOAD_ENVIRONMENT="prod"
  export VORTEX_DB_DOWNLOAD_ACQUIA_DB_NAME="testdb"
  export VORTEX_DB_DIR=".data"
  export VORTEX_DB_FILE="db.sql"
  export VORTEX_DB_DOWNLOAD_FRESH="1"

  mocks="$(run_steps "setup")"
  run scripts/vortex/download-db-acquia.sh
  run_steps "assert" "${mocks}"

  assert_failure

  popd >/dev/null
}

@test "download-db-acquia: Fresh backup fails during creation" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    "[INFO] Started database dump download from Acquia."

    # Authentication
    "[TASK] Retrieving authentication token."
    '@curl -s -L https://accounts.acquia.com/api/auth/oauth/token --data-urlencode client_id=test-key --data-urlencode client_secret=test-secret --data-urlencode grant_type=client_credentials # {"access_token":"test-token","expires_in":3600}'

    # Application UUID
    "[TASK] Retrieving testapp application UUID."
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications?filter=name%3Dtestapp # {"_embedded":{"items":[{"uuid":"app-uuid-123","name":"testapp"}]}}'

    # Environment ID
    "[TASK] Retrieving prod environment ID."
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Dprod # {"_embedded":{"items":[{"id":"env-id-456","name":"prod"}]}}'

    # Create backup
    "[TASK] Creating new database backup for testdb."
    '@curl -s -L -X POST -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/environments/env-id-456/databases/testdb/backups # {"_links":{"notification":{"href":"https://cloud.acquia.com/api/notifications/notification-uuid-123"}}}'

    # Wait for backup - status check returns failed
    "[TASK] Waiting for backup to complete."
    '@sleep 10 # 0'
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/notifications/notification-uuid-123 # {"status":"failed","message":"Database backup failed due to insufficient disk space"}'

    # Assert failure message
    "[FAIL] Backup creation failed."
  )

  export VORTEX_ACQUIA_KEY="test-key"
  export VORTEX_ACQUIA_SECRET="test-secret"
  export VORTEX_ACQUIA_APP_NAME="testapp"
  export VORTEX_DB_DOWNLOAD_ENVIRONMENT="prod"
  export VORTEX_DB_DOWNLOAD_ACQUIA_DB_NAME="testdb"
  export VORTEX_DB_DIR=".data"
  export VORTEX_DB_FILE="db.sql"
  export VORTEX_DB_DOWNLOAD_FRESH="1"

  mocks="$(run_steps "setup")"
  run scripts/vortex/download-db-acquia.sh
  run_steps "assert" "${mocks}"

  assert_failure

  popd >/dev/null
}

@test "download-db-acquia: Fresh backup times out" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  declare -a STEPS=(
    "[INFO] Started database dump download from Acquia."

    # Authentication
    "[TASK] Retrieving authentication token."
    '@curl -s -L https://accounts.acquia.com/api/auth/oauth/token --data-urlencode client_id=test-key --data-urlencode client_secret=test-secret --data-urlencode grant_type=client_credentials # {"access_token":"test-token","expires_in":3600}'

    # Application UUID
    "[TASK] Retrieving testapp application UUID."
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications?filter=name%3Dtestapp # {"_embedded":{"items":[{"uuid":"app-uuid-123","name":"testapp"}]}}'

    # Environment ID
    "[TASK] Retrieving prod environment ID."
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Dprod # {"_embedded":{"items":[{"id":"env-id-456","name":"prod"}]}}'

    # Create backup
    "[TASK] Creating new database backup for testdb."
    '@curl -s -L -X POST -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/environments/env-id-456/databases/testdb/backups # {"_links":{"notification":{"href":"https://cloud.acquia.com/api/notifications/notification-uuid-123"}}}'

    # Wait for backup - keep returning in-progress until timeout
    "[TASK] Waiting for backup to complete."
    '@sleep 5 # 0'
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/notifications/notification-uuid-123 # {"status":"in-progress"}'
    "       Backup in progress (5s elapsed)..."
    '@sleep 5 # 0'
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/notifications/notification-uuid-123 # {"status":"in-progress"}'
    "       Backup in progress (10s elapsed)..."
    '@sleep 5 # 0'
    '@curl -s -L -H Accept: application/json, version=2 -H Authorization: Bearer test-token https://cloud.acquia.com/api/notifications/notification-uuid-123 # {"status":"in-progress"}'
    "       Backup in progress (15s elapsed)..."

    # Assert timeout failure message
    "[FAIL] Backup creation timed out after 15 seconds."
  )

  export VORTEX_ACQUIA_KEY="test-key"
  export VORTEX_ACQUIA_SECRET="test-secret"
  export VORTEX_ACQUIA_APP_NAME="testapp"
  export VORTEX_DB_DOWNLOAD_ENVIRONMENT="prod"
  export VORTEX_DB_DOWNLOAD_ACQUIA_DB_NAME="testdb"
  export VORTEX_DB_DIR=".data"
  export VORTEX_DB_FILE="db.sql"
  export VORTEX_DB_DOWNLOAD_FRESH="1"
  export VORTEX_DB_DOWNLOAD_ACQUIA_BACKUP_MAX_WAIT="15"
  export VORTEX_DB_DOWNLOAD_ACQUIA_BACKUP_WAIT_INTERVAL="5"

  mocks="$(run_steps "setup")"
  run scripts/vortex/download-db-acquia.sh
  run_steps "assert" "${mocks}"

  assert_failure

  popd >/dev/null
}
