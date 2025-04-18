#!/usr/bin/env bats
#
# Test for setup-ssh.sh script.
#
# IMPORTANT! This test uses mocks for ssd-add, so do not try to assert
# the actual key presence in the agent.
#
# shellcheck disable=SC2030,SC2031,SC2129,SC2155

load _helper.bash

@test "No VORTEX_SSH_PREFIX => failure" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  setup_ssh_key_fixture

  run scripts/vortex/setup-ssh.sh
  assert_failure
  assert_output_contains "Missing the required VORTEX_SSH_PREFIX environment variable"

  popd >/dev/null
}

@test "SSH setup in not required => success" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  setup_ssh_key_fixture
  export VORTEX_SSH_PREFIX="TEST"
  export VORTEX_TEST_SSH_FILE=false

  run scripts/vortex/setup-ssh.sh
  assert_success
  assert_output_contains "Found variable VORTEX_TEST_SSH_FILE with value false."

  popd >/dev/null
}

@test "Default SSH Key, SSH Key missing => failure" {
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

@test "Default SSH Key, SSH Key exists => success" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  setup_ssh_key_fixture
  provision_default_ssh_key
  export VORTEX_SSH_PREFIX="TEST"
  local file=${HOME}/.ssh/id_rsa

  # Override the values that could be coming from the environment with defaults.
  export VORTEX_SSH_REMOVE_ALL_KEYS="0"
  export VORTEX_SSH_DISABLE_STRICT_HOST_KEY_CHECKING="0"

  declare -a STEPS=(
    "Did not find fingerprint variable VORTEX_TEST_SSH_FINGERPRINT."
    "Did not find a variable VORTEX_TEST_SSH_FILE. Using default value ${file}."
    "@ssh-add -l # ${file}"
    "SSH agent already has ${file} key loaded."
    "- Removing all keys from the SSH agent."
    "- Disabling strict host key checking."
  )
  mocks="$(run_steps "setup")"

  run scripts/vortex/setup-ssh.sh
  assert_success
  run_steps "assert" "${mocks[@]}"

  popd >/dev/null
}

@test "Use SSH Prefix, SSH Key with suffix, SSH Key exists => success" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  setup_ssh_key_fixture
  local suffix="TEST"
  provision_ssh_key_with_suffix "${suffix}"
  export VORTEX_SSH_PREFIX="KEY_IDENTIFIER"
  export VORTEX_KEY_IDENTIFIER_SSH_FILE="${SSH_KEY_FIXTURE_DIR}/id_rsa_${suffix}"

  # Override the values that could be coming from the environment with defaults.
  export VORTEX_SSH_REMOVE_ALL_KEYS="0"
  export VORTEX_SSH_DISABLE_STRICT_HOST_KEY_CHECKING="0"

  declare -a STEPS=(
    "Started SSH setup"
    "Found variable VORTEX_KEY_IDENTIFIER_SSH_FILE with value ${VORTEX_KEY_IDENTIFIER_SSH_FILE}."
    "Using SSH key file ${VORTEX_KEY_IDENTIFIER_SSH_FILE}."
    "@ssh-add -l # ${VORTEX_KEY_IDENTIFIER_SSH_FILE}"
    "SSH agent already has ${VORTEX_KEY_IDENTIFIER_SSH_FILE} key loaded."
    "- Removing all keys from the SSH agent."
    "- Disabling strict host key checking."
    "Finished SSH setup"
  )
  mocks="$(run_steps "setup")"
    run scripts/vortex/setup-ssh.sh
  assert_success
  run_steps "assert" "${mocks[@]}"

  popd >/dev/null
}

@test "Use SSH Fingerprint, No matching SSH Key, Cannot load to agent => failure" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  setup_ssh_key_fixture
  export VORTEX_SSH_PREFIX="TEST"
  export VORTEX_TEST_SSH_FINGERPRINT="DOES_NOT_EXIST"

  # Override the values that could be coming from the environment with defaults.
  export VORTEX_SSH_REMOVE_ALL_KEYS="0"
  export VORTEX_SSH_DISABLE_STRICT_HOST_KEY_CHECKING="0"

  run scripts/vortex/setup-ssh.sh
  assert_failure
  assert_output_contains "Found fingerprint variable VORTEX_TEST_SSH_FINGERPRINT with value ${VORTEX_TEST_SSH_FINGERPRINT}."
  assert_output_contains "Using fingerprint-based deploy key because fingerprint was provided."
  assert_output_contains "SSH key file ${HOME}/.ssh/id_rsa_${VORTEX_TEST_SSH_FINGERPRINT} does not exist."

  popd >/dev/null
}

