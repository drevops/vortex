#!/usr/bin/env bats
##
# Unit tests for the 'vortex-push-db' script.
#
# The stub markers use single quotes on purpose: the `${...}` must reach the
# generated stub literally and expand when the stub runs - so SC2016 is
# suppressed here.
#
# shellcheck disable=SC2016,SC2030,SC2031

load ../_helper.bash

# Replaces each push script with a stub that names the destination it serves.
stub_push_scripts() {
  stub_sibling "vortex-push-db-ftp" 'Pushed to ftp with index [${VORTEX_DB_INDEX}].'
  stub_sibling "vortex-push-db-s3" 'Pushed to s3 with index [${VORTEX_DB_INDEX}].'
}

@test "push-db: Skip when the push is not requested" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  stub_push_scripts

  export VORTEX_PUSH_DB_DESTINATIONS="ftp"

  for proceed in "" 0; do
    export VORTEX_PUSH_DB_PROCEED="${proceed}"

    run .vortex/tooling/src/vortex-push-db
    assert_success
    assert_output_contains "[INFO] Started database push."
    assert_output_contains "[ OK ] Skipped database push as VORTEX_PUSH_DB_PROCEED is not set to 1."
    assert_output_not_contains "Pushed to"
    assert_output_not_contains "Finished database push."
  done

  popd >/dev/null
}

@test "push-db: Push to the store by default" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  stub_push_scripts

  export VORTEX_PUSH_DB_PROCEED=1
  export VORTEX_FETCH_DB_SOURCE="lagoon"
  unset VORTEX_PUSH_DB_DESTINATIONS

  for db_store in ftp s3; do
    export VORTEX_FETCH_DB_STORE="${db_store}"

    run .vortex/tooling/src/vortex-push-db
    assert_success
    assert_output_contains "Pushed to ${db_store} with index []."
    assert_output_contains "[ OK ] Finished database push."

    for other_store in ftp s3; do
      [ "${other_store}" = "${db_store}" ] && continue
      assert_output_not_contains "Pushed to ${other_store}"
    done
  done

  popd >/dev/null
}

@test "push-db: Push to every destination in the list" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  stub_push_scripts

  export VORTEX_PUSH_DB_PROCEED=1
  export VORTEX_FETCH_DB_SOURCE="lagoon"

  for destinations in "ftp,s3" "ftp s3" " ftp , s3 "; do
    export VORTEX_PUSH_DB_DESTINATIONS="${destinations}"

    run .vortex/tooling/src/vortex-push-db
    assert_success
    assert_output_contains "Pushing to the ftp destination."
    assert_output_contains "Pushed to ftp with index []."
    assert_output_contains "Pushing to the s3 destination."
    assert_output_contains "Pushed to s3 with index []."
    assert_output_contains "[ OK ] Finished database push."
  done

  popd >/dev/null
}

@test "push-db: Push to the store and another destination" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  stub_push_scripts

  export VORTEX_PUSH_DB_PROCEED=1
  export VORTEX_FETCH_DB_SOURCE="lagoon"
  export VORTEX_FETCH_DB_STORE="ftp"
  export VORTEX_PUSH_DB_DESTINATIONS="ftp,s3"

  run .vortex/tooling/src/vortex-push-db
  assert_success
  assert_output_contains "Pushed to s3 with index []."
  # The store listed as a destination too is pushed to once.
  assert_equal "1" "$(grep -c "Pushed to ftp with index \[\]." <<<"${output}")"

  popd >/dev/null
}

@test "push-db: Push an indexed database to its own destinations" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  stub_push_scripts

  export VORTEX_DB_INDEX=2
  export VORTEX_PUSH_DB_PROCEED=1
  export VORTEX_FETCH_DB2_SOURCE="url"
  export VORTEX_FETCH_DB2_STORE="s3"
  export VORTEX_FETCH_DB_STORE="ftp"
  export VORTEX_PUSH_DB_DESTINATIONS="ftp"
  # The indexed database is pushed as fetched, so the sanitization setting
  # does not apply to it.
  export VORTEX_PROVISION_SANITIZE_DB_SKIP=1

  run .vortex/tooling/src/vortex-push-db
  assert_success
  assert_output_contains "[INFO] Started database 2 push."
  assert_output_contains "Pushed to s3 with index [2]."
  assert_output_not_contains "Pushed to ftp"
  assert_output_contains "[ OK ] Finished database 2 push."

  export VORTEX_PUSH_DB2_DESTINATIONS="ftp"

  run .vortex/tooling/src/vortex-push-db
  assert_success
  assert_output_contains "Pushed to s3 with index [2]."
  assert_output_contains "Pushed to ftp with index [2]."

  popd >/dev/null
}

