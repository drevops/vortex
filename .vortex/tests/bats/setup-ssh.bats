#!/usr/bin/env bats
#
# Test for CircleCI lifecycle.
#
# shellcheck disable=SC2030,SC2031,SC2129,SC2155

load _helper.bash

@test "No VORTEX_SSH_PREFIX" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  setup_ssh_key_fixture

  run scripts/vortex/setup-ssh.sh
  assert_failure
  assert_output_contains "Missing the required VORTEX_SSH_PREFIX environment variable"

  popd >/dev/null
}

@test "SSH setup in not required" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  setup_ssh_key_fixture
  export VORTEX_SSH_PREFIX="TEST"
  export VORTEX_TEST_SSH_FILE=false

  run scripts/vortex/setup-ssh.sh
  assert_success
  assert_output_contains "Found variable VORTEX_TEST_SSH_FILE with value false."

  popd >/dev/null
}

@test "Default SSH Key, SSH Key missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  setup_ssh_key_fixture
  export VORTEX_SSH_PREFIX="TEST"
  local file=${HOME}/.ssh/id_rsa

  run scripts/vortex/setup-ssh.sh
  assert_failure

  assert_output_contains "Did not find fingerprint variable VORTEX_TEST_SSH_FINGERPRINT."
  assert_output_contains "Did not find a variable VORTEX_test_SSH_FILE. Using default value ${file}."
  assert_output_contains "SSH key file ${file} does not exist."

  popd >/dev/null
}

@test "Default SSH Key, SSH Key exists" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  setup_ssh_key_fixture
  provision_default_ssh_key
  export VORTEX_SSH_PREFIX="TEST"
  local file=${HOME}/.ssh/id_rsa

  declare -a STEPS=(
    "Did not find fingerprint variable VORTEX_TEST_SSH_FINGERPRINT."
    "Did not find a variable VORTEX_TEST_SSH_FILE. Using default value ${file}."
    "@ssh-add -l # ${file}"
    "SSH agent has ${file} key loaded."
  )
  mocks="$(run_steps "setup")"

  run scripts/vortex/setup-ssh.sh
  assert_success
  run_steps "assert" "${mocks[@]}"

  popd >/dev/null
}

@test "Use SSH Prefix, SSH Key with suffix, SSH Key exists" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1
  setup_ssh_key_fixture
  local suffix="TEST"
  provision_ssh_key_with_suffix ${suffix}
  export VORTEX_SSH_PREFIX="KEY_IDENTIFIER"
  export VORTEX_KEY_IDENTIFIER_SSH_FILE="${SSH_KEY_FIXTURE_DIR}/id_rsa_${suffix}"
  declare -a STEPS=(
    "Started SSH setup"
    "Found variable VORTEX_KEY_IDENTIFIER_SSH_FILE with value ${VORTEX_KEY_IDENTIFIER_SSH_FILE}."
    "Using SSH key file ${VORTEX_KEY_IDENTIFIER_SSH_FILE}."
    "@ssh-add -l # ${VORTEX_KEY_IDENTIFIER_SSH_FILE}"
    "SSH agent has ${VORTEX_KEY_IDENTIFIER_SSH_FILE} key loaded."
    "Finished SSH setup"
  )
  mocks="$(run_steps "setup")"
    run scripts/vortex/setup-ssh.sh
  assert_success
  run_steps "assert" "${mocks[@]}"

  popd >/dev/null
}

@test "Use SSH Fingerprint, No matching SSH Key, Cannot load to agent" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  setup_ssh_key_fixture
  export VORTEX_SSH_PREFIX="TEST"
  export VORTEX_TEST_SSH_FINGERPRINT="DOES_NOT_EXIST"

  run scripts/vortex/setup-ssh.sh
  assert_failure
  assert_output_contains "Found fingerprint variable VORTEX_TEST_SSH_FINGERPRINT with value ${VORTEX_TEST_SSH_FINGERPRINT}."
  assert_output_contains "Using fingerprint-based deploy key because fingerprint was provided."
  assert_output_contains "SSH key file ${HOME}/.ssh/id_rsa_${VORTEX_TEST_SSH_FINGERPRINT} does not exist."

  popd >/dev/null
}

@test "Use SSH Fingerprint, SSH Key provided" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  setup_ssh_key_fixture
  # Assert using fingerprint with ssh key
  export VORTEX_TEST_SSH_FINGERPRINT="TEST"
  provision_ssh_key_with_suffix ${VORTEX_TEST_SSH_FINGERPRINT}
  export VORTEX_SSH_PREFIX="TEST"
  local file="${SSH_KEY_FIXTURE_DIR}/id_rsa_${VORTEX_TEST_SSH_FINGERPRINT}"

  declare -a STEPS=(
    "Found fingerprint variable VORTEX_TEST_SSH_FINGERPRINT with value ${VORTEX_TEST_SSH_FINGERPRINT}."
    "Using fingerprint-based deploy key because fingerprint was provided."
    "Using SSH key file ${file}."
    "@ssh-add -l # ${file}"
    "SSH agent has ${file} key loaded."
  )
  mocks="$(run_steps "setup")"

  run scripts/vortex/setup-ssh.sh
  assert_success
  run_steps "assert" "${mocks[@]}"

  popd >/dev/null
}

