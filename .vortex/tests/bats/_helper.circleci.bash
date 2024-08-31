#!/usr/bin/env bash
# shellcheck disable=SC2154,SC2129
#
# Helpers related to Vortex CircleCI testing functionality.
#

#
# Get numbers of previous jobs that current job depends on.
#
circleci_get_previous_job_numbers() {
  local current_job_number="${1}"

  workflow_id="$(circleci_get_workflow_id_from_job_number "${current_job_number}")"

  workflow_data="$(curl -sSL --request GET \
    --header "Circle-Token: $TEST_CIRCLECI_TOKEN" \
    "https:/circleci.com/api/v2/workflow/${workflow_id}/job")"

  dependencies_job_ids="$(echo "${workflow_data}" | jq -r ".items[] | select(.job_number == ${current_job_number}) | .dependencies[]")"

  for dependencies_job_id in "${dependencies_job_ids[@]}"; do
    echo "${workflow_data}" | jq ".items[] | select(.id == \"${dependencies_job_id}\") | .job_number"
  done
}

#
# Get workflow ID from the job number.
#
circleci_get_workflow_id_from_job_number() {
  curl -sSL --request GET \
    --header "Circle-Token: $TEST_CIRCLECI_TOKEN" \
    "https:/circleci.com/api/v2/project/gh/${CIRCLE_PROJECT_USERNAME}/${CIRCLE_PROJECT_REPONAME}/job/${1}" |
    jq -r '.latest_workflow.id'
}

#
# Get artifacts for a job.
#
circleci_get_job_artifacts() {
  curl -sSL --request GET \
    --header "Circle-Token: $TEST_CIRCLECI_TOKEN" \
    "https:/circleci.com/api/v2/project/gh/${CIRCLE_PROJECT_USERNAME}/${CIRCLE_PROJECT_REPONAME}/${1}/artifacts"
}

#
# get test metadata for a job.
#
circleci_get_job_test_metadata() {
  curl -sSL --request GET \
    --header "Circle-Token: $TEST_CIRCLECI_TOKEN" \
    "https:/circleci.com/api/v2/project/gh/${CIRCLE_PROJECT_USERNAME}/${CIRCLE_PROJECT_REPONAME}/${1}/tests"
}
