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

@test "push-db: Push to the dump store fetch source by default" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  stub_push_scripts

  export VORTEX_PUSH_DB_PROCEED=1
  export VORTEX_REFRESH_DB_SOURCE="lagoon"
  unset VORTEX_PUSH_DB_DESTINATIONS

  for db_source in ftp s3; do
    export VORTEX_FETCH_DB_SOURCE="${db_source}"

    run .vortex/tooling/src/vortex-push-db
    assert_success
    assert_output_contains "Pushed to ${db_source} with index []."
    assert_output_contains "[ OK ] Finished database push."

    for other_source in ftp s3; do
      [ "${other_source}" = "${db_source}" ] && continue
      assert_output_not_contains "Pushed to ${other_source}"
    done
  done

  popd >/dev/null
}

@test "push-db: Push to every destination in the list" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  stub_push_scripts

  export VORTEX_PUSH_DB_PROCEED=1
  export VORTEX_FETCH_DB_SOURCE="lagoon"
  unset VORTEX_REFRESH_DB_SOURCE

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

@test "push-db: Push to the dump store fetch source and another destination" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  stub_push_scripts

  export VORTEX_PUSH_DB_PROCEED=1
  export VORTEX_FETCH_DB_SOURCE="ftp"
  export VORTEX_REFRESH_DB_SOURCE="lagoon"
  export VORTEX_PUSH_DB_DESTINATIONS="ftp,s3"

  run .vortex/tooling/src/vortex-push-db
  assert_success
  assert_output_contains "Pushed to ftp with index []."
  assert_output_contains "Pushed to s3 with index []."

  popd >/dev/null
}

@test "push-db: Push an indexed database to its own destinations" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  stub_push_scripts

  export VORTEX_DB_INDEX=2
  export VORTEX_PUSH_DB_PROCEED=1
  export VORTEX_FETCH_DB2_SOURCE="url"
  export VORTEX_PUSH_DB2_DESTINATIONS="s3"
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

  popd >/dev/null
}

@test "push-db: Skip without destinations" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  stub_push_scripts

  export VORTEX_PUSH_DB_PROCEED=1
  unset VORTEX_PUSH_DB_DESTINATIONS

  # A fetch source that is not a dump store, or a dump store source with no
  # upstream source, gives no destination.
  for case in "url:lagoon" "lagoon:lagoon" "container_registry:lagoon" "ftp:" "s3:"; do
    export VORTEX_FETCH_DB_SOURCE="${case%%:*}"
    export VORTEX_REFRESH_DB_SOURCE="${case#*:}"

    run .vortex/tooling/src/vortex-push-db
    assert_success
    assert_output_contains "[ OK ] Skipped database push as VORTEX_PUSH_DB_DESTINATIONS is empty and the \"${case%%:*}\" fetch source is not a dump store."
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
  assert_output_contains '[ OK ] Skipped database push as VORTEX_PUSH_DB_DESTINATIONS is empty and the "url" fetch source is not a dump store.'
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
  assert_output_contains '[FAIL] Unsupported database push destination "gdrive" in VORTEX_PUSH_DB_DESTINATIONS. Use one or more of: ftp, s3.'
  # Every destination is checked before the first push.
  assert_output_not_contains "Pushed to"

  popd >/dev/null
}

@test "push-db: Refuse to push to the fetch source without an upstream source" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  stub_push_scripts

  export VORTEX_PUSH_DB_PROCEED=1
  export VORTEX_FETCH_DB_SOURCE="ftp"
  export VORTEX_REFRESH_DB_SOURCE=""
  export VORTEX_PUSH_DB_DESTINATIONS="s3,ftp"

  run .vortex/tooling/src/vortex-push-db
  assert_failure
  assert_output_contains '[FAIL] Refused to push the database to the "ftp" fetch source as VORTEX_REFRESH_DB_SOURCE is not set, so the push would replace the dump it fetched.'
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
