#!/usr/bin/env bash
##
# Update project labels in GitHub.
#
# @usage:
# Interactive prompt:
# ./github-labels.sh
#
# Silent, if $DREVOPS_GITHUB_TOKEN or $GITHUB_TOKEN is set in an environment and
# a repository provided as an argument:
# DREVOPS_GITHUB_TOKEN=ghp_123 DREVOPS_GITHUB_REPO=myorg/myrepo ./github-labels.sh
#
# shellcheck disable=SC2155

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# GitHub token to perform operations.
DREVOPS_GITHUB_TOKEN="${DREVOPS_GITHUB_TOKEN:-${GITHUB_TOKEN}}"

# GitHub repository as "org/name" to perform operations on.
DREVOPS_GITHUB_REPO="${DREVOPS_GITHUB_REPO:-$1}"

# Delete existing labels to mirror the list below.
DREVOPS_GITHUB_DELETE_EXISTING_LABELS="${DREVOPS_GITHUB_DELETE_EXISTING_LABELS:-1}"

# Array of labels to create. If DELETE_EXISTING_LABELS=1, the labels list will
# be exactly as below, otherwise labels below will be added to existing ones.
labels=(
  "AUTOMERGE" "934BF4" "Pull request has been approved and set to automerge"
  "CONFLICT" "bc143e" "Pull request has a conflict that needs to be resolved before it can be merged"
  "DO NOT MERGE" "d93f0b" "Do not merge this pull request"
  "Do not review" "d93f0b" "Do not review this pull request"
  "Needs review" "5319e7" "Pull request needs a review from assigned developers"
  "Questions" "b5f492" "Pull request has some questions that need to be answered before further review can progress"
  "Ready for test" "0e8a16" "Pull request is ready for manual testing"
  "Ready to be merged" "c2e0c6" "Pull request is ready to be merged (assigned after testing is complete)"
  "Requires more work" "b60205" "Pull request was reviewed and reviver(s) asked to work further on the pull request"
  "URGENT" "d93f0b" "Pull request needs to be urgently reviewed"
  "dependencies" "62E795" "Pull request was raised automatically by a dependency bot"

  # Uncomment default Github labels below to preserve them.
  # "bug"                 "d73a4a"  "Something isn't working"
  # "duplicate"           "cfd3d7"  "This issue or pull request already exists"
  # "enhancement"         "a2eeef"  "New feature or request"
  # "help wanted"         "008672"  "Extra attention is needed"
  # "good first issue"    "7057ff"  "Good for newcomers"
  # "invalid"             "e4e669o" "This doesn't seem right"
  # "question"            "d876e3"  "Further information is requested"
  # "wontfix"             "ffffff"  "This will not be worked on"
)

# ------------------------------------------------------------------------------

main(){
  echo "==> Processing GitHub labels."

  echo
  if [ "${DREVOPS_GITHUB_DELETE_EXISTING_LABELS}" = "1" ]; then
    echo "  This script will remove the default GitHub labels."
  else
    echo "  This script will not remove the default GitHub labels."
  fi
  echo "  This script will create new labels."
  echo "  A personal access token is required to access private repositories."
  echo

  if [  "${DREVOPS_GITHUB_REPO}" = "" ]; then
    echo ''
    echo -n 'Enter GitHub Org/Repo (e.g. myorg/myrepo): '
    read -r DREVOPS_GITHUB_REPO
  fi

  [  "${DREVOPS_GITHUB_REPO}" = "" ] && echo "ERROR: GitHub repository name is required" && exit 1

  if [  "${DREVOPS_GITHUB_TOKEN}" = "" ]; then
    echo ''
    echo -n 'GitHub Personal Access Token: '
    read -r -s DREVOPS_GITHUB_TOKEN
  fi
  [  "${DREVOPS_GITHUB_TOKEN}" = "" ] && echo "ERROR: GitHub token name is required" && exit 1

  repo_org=$(echo "$DREVOPS_GITHUB_REPO" | cut -f1 -d /)
  repo_name=$(echo "$DREVOPS_GITHUB_REPO" | cut -f2 -d /)

  if ! user_has_access; then
    echo "ERROR: User does not have access to specified repository. Please check your credentials" && exit 1
  fi

  echo
  echo "  > Starting label processing"
  echo

  timeout 5
  echo

  if [ "${DREVOPS_GITHUB_DELETE_EXISTING_LABELS}" = "1" ]; then
    echo "  > Checking existing labels"
    existing_labels_strings="$(label_all)"
    # shellcheck disable=SC2207
    IFS=$'\n' existing_labels=( $(xargs -n1 <<<"${existing_labels_strings}") )
    for existing_label_name in "${existing_labels[@]}"; do
      if ! is_provided_label "${existing_label_name}"; then
        echo "    Removing label \"${existing_label_name}\" as it is not in thr provided list"
        if label_delete "${existing_label_name}"; then
          echo "    DELETED label \"${existing_label_name}\""
        else
          echo "    Unable to DELETE label \"${existing_label_name}\""
        fi
      fi
    done
  fi

  count=0
  for value in "${labels[@]}"; do
    if (( count % 3 == 0)); then
      name="${value}"
    elif (( count % 3 == 1)); then
      color="${value}"
    else
      description="${value}"

      echo "  > Processing label \"${name}\""
      if label_exists "${name}"; then
        if label_update "${name}" "${color}" "${description}"; then
          echo "    UPDATED label \"${name}\""
        else
          echo "    Unable to UPDATE label \"${name}\""
        fi
      else
        if label_create "${name}" "${color}" "${description}"; then
          echo "    CREATED label \"${name}\""
        else
          echo "    Unable to CREATE label \"${name}\""
        fi
      fi

    fi
    count=$(( count + 1 ))
  done

  echo
  echo "==> Label processing complete"
  echo
}