@test "Loading SSH key to SSH Agent, Key exists, CI environment" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  setup_ssh_key_fixture

  # Assert does not have key loaded
  export VORTEX_SSH_PREFIX="IDENTIFIER"
  export VORTEX_IDENTIFIER_SSH_FINGERPRINT="TEST"
  provision_ssh_key_with_suffix ${VORTEX_IDENTIFIER_SSH_FINGERPRINT}
  export CI="1"
  local file="${SSH_KEY_FIXTURE_DIR}/id_rsa_${VORTEX_IDENTIFIER_SSH_FINGERPRINT}"

  declare -a STEPS=(
    "Found fingerprint variable VORTEX_${VORTEX_SSH_PREFIX}_SSH_FINGERPRINT with value ${VORTEX_IDENTIFIER_SSH_FINGERPRINT}."
    "Using fingerprint-based deploy key because fingerprint was provided."
    "Using SSH key file ${file}."
    "@ssh-add -l # The agent has no identities."
    "SSH agent does not have a required key loaded. Trying to load."
    "- SSH agent has ${file} key loaded."
    "@ssh-add -D"
    "@ssh-add ${file}"
    "@ssh-add -l # ${file}"
    "Disabling strict host key checking in CI."
    "Finished SSH setup."
  )
  mocks="$(run_steps "setup")"

  run scripts/vortex/setup-ssh.sh
  assert_success
  run_steps "assert" "${mocks[@]}"

  popd >/dev/null
}

@test "Key provided, MD5 Fingerprint, Key not found" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  setup_ssh_key_fixture
  local suffix="TEST"
  provision_ssh_key_with_suffix ${suffix}
  export VORTEX_SSH_PREFIX="TEST"
  export VORTEX_TEST_SSH_FINGERPRINT="$(ssh-keygen -l -E md5 -f "${SSH_KEY_FIXTURE_DIR}/id_rsa_${suffix}" | awk '{print $2}')"
  export VORTEX_TEST_SSH_FILE="${SSH_KEY_FIXTURE_DIR}/id_rsa_${suffix}"

  export CI=""
  local ssh_key_file="${VORTEX_TEST_SSH_FINGERPRINT//:/}"
  ssh_key_file="${HOME}/.ssh/id_rsa_${ssh_key_file//\"/}"

  declare -a STEPS=(
    "Found fingerprint variable VORTEX_TEST_SSH_FINGERPRINT with value ${VORTEX_TEST_SSH_FINGERPRINT}."
    "Found variable VORTEX_TEST_SSH_FILE with value ${VORTEX_TEST_SSH_FILE}."
    "Using fingerprint-based deploy key because fingerprint was provided."
    "SSH key file ${ssh_key_file} does not exist."
  )
  mocks="$(run_steps "setup")"

  run scripts/vortex/setup-ssh.sh
  assert_failure
  run_steps "assert" "${mocks[@]}"

  popd >/dev/null
}

@test "Key found, SHA256 fingerprint, Not CI environment" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  setup_ssh_key_fixture
  local suffix="TEST"
  provision_ssh_key_with_suffix ${suffix}
  export VORTEX_SSH_PREFIX="TEST"
  export VORTEX_TEST_SSH_FINGERPRINT="$(ssh-keygen -l -E sha256 -f "${SSH_KEY_FIXTURE_DIR}/id_rsa_${suffix}" | awk '{print $2}')"

  export CI=""
  local md5_fingerprint="$(ssh-keygen -l -E md5 -f "${SSH_KEY_FIXTURE_DIR}/id_rsa_${suffix}" | awk '{print $2}')"
  md5_fingerprint="${md5_fingerprint#MD5:}"
  local ssh_key_file="${md5_fingerprint//:/}"
  ssh_key_file="${HOME}/.ssh/id_rsa_${ssh_key_file//\"/}"
  local file="${SSH_KEY_FIXTURE_DIR}/id_rsa_${suffix}"

  declare -a STEPS=(
    "Found fingerprint variable VORTEX_TEST_SSH_FINGERPRINT with value ${VORTEX_TEST_SSH_FINGERPRINT}."
    "Using fingerprint-based deploy key because fingerprint was provided."
    "Searching for MD5 hash as fingerprint starts with SHA256."
    "Found matching existing key file ${file}."
    "SSH key file ${ssh_key_file} does not exist."
    "- Disabling strict host key checking in CI."
  )
  mocks="$(run_steps "setup")"

  run scripts/vortex/setup-ssh.sh
  assert_failure
  run_steps "assert" "${mocks[@]}"

  popd >/dev/null
}
