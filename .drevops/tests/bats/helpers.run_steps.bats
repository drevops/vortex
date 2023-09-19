#!/usr/bin/env bats
#
# Tests for DrevOps Bats 'run_steps' helper.
#
# shellcheck disable=SC2034,SC2030,SC2031

load _helper.bash

@test "Substring presence" {
  declare -a STEPS=(
    "Some Substring"
  )

  # Shorthand.
  run echo "Some Substring"
  run_steps

  # Full.
  run_steps "setup"
  run echo "Some Substring"
  run_steps "assert"

  # Full with mocks
  mocks="$(run_steps "setup")"
  run echo "Some Substring"
  run_steps "assert" "${mocks[@]}"

  # Negative.
  run echo "Some other"
  run run_steps "assert"
  assert_failure
}

@test "Substring absence" {
  declare -a STEPS=(
    "- Some Substring"
  )

  # Shorthand.
  run echo "Some other"
  run_steps

  # Full.
  run_steps "setup"
  run echo "Some other"
  run_steps "assert"

  # Full with mocks
  mocks="$(run_steps "setup")"
  run echo "Some other"
  run_steps "assert" "${mocks[@]}"

  # Negative.
  run echo "Some Substring"
  run run_steps "assert"
  assert_failure
}

@test "Direct command execution" {
  declare -a STEPS=(
    "@somebin # 0 # someval"
  )

  mocks="$(run_steps "setup")"
  somebin
  run_steps "assert" "${mocks[@]}"
}

@test "Direct command execution, args" {
  declare -a STEPS=(
    "@somebin --opt1 --opt2 # 0 # someval"
  )

  mocks="$(run_steps "setup")"
  somebin --opt1 --opt2
  run_steps "assert" "${mocks[@]}"
}

@test "Wrapped execution through Bat's 'run'" {
  declare -a STEPS=(
    "@somebin # 0 # someval"
  )

  mocks="$(run_steps "setup")"
  run somebin
  assert_output_contains "someval"
  run_steps "assert" "${mocks[@]}"
}

@test "Command, args" {
  declare -a STEPS=(
    "@somebin --opt1 --opt2 # 0 # someval"
  )

  mocks="$(run_steps "setup")"
  run somebin --opt1 --opt2
  assert_output_contains "someval"
  run_steps "assert" "${mocks[@]}"
}

@test "Command, args - negative: wrong args" {
  declare -a STEPS=(
    "@somebin --opt1 --opt2 # 0 # someval"
  )

  mocks="$(run_steps "setup")"
  run somebin --opt1 --opt2 --opt3
  assert_output_contains "someval"

  run run_steps "assert" "${mocks[@]}"
  assert_failure
}

@test "Command, args, no exit code or output" {
  declare -a STEPS=(
    "@somebin --opt1 --opt2"
  )

  mocks="$(run_steps "setup")"
  run somebin --opt1 --opt2

  run run_steps "assert" "${mocks[@]}"
  assert_success
}

@test "Command, args, output, no exit code" {
  declare -a STEPS=(
    "@somebin --opt1 --opt2 # someval with spaces"
  )

  mocks="$(run_steps "setup")"
  run somebin --opt1 --opt2
  assert_output_contains "someval with spaces"

  run run_steps "assert" "${mocks[@]}"
  assert_success
}

@test "Command, args, error exit code" {
  declare -a STEPS=(
    "@somebin --opt1 --opt2 # 1 # someval with spaces"
  )

  mocks="$(run_steps "setup")"
  run somebin --opt1 --opt2
  assert_failure
  assert_output_contains "someval with spaces"

  run run_steps "assert" "${mocks[@]}"
  assert_success
}

@test "Command, args - negative: incorrect input - delim" {
  declare -a STEPS=(
    "@somebin --opt1 --opt2 # 0 ## someval"
  )

  run run_steps "setup" "${mocks[@]}"
  assert_failure
  assert_output_contains "ERROR: The string should not contain consecutive '##' and should have a maximum of two '#' characters in total."
}