is_provided_label(){
  label="${1}"

  count=0
  for value in "${labels[@]}"; do
    if (( count % 3 == 0)); then
      name="${value}"
      if [ "${label}" = "${name}" ]; then
        return 0;
      fi
    fi
    count=$(( count + 1 ))
  done

  return 1
}

user_has_access(){
  status=$( \
    curl -s -I \
    -u "${DREVOPS_GITHUB_TOKEN}":x-oauth-basic \
    --include -H "Accept: application/vnd.github.symmetra-preview+json" \
    -o /dev/null \
    -w "%{http_code}" \
    --request GET \
    "https://api.github.com/repos/${repo_org}/${repo_name}/labels" \
  )
  [ "${status}" = "200" ]
}

label_all(){
  response=$( \
    curl -s \
    -u "${DREVOPS_GITHUB_TOKEN}":x-oauth-basic \
    --include -H "Accept: application/vnd.github.symmetra-preview+json" \
    --request GET \
    "https://api.github.com/repos/${repo_org}/${repo_name}/labels" \
  )
  jsonval "${response}" "name"
}

label_exists() {
  local name="${1}"
  local name_encoded=$(uriencode "${name}")
  status=$( \
    curl -s -I \
    -u "${DREVOPS_GITHUB_TOKEN}":x-oauth-basic \
    --include -H "Accept: application/vnd.github.symmetra-preview+json" \
    -o /dev/null \
    -w "%{http_code}" \
    --request GET \
    "https://api.github.com/repos/${repo_org}/${repo_name}/labels/${name_encoded}" \
    )
  [ "${status}" = "200" ]
}

label_create(){
  local name="${1}"
  local color="${2}"
  local description="${3}"
  local status=$(curl -s \
    -u "${DREVOPS_GITHUB_TOKEN}":x-oauth-basic \
    -H "Accept: application/vnd.github.symmetra-preview+json" \
    -o /dev/null \
    -w "%{http_code}" \
    --request POST \
    --data "{\"name\":\"${name}\",\"color\":\"${color}\", \"description\":\"${description}\"}" \
    "https://api.github.com/repos/${repo_org}/${repo_name}/labels" \
  )
  [ "${status}" = "201" ]
}

label_update(){
  local name="${1}"
  local color="${2}"
  local description="${3}"
  local name_encoded=$(uriencode "${name}")
  local status=$(curl -s \
    -u "${DREVOPS_GITHUB_TOKEN}":x-oauth-basic \
    -H "Accept: application/vnd.github.symmetra-preview+json" \
    -o /dev/null \
    -w "%{http_code}" \
    --request PATCH \
    --data "{\"name\":\"${name}\",\"color\":\"${color}\", \"description\":\"${description}\"}" \
    "https://api.github.com/repos/${repo_org}/${repo_name}/labels/${name_encoded}" \
  )
  [ "${status}" = "200" ]
}

label_delete(){
  local name="${1}"
  local color="${2}"
  local description="${3}"
  local name_encoded=$(uriencode "${name}")
  local status=$(curl -s \
    -u "${DREVOPS_GITHUB_TOKEN}":x-oauth-basic \
    -H "Accept: application/vnd.github.symmetra-preview+json" \
    -o /dev/null \
    -w "%{http_code}" \
    --request DELETE \
    "https://api.github.com/repos/${repo_org}/${repo_name}/labels/${name_encoded}" \
  )
  [ "${status}" = "204" ]
}

jsonval(){
  local json="${1}"
  local prop="${2}"

  temp=$(echo "${json}" \
    | sed 's/\\\\\//\//g' \
    | sed 's/[{}]//g'    \
    | awk -v k="text" '{n=split($0,a,","); for (i=1; i<=n; i++) print a[i]}' \
    | sed 's/\"\:\"/\|/g' \
    | sed 's/[\,]/ /g' \
    | grep -w "${prop}" \
    | cut -d":" -f2 \
    | sed -e 's/^ *//g' -e 's/ *$//g' \
  )
  temp="${temp//${prop}|/}"
  temp="$(echo "${temp}"|tr '\r\n' ' ')"

  echo "${temp}"
}

uriencode(){
  s="${1//'%'/%25}"
  s="${s//' '/%20}"
  s="${s//'"'/%22}"
  s="${s//'#'/%23}"
  s="${s//'$'/%24}"
  s="${s//'&'/%26}"
  s="${s//'+'/%2B}"
  s="${s//','/%2C}"
  s="${s//'/'/%2F}"
  s="${s//':'/%3A}"
  s="${s//';'/%3B}"
  s="${s//'='/%3D}"
  s="${s//'?'/%3F}"
  s="${s//'@'/%40}"
  s="${s//'['/%5B}"
  s="${s//']'/%5D}"
  printf %s "$s"
}

timeout(){
  local seconds=${1}
  while [ "${seconds}" -gt 0 ]; do
     echo -ne "Processing will start in $seconds seconds. Press Ctrl+C to abort\033[0K\r"
     sleep 1
     : $((seconds--))
  done
  echo
}

main "$@"
