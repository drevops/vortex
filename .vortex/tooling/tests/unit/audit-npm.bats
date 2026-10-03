#!/usr/bin/env bats
##
# Unit tests for the 'vortex-audit-npm' script.
#
# shellcheck disable=SC2030,SC2031,SC2034

load ../_helper.bash

# Writes a 'package.json' into the 'tree' directory with the given value of the
# 'auditIgnore' key.
fixture_manifest() {
  mkdir -p tree
  printf '{"name": "fixture", "auditIgnore": %s}\n' "${1}" >tree/package.json
}

# Prints a one-line 'npm audit --json' report with 1 advisory per argument, each
# given as '<package>:<severity>:<advisory ID>'.
npm_report() {
  local vulnerabilities='{}'
  local spec package severity id

  for spec in "$@"; do
    IFS=: read -r package severity id <<<"${spec}"
    vulnerabilities="$(jq -c --arg p "${package}" --arg s "${severity}" --arg i "${id}" '. + {($p): {name: $p, severity: $s, via: [{source: 1, name: $p, title: ($p + " issue"), url: ("https://github.com/advisories/" + $i), severity: $s, range: "*"}]}}' <<<"${vulnerabilities}")"
  done

  jq -c '{auditReportVersion: 2, vulnerabilities: .}' <<<"${vulnerabilities}"
}

@test "audit-npm: Passes when the report has no advisories" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_manifest '{}'

  declare -a STEPS=(
    "@npm config get audit-level --prefix=tree # high"
    "@npm audit --package-lock-only --json --prefix=tree # $(npm_report)"

    "Started npm audit."
    "Directory:   tree"
    "Audit level: high"
    "Report:      .logs/audit/npm-audit.json"
    "Audited npm packages."
    "Found no advisories at or above the 'high' audit level."
    "Finished npm audit."
  )

  mocks="$(steps_run "setup")"

  run .vortex/tooling/src/vortex-audit-npm tree
  assert_success

  steps_run "assert" "${mocks[@]}"

  assert_file_exists ".logs/audit/npm-audit.json"
  assert_equal "{}" "$(jq -c '.vulnerabilities' .logs/audit/npm-audit.json)"

  popd >/dev/null || exit 1
}

@test "audit-npm: Fails on an advisory at or above the audit level" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_manifest '{}'

  declare -a STEPS=(
    "@npm config get audit-level --prefix=tree # high"
    "@npm audit --package-lock-only --json --prefix=tree # 1 # $(npm_report "minimist:critical:GHSA-xvch-5gv4-984h" "debug:moderate:GHSA-gxpj-cx7g-858c")"

    "Below the audit level: GHSA-gxpj-cx7g-858c (moderate) in debug - debug issue"
    "Blocking: GHSA-xvch-5gv4-984h (critical) in minimist - minimist issue https://github.com/advisories/GHSA-xvch-5gv4-984h"
    "Found 1 advisories at or above the 'high' audit level and 0 expired or invalid ignore entries."
    "- Finished npm audit."
  )

  mocks="$(steps_run "setup")"

  run .vortex/tooling/src/vortex-audit-npm tree
  assert_failure

  steps_run "assert" "${mocks[@]}"

  # The report is written before the outcome, so code scanning still gets it.
  assert_equal "2" "$(jq '.vulnerabilities | length' .logs/audit/npm-audit.json)"

  popd >/dev/null || exit 1
}

@test "audit-npm: Accepts an ignored advisory and leaves it out of the report" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_manifest '{"GHSA-vfj7-8cjw-p6xm": {"reason": "Build tooling only.", "expires": "2099-12-31"}}'

  report="$(npm_report "braces:high:GHSA-vfj7-8cjw-p6xm" | jq -c '.vulnerabilities.micromatch = {name: "micromatch", severity: "high", via: ["braces"]}')"

  declare -a STEPS=(
    "@npm config get audit-level --prefix=tree # high"
    "@npm audit --package-lock-only --json --prefix=tree # 1 # ${report}"

    "Accepted until 2099-12-31: GHSA-vfj7-8cjw-p6xm in braces - braces issue"
    "Found no advisories at or above the 'high' audit level."
    "Finished npm audit."
    "- Blocking:"
  )

  mocks="$(steps_run "setup")"

  run .vortex/tooling/src/vortex-audit-npm tree
  assert_success

  steps_run "assert" "${mocks[@]}"

  assert_equal "[]" "$(jq -c '.vulnerabilities.braces.via' .logs/audit/npm-audit.json)"
  assert_equal '["braces"]' "$(jq -c '.vulnerabilities.micromatch.via' .logs/audit/npm-audit.json)"

  popd >/dev/null || exit 1
}

