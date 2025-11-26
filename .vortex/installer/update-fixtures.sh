#!/usr/bin/env bash
##
# Update installer test fixtures by running each dataset individually.
#
# This script runs PHPUnit with UPDATE_FIXTURES=1 for each dataset to avoid
# memory issues and handle hanging tests with timeouts and retries.
#
# Usage:
#   ./update-fixtures.sh              # Update all fixtures
#   ./update-fixtures.sh baseline     # Update only baseline dataset
#   ./update-fixtures.sh my-dataset   # Update only my-dataset
#
# shellcheck disable=SC2015

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# ------------------------------------------------------------------------------

# Variables.
DATASET="${1:-}"
TIMEOUT=30
MAX_RETRIES=3
START_TIME=$(date +%s)

# Trap to handle Ctrl+C and cleanup.
trap 'printf "\nInterrupted by user\n"; exit 130' INT TERM

# ------------------------------------------------------------------------------

# Helper function to run PHPUnit with timeout and retries.
# Args: filter, current_num, total_num, dataset_name
# Returns: 0 = success, 1 = failed, 2 = timeout
run_with_timeout() {
  local filter="${1}"
  local current="${2}"
  local total="${3}"
  local dataset="${4}"
  local attempt=1
  local exit_code

  # Print initial line.
  printf "[%s/%s] %s" "${current}" "${total}" "${dataset}"

  while [ "${attempt}" -le "${MAX_RETRIES}" ]; do
    set +e
    UPDATE_FIXTURES=1 timeout --foreground "${TIMEOUT}s" ./vendor/bin/phpunit --no-coverage --filter="${filter}" >/dev/null 2>&1
    exit_code=$?

    # Exit code 130 means interrupted by user (Ctrl+C), propagate immediately.
    if [ "${exit_code}" -eq 130 ]; then
      printf "\n"
      exit 130
    fi

    # Success.
    if [ "${exit_code}" -eq 0 ]; then
      printf " ✓\n"
      return 0
    fi

    # Exit code 124 means timeout - retry.
    if [ "${exit_code}" -eq 124 ]; then
      if [ "${attempt}" -lt "${MAX_RETRIES}" ]; then
        # Show retry attempt and overwrite line.
        printf "\r[%s/%s] %s (%s/%s)" "${current}" "${total}" "${dataset}" "$((attempt + 1))" "${MAX_RETRIES}"
        attempt=$((attempt + 1))
      else
        # Max retries reached, show timeout error.
        printf "\r[%s/%s] %s (%s/%s) ✗ TIMEOUT - exiting\n" "${current}" "${total}" "${dataset}" "${MAX_RETRIES}" "${MAX_RETRIES}"
        return 2
      fi
    else
      # Test failure (non-timeout) - do NOT retry, move to next test.
      printf " ✗\n"
      return 1
    fi
  done

  # Should not reach here, but just in case.
  printf " ✗\n"
  return 1
}

# ------------------------------------------------------------------------------

# Main execution.

# Change to script directory (installer directory).
cd "$(dirname "${0}")" || exit 1

# If dataset provided, run single PHPUnit command with filter.
if [ -n "${DATASET}" ]; then
  printf "Scanning for dataset: %s\n" "${DATASET}"

  if UPDATE_FIXTURES=1 ./vendor/bin/phpunit --no-coverage --filter="testInstall@${DATASET}"; then
    printf "Completed successfully\n"
    exit 0
  else
    printf "Failed\n"
    exit 1
  fi
fi

# No dataset provided - discover all datasets and process individually.
printf "Discovering datasets...\n"

# Get list of all tests with datasets from PHPUnit.
test_list=$(./vendor/bin/phpunit --list-tests tests/Functional/Handlers 2>/dev/null | grep "testInstall" || true)

if [ -z "${test_list}" ]; then
  printf "No datasets found\n"
  exit 1
fi

# Extract dataset names from test list.
# Format: " - ClassName::testInstall"dataset_name""
datasets=$(echo "${test_list}" | sed -E 's/.*testInstall"([^"]+)".*/\1/' | sort -u)

# Ensure "baseline" dataset runs first.
if echo "${datasets}" | grep -q "^baseline$"; then
  # Remove baseline from the list and prepend it.
  datasets=$(echo "${datasets}" | grep -v "^baseline$")
  datasets="baseline"$'\n'"${datasets}"
fi

# Count total datasets.
total_datasets=$(echo "${datasets}" | wc -l | tr -d ' ')
current=0
failed=0
succeeded=0
timedout=0