@test "Command, multiple commands, same, repeated call" {
  declare -a STEPS=(
    "@somebin --opt1 --opt2 # 0 # someval1 with spaces"
    "@somebin --opt1 --opt2 # 0 # someval2 with spaces"
  )

  mocks="$(run_steps "setup")"

  run somebin --opt1 --opt2
  assert_output_contains "someval1 with spaces"
  assert_output_not_contains "someval2 with spaces"
  run somebin --opt1 --opt2
  assert_output_not_contains "someval1 with spaces"
  assert_output_contains "someval2 with spaces"

  run run_steps "assert" "${mocks[@]}"
  assert_success
}

@test "Command, multiple commands, same, combined execution" {
  declare -a STEPS=(
    "@somebin --opt1 --opt2 # 0 # someval1 with spaces"
    "@somebin --opt1 --opt2 # 0 # someval2 with spaces"
  )

  mocks="$(run_steps "setup")"

  run bash -c "somebin --opt1 --opt2; somebin --opt1 --opt2"
  assert_output_contains "someval1 with spaces"
  assert_output_contains "someval2 with spaces"

  run run_steps "assert" "${mocks[@]}"
  assert_success
}

@test "Command, multiple commands, same, combined execution, and" {
  declare -a STEPS=(
    "@somebin --opt1 --opt2 # 0 # someval1 with spaces"
    "@somebin --opt1 --opt2 # 0 # someval2 with spaces"
  )

  mocks="$(run_steps "setup")"

  run bash -c "somebin --opt1 --opt2 && somebin --opt1 --opt2"
  assert_output_contains "someval1 with spaces"
  assert_output_contains "someval2 with spaces"

  run run_steps "assert" "${mocks[@]}"
  assert_success
}

@test "Command, multiple commands, different" {
  declare -a STEPS=(
    "@somebin --opt1 --opt2 # 0 # someval1 with spaces"
    "@otherbin --opt1 --opt2 # 0 # someval2 with spaces"
  )

  mocks="$(run_steps "setup")"

  run somebin
  assert_success
  run otherbin
  assert_success

  run run_steps "assert" "${mocks[@]}"

}

@test "Command, multiple commands, different - negative: incorrect call order" {
  declare -a STEPS=(
    "@somebin --opt1 --opt2 # 0 # someval1 with spaces"
    "@otherbin --opt1 --opt2 # 0 # someval2 with spaces"
  )

  mocks="$(run_steps "setup")"
  run otherbin
  assert_success
  run somebin
  assert_success

  run run_steps "assert" "${mocks[@]}"
  assert_failure
}

@test "Command, multiple commands, different, repeated call" {
  declare -a STEPS=(
    "@somebin --opt1 --opt2 # 0 # someval1 with spaces"
    "@somebin --opt1 --opt2 # 0 # someval2 with spaces"
    "@otherbin --opt3 --opt4 # 0 # someval3 with spaces"
  )

  mocks="$(run_steps "setup")"

  run somebin --opt1 --opt2
  assert_output_contains "someval1 with spaces"
  assert_output_not_contains "someval2 with spaces"
  assert_output_not_contains "someval3 with spaces"
  run somebin --opt1 --opt2
  assert_output_not_contains "someval1 with spaces"
  assert_output_contains "someval2 with spaces"
  assert_output_not_contains "someval3 with spaces"
  run otherbin --opt3 --opt4
  assert_output_not_contains "someval1 with spaces"
  assert_output_not_contains "someval2 with spaces"
  assert_output_contains "someval3 with spaces"

  run run_steps "assert" "${mocks[@]}"
  assert_success
}

@test "Command, multiple commands, different, repeated call - negative" {
  declare -a STEPS=(
    "@somebin --opt1 --opt2 # 0 # someval1 with spaces"
    "@somebin --opt1 --opt2 # 0 # someval2 with spaces"
    "@otherbin --opt3 --opt4 # 0 # someval3 with spaces"
  )

  mocks="$(run_steps "setup")"

  run somebin --opt1 --opt2
  assert_output_contains "someval1 with spaces"
  assert_output_not_contains "someval2 with spaces"
  assert_output_not_contains "someval3 with spaces"
  run somebin --opt1 --opt2
  assert_output_not_contains "someval1 with spaces"
  assert_output_contains "someval2 with spaces"
  assert_output_not_contains "someval3 with spaces"

  # Asserting missing call to the 'otherbin'.
  run run_steps "assert" "${mocks[@]}"
  assert_failure
}