@test "audit-npm: Accepts only the ignored advisory when another one blocks" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_manifest '{"GHSA-vfj7-8cjw-p6xm": {"reason": "Build tooling only.", "expires": "2099-12-31"}}'

  declare -a STEPS=(
    "@npm config get audit-level --prefix=tree # high"
    "@npm audit --package-lock-only --json --prefix=tree # 1 # $(npm_report "braces:high:GHSA-vfj7-8cjw-p6xm" "undici:high:GHSA-aaaa-bbbb-cccc")"

    "Accepted until 2099-12-31: GHSA-vfj7-8cjw-p6xm in braces - braces issue"
    "Blocking: GHSA-aaaa-bbbb-cccc (high) in undici - undici issue https://github.com/advisories/GHSA-aaaa-bbbb-cccc"
    "Found 1 advisories at or above the 'high' audit level and 0 expired or invalid ignore entries."
  )

  mocks="$(steps_run "setup")"

  run .vortex/tooling/src/vortex-audit-npm tree
  assert_failure

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "audit-npm: Matches the advisory ID in any case and accepts it on its expiry date" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  today="$(date +%Y-%m-%d)"
  fixture_manifest '{"ghsa-vfj7-8cjw-p6xm": {"reason": "Build tooling only.", "expires": "'"${today}"'"}}'

  declare -a STEPS=(
    "@npm config get audit-level --prefix=tree # high"
    "@npm audit --package-lock-only --json --prefix=tree # 1 # $(npm_report "braces:high:GHSA-vfj7-8cjw-p6xm")"

    "Accepted until ${today}: GHSA-vfj7-8cjw-p6xm in braces - braces issue"
    "Found no advisories at or above the 'high' audit level."
  )

  mocks="$(steps_run "setup")"

  run .vortex/tooling/src/vortex-audit-npm tree
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "audit-npm: Fails on an expired entry and stops accepting its advisory" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_manifest '{"GHSA-vfj7-8cjw-p6xm": {"reason": "Build tooling only.", "expires": "2000-01-01"}}'

  declare -a STEPS=(
    "@npm config get audit-level --prefix=tree # high"
    "@npm audit --package-lock-only --json --prefix=tree # 1 # $(npm_report "braces:high:GHSA-vfj7-8cjw-p6xm")"

    "Expired: GHSA-vfj7-8cjw-p6xm expired on 2000-01-01. Resolve the advisory or renew the entry."
    "Blocking: GHSA-vfj7-8cjw-p6xm (high) in braces - braces issue https://github.com/advisories/GHSA-vfj7-8cjw-p6xm"
    "Found 1 advisories at or above the 'high' audit level and 1 expired or invalid ignore entries."
    "- Accepted until"
  )

  mocks="$(steps_run "setup")"

  run .vortex/tooling/src/vortex-audit-npm tree
  assert_failure

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "audit-npm: Fails on entries without a reason or a valid expiry date" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_manifest '{"GHSA-0001-0001-0001": {"expires": "2099-12-31"}, "GHSA-0002-0002-0002": {"reason": "Some reason.", "expires": "31/12/2099"}, "GHSA-0003-0003-0003": "Some reason."}'

  declare -a STEPS=(
    "@npm config get audit-level --prefix=tree # high"
    "@npm audit --package-lock-only --json --prefix=tree # $(npm_report)"

    "Invalid: GHSA-0001-0001-0001 needs a reason and an expires date in the YYYY-MM-DD format."
    "Invalid: GHSA-0002-0002-0002 needs a reason and an expires date in the YYYY-MM-DD format."
    "Invalid: GHSA-0003-0003-0003 needs a reason and an expires date in the YYYY-MM-DD format."
    "Found 0 advisories at or above the 'high' audit level and 3 expired or invalid ignore entries."
  )

  mocks="$(steps_run "setup")"

  run .vortex/tooling/src/vortex-audit-npm tree
  assert_failure

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "audit-npm: Reports an entry that matches no advisory" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_manifest '{"GHSA-vfj7-8cjw-p6xm": {"reason": "Build tooling only.", "expires": "2099-12-31"}}'

  declare -a STEPS=(
    "@npm config get audit-level --prefix=tree # high"
    "@npm audit --package-lock-only --json --prefix=tree # $(npm_report)"

    "Unused: GHSA-vfj7-8cjw-p6xm matches no advisory. Remove it from auditIgnore."
    "Found no advisories at or above the 'high' audit level."
  )

  mocks="$(steps_run "setup")"

  run .vortex/tooling/src/vortex-audit-npm tree
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "audit-npm: Falls back to the npm default audit level" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_manifest '{}'

  declare -a STEPS=(
    "@npm config get audit-level --prefix=tree # null"
    "@npm audit --package-lock-only --json --prefix=tree # 1 # $(npm_report "debug:low:GHSA-gxpj-cx7g-858c" "thing:info:GHSA-0000-0000-0000")"

    "Audit level: low"
    "Below the audit level: GHSA-0000-0000-0000 (info) in thing - thing issue"
    "Blocking: GHSA-gxpj-cx7g-858c (low) in debug - debug issue https://github.com/advisories/GHSA-gxpj-cx7g-858c"
    "Found 1 advisories at or above the 'low' audit level and 0 expired or invalid ignore entries."
  )

  mocks="$(steps_run "setup")"

  run .vortex/tooling/src/vortex-audit-npm tree
  assert_failure

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "audit-npm: Blocks no advisory at the 'none' audit level" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_manifest '{}'

  declare -a STEPS=(
    "@npm config get audit-level --prefix=tree # none"
    "@npm audit --package-lock-only --json --prefix=tree # 1 # $(npm_report "minimist:critical:GHSA-xvch-5gv4-984h")"

    "Below the audit level: GHSA-xvch-5gv4-984h (critical) in minimist - minimist issue"
    "Found no advisories at or above the 'none' audit level."
  )

  mocks="$(steps_run "setup")"

  run .vortex/tooling/src/vortex-audit-npm tree
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "audit-npm: Identifies an advisory without a link by its npm source ID" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_manifest '{"npm-1234": {"reason": "Not reachable.", "expires": "2099-12-31"}}'

  report="$(npm_report "thing:high:unused" | jq -c '.vulnerabilities.thing.via[0] |= (del(.url) | .source = 1234)')"

  declare -a STEPS=(
    "@npm config get audit-level --prefix=tree # high"
    "@npm audit --package-lock-only --json --prefix=tree # 1 # ${report}"

    "Accepted until 2099-12-31: npm-1234 in thing - thing issue"
    "Found no advisories at or above the 'high' audit level."
  )

  mocks="$(steps_run "setup")"

  run .vortex/tooling/src/vortex-audit-npm tree
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "audit-npm: Audits the current directory and writes the report to a custom path" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  printf '{"name": "fixture"}\n' >package.json

  declare -a STEPS=(
    "@npm config get audit-level --prefix=. # high"
    "@npm audit --package-lock-only --json --prefix=. # $(npm_report)"

    "Directory:   ."
    "Report:      reports/npm.json"
    "Found no advisories at or above the 'high' audit level."
  )

  mocks="$(steps_run "setup")"

  export VORTEX_AUDIT_NPM_REPORT_FILE=reports/npm.json
  run .vortex/tooling/src/vortex-audit-npm
  assert_success

  steps_run "assert" "${mocks[@]}"

  assert_file_exists "reports/npm.json"

  popd >/dev/null || exit 1
}

