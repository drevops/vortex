#!/usr/bin/env bats
#
# Test for CircleCI lifecycle.
#
# shellcheck disable=SC2030,SC2031,SC2129

load _helper.bash
load _helper.circleci.bash

@test "CircleCI artifacts are saved" {
  if [ -z "${CIRCLECI}" ]; then
    skip "This test is only run on CircleCI"
  fi

  export TEST_CIRCLECI_TOKEN="${TEST_CIRCLECI_TOKEN?CircleCI token is not set}"
  export CIRCLE_PROJECT_REPONAME="${CIRCLE_PROJECT_REPONAME?CircleCI project repo name is not set}"
  export CIRCLE_PROJECT_USERNAME="${CIRCLE_PROJECT_USERNAME?CircleCI project username is not set}"
  export CIRCLE_BUILD_NUM="${CIRCLE_BUILD_NUM?CircleCI build number is not set}"

  previous_job_numbers="$(circleci_get_previous_job_numbers "${CIRCLE_BUILD_NUM}")"

  for previous_job_number in ${previous_job_numbers}; do
    artifacts_data="$(circleci_get_job_artifacts "${previous_job_number}")"

    artifact_path_runner_0="$(echo "${artifacts_data}" | jq -r '.items | map(select(.node_index == 0).path) | join("\n")')"
    assert_contains "coverage/phpunit/cobertura.xml" "${artifact_path_runner_0}"
    assert_contains "coverage/phpunit/.coverage-html/index.html" "${artifact_path_runner_0}"

    assert_contains "homepage.feature" "${artifact_path_runner_0}"
    assert_contains "login.feature" "${artifact_path_runner_0}"
    assert_contains "clamav.feature" "${artifact_path_runner_0}"
    assert_not_contains "search.feature" "${artifact_path_runner_0}"

    artifact_path_runner_1="$(echo "${artifacts_data}" | jq -r '.items | map(select(.node_index == 1).path) | join("\n")')"
    assert_contains "coverage/phpunit/cobertura.xml" "${artifact_path_runner_1}"
    assert_contains "coverage/phpunit/.coverage-html/index.html" "${artifact_path_runner_1}"

    assert_contains "homepage.feature" "${artifact_path_runner_1}"
    assert_contains "login.feature" "${artifact_path_runner_1}"
    assert_not_contains "clamav.feature" "${artifact_path_runner_1}"
    assert_contains "search.feature" "${artifact_path_runner_1}"
  done
}

@test "CircleCI test results are saved" {
  if [ -z "${CIRCLECI}" ]; then
    skip "This test is only run on CircleCI"
  fi

  export TEST_CIRCLECI_TOKEN="${TEST_CIRCLECI_TOKEN?CircleCI token is not set}"
  export CIRCLE_PROJECT_REPONAME="${CIRCLE_PROJECT_REPONAME?CircleCI project repo name is not set}"
  export CIRCLE_PROJECT_USERNAME="${CIRCLE_PROJECT_USERNAME?CircleCI project username is not set}"
  export CIRCLE_BUILD_NUM="${CIRCLE_BUILD_NUM?CircleCI build number is not set}"

  previous_job_numbers="$(circleci_get_previous_job_numbers "${CIRCLE_BUILD_NUM}")"

  for previous_job_number in ${previous_job_numbers}; do
    tests_data="$(circleci_get_job_test_metadata "${previous_job_number}")"
    assert_contains "tests/phpunit/CircleCiConfigTest.php" "${tests_data}"
    assert_contains "tests/phpunit/Drupal/DatabaseSettingsTest.php" "${tests_data}"
    assert_contains "tests/phpunit/Drupal/EnvironmentSettingsTest.php" "${tests_data}"
    assert_contains "tests/phpunit/Drupal/SwitchableSettingsTest.php" "${tests_data}"
    assert_contains "web/modules/custom/ys_core/tests/src/Functional/ExampleTest.php" "${tests_data}"
    assert_contains "web/modules/custom/ys_core/tests/src/Kernel/ExampleTest.php" "${tests_data}"
    assert_contains "web/modules/custom/ys_core/tests/src/Unit/ExampleTest.php" "${tests_data}"

    assert_contains "homepage.feature" "${tests_data}"
    assert_contains "login.feature" "${tests_data}"
    assert_contains "clamav.feature" "${tests_data}"
    assert_contains "search.feature" "${tests_data}"
  done
}
