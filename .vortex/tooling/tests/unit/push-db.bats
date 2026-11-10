#!/usr/bin/env bats
##
# Unit tests for the 'vortex-push-db' script.
#
# shellcheck disable=SC2030,SC2031

load ../_helper.bash

@test "push-db: Skip when the push is not requested" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  stub_sibling "vortex-push-db-gdrive" "Pushed to gdrive."

  export VORTEX_FETCH_DB_SOURCE="gdrive"

  for proceed in "" 0; do
    export VORTEX_PUSH_DB_PROCEED="${proceed}"

    run .vortex/tooling/src/vortex-push-db
    assert_success
    assert_output_contains "[INFO] Started database push."
    assert_output_contains "[ OK ] Skipped database push as VORTEX_PUSH_DB_PROCEED is not set to 1."
    assert_output_not_contains "Pushed to gdrive."
    assert_output_not_contains "Finished database push."
  done

  popd >/dev/null
}

@test "push-db: Route each dump store source to its push script" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  stub_sibling "vortex-push-db-gdrive" "Pushed to gdrive."
  stub_sibling "vortex-push-db-s3" "Pushed to s3."

  export VORTEX_PUSH_DB_PROCEED=1

  for db_source in gdrive s3; do
    export VORTEX_FETCH_DB_SOURCE="${db_source}"

    run .vortex/tooling/src/vortex-push-db
    assert_success
    assert_output_contains "Pushed to ${db_source}."
    assert_output_contains "[ OK ] Finished database push."

    for other_source in gdrive s3; do
      [ "${other_source}" = "${db_source}" ] && continue
      assert_output_not_contains "Pushed to ${other_source}."
    done
  done

  popd >/dev/null
}

@test "push-db: Skip for a source that does not accept a push" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  stub_sibling "vortex-push-db-gdrive" "Pushed to gdrive."
  stub_sibling "vortex-push-db-s3" "Pushed to s3."

  export VORTEX_PUSH_DB_PROCEED=1

  for db_source in url ftp acquia lagoon container_registry none; do
    export VORTEX_FETCH_DB_SOURCE="${db_source}"

    run .vortex/tooling/src/vortex-push-db
    assert_success
    assert_output_contains "[ OK ] Skipped database push as the \"${db_source}\" database source in VORTEX_FETCH_DB_SOURCE does not accept a push. Use one of: gdrive, s3."
    assert_output_not_contains "Pushed to"
    assert_output_not_contains "Finished database push."
  done

  popd >/dev/null
}

@test "push-db: Use the source from .env by default" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  stub_sibling "vortex-push-db-gdrive" "Pushed to gdrive."

  unset VORTEX_FETCH_DB_SOURCE
  export VORTEX_PUSH_DB_PROCEED=1

  run .vortex/tooling/src/vortex-push-db
  assert_success
  assert_output_contains '[ OK ] Skipped database push as the "url" database source in VORTEX_FETCH_DB_SOURCE does not accept a push. Use one of: gdrive, s3.'
  assert_output_not_contains "Pushed to gdrive."

  popd >/dev/null
}

@test "push-db: Refuse to push a database that is not sanitized" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  stub_sibling "vortex-push-db-gdrive" "Pushed to gdrive."

  export VORTEX_FETCH_DB_SOURCE="gdrive"
  export VORTEX_PUSH_DB_PROCEED=1
  export VORTEX_PROVISION_SANITIZE_DB_SKIP=1

  run .vortex/tooling/src/vortex-push-db
  assert_failure
  assert_output_contains "[FAIL] Refused to push the database as VORTEX_PROVISION_SANITIZE_DB_SKIP is set to 1 and the database dump is not sanitized."
  assert_output_not_contains "Pushed to gdrive."
  assert_output_not_contains "Finished database push."

  popd >/dev/null
}

@test "push-db: Fail when the push script fails" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  printf '#!/usr/bin/env bash\necho "Failed to push to s3."\nexit 1\n' >.vortex/tooling/src/vortex-push-db-s3
  chmod +x .vortex/tooling/src/vortex-push-db-s3

  export VORTEX_FETCH_DB_SOURCE="s3"
  export VORTEX_PUSH_DB_PROCEED=1

  run .vortex/tooling/src/vortex-push-db
  assert_failure
  assert_output_contains "Failed to push to s3."
  assert_output_not_contains "Finished database push."

  popd >/dev/null
}