@test "push-db: Skip without destinations" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  stub_push_scripts

  export VORTEX_PUSH_DB_PROCEED=1
  unset VORTEX_PUSH_DB_DESTINATIONS VORTEX_FETCH_DB_STORE

  for db_source in url lagoon container_registry ftp s3; do
    export VORTEX_FETCH_DB_SOURCE="${db_source}"

    run .vortex/tooling/src/vortex-push-db
    assert_success
    assert_output_contains "[ OK ] Skipped database push as VORTEX_FETCH_DB_STORE and VORTEX_PUSH_DB_DESTINATIONS are empty."
    assert_output_not_contains "Pushed to"
    assert_output_not_contains "Finished database push."
  done

  popd >/dev/null
}

@test "push-db: Use the source from .env by default" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  stub_push_scripts

  unset VORTEX_FETCH_DB_SOURCE VORTEX_PUSH_DB_DESTINATIONS
  export VORTEX_PUSH_DB_PROCEED=1

  run .vortex/tooling/src/vortex-push-db
  assert_success
  assert_output_contains '[ OK ] Skipped database push as VORTEX_FETCH_DB_STORE and VORTEX_PUSH_DB_DESTINATIONS are empty.'
  assert_output_not_contains "Pushed to"

  popd >/dev/null
}

@test "push-db: Fail with an unsupported destination" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  stub_push_scripts

  export VORTEX_PUSH_DB_PROCEED=1
  export VORTEX_FETCH_DB_SOURCE="lagoon"
  export VORTEX_PUSH_DB_DESTINATIONS="ftp,gdrive"

  run .vortex/tooling/src/vortex-push-db
  assert_failure
  assert_output_contains '[FAIL] Unsupported database push destination "gdrive" in VORTEX_FETCH_DB_STORE or VORTEX_PUSH_DB_DESTINATIONS. Use one or more of: ftp, s3.'
  # Every destination is checked before the first push.
  assert_output_not_contains "Pushed to"

  popd >/dev/null
}

@test "push-db: Refuse to push to the fetch source" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  stub_push_scripts

  export VORTEX_PUSH_DB_PROCEED=1
  export VORTEX_FETCH_DB_SOURCE="ftp"
  export VORTEX_PUSH_DB_DESTINATIONS="s3,ftp"

  run .vortex/tooling/src/vortex-push-db
  assert_failure
  assert_output_contains '[FAIL] Refused to push the database to the "ftp" fetch source, as the push would replace the dump the database was fetched from.'
  assert_output_not_contains "Pushed to"

  popd >/dev/null
}

@test "push-db: Refuse to push a database that is not sanitized" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  stub_push_scripts

  export VORTEX_PUSH_DB_PROCEED=1
  export VORTEX_FETCH_DB_SOURCE="lagoon"
  export VORTEX_PUSH_DB_DESTINATIONS="ftp"
  export VORTEX_PROVISION_SANITIZE_DB_SKIP=1

  run .vortex/tooling/src/vortex-push-db
  assert_failure
  assert_output_contains "[FAIL] Refused to push the database as VORTEX_PROVISION_SANITIZE_DB_SKIP is set to 1 and the database dump is not sanitized."
  assert_output_not_contains "Pushed to"
  assert_output_not_contains "Finished database push."

  popd >/dev/null
}

@test "push-db: Stop at the first destination that fails" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  stub_push_scripts
  printf '#!/usr/bin/env bash\necho "Failed to push to ftp."\nexit 1\n' >.vortex/tooling/src/vortex-push-db-ftp
  chmod +x .vortex/tooling/src/vortex-push-db-ftp

  export VORTEX_PUSH_DB_PROCEED=1
  export VORTEX_FETCH_DB_SOURCE="lagoon"
  export VORTEX_PUSH_DB_DESTINATIONS="ftp,s3"

  run .vortex/tooling/src/vortex-push-db
  assert_failure
  assert_output_contains "Failed to push to ftp."
  assert_output_not_contains "Pushed to s3"
  assert_output_not_contains "Finished database push."

  popd >/dev/null
}
