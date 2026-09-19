#!/usr/bin/env bats
##
# Unit tests for the 'vortex-convert-audit-sarif' script.
#
# shellcheck disable=SC2030,SC2031,SC2034

load ../_helper.bash

# Writes a 'composer audit' report fixture at the path the script reads by
# default.
fixture_audit_report() {
  mkdir -p .logs/audit
  printf '%s' "${1}" >.logs/audit/composer-audit.json
}

# Writes an 'npm audit --json' report fixture.
fixture_npm_report() {
  mkdir -p .logs/audit
  printf '%s' "${1}" >.logs/audit/npm-audit.json
}

# Reads a value out of the SARIF report produced from the Composer fixture.
sarif() {
  jq -r "${1}" .logs/audit/composer-audit.sarif
}

# Reads a value out of the SARIF report produced from the npm fixture.
sarif_npm() {
  jq -r "${1}" .logs/audit/npm-audit.sarif
}

@test "convert-audit-sarif: Converts an advisory carrying a link, a CVE and a severity" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_audit_report '{
    "advisories": {
      "drupal/core": [
        {
          "advisoryId": "PKSA-1111-2222-3333",
          "packageName": "drupal/core",
          "affectedVersions": ">=11.0.0,<11.4.6",
          "title": "Access bypass",
          "cve": "CVE-2026-0001",
          "link": "https://www.drupal.org/sa-core-2026-001",
          "severity": "high"
        }
      ]
    },
    "abandoned": []
  }'

  run .vortex/tooling/src/vortex-convert-audit-sarif
  assert_success

  assert_output_contains "Started audit report conversion."
  assert_output_contains "Findings:  1"
  assert_output_contains "Finished audit report conversion."

  assert_file_exists ".logs/audit/composer-audit.sarif"

  assert_equal "2.1.0" "$(sarif '.version')"
  assert_equal "Composer Audit" "$(sarif '.runs[0].tool.driver.name')"

  assert_equal "PKSA-1111-2222-3333" "$(sarif '.runs[0].results[0].ruleId')"
  assert_equal "error" "$(sarif '.runs[0].results[0].level')"
  assert_equal "drupal/core: Access bypass (affects >=11.0.0,<11.4.6)." "$(sarif '.runs[0].results[0].message.text')"
  assert_equal "composer.lock" "$(sarif '.runs[0].results[0].locations[0].physicalLocation.artifactLocation.uri')"
  assert_equal "PKSA-1111-2222-3333|drupal/core" "$(sarif '.runs[0].results[0].partialFingerprints["vortexAudit/v1"]')"

  assert_equal "Access bypass" "$(sarif '.runs[0].tool.driver.rules[0].shortDescription.text')"
  assert_equal "drupal/core is affected by CVE-2026-0001. Affected versions: >=11.0.0,<11.4.6." "$(sarif '.runs[0].tool.driver.rules[0].fullDescription.text')"
  assert_equal "https://www.drupal.org/sa-core-2026-001" "$(sarif '.runs[0].tool.driver.rules[0].helpUri')"
  assert_equal "8.0" "$(sarif '.runs[0].tool.driver.rules[0].properties["security-severity"]')"
  assert_equal "security composer" "$(sarif '.runs[0].tool.driver.rules[0].properties.tags | join(" ")')"

  popd >/dev/null || exit 1
}

@test "convert-audit-sarif: Maps advisory severities to levels and scores" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  cases=(
    '"critical" error 9.5'
    '"high" error 8.0'
    '"medium" warning 5.5'
    '"moderate" warning 5.5'
    '"low" warning 3.0'
    '"whatever" warning null'
    "null warning null"
  )

  for entry in "${cases[@]}"; do
    severity="${entry%% *}"
    rest="${entry#* }"
    level="${rest%% *}"
    score="${rest#* }"

    fixture_audit_report '{
      "advisories": {
        "vendor/thing": [
          {
            "advisoryId": "PKSA-1-2-3",
            "packageName": "vendor/thing",
            "affectedVersions": "<2.0.0",
            "title": "Issue",
            "severity": '"${severity}"'
          }
        ]
      },
      "abandoned": []
    }'

    run .vortex/tooling/src/vortex-convert-audit-sarif
    assert_success

    assert_equal "${level}" "$(sarif '.runs[0].results[0].level')"
    assert_equal "${score}" "$(sarif '.runs[0].tool.driver.rules[0].properties["security-severity"]')"
  done

  popd >/dev/null || exit 1
}