@test "Command, multiple commands, different, repeated call, order" {
  declare -a STEPS=(
    "@somebin --opt11 --opt21 # 0 # someval1 with spaces"
    "@somebin --opt11 --opt22 # 0 # someval2 with spaces"
    "@otherbin --opt31 --opt41 # 0 # someval3 with spaces"
    "@somebin --opt13 --opt23 # 0 # someval4 with spaces"
    "@otherbin --opt32 --opt42 # 0 # someval5 with spaces"
    "@otherbin --opt33 --opt43 # 0 # someval6 with spaces"
  )

  mocks="$(run_steps "setup")"

  run somebin --opt11 --opt21
  run somebin --opt11 --opt22
  run otherbin --opt31 --opt41
  run somebin --opt13 --opt23
  run otherbin --opt32 --opt42
  run otherbin --opt33 --opt43

  run run_steps "assert" "${mocks[@]}"
  assert_success
}

@test "Command, multiple commands, different, repeated call, order - negative" {
  declare -a STEPS=(
    "@somebin --opt11 --opt21 # 0 # someval1 with spaces"
    "@somebin --opt11 --opt22 # 0 # someval2 with spaces"
    "@otherbin --opt31 --opt41 # 0 # someval3 with spaces"
    "@somebin --opt13 --opt23 # 0 # someval4 with spaces"
    "@otherbin --opt32 --opt42 # 0 # someval5 with spaces"
    "@otherbin --opt33 --opt43 # 0 # someval6 with spaces"
  )

  mocks="$(run_steps "setup")"

  run somebin --opt11 --opt21
  run somebin --opt13 --opt23
  run somebin --opt11 --opt22
  run otherbin --opt32 --opt42
  run otherbin --opt31 --opt41
  run otherbin --opt33 --opt43

  run run_steps "assert" "${mocks[@]}"
  assert_failure
}

@test "Command, multiple commands, different, combined, repeated call, order" {
  # To assert string presence/absence without creating a script that prints
  # strings, we use the output of commands.
  declare -a STEPS=(
    "@somebin --opt1 --opt2 # 0 # someval1 with spaces"
    "someval1 with spaces"
    "@somebin --opt1 --opt2 # 0 # someval2 with spaces"
    "someval2 with spaces"
    "- absent someval21 with spaces"
    "@somebin --opt1 --opt2 --opt3 # 0 # someval3 with spaces"
    "someval3 with spaces"
    "@otherbin --opt1 --opt2 --opt3 # 0 # someval4 with spaces"
    "@otherbin --opt1 --opt2 --opt3 # 0 # someval5 with spaces"
    "- absent someval5 with spaces"
  )

  mocks="$(run_steps "setup")"

  run bash -c "somebin --opt1 --opt2; somebin --opt1 --opt2; somebin --opt1 --opt2 --opt3; otherbin --opt1 --opt2 --opt3; otherbin --opt1 --opt2 --opt3"

  run run_steps "assert" "${mocks[@]}"
  assert_success
}

@test "Command, multiple commands, different, combined, repeated call, order, shorthand" {
  # To assert string presence/absence without creating a script that prints
  # strings, we use the output of commands.
  declare -a STEPS=(
    "@somebin --opt1 --opt2 # someval1 with spaces"
    "someval1 with spaces"
    "@somebin --opt1 --opt2 # someval2 with spaces"
    "someval2 with spaces"
    "- absent someval21 with spaces"
    "@somebin --opt1 --opt2 --opt3 # someval3 with spaces"
    "someval3 with spaces"
    "@otherbin --opt1 --opt2 --opt3 # someval4 with spaces"
    "@otherbin --opt1 --opt2 --opt3 # someval5 with spaces"
    "- absent someval5 with spaces"
  )

  mocks="$(run_steps "setup")"

  run bash -c "somebin --opt1 --opt2; somebin --opt1 --opt2; somebin --opt1 --opt2 --opt3; otherbin --opt1 --opt2 --opt3; otherbin --opt1 --opt2 --opt3"

  run run_steps "assert" "${mocks[@]}"
  assert_success
}