printf "Found %s unique datasets\n" "${total_datasets}"

# Process each dataset.
while IFS= read -r dataset; do
  current=$((current + 1))

  # Run PHPUnit for this specific dataset with timeout and retries.
  set +e
  run_with_timeout "testInstall@${dataset}" "${current}" "${total_datasets}" "${dataset}"
  result=$?

  # After baseline dataset runs, check if baseline fixtures were updated and commit them.
  # This must happen BEFORE any exit logic so changes are always committed.
  if [ "${dataset}" = "baseline" ]; then
    # Navigate to git root (two levels up from installer directory).
    cd ../.. || exit 1

    # Check if there are changes in baseline fixtures (modified, staged, or untracked).
    baseline_path=".vortex/installer/tests/Fixtures/install/_baseline"
    has_changes=0

    # Check for modified files (unstaged).
    if ! git diff --quiet "${baseline_path}" 2>/dev/null; then
      has_changes=1
    fi

    # Check for staged files.
    if ! git diff --cached --quiet "${baseline_path}" 2>/dev/null; then
      has_changes=1
    fi

    # Check for untracked files.
    if [ -n "$(git ls-files --others --exclude-standard "${baseline_path}" 2>/dev/null)" ]; then
      has_changes=1
    fi

    if [ "${has_changes}" -eq 1 ]; then
      printf "Baseline fixtures updated - committing and continuing...\n"

      # Commit baseline changes.
      if git add "${baseline_path}" && git commit -m "Updated baseline."; then
        printf "Baseline committed successfully. Continuing with remaining datasets...\n"
      else
        printf "Failed to commit baseline changes.\n"
        exit 1
      fi
    fi

    # Navigate back to installer directory.
    cd .vortex/installer || exit 1
  fi

  # Handle test results.
  if [ "${result}" -eq 0 ]; then
    succeeded=$((succeeded + 1))
  elif [ "${result}" -eq 2 ]; then
    timedout=$((timedout + 1))
    # Hard exit on timeout.
    printf "Total: %s | Succeeded: %s | Failed: %s | Timed out: %s\n" "${total_datasets}" "${succeeded}" "${failed}" "${timedout}"
    exit 1
  else
    failed=$((failed + 1))
  fi

  # Re-enable exit on error for next iteration.
  set -e
done <<< "${datasets}"

# Summary.
printf "Total: %s | Succeeded: %s | Failed: %s | Timed out: %s\n" "${total_datasets}" "${succeeded}" "${failed}" "${timedout}"

# If there were failures or timeouts, check for additional uncommitted fixture changes and amend the baseline commit.
if [ "${failed}" -gt 0 ] || [ "${timedout}" -gt 0 ]; then
  # Navigate to git root (two levels up from installer directory).
  cd ../.. || exit 1

  # Check if there are uncommitted changes in fixtures directory.
  fixtures_path=".vortex/installer/tests/Fixtures/install"
  has_changes=0

  # Check for modified files (unstaged).
  if ! git diff --quiet "${fixtures_path}" 2>/dev/null; then
    has_changes=1
  fi

  # Check for staged files.
  if ! git diff --cached --quiet "${fixtures_path}" 2>/dev/null; then
    has_changes=1
  fi

  # Check for untracked files.
  if [ -n "$(git ls-files --others --exclude-standard "${fixtures_path}" 2>/dev/null)" ]; then
    has_changes=1
  fi

  if [ "${has_changes}" -eq 1 ]; then
    # Stage all fixture changes.
    if git add "${fixtures_path}"; then
      # Amend the baseline commit with all fixture changes.
      if git commit --amend -m "Updated fixtures."; then
        printf "Note: Amended previous commit to include all fixture updates.\n"
      else
        printf "Failed to amend commit with fixture changes.\n"
      fi
    else
      printf "Failed to stage fixture changes.\n"
    fi
  fi

  # Navigate back to installer directory.
  cd .vortex/installer || exit 1

  # Calculate and display execution time.
  ELAPSED=$(($(date +%s) - START_TIME))
  MINUTES=$((ELAPSED / 60))
  SECONDS=$((ELAPSED % 60))
  printf "Total execution time: %d minutes %d seconds\n" "${MINUTES}" "${SECONDS}"

  exit 1
fi

# Calculate and display execution time.
ELAPSED=$(($(date +%s) - START_TIME))
MINUTES=$((ELAPSED / 60))
SECONDS=$((ELAPSED % 60))
printf "Total execution time: %d minutes %d seconds\n" "${MINUTES}" "${SECONDS}"