@test "convert-audit-sarif: Falls back to the Packagist advisory link" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_audit_report '{
    "advisories": {
      "vendor/thing": [
        {"advisoryId": "PKSA-1-2-3", "packageName": "vendor/thing", "title": "Issue", "cve": "CVE-1", "link": null}
      ]
    },
    "abandoned": []
  }'

  run .vortex/tooling/src/vortex-convert-audit-sarif
  assert_success

  assert_equal "https://packagist.org/security-advisories/PKSA-1-2-3" "$(sarif '.runs[0].tool.driver.rules[0].helpUri')"

  popd >/dev/null || exit 1
}

@test "convert-audit-sarif: Falls back to the CVE record link" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_audit_report '{
    "advisories": {
      "vendor/thing": [
        {"advisoryId": "internal-1", "packageName": "vendor/thing", "title": "Issue", "cve": "CVE-1"}
      ]
    },
    "abandoned": []
  }'

  run .vortex/tooling/src/vortex-convert-audit-sarif
  assert_success

  assert_equal "https://www.cve.org/CVERecord?id=CVE-1" "$(sarif '.runs[0].tool.driver.rules[0].helpUri')"

  popd >/dev/null || exit 1
}

@test "convert-audit-sarif: Omits the help link when the advisory carries none" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_audit_report '{
    "advisories": {
      "vendor/thing": [
        {"advisoryId": "internal-1", "packageName": "vendor/thing", "title": "Issue"}
      ]
    },
    "abandoned": []
  }'

  run .vortex/tooling/src/vortex-convert-audit-sarif
  assert_success

  assert_equal "null" "$(sarif '.runs[0].tool.driver.rules[0].helpUri')"
  assert_equal "vendor/thing is affected by internal-1. Affected versions: *." "$(sarif '.runs[0].tool.driver.rules[0].help.markdown')"

  popd >/dev/null || exit 1
}

@test "convert-audit-sarif: Reports abandoned packages at note level" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_audit_report '{
    "advisories": [],
    "abandoned": {"vendor/old": "vendor/new", "vendor/dead": null}
  }'

  run .vortex/tooling/src/vortex-convert-audit-sarif
  assert_success

  assert_output_contains "Findings:  2"

  assert_equal "1" "$(sarif '.runs[0].tool.driver.rules | length')"
  assert_equal "composer-audit/abandoned-package" "$(sarif '.runs[0].tool.driver.rules[0].id')"
  assert_equal "maintainability composer" "$(sarif '.runs[0].tool.driver.rules[0].properties.tags | join(" ")')"

  assert_equal "note" "$(sarif '.runs[0].results[0].level')"
  assert_equal "vendor/old is abandoned. Use vendor/new instead." "$(sarif '.runs[0].results[0].message.text')"
  assert_equal "vendor/dead is abandoned and suggests no replacement." "$(sarif '.runs[0].results[1].message.text')"

  popd >/dev/null || exit 1
}

@test "convert-audit-sarif: Reports dependency policy matches" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_audit_report '{
    "advisories": [],
    "abandoned": [],
    "filter": {
      "vendor/malicious": [
        {"packageName": "vendor/malicious", "listName": "malware", "constraint": "== 1.0.0", "url": "https://example.com/malware", "reason": "Ships a backdoor.", "id": "MAL-1", "source": "packagist.org"}
      ],
      "vendor/blocked": [
        {"packageName": "vendor/blocked", "listName": "internal", "constraint": "<2.0.0", "url": null, "reason": null, "id": null, "source": null}
      ]
    }
  }'

  run .vortex/tooling/src/vortex-convert-audit-sarif
  assert_success

  assert_output_contains "Findings:  2"

  assert_equal "composer-audit/policy-malware" "$(sarif '.runs[0].results[0].ruleId')"
  assert_equal "error" "$(sarif '.runs[0].results[0].level')"
  assert_equal "vendor/malicious matched the malware dependency policy (== 1.0.0). Ships a backdoor." "$(sarif '.runs[0].results[0].message.text')"
  assert_equal "composer-audit/policy-malware|vendor/malicious" "$(sarif '.runs[0].results[0].partialFingerprints["vortexAudit/v1"]')"
  assert_equal "https://example.com/malware" "$(sarif '.runs[0].tool.driver.rules[0].helpUri')"

  assert_equal "composer-audit/policy-internal" "$(sarif '.runs[0].results[1].ruleId')"
  assert_equal "vendor/blocked matched the internal dependency policy (<2.0.0)." "$(sarif '.runs[0].results[1].message.text')"
  assert_equal "null" "$(sarif '.runs[0].tool.driver.rules[1].helpUri')"

  popd >/dev/null || exit 1
}