@test "audit-npm: Fails when npm audit returns an error" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_manifest '{}'

  declare -a STEPS=(
    "@npm config get audit-level --prefix=tree # high"
    '@npm audit --package-lock-only --json --prefix=tree # 1 # {"error": {"code": "ENOLOCK", "summary": "This command requires an existing lockfile."}}'

    "npm audit did not return a report. This command requires an existing lockfile."
    "- Checking advisories against the ignore list."
  )

  mocks="$(steps_run "setup")"

  run .vortex/tooling/src/vortex-audit-npm tree
  assert_failure

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "audit-npm: Fails when npm audit returns no report" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_manifest '{}'

  declare -a STEPS=(
    "@npm config get audit-level --prefix=tree # high"
    "@npm audit --package-lock-only --json --prefix=tree # 1 # not a report"

    "npm audit did not return a report."
  )

  mocks="$(steps_run "setup")"

  run .vortex/tooling/src/vortex-audit-npm tree
  assert_failure

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "audit-npm: Fails when the package.json is missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_command "npm" >/dev/null

  run .vortex/tooling/src/vortex-audit-npm missing
  assert_failure

  assert_output_contains "File missing/package.json does not exist."

  popd >/dev/null || exit 1
}

@test "audit-npm: Fails when the package.json is not valid JSON" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_command "npm" >/dev/null

  mkdir -p tree
  printf 'not json' >tree/package.json

  run .vortex/tooling/src/vortex-audit-npm tree
  assert_failure

  assert_output_contains "File tree/package.json is not valid JSON."

  popd >/dev/null || exit 1
}

@test "audit-npm: Fails when the ignore list is not an object" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mock_command "npm" >/dev/null

  fixture_manifest '["GHSA-vfj7-8cjw-p6xm"]'

  run .vortex/tooling/src/vortex-audit-npm tree
  assert_failure

  assert_output_contains "The 'auditIgnore' value in tree/package.json is not an object keyed by advisory ID."

  popd >/dev/null || exit 1
}
