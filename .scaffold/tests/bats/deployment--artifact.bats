#!/usr/bin/env bats
#
# Test for CircleCI lifecycle.
#
# shellcheck disable=SC2030,SC2031,SC2129,SC2155

load _helper.bash
load _helper.deployment.bash


@test "Missing or Invalid DREVOPS_DEPLOY_TYPES" {
  substep "Swap to ${LOCAL_REPO_DIR}"
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export DREVOPS_DEPLOY_TYPES=""
  run ahoy deploy
  assert_failure

  assert_output_contains "Missing required value for DREVOPS_DEPLOY_TYPES. Must be a combination of comma-separated values (to support multiple deployments): code, docker, webhook, lagoon."

  export DREVOPS_DEPLOY_ALLOW_SKIP=0
  export DREVOPS_DEPLOY_TYPES="invalid"
  run ahoy deploy
  assert_failure
  assert_output_contains "No deployments found for: ${DREVOPS_DEPLOY_TYPES}."

  popd >/dev/null
}

@test "SSH Key Setup script" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1
  setup_ssh_key_fixture

  # Assert missing DREVOPS_SSH_PREFIX
  run scripts/drevops/setup-ssh.sh
  assert_failure
  assert_output_contains "Missing the required DREVOPS_SSH_PREFIX environment variable"

  # Assert using default SSH file
  export DREVOPS_SSH_PREFIX="test"
  local file=${HOME}/.ssh/id_rsa
  run scripts/drevops/setup-ssh.sh
  assert_failure
  assert_output_contains "Using default SSH file ${file}."
  assert_output_contains "SSH key file ${file} does not exist."

  ssh-keygen -t rsa -b 4096 -N "" -f "${SSH_KEY_FIXTURE_DIR}/id_rsa"
  export DREVOPS_SSH_PREFIX="test"
  local file=${HOME}/.ssh/id_rsa
  run scripts/drevops/setup-ssh.sh
  assert_success
  assert_output_contains "Using default SSH file ${file}."
  assert_output_contains "Using SSH key file ${file}."

  # Assert using fingerprint no ssh key
  export DREVOPS_SSH_PREFIX="test"
  export DREVOPS_test_SSH_FINGERPRINT="TEST"
  run scripts/drevops/setup-ssh.sh
  assert_failure
  assert_output_contains "Found variable DREVOPS_test_SSH_FINGERPRINT with value ${DREVOPS_test_SSH_FINGERPRINT}."
  assert_output_contains "Using fingerprint-based deploy key because fingerprint was provided."
  assert_output_contains "SSH key file ${HOME}/.ssh/id_rsa_${DREVOPS_test_SSH_FINGERPRINT} does not exist."

  # Assert using fingerprint with ssh key
  export DREVOPS_SSH_PREFIX="test"
  export DREVOPS_test_SSH_FINGERPRINT="TEST"
  ssh-keygen -t rsa -b 4096 -N "" -f "${SSH_KEY_FIXTURE_DIR}/id_rsa_${DREVOPS_test_SSH_FINGERPRINT}"
  local file="${SSH_KEY_FIXTURE_DIR}/id_rsa_${DREVOPS_test_SSH_FINGERPRINT}"
  declare -a STEPS=(
    "Found variable DREVOPS_test_SSH_FINGERPRINT with value ${DREVOPS_test_SSH_FINGERPRINT}."
    "Using fingerprint-based deploy key because fingerprint was provided."
    "Using SSH key file ${file}."
    "@ssh-add -l # ${file}"
    "SSH agent has ${file} key loaded."
  )
  mocks="$(run_steps "setup")"
  run scripts/drevops/setup-ssh.sh
  assert_success
  run_steps "assert" "${mocks[@]}"

  # Assert does not have key loaded
  export DREVOPS_SSH_PREFIX="test"
  export DREVOPS_test_SSH_FINGERPRINT="TEST"
  local file="${SSH_KEY_FIXTURE_DIR}/id_rsa_${DREVOPS_test_SSH_FINGERPRINT}"
  declare -a STEPS=(
    "Found variable DREVOPS_test_SSH_FINGERPRINT with value ${DREVOPS_test_SSH_FINGERPRINT}."
    "Using fingerprint-based deploy key because fingerprint was provided."
    "Using SSH key file ${file}."
    "@ssh-add -l # The agent has no identities."
    "SSH agent does not have a required key loaded. Trying to load."
    "- SSH agent has ${file} key loaded."
    "@ssh-add -D"
    "@ssh-add ${file}"
    "@ssh-add -l # ${file}"
    "Finished SSH setup."
  )
  mocks="$(run_steps "setup")"
  run scripts/drevops/setup-ssh.sh
  assert_success
  run_steps "assert" "${mocks[@]}"


  # Finding key with matching fingerprint.
  export DREVOPS_SSH_PREFIX="test"
  export DREVOPS_test_SSH_FINGERPRINT="$(ssh-keygen -l -E sha256 -f "${SSH_KEY_FIXTURE_DIR}/id_rsa_TEST" | awk '{print $2}')"
  local md5_fingerprint="$(ssh-keygen -l -E md5 -f "${SSH_KEY_FIXTURE_DIR}/id_rsa_TEST" | awk '{print $2}')"
  md5_fingerprint="${md5_fingerprint#MD5:}"
  local ssh_key_file="${md5_fingerprint//:/}"
  ssh_key_file="${HOME}/.ssh/id_rsa_${ssh_key_file//\"/}"
  local file="${SSH_KEY_FIXTURE_DIR}/id_rsa_TEST"
  declare -a STEPS=(
    "Found variable DREVOPS_test_SSH_FINGERPRINT with value ${DREVOPS_test_SSH_FINGERPRINT}."
    "Using fingerprint-based deploy key because fingerprint was provided."
    "Searching for MD5 hash as fingerprint starts with SHA256."
    "Found matching existing key file ${file}."
    "SSH key file ${ssh_key_file} does not exist."
  )
  mocks="$(run_steps "setup")"
  run scripts/drevops/setup-ssh.sh
  assert_failure
  run_steps "assert" "${mocks[@]}"

  popd >/dev/null
}
