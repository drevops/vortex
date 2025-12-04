#!/usr/bin/env bats
##
# Unit tests for update-vortex.sh
#
# shellcheck disable=SC2030,SC2031,SC2034

load ../_helper.bash

# Test helper functions - do not mock output formatters as they are defined in the script

@test "Environment variables loading and defaults work correctly" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  create_global_command_wrapper "curl"
  create_global_command_wrapper "php"

  export VORTEX_INSTALLER_URL_CACHE_BUST="1234567890"

  # Test default values when no environment variables are set.
  declare -a STEPS=(
    "@curl -fsSL https://www.vortextemplate.com/install?1234567890 -o installer.php # 0"
    "@php installer.php --no-interaction --uri=https://github.com/drevops/vortex.git\#stable # 0"
    "Using installer script from URL: https://www.vortextemplate.com/install"
    "Downloading installer to installer.php"
  )

  mocks="$(run_steps "setup")"
  run "${ROOT_DIR}/scripts/vortex/update-vortex.sh"
  run_steps "assert" "${mocks[@]}"

  assert_success

  popd >/dev/null || exit 1
}

@test "Custom template repository URI is used when provided as argument" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  create_global_command_wrapper "curl"
  create_global_command_wrapper "php"

  export VORTEX_INSTALLER_URL_CACHE_BUST="1234567890"

  declare -a STEPS=(
    "@curl -fsSL https://www.vortextemplate.com/install?1234567890 -o installer.php # 0"
    "@php installer.php --no-interaction --uri=https://github.com/custom/repo.git\#main # 0"
    "Using installer script from URL: https://www.vortextemplate.com/install"
    "Downloading installer to installer.php"
  )

  mocks="$(run_steps "setup")"
  run "${ROOT_DIR}/scripts/vortex/update-vortex.sh" "https://github.com/custom/repo.git#main"
  run_steps "assert" "${mocks[@]}"

  assert_success

  popd >/dev/null || exit 1
}

@test "Local installer path is used when VORTEX_INSTALLER_PATH is set and file exists" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  create_global_command_wrapper "curl"
  create_global_command_wrapper "php"

  # Create temporary installer file.
  local test_installer="${LOCAL_REPO_DIR}/test-installer.php"
  echo "<?php echo 'test installer';" >"${test_installer}"

  export VORTEX_INSTALLER_PATH="${test_installer}"

  declare -a STEPS=(
    "@php ${test_installer} --no-interaction --uri=https://github.com/drevops/vortex.git\#stable # 0"
    "Using installer script from local path: ${test_installer}"
  )

  mocks="$(run_steps "setup")"
  run "${ROOT_DIR}/scripts/vortex/update-vortex.sh"
  run_steps "assert" "${mocks[@]}"

  assert_success

  # Clean up.
  rm -f "${test_installer}"

  popd >/dev/null || exit 1
}

@test "Script fails when local installer path is set but file does not exist" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  create_global_command_wrapper "curl"
  create_global_command_wrapper "php"

  export VORTEX_INSTALLER_PATH="/nonexistent/path/installer.php"

  declare -a STEPS=(
    "Using installer script from local path: /nonexistent/path/installer.php"
    "[FAIL] Installer script not found at /nonexistent/path/installer.php"
  )

  mocks="$(run_steps "setup")"
  run "${ROOT_DIR}/scripts/vortex/update-vortex.sh"
  run_steps "assert" "${mocks[@]}"

  assert_failure

  popd >/dev/null || exit 1
}

@test "Script works with file:// URL for template repository" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  create_global_command_wrapper "curl"
  create_global_command_wrapper "php"

  export VORTEX_INSTALLER_URL_CACHE_BUST="1234567890"

  declare -a STEPS=(
    "@curl -fsSL https://www.vortextemplate.com/install?1234567890 -o installer.php # 0"
    "@php installer.php --no-interaction --uri=file:///local/path/to/vortex # 0"
    "Using installer script from URL: https://www.vortextemplate.com/install"
    "Downloading installer to installer.php"
  )

  mocks="$(run_steps "setup")"
  run "${ROOT_DIR}/scripts/vortex/update-vortex.sh" "file:///local/path/to/vortex"
  run_steps "assert" "${mocks[@]}"

  assert_success

  popd >/dev/null || exit 1
}

