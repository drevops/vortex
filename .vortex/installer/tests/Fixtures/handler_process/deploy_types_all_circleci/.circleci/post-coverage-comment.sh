#!/usr/bin/env bash
##
## Post code coverage summary as a PR comment on GitHub.
##
## Minimizes previous coverage comments before posting a new one.
##
## Environment variables:
##   CIRCLE_PULL_REQUEST  - CircleCI PR URL.
##   GITHUB_TOKEN         - GitHub token for API access.
##   CIRCLE_PROJECT_USERNAME - GitHub org/user.
##   CIRCLE_PROJECT_REPONAME - GitHub repo name.
##
## Usage:
##   .circleci/post-coverage-comment.sh /path/to/coverage.txt

set -euo pipefail

COVERAGE_FILE="${1:-}"

if [ -z "${COVERAGE_FILE}" ] || [ ! -f "${COVERAGE_FILE}" ]; then
  echo "ERROR: Coverage file not found: ${COVERAGE_FILE}" >&2
  exit 1
fi

if [ -z "${CIRCLE_PULL_REQUEST:-}" ]; then
  echo "Not a pull request. Skipping."
  exit 0
fi

if [ -z "${GITHUB_TOKEN:-}" ]; then
  echo "GITHUB_TOKEN is not set. Skipping."
  exit 0
fi

COVERAGE_CONTENT=$(sed '/./,$!d' "${COVERAGE_FILE}")
PR_NUMBER=$(echo "${CIRCLE_PULL_REQUEST}" | cut -d'/' -f 7)
REPO_SLUG="${CIRCLE_PROJECT_USERNAME}/${CIRCLE_PROJECT_REPONAME}"

MARKER="<!-- coverage-circleci -->"

BODY=$(jq -n --arg body "**Code coverage (CircleCI)**
\`\`\`
${COVERAGE_CONTENT}
\`\`\`
${MARKER}" '{body: $body}')

# Minimize previous coverage comments.
COMMENTS_JSON=$(curl -s \
  -H "Authorization: token ${GITHUB_TOKEN}" \
  -H "Accept: application/vnd.github.v3+json" \
  "https://api.github.com/repos/${REPO_SLUG}/issues/${PR_NUMBER}/comments?per_page=100")

EXISTING_IDS=$(echo "${COMMENTS_JSON}" | jq -r '.[] | select(.body | contains("<!-- coverage-circleci -->")) | .node_id')

for NODE_ID in ${EXISTING_IDS}; do
  GRAPHQL_BODY=$(jq -n --arg id "${NODE_ID}" '{query: "mutation($id:ID!){minimizeComment(input:{subjectId:$id,classifier:OUTDATED}){minimizedComment{isMinimized}}}", variables: {id: $id}}')
  curl -s -X POST \
    -H "Authorization: bearer ${GITHUB_TOKEN}" \
    -H "Content-Type: application/json" \
    "https://api.github.com/graphql" \
    -d "${GRAPHQL_BODY}"
done

# Post new coverage comment.
curl -s -X POST \
  -H "Authorization: token ${GITHUB_TOKEN}" \
  -H "Accept: application/vnd.github.v3+json" \
  "https://api.github.com/repos/${REPO_SLUG}/issues/${PR_NUMBER}/comments" \
  -d "${BODY}"