@test "convert-audit-sarif: Skips ignored advisories and malformed entries" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_audit_report '{
    "advisories": {"vendor/broken": "not-a-list", "vendor/empty": ["not-an-advisory"]},
    "ignored-advisories": {
      "vendor/assessed": [
        {"advisoryId": "PKSA-9-9-9", "packageName": "vendor/assessed", "title": "Dismissed", "ignoreReason": "Not exploitable."}
      ]
    },
    "abandoned": []
  }'

  run .vortex/tooling/src/vortex-convert-audit-sarif
  assert_success

  assert_output_contains "Findings:  0"

  assert_equal "0" "$(sarif '.runs[0].results | length')"
  assert_equal "0" "$(sarif '.runs[0].tool.driver.rules | length')"

  popd >/dev/null || exit 1
}

@test "convert-audit-sarif: Writes a valid report when there are no findings" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_audit_report '{"advisories": [], "abandoned": [], "filter": []}'

  run .vortex/tooling/src/vortex-convert-audit-sarif
  assert_success

  # shellcheck disable=SC2016
  assert_equal "https://json.schemastore.org/sarif-2.1.0.json" "$(sarif '."$schema"')"
  assert_equal "0" "$(sarif '.runs[0].results | length')"

  popd >/dev/null || exit 1
}

@test "convert-audit-sarif: Locates a finding at the package line in the lock file" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  printf '%s\n' '{' '    "packages": [' '        {' '            "name": "drupal/core",' '            "authors": [' '                {' '                    "name": "Dries Buytaert"' '                }' '            ]' '        },' '        {' '            "name": "vendor/thing",' '            "replace": {' '                "name": "drupal/core",' '            }' '        }' '    ]' '}' >composer.lock

  fixture_audit_report '{
    "advisories": {
      "vendor/thing": [
        {"advisoryId": "PKSA-1-2-3", "packageName": "vendor/thing", "title": "Issue"}
      ],
      "vendor/absent": [
        {"advisoryId": "PKSA-4-5-6", "packageName": "vendor/absent", "title": "Issue"}
      ]
    },
    "abandoned": {"drupal/core": null}
  }'

  run .vortex/tooling/src/vortex-convert-audit-sarif
  assert_success

  assert_equal "12" "$(sarif '.runs[0].results[0].locations[0].physicalLocation.region.startLine')"
  assert_equal "1" "$(sarif '.runs[0].results[1].locations[0].physicalLocation.region.startLine')"
  assert_equal "4" "$(sarif '.runs[0].results[2].locations[0].physicalLocation.region.startLine')"

  popd >/dev/null || exit 1
}

@test "convert-audit-sarif: Locates findings in a lock file at a non-default path" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  mkdir -p nested
  printf '%s\n' '{' '    "packages": [' '        {' '            "name": "vendor/thing",' '            "version": "1.0.0"' '        }' '    ]' '}' >nested/composer.lock

  fixture_audit_report '{
    "advisories": {
      "vendor/thing": [
        {"advisoryId": "PKSA-1-2-3", "packageName": "vendor/thing", "title": "Issue"}
      ]
    },
    "abandoned": []
  }'

  run .vortex/tooling/src/vortex-convert-audit-sarif .logs/audit/composer-audit.json composer nested/composer.lock
  assert_success

  assert_equal "nested/composer.lock" "$(sarif '.runs[0].results[0].locations[0].physicalLocation.artifactLocation.uri')"
  assert_equal "4" "$(sarif '.runs[0].results[0].locations[0].physicalLocation.region.startLine')"

  popd >/dev/null || exit 1
}