@test "Use SSH Fingerprint, SSH Key provided => success" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  setup_ssh_key_fixture
  # Assert using fingerprint with ssh key
  export VORTEX_TEST_SSH_FINGERPRINT="TEST"
  provision_ssh_key_with_suffix ${VORTEX_TEST_SSH_FINGERPRINT}
  export VORTEX_SSH_PREFIX="TEST"
  local file="${SSH_KEY_FIXTURE_DIR}/id_rsa_${VORTEX_TEST_SSH_FINGERPRINT}"

  # Override the values that could be coming from the environment with defaults.
  export VORTEX_SSH_REMOVE_ALL_KEYS="0"
  export VORTEX_SSH_DISABLE_STRICT_HOST_KEY_CHECKING="0"

  declare -a STEPS=(
    "Found fingerprint variable VORTEX_TEST_SSH_FINGERPRINT with value ${VORTEX_TEST_SSH_FINGERPRINT}."
    "Using fingerprint-based deploy key because fingerprint was provided."
    "Using SSH key file ${file}."
    "@ssh-add -l # ${file}"
    "SSH agent already has ${file} key loaded."
    "- Removing all keys from the SSH agent."
    "- Disabling strict host key checking."
  )
  mocks="$(run_steps "setup")"

  run scripts/vortex/setup-ssh.sh
  assert_success
  run_steps "assert" "${mocks[@]}"

  popd >/dev/null
}

@test "Loading SSH key to SSH Agent, Key exists, No strict host checking => success" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  setup_ssh_key_fixture

  # Assert does not have key loaded
  export VORTEX_SSH_PREFIX="IDENTIFIER"
  export VORTEX_IDENTIFIER_SSH_FINGERPRINT="TEST"
  provision_ssh_key_with_suffix ${VORTEX_IDENTIFIER_SSH_FINGERPRINT}
  local file="${SSH_KEY_FIXTURE_DIR}/id_rsa_${VORTEX_IDENTIFIER_SSH_FINGERPRINT}"

  # Override the values that could be coming from the environment with defaults.
  export VORTEX_SSH_REMOVE_ALL_KEYS="0"
  # Disable strict host key checking.
  export VORTEX_SSH_DISABLE_STRICT_HOST_KEY_CHECKING="1"

  declare -a STEPS=(
    "Found fingerprint variable VORTEX_${VORTEX_SSH_PREFIX}_SSH_FINGERPRINT with value ${VORTEX_IDENTIFIER_SSH_FINGERPRINT}."
    "Using fingerprint-based deploy key because fingerprint was provided."
    "Using SSH key file ${file}."
    "@ssh-add -l # The agent has no identities."
    "SSH agent does not have a required key loaded. Trying to load."
    "- SSH agent already has ${file} key loaded."
    "- Removing all keys from the SSH agent."
    "@ssh-add ${file}"
    "@ssh-add -l # ${file}"
    "Disabling strict host key checking."
    "Finished SSH setup."
  )
  mocks="$(run_steps "setup")"

  run scripts/vortex/setup-ssh.sh
  assert_success
  run_steps "assert" "${mocks[@]}"

  popd >/dev/null
}

@test "Loading SSH key to SSH Agent, Key exists, Remove existing keys => success" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  setup_ssh_key_fixture

  # Assert does not have key loaded
  export VORTEX_SSH_PREFIX="IDENTIFIER"
  export VORTEX_IDENTIFIER_SSH_FINGERPRINT="TEST"
  provision_ssh_key_with_suffix ${VORTEX_IDENTIFIER_SSH_FINGERPRINT}
  local file="${SSH_KEY_FIXTURE_DIR}/id_rsa_${VORTEX_IDENTIFIER_SSH_FINGERPRINT}"

  # Override the values that could be coming from the environment with defaults.
  export VORTEX_SSH_DISABLE_STRICT_HOST_KEY_CHECKING="0"
  # Remove all keys from the SSH agent.
  export VORTEX_SSH_REMOVE_ALL_KEYS="1"

  declare -a STEPS=(
    "Found fingerprint variable VORTEX_${VORTEX_SSH_PREFIX}_SSH_FINGERPRINT with value ${VORTEX_IDENTIFIER_SSH_FINGERPRINT}."
    "Using fingerprint-based deploy key because fingerprint was provided."
    "Using SSH key file ${file}."
    "@ssh-add -l # The agent has no identities."
    "SSH agent does not have a required key loaded. Trying to load."
    "- SSH agent already has ${file} key loaded."
    "Removing all keys from the SSH agent."
    "@ssh-add -D"
    "@ssh-add ${file}"
    "@ssh-add -l # ${file}"
    "- Disabling strict host key checking."
    "Finished SSH setup."
  )
  mocks="$(run_steps "setup")"

  run scripts/vortex/setup-ssh.sh
  assert_success
  run_steps "assert" "${mocks[@]}"

  popd >/dev/null
}