@test "Script works with local path for template repository" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  create_global_command_wrapper "curl"
  create_global_command_wrapper "php"

  export VORTEX_INSTALLER_URL_CACHE_BUST="1234567890"

  declare -a STEPS=(
    "@curl -fsSL https://www.vortextemplate.com/install?1234567890 -o installer.php # 0"
    "@php installer.php --no-interaction --uri=/local/path/to/vortex\#stable # 0"
    "Using installer script from URL: https://www.vortextemplate.com/install"
    "Downloading installer to installer.php"
  )

  mocks="$(run_steps "setup")"
  run "${ROOT_DIR}/scripts/vortex/update-vortex.sh" "/local/path/to/vortex#stable"
  run_steps "assert" "${mocks[@]}"

  assert_success

  popd >/dev/null || exit 1
}

@test "Script works with git SSH URL for template repository" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  create_global_command_wrapper "curl"
  create_global_command_wrapper "php"

  export VORTEX_INSTALLER_URL_CACHE_BUST="1234567890"

  declare -a STEPS=(
    "@curl -fsSL https://www.vortextemplate.com/install?1234567890 -o installer.php # 0"
    "@php installer.php --no-interaction --uri=git@github.com:drevops/vortex.git\#v1.2.3 # 0"
    "Using installer script from URL: https://www.vortextemplate.com/install"
    "Downloading installer to installer.php"
  )

  mocks="$(run_steps "setup")"
  run "${ROOT_DIR}/scripts/vortex/update-vortex.sh" "git@github.com:drevops/vortex.git#v1.2.3"
  run_steps "assert" "${mocks[@]}"

  assert_success

  popd >/dev/null || exit 1
}

@test "Script fails gracefully when PHP installer execution fails" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  create_global_command_wrapper "curl"
  create_global_command_wrapper "php"

  export VORTEX_INSTALLER_URL_CACHE_BUST="1234567890"

  declare -a STEPS=(
    "@curl -fsSL https://www.vortextemplate.com/install?1234567890 -o installer.php # 0"
    "@php installer.php --no-interaction --uri=https://github.com/drevops/vortex.git\#stable # 1"
    "Using installer script from URL: https://www.vortextemplate.com/install"
    "Downloading installer to installer.php"
  )

  mocks="$(run_steps "setup")"
  run "${ROOT_DIR}/scripts/vortex/update-vortex.sh"
  run_steps "assert" "${mocks[@]}"

  assert_failure

  popd >/dev/null || exit 1
}

@test "Script runs in interactive mode when --interactive flag is provided" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  create_global_command_wrapper "curl"
  create_global_command_wrapper "php"

  export VORTEX_INSTALLER_URL_CACHE_BUST="1234567890"

  declare -a STEPS=(
    "@curl -fsSL https://www.vortextemplate.com/install?1234567890 -o installer.php # 0"
    "@php installer.php --uri=https://github.com/drevops/vortex.git\#stable # 0"
    "Using installer script from URL: https://www.vortextemplate.com/install"
    "Downloading installer to installer.php"
  )

  mocks="$(run_steps "setup")"
  run "${ROOT_DIR}/scripts/vortex/update-vortex.sh" --interactive
  run_steps "assert" "${mocks[@]}"

  assert_success

  popd >/dev/null || exit 1
}

@test "Script runs in interactive mode with custom template repository" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  create_global_command_wrapper "curl"
  create_global_command_wrapper "php"

  export VORTEX_INSTALLER_URL_CACHE_BUST="1234567890"

  declare -a STEPS=(
    "@curl -fsSL https://www.vortextemplate.com/install?1234567890 -o installer.php # 0"
    "@php installer.php --uri=https://github.com/custom/repo.git\#main # 0"
    "Using installer script from URL: https://www.vortextemplate.com/install"
    "Downloading installer to installer.php"
  )

  mocks="$(run_steps "setup")"
  run "${ROOT_DIR}/scripts/vortex/update-vortex.sh" --interactive https://github.com/custom/repo.git#main
  run_steps "assert" "${mocks[@]}"

  assert_success

  popd >/dev/null || exit 1
}
