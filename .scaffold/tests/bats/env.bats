#!/usr/bin/env bats
#
# Test for environment variables.
#
# Note on BAT's 'run' helper command: it does not support pipes in argument, so
# either `run bash -c "mycommand | grep mytext"` or input redirect
# `run grep mytext <(mycommand)` should be used.
#
# shellcheck disable=SC2030,SC2031,SC2129

load _helper.bash

@test "Environment variables availability" {
  run_installer_quiet

  # Prepare temp test script used to print env variables from the script.
  echo "#!/usr/bin/env bash" >test.sh && echo "printenv" >>test.sh && chmod 755 test.sh

  # Re-using existing 'log' and 'restart' commands to add our test commands.
  # This is due to complexity of modifying YAML using Bash.
  cp .ahoy.yml .ahoy.yml.bak
  replace_string_content "cmd: docker compose logs \"\$\@\"" "cmd: printenv" ".ahoy.yml"
  replace_string_content "cmd: docker compose restart \"\$\@\"" "cmd: ./test.sh" ".ahoy.yml"

  # Assert that .env does not contain test values.
  assert_file_not_contains ".env" "MY_CUSTOM_VAR"
  assert_file_not_contains ".env" "my_test_var1_value1"

  # Assert that test variable is not available in current host env.
  run grep MY_CUSTOM_VAR <(printenv)
  assert_failure

  # Assert that env variable is not available in Ahoy.
  run grep MY_CUSTOM_VAR <(ahoy logs)
  assert_failure

  # Assert that env variable is not available in script called by Ahoy.
  run grep MY_CUSTOM_VAR <(ahoy restart)
  assert_failure

  # Create env variable in current host environment.
  export MY_CUSTOM_VAR=my_custom_env_value

  # Assert that env variable is available on host env.
  run grep MY_CUSTOM_VAR <(printenv)
  assert_success
  assert_output_contains "my_custom_env_value"

  # Assert that env variable is available in Ahoy.
  run grep MY_CUSTOM_VAR <(ahoy logs)
  assert_success
  assert_output_contains "my_custom_env_value"

  # Assert that env variable is available in script called by Ahoy.
  run grep MY_CUSTOM_VAR <(ahoy restart)
  assert_success
  assert_output_contains "my_custom_env_value"

  # Add new value of the variable to .env file and re-run assertions.
  # The existing environment value should still be used.
  add_var_to_file .env "MY_CUSTOM_VAR" "my_custom_envfile_value"

  # Assert that env variable is available on host env.
  run grep MY_CUSTOM_VAR <(printenv)
  assert_success
  assert_output_contains "my_custom_env_value"

  # Assert that env variable is available in Ahoy.
  run grep MY_CUSTOM_VAR <(ahoy logs)
  assert_success
  assert_output_contains "my_custom_env_value"

  # Assert that env variable is available in script called by Ahoy.
  run grep MY_CUSTOM_VAR <(ahoy restart)
  assert_success
  assert_output_contains "my_custom_env_value"

  # Unset environment variable and assert that a variable from .env file is used.
  unset MY_CUSTOM_VAR

  # Assert that env variable is no longer available on host env.
  run grep MY_CUSTOM_VAR <(printenv)
  assert_failure

  # Assert that env variable is available in Ahoy because it is read from .env file.
  run grep MY_CUSTOM_VAR <(ahoy logs)
  assert_success
  assert_output_contains "my_custom_envfile_value"

  # Assert that env variable is available in script called by Ahoy.
  run grep MY_CUSTOM_VAR <(ahoy restart)
  assert_success
  assert_output_contains "my_custom_envfile_value"
}
