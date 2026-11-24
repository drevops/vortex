#!/usr/bin/env bats
##
# Unit tests for the 'vortex-remove-environment-lagoon' script.
#
#shellcheck disable=SC2030,SC2031,SC2034

load ../_helper.bash

setup_payload() {
  export VORTEX_REMOVE_ENVIRONMENT_LAGOON_WEBHOOK_TOKEN="test-token"
  export VORTEX_REMOVE_ENVIRONMENT_LAGOON_WEBHOOK_ENDPOINT="https://webhook.example.com/"
  export VORTEX_REMOVE_ENVIRONMENT_LAGOON_PAYLOAD_FILE="${BUILD_DIR}/event.json"
  export VORTEX_REMOVE_ENVIRONMENT_LAGOON_DELIVERY_ID="test-delivery"
  echo '{"action":"closed","number":123}' >"${VORTEX_REMOVE_ENVIRONMENT_LAGOON_PAYLOAD_FILE}"

  mock_openssl=$(mock_command "openssl")
  mock_set_output "${mock_openssl}" "HMAC-SHA2-256(event.json)= test-signature"
}

@test "Remove environment Lagoon: missing token" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  setup_payload
  unset VORTEX_REMOVE_ENVIRONMENT_LAGOON_WEBHOOK_TOKEN

  run .vortex/tooling/src/vortex-remove-environment-lagoon
  assert_failure
  assert_output_contains "Missing required value for VORTEX_REMOVE_ENVIRONMENT_LAGOON_WEBHOOK_TOKEN."

  popd >/dev/null || exit 1
}

@test "Remove environment Lagoon: missing payload file value" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  setup_payload
  unset VORTEX_REMOVE_ENVIRONMENT_LAGOON_PAYLOAD_FILE
  unset GITHUB_EVENT_PATH

  run .vortex/tooling/src/vortex-remove-environment-lagoon
  assert_failure
  assert_output_contains "Missing required value for VORTEX_REMOVE_ENVIRONMENT_LAGOON_PAYLOAD_FILE."

  popd >/dev/null || exit 1
}

@test "Remove environment Lagoon: payload file does not exist" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  setup_payload
  export VORTEX_REMOVE_ENVIRONMENT_LAGOON_PAYLOAD_FILE="${BUILD_DIR}/missing.json"

  run .vortex/tooling/src/vortex-remove-environment-lagoon
  assert_failure
  assert_output_contains "Payload file ${BUILD_DIR}/missing.json does not exist."

  popd >/dev/null || exit 1
}

@test "Remove environment Lagoon: environment removed" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  setup_payload
  mock_curl=$(mock_command "curl")
  mock_set_output "${mock_curl}" '{"message":"removing pr-123"}'

  run .vortex/tooling/src/vortex-remove-environment-lagoon
  assert_success
  assert_output_contains "Started Lagoon environment removal."
  assert_output_contains 'Lagoon response: {"message":"removing pr-123"}'
  assert_output_contains "Called Lagoon webhook to remove the environment."
  assert_output_contains "Finished Lagoon environment removal."

  assert_equal "2" "$(mock_get_call_num "${mock_openssl}")"
  assert_equal "1" "$(mock_get_call_num "${mock_curl}")"
  curl_args="$(mock_get_call_args "${mock_curl}" 1)"
  assert_string_contains "${curl_args}" "X-GitHub-Delivery: test-delivery"
  assert_string_contains "${curl_args}" "X-Hub-Signature-256: sha256=test-signature"
  assert_string_contains "${curl_args}" "X-Hub-Signature: sha1=test-signature"
  assert_string_contains "${curl_args}" "https://webhook.example.com/"

  popd >/dev/null || exit 1
}

@test "Remove environment Lagoon: payload file from GITHUB_EVENT_PATH and default endpoint" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  setup_payload
  export GITHUB_EVENT_PATH="${VORTEX_REMOVE_ENVIRONMENT_LAGOON_PAYLOAD_FILE}"
  unset VORTEX_REMOVE_ENVIRONMENT_LAGOON_PAYLOAD_FILE
  unset VORTEX_REMOVE_ENVIRONMENT_LAGOON_WEBHOOK_ENDPOINT
  mock_curl=$(mock_command "curl")
  mock_set_output "${mock_curl}" '{"message":"removing pr-123"}'

  run .vortex/tooling/src/vortex-remove-environment-lagoon
  assert_success
  assert_output_contains "Called Lagoon webhook to remove the environment."

  curl_args="$(mock_get_call_args "${mock_curl}" 1)"
  assert_string_contains "${curl_args}" "@${GITHUB_EVENT_PATH}"
  assert_string_contains "${curl_args}" "https://webhook.amazeeio.cloud/"

  popd >/dev/null || exit 1
}

@test "Remove environment Lagoon: no environment to remove" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  setup_payload
  mock_curl=$(mock_command "curl")
  mock_set_output "${mock_curl}" '{"error":"invalid resquest payload"}'

  run .vortex/tooling/src/vortex-remove-environment-lagoon
  assert_success
  assert_output_contains "Lagoon removed no environment."
  assert_output_contains "Finished Lagoon environment removal."
  assert_output_not_contains "Called Lagoon webhook to remove the environment."

  popd >/dev/null || exit 1
}

@test "Remove environment Lagoon: webhook rejected" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  setup_payload
  mock_curl=$(mock_command "curl")
  mock_set_output "${mock_curl}" '{"error":"signature mismatch"}'

  run .vortex/tooling/src/vortex-remove-environment-lagoon
  assert_failure
  assert_output_contains 'Lagoon response: {"error":"signature mismatch"}'
  assert_output_contains "Lagoon rejected the environment removal."
  assert_output_not_contains "Finished Lagoon environment removal."

  popd >/dev/null || exit 1
}

@test "Remove environment Lagoon: webhook call failed" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  setup_payload
  mock_curl=$(mock_command "curl")
  mock_set_output "${mock_curl}" "Internal Server Error"
  mock_set_status "${mock_curl}" 22

  run .vortex/tooling/src/vortex-remove-environment-lagoon
  assert_failure
  assert_output_contains "Lagoon response: Internal Server Error"
  assert_output_contains "Unable to call Lagoon webhook."
  assert_output_not_contains "Finished Lagoon environment removal."

  popd >/dev/null || exit 1
}
