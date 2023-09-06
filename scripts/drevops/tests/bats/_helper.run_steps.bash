#!/usr/bin/env bash

set -e
set -u

################################################################################
# Setup and process a sequence of string and mocked command assertions.
#
# Global variables:
# - STEPS: An array holding the steps to be processed.
# - RUN_STEPS_DEBUG: A boolean flag to enable debug output.
#
# Parameters:
# 1. Phase: Either "setup" or "assert". Defaults to "assert".
# 2. Mocked Commands (optional for 'setup', required for 'assert' phase):
#    An array holding the mocked command details.
#
# Return:
#  The mocked commands array for the 'setup' phase.
#
# Dependencies:
# Assertions and mocks from https://github.com/drevops/bats-helpers
#
# Usage:
# When used with commands, this function needs to be called twice, once for
# the 'setup' phase and once for the 'assert' phase. The 'setup' phase will mock
# the commands and the 'assert' phase will assert the commands.
# When used with strings, just call it once for the 'assert' phase.
#
# STEPS=(...)
# mocks="$(process_steps "setup")" # $mocks will hold created mocks
# # ... code to be tested ...
# process_steps "assert" "$steps" "$mocks"
#
# Every step is a string that can be one of the following:
# @<command> [<args>] # <mock_status> [ # <mock_output> ]
#   Mock the command <command> with the given status and optional output.
#   Status can be omitted and <mock_output> can be used instead.
#   Different commands can be mocked multiple times.
#   Call to the same command will be using the same mock.
#
# <substring>
#   Check that the output contains the given substring.
#
# - <substring>
#   Ensure the output does NOT contain the specified substring.
#   Starts with '- ' (minus followed by space).
#
# Example:
# declare -a test_steps=(
#   # Mock `drush` binary with an exit status of 1 and not output.
#   "@drush -y status --field=drupal-version # 1"
#   # Mock `drush` binary with an exit status of 0 and output "success".
#   "@drush -y status --fields=bootstrap # success"
#   # Mock `drush` binary with an exit status of 1 and output "failure".
#   "@drush -y status --fields=bootstrap # 1 # failure"
#   # Assert presence of the partial string in the output "Hello world"
#   "Hello world"
#   # Assert absence of the partial string in the output "Goodbye world"
#   "- Goodbye world"
# )
#
################################################################################
run_steps() {
  declare -g STEPS
  declare -g RUN_STEPS_DEBUG

  local PHASE_ASSERT="assert"

  local phase="${1:-${PHASE_ASSERT}}"
  local mocked_commands_var="${2:-}"

  RUN_STEPS_DEBUG="${RUN_STEPS_DEBUG:-false}"

  # Run bats with `--tap` option to debug the output.
  stepdebug() {
    if [ "${RUN_STEPS_DEBUG}" = "1" ]; then echo "  > ${1:-}" >&3; fi
  }
  substepdebug() {
    if [ "${RUN_STEPS_DEBUG}" = "1" ]; then echo "  >   ${1:-}" >&3; fi
  }

  declare -A command_indexes
  declare -A mocked_commands

  stepdebug "Phase       : ${phase}"
  stepdebug "Total steps : ${#STEPS[@]}"
  stepdebug

  # Create associative array for mocked commands
  if [[ -n ${mocked_commands_var} ]]; then
    while IFS= read -r line; do
      local key="${line%%=*}"
      local value="${line#*=}"
      mocked_commands["${key}"]="${value}"
    done <<<"${mocked_commands_var}"
  fi

  local mock_cmd
  for ((i = 0; i < ${#STEPS[@]}; i++)); do
    local item="${STEPS[${i}]}"

    stepdebug "STEP START: '${item}'"

    #########################################################################
    #                                COMMAND                                #
    #########################################################################
    if [[ ${item} == "@"* ]]; then
      stepdebug "Type: command"
      stepdebug

      #------------------------------------------------------------------------
      # Parsing the command, status, and optional output.
      #------------------------------------------------------------------------

      substepdebug "PARSE: STARTED"

      if [[ ${item} =~ (##) || $(echo "${item}" | grep -o "#" | wc -l) -gt 2 ]]; then
        echo "ERROR: The string should not contain consecutive '##' and should have a maximum of two '#' characters in total."
        exit 1
      fi

      # Split command, status, and optional output.
      local command_parts
      IFS='#' read -ra command_parts <<<"${item}"
      command_parts=("${command_parts[@]/# /}") # Remove leading spaces.
      command_parts=("${command_parts[@]/% /}") # Remove trailing spaces.

      # Extract the command binary and its arguments from the first command part.
      local full_command
      IFS=' ' read -ra full_command <<<"${command_parts[0]:1}" # Removing '@'.
      local command_binary="${full_command[0]}"
      local command_args="${full_command[*]:1}" # Extract all elements except the first one.

      local mock_status="${command_parts[1]:-}"
      local mock_output="${command_parts[2]:-}"

      if ! [[ ${mock_status} =~ ^[0-9]+$ ]]; then
        substepdebug "PARSE: Converting output to '${mock_status}' output."
        substepdebug "PARSE: Setting status to '0'."
        mock_output="${mock_status}"
        mock_status=0
      fi

      substepdebug "PARSE: FINISHED"
      substepdebug "       cmd    : '${command_binary}'"
      substepdebug "       args   : '${command_args}'"
      substepdebug "       status : '${mock_status}'"
      substepdebug "       output : '${mock_output}'"

      #------------------------------------------------------------------------
      # Processing the command.
      #------------------------------------------------------------------------

      # Track the index of the command call per binary.
      mock_cmd_index=${command_indexes[${command_binary}]:-1}
      substepdebug "Command index for '${command_binary}' is '${mock_cmd_index}'."

      if [[ ${phase} == "${phase}_SETUP" ]]; then
        # Get mock from passed array or create a new one.
        if [[ -z ${mocked_commands["${command_binary}"]:-} ]]; then
          mock_cmd=$(mock_command "${command_binary}")
          mocked_commands["${command_binary}"]=${mock_cmd}
          substepdebug "SETUP: Created new mock for '${command_binary}' with value '${mocked_commands[${command_binary}]}'."
        else
          mock_cmd="${mocked_commands["${command_binary}"]}"
          substepdebug "SETUP: Using existing mock for '${command_binary}' with value '${mocked_commands[${command_binary}]}'."
        fi

        substepdebug "SETUP: Setting mock status to '${mock_status}'."
        mock_set_status "${mock_cmd}" "${mock_status}" "${mock_cmd_index}"

        if [[ -n ${mock_output} ]]; then
          substepdebug "SETUP: Setting mock output to '${mock_output}'."
          mock_set_output "${mock_cmd}" "${mock_output}" "${mock_cmd_index}"
        fi

        substepdebug "SETUP: Setup mock for binary '${command_binary}' complete."
      else
        # Check if mock for the binary exists in the assert phase
        if [[ -z ${mocked_commands["${command_binary}"]} ]]; then
          echo "ERROR: Mock for the binary '${command_binary}' does not exist."
          exit 1
        fi

        substepdebug "ASSERT: Found mock for '${command_binary}' with value '${mocked_commands[${command_binary}]}'"

        local mock_args_actual
        mock_cmd="${mocked_commands[${command_binary}]}"
        substepdebug "        command     : ${command_binary}"
        substepdebug "        args        : ${command_args}"
        substepdebug "        mock        : ${mock_cmd}"
        substepdebug "        index       : ${mock_cmd_index}"
        mock_args_actual="$(mock_get_call_args "${mock_cmd}" "${mock_cmd_index}")"
        substepdebug "        actual args : ${mock_args_actual}"

        assert_equal "${command_args}" "${mock_args_actual}"

        # shellcheck disable=SC2181
        if [[ $? -ne 0 ]]; then
          substepdebug "ASSERT: Assertion failed. Returning error code."
          exit 1
        fi
      fi

      command_indexes["${command_binary}"]=$((mock_cmd_index + 1))
      stepdebug "Updated command index for '${command_binary}' to '${command_indexes[${command_binary}]}'"

    #########################################################################
    #                            STRING ABSENT                              #
    #########################################################################
    elif [[ ${item} == "-"* ]]; then
      stepdebug "Type: string absent"

      if [[ ${phase} == "${PHASE_ASSERT}" ]]; then
        assert_output_not_contains "${item:2}" # Assuming 2 chars to skip '-' and a space
        # shellcheck disable=SC2181
        if [[ $? -ne 0 ]]; then
          substepdebug "ASSERT: Assertion failed. Returning error code."
          exit 1
        fi
      fi
    #########################################################################
    #                            STRING PRESENT                             #
    #########################################################################
    else
      stepdebug "Type: string present"

      if [[ ${phase} == "${PHASE_ASSERT}" ]]; then
        assert_output_contains "${item}"
        # shellcheck disable=SC2181
        if [[ $? -ne 0 ]]; then
          substepdebug "ASSERT: Assertion failed. Returning error code."
          exit 1
        fi
      fi
    fi

    stepdebug "STEP FINISH: '${item}'"
    stepdebug
  done

  # Return mocked commands as a string to pass it to the next phase.
  if [[ ${phase} == "${phase}_SETUP" ]]; then
    local mc_string=""
    for key in "${!mocked_commands[@]}"; do
      mc_string+="${key}=${mocked_commands[${key}]}"$'\n'
    done
    echo "${mc_string}"
  fi
}