@test "convert-audit-sarif: Converts an npm report, splitting the via array into findings" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_npm_report '{
    "auditReportVersion": 2,
    "vulnerabilities": {
      "minimist": {
        "name": "minimist",
        "severity": "critical",
        "via": [
          {"title": "Prototype Pollution", "url": "https://github.com/advisories/GHSA-vh95-rmgr-6w4m", "severity": "moderate", "cvss": {"score": 5.6}, "range": ">=1.0.0 <1.2.3"},
          {"title": "Prototype Pollution", "url": "https://github.com/advisories/GHSA-xvch-5gv4-984h", "severity": "critical", "cvss": {"score": 9.8}, "range": ">=1.0.0 <1.2.6"},
          "another-package"
        ]
      }
    }
  }'

  run .vortex/tooling/src/vortex-convert-audit-sarif .logs/audit/npm-audit.json npm
  assert_success

  assert_output_contains "Format:    npm"
  assert_output_contains "Findings:  2"

  assert_equal "npm audit" "$(sarif_npm '.runs[0].tool.driver.name')"

  assert_equal "GHSA-vh95-rmgr-6w4m" "$(sarif_npm '.runs[0].results[0].ruleId')"
  assert_equal "warning" "$(sarif_npm '.runs[0].results[0].level')"
  assert_equal "5.6" "$(sarif_npm '.runs[0].tool.driver.rules[0].properties["security-severity"]')"
  assert_equal "security npm" "$(sarif_npm '.runs[0].tool.driver.rules[0].properties.tags | join(" ")')"
  assert_equal "https://github.com/advisories/GHSA-vh95-rmgr-6w4m" "$(sarif_npm '.runs[0].tool.driver.rules[0].helpUri')"

  assert_equal "GHSA-xvch-5gv4-984h" "$(sarif_npm '.runs[0].results[1].ruleId')"
  assert_equal "error" "$(sarif_npm '.runs[0].results[1].level')"
  assert_equal "9.8" "$(sarif_npm '.runs[0].tool.driver.rules[1].properties["security-severity"]')"
  assert_equal "minimist: Prototype Pollution (affects >=1.0.0 <1.2.6)." "$(sarif_npm '.runs[0].results[1].message.text')"
  assert_equal "GHSA-xvch-5gv4-984h|minimist" "$(sarif_npm '.runs[0].results[1].partialFingerprints["vortexAudit/v1"]')"

  popd >/dev/null || exit 1
}

@test "convert-audit-sarif: Falls back to the npm severity word when there is no CVSS score" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_npm_report '{
    "auditReportVersion": 2,
    "vulnerabilities": {
      "thing": {
        "name": "thing",
        "severity": "info",
        "via": [
          {"title": "Informational", "url": "https://github.com/advisories/GHSA-0000-0000-0000", "severity": "info", "range": "*"}
        ]
      }
    }
  }'

  run .vortex/tooling/src/vortex-convert-audit-sarif .logs/audit/npm-audit.json npm
  assert_success

  assert_equal "note" "$(sarif_npm '.runs[0].results[0].level')"
  assert_equal "null" "$(sarif_npm '.runs[0].tool.driver.rules[0].properties["security-severity"]')"

  popd >/dev/null || exit 1
}

@test "convert-audit-sarif: Locates an npm finding at its lock file entry" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  printf '%s\n' '{' '    "lockfileVersion": 3,' '    "packages": {' '        "": {},' '        "node_modules/minimist": {' '            "version": "1.2.0"' '        }' '    }' '}' >npm.lock.json

  fixture_npm_report '{
    "auditReportVersion": 2,
    "vulnerabilities": {
      "minimist": {"name": "minimist", "severity": "critical", "via": [{"title": "Issue", "url": "https://github.com/advisories/GHSA-1", "severity": "critical", "range": "*"}]}
    }
  }'

  run .vortex/tooling/src/vortex-convert-audit-sarif .logs/audit/npm-audit.json npm npm.lock.json
  assert_success

  assert_equal "npm.lock.json" "$(sarif_npm '.runs[0].results[0].locations[0].physicalLocation.artifactLocation.uri')"
  assert_equal "5" "$(sarif_npm '.runs[0].results[0].locations[0].physicalLocation.region.startLine')"

  popd >/dev/null || exit 1
}

@test "convert-audit-sarif: Fails on an unknown report format" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  run .vortex/tooling/src/vortex-convert-audit-sarif .logs/audit/composer-audit.json yarn
  assert_failure

  assert_output_contains "Unknown report format yarn. Use 'composer' or 'npm'."

  popd >/dev/null || exit 1
}

@test "convert-audit-sarif: Fails when the audit report is missing" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  run .vortex/tooling/src/vortex-convert-audit-sarif
  assert_failure

  assert_output_contains "Audit report .logs/audit/composer-audit.json does not exist."

  popd >/dev/null || exit 1
}

@test "convert-audit-sarif: Fails when the audit report is not valid JSON" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  fixture_audit_report 'not json'

  run .vortex/tooling/src/vortex-convert-audit-sarif
  assert_failure

  assert_output_contains "Audit report .logs/audit/composer-audit.json is not valid JSON."

  popd >/dev/null || exit 1
}