@test "Key provided, MD5 Fingerprint, Key not found => failure" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  setup_ssh_key_fixture
  local suffix="TEST"
  provision_ssh_key_with_suffix "${suffix}"
  export VORTEX_SSH_PREFIX="TEST"
  export VORTEX_TEST_SSH_FINGERPRINT="$(ssh-keygen -l -E md5 -f "${SSH_KEY_FIXTURE_DIR}/id_rsa_${suffix}" | awk '{print $2}')"
  export VORTEX_TEST_SSH_FILE="${SSH_KEY_FIXTURE_DIR}/id_rsa_${suffix}"

  local ssh_key_file="${VORTEX_TEST_SSH_FINGERPRINT//:/}"
  ssh_key_file="${HOME}/.ssh/id_rsa_${ssh_key_file//\"/}"

  # Override the values that could be coming from the environment with defaults.
  export VORTEX_SSH_REMOVE_ALL_KEYS="0"
  export VORTEX_SSH_DISABLE_STRICT_HOST_KEY_CHECKING="0"

  declare -a STEPS=(
    "Found fingerprint variable VORTEX_TEST_SSH_FINGERPRINT with value ${VORTEX_TEST_SSH_FINGERPRINT}."
    "Found variable VORTEX_TEST_SSH_FILE with value ${VORTEX_TEST_SSH_FILE}."
    "Using fingerprint-based deploy key because fingerprint was provided."
    "SSH key file ${ssh_key_file} does not exist."
    "- Removing all keys from the SSH agent."
    "- Disabling strict host key checking."
  )
  mocks="$(run_steps "setup")"

  run scripts/vortex/setup-ssh.sh
  assert_failure
  run_steps "assert" "${mocks[@]}"

  popd >/dev/null
}

@test "Key provided, SHA256 fingerprint => failure" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  setup_ssh_key_fixture
  local suffix="TEST"
  provision_ssh_key_with_suffix "${suffix}"
  export VORTEX_SSH_PREFIX="TEST"
  export VORTEX_TEST_SSH_FINGERPRINT="$(ssh-keygen -l -E sha256 -f "${SSH_KEY_FIXTURE_DIR}/id_rsa_${suffix}" | awk '{print $2}')"

  local md5_fingerprint="$(ssh-keygen -l -E md5 -f "${SSH_KEY_FIXTURE_DIR}/id_rsa_${suffix}" | awk '{print $2}')"
  md5_fingerprint="${md5_fingerprint#MD5:}"
  local ssh_key_file="${md5_fingerprint//:/}"
  ssh_key_file="${HOME}/.ssh/id_rsa_${ssh_key_file//\"/}"
  local file="${SSH_KEY_FIXTURE_DIR}/id_rsa_${suffix}"

  # Override the values that could be coming from the environment with defaults.
  export VORTEX_SSH_REMOVE_ALL_KEYS="0"
  export VORTEX_SSH_DISABLE_STRICT_HOST_KEY_CHECKING="0"

  declare -a STEPS=(
    "Found fingerprint variable VORTEX_TEST_SSH_FINGERPRINT with value ${VORTEX_TEST_SSH_FINGERPRINT}."
    "Using fingerprint-based deploy key because fingerprint was provided."
    "Searching for MD5 hash as fingerprint starts with SHA256."
    "Found matching existing key file ${file}."
    "SSH key file ${ssh_key_file} does not exist."
    "- Removing all keys from the SSH agent."
    "- Disabling strict host key checking."
  )
  mocks="$(run_steps "setup")"

  run scripts/vortex/setup-ssh.sh
  assert_failure
  run_steps "assert" "${mocks[@]}"

  popd >/dev/null
}
