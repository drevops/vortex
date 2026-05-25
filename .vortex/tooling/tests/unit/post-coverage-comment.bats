#!/usr/bin/env bats
##
# Unit tests for .circleci/post-coverage-comment.sh
#
# shellcheck disable=SC2030,SC2031,SC2034

load ../_helper.bash

@test "post-coverage-comment: missing coverage file" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export CIRCLE_PULL_REQUEST="https://github.com/myorg/myrepo/pull/123"
  export GITHUB_TOKEN="token12345"
  export CIRCLE_PROJECT_USERNAME="myorg"
  export CIRCLE_PROJECT_REPONAME="myrepo"

  run .circleci/post-coverage-comment.sh /nonexistent/file.txt
  assert_failure
  assert_output_contains "ERROR: Coverage file not found"

  popd >/dev/null || exit 1
}

@test "post-coverage-comment: no arguments" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  export CIRCLE_PULL_REQUEST="https://github.com/myorg/myrepo/pull/123"
  export GITHUB_TOKEN="token12345"

  run .circleci/post-coverage-comment.sh
  assert_failure
  assert_output_contains "ERROR: Coverage file not found"

  popd >/dev/null || exit 1
}

@test "post-coverage-comment: skip when not a pull request" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p .logs/coverage/phpunit
  echo "Lines: 100.00%" >.logs/coverage/phpunit/coverage.txt

  unset CIRCLE_PULL_REQUEST
  export GITHUB_TOKEN="token12345"

  run .circleci/post-coverage-comment.sh .logs/coverage/phpunit/coverage.txt
  assert_success
  assert_output_contains "Not a pull request. Skipping."

  popd >/dev/null || exit 1
}

@test "post-coverage-comment: skip when no GITHUB_TOKEN" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p .logs/coverage/phpunit
  echo "Lines: 100.00%" >.logs/coverage/phpunit/coverage.txt

  export CIRCLE_PULL_REQUEST="https://github.com/myorg/myrepo/pull/123"
  unset GITHUB_TOKEN

  run .circleci/post-coverage-comment.sh .logs/coverage/phpunit/coverage.txt
  assert_success
  assert_output_contains "GITHUB_TOKEN is not set. Skipping."

  popd >/dev/null || exit 1
}

@test "post-coverage-comment: post with no existing comments" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p .logs/coverage/phpunit
  printf "Code Coverage Report:\n  2024-01-01 12:00:00\n\n Summary:\n  Classes: 100.00%% (1/1)\n  Methods: 100.00%% (2/2)\n  Lines:   100.00%% (4/4)\n\nApp\\\\MyClass\n  Methods: 100.00%% ( 2/ 2)   Lines: 100.00%% (  4/  4)\n" >.logs/coverage/phpunit/coverage.txt

  declare -a STEPS=(
    # GET existing comments - return empty array.
    '@curl * # []'
    # POST new comment.
    '@curl * # {"id": 1}'
  )

  mocks="$(run_steps "setup")"

  export CIRCLE_PULL_REQUEST="https://github.com/myorg/myrepo/pull/123"
  export GITHUB_TOKEN="token12345"
  export CIRCLE_PROJECT_USERNAME="myorg"
  export CIRCLE_PROJECT_REPONAME="myrepo"

  run .circleci/post-coverage-comment.sh .logs/coverage/phpunit/coverage.txt
  assert_success

  run_steps "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "post-coverage-comment: minimize existing comments before posting" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p .logs/coverage/phpunit
  printf "Code Coverage Report:\n  2024-01-01 12:00:00\n\n Summary:\n  Classes: 50.00%% (1/2)\n  Methods: 66.67%% (2/3)\n  Lines:   95.00%% (6/8)\n\nApp\\\\ClassA\n  Methods: 100.00%% ( 2/ 2)   Lines: 100.00%% (  4/  4)\nApp\\\\ClassB\n  Methods:   0.00%% ( 0/ 1)   Lines:   50.00%% (  2/  4)\n" >.logs/coverage/phpunit/coverage.txt

  declare -a STEPS=(
    # GET existing comments - return one with marker.
    '@curl * # [{"node_id": "MDEyOklzc3VlQ29tbWVudDE=", "body": "old coverage <!-- coverage-circleci -->"}]'
    # POST GraphQL to minimize existing comment.
    '@curl * # {"data":{"minimizeComment":{"minimizedComment":{"isMinimized":true}}}}'
    # POST new comment.
    '@curl * # {"id": 2}'
  )

  mocks="$(run_steps "setup")"

  export CIRCLE_PULL_REQUEST="https://github.com/myorg/myrepo/pull/456"
  export GITHUB_TOKEN="token12345"
  export CIRCLE_PROJECT_USERNAME="myorg"
  export CIRCLE_PROJECT_REPONAME="myrepo"

  run .circleci/post-coverage-comment.sh .logs/coverage/phpunit/coverage.txt
  assert_success

  run_steps "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}
