#!/usr/bin/env bats
##
# Unit tests for the 'vortex-lighthouse' script.
#
# The mocked 'lighthouse' copies a report fixture to the output path as its
# side effect, so the real 'node' reads the scores.
#
#shellcheck disable=SC2030,SC2031,SC2034,SC2016

load ../_helper.bash

LH_ARGS="--quiet --chrome-flags=--headless=new --no-sandbox --disable-dev-shm-usage --only-categories=performance,accessibility,best-practices,seo --output=json --output=html"

# Clears the Lighthouse variables inherited from the shell running the suite
# and writes the report fixtures.
lighthouse_env_reset() {
  unset VORTEX_LIGHTHOUSE_BASE_URL VORTEX_LIGHTHOUSE_URLS VORTEX_LIGHTHOUSE_SITEMAP VORTEX_LIGHTHOUSE_SITEMAP_LIMIT VORTEX_LIGHTHOUSE_FORM_FACTOR VORTEX_LIGHTHOUSE_BUDGET_PERFORMANCE VORTEX_LIGHTHOUSE_BUDGET_ACCESSIBILITY VORTEX_LIGHTHOUSE_BUDGET_BEST_PRACTICES VORTEX_LIGHTHOUSE_BUDGET_SEO VORTEX_LIGHTHOUSE_REPORT_DIR VORTEX_LIGHTHOUSE_CHROME_FLAGS

  rm ./.env && touch ./.env

  mkdir -p ./lh-fixtures
  printf '%s' '{"categories":{"performance":{"score":0.91},"accessibility":{"score":1},"best-practices":{"score":0.96},"seo":{"score":0.8}}}' >./lh-fixtures/good.json
  printf '%s' '{"categories":{"performance":{"score":0.42},"accessibility":{"score":0.87},"best-practices":{"score":1},"seo":{"score":null}}}' >./lh-fixtures/poor.json
}

@test "Lighthouse: audit the default page" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  lighthouse_env_reset

  declare -a STEPS=(
    "@lighthouse http://nginx:8080/ ${LH_ARGS} --output-path=.logs/lighthouse/01-front # 0 #  # cp ./lh-fixtures/good.json .logs/lighthouse/01-front.report.json"

    "Started Lighthouse audit."
    "Pages to audit: 1"
    "Form factor: mobile"
    "Reports: .logs/lighthouse"
    "Auditing http://nginx:8080/."
    "Performance: 91"
    "Accessibility: 100"
    "Best practices: 96"
    "SEO: 80"
    "Audited http://nginx:8080/."
    "Summary: .logs/lighthouse/summary.md"
    "Finished Lighthouse audit."
    "- (budget"
    "- Discovering pages"
  )

  mocks="$(steps_run "setup")"

  run .vortex/tooling/src/vortex-lighthouse
  assert_success

  steps_run "assert" "${mocks[@]}"

  assert_file_contains ".logs/lighthouse/summary.md" "## Lighthouse audit (mobile)"
  assert_file_contains ".logs/lighthouse/summary.md" "| Page | Performance | Accessibility | Best practices | SEO |"
  assert_file_contains ".logs/lighthouse/summary.md" "| http://nginx:8080/ | 91 | 100 | 96 | 80 |"

  popd >/dev/null || exit 1
}

@test "Lighthouse: audit pages passed as arguments" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  lighthouse_env_reset

  # Arguments replace the configured list, and a repeated page is audited once.
  export VORTEX_LIGHTHOUSE_URLS="/configured"

  declare -a STEPS=(
    "@lighthouse http://nginx:8080/about-us ${LH_ARGS} --output-path=.logs/lighthouse/01-about-us # 0 #  # cp ./lh-fixtures/good.json .logs/lighthouse/01-about-us.report.json"
    "@lighthouse https://example.com/news?page=2 ${LH_ARGS} --output-path=.logs/lighthouse/02-news-page-2 # 0 #  # cp ./lh-fixtures/good.json .logs/lighthouse/02-news-page-2.report.json"

    "Pages to audit: 2"
    "Audited http://nginx:8080/about-us."
    "Audited https://example.com/news?page=2."
    "- /configured"
  )

  mocks="$(steps_run "setup")"

  run .vortex/tooling/src/vortex-lighthouse about-us https://example.com/news?page=2 /about-us
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Lighthouse: cut a long path in the report name" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  lighthouse_env_reset

  long="$(printf 'a%.0s' {1..300})"
  stem="search-q-$(printf 'a%.0s' {1..91})"

  declare -a STEPS=(
    "@lighthouse http://nginx:8080/search?q=${long} ${LH_ARGS} --output-path=.logs/lighthouse/01-${stem} # 0 #  # cp ./lh-fixtures/good.json .logs/lighthouse/01-${stem}.report.json"

    "Finished Lighthouse audit."
  )

  mocks="$(steps_run "setup")"

  run .vortex/tooling/src/vortex-lighthouse "/search?q=${long}"
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Lighthouse: audit configured pages on desktop with custom settings" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  lighthouse_env_reset

  export VORTEX_LIGHTHOUSE_BASE_URL="http://web.local/"
  export VORTEX_LIGHTHOUSE_URLS="/, /node/1 ,/node/1"
  export VORTEX_LIGHTHOUSE_FORM_FACTOR=desktop
  export VORTEX_LIGHTHOUSE_REPORT_DIR=reports/lh
  export VORTEX_LIGHTHOUSE_CHROME_FLAGS="--headless=new"

  mkdir -p reports/lh
  touch reports/lh/stale.report.json reports/lh/stale.report.html reports/lh/summary.md reports/lh/phpunit.xml

  declare -a STEPS=(
    "@lighthouse http://web.local/ --quiet --chrome-flags=--headless=new --only-categories=performance,accessibility,best-practices,seo --output=json --output=html --output-path=reports/lh/01-front --preset=desktop # 0 #  # cp ./lh-fixtures/good.json reports/lh/01-front.report.json"
    "@lighthouse http://web.local/node/1 --quiet --chrome-flags=--headless=new --only-categories=performance,accessibility,best-practices,seo --output=json --output=html --output-path=reports/lh/02-node-1 --preset=desktop # 0 #  # cp ./lh-fixtures/good.json reports/lh/02-node-1.report.json"

    "Pages to audit: 2"
    "Form factor: desktop"
    "Reports: reports/lh"
    "Finished Lighthouse audit."
  )

  mocks="$(steps_run "setup")"

  run .vortex/tooling/src/vortex-lighthouse
  assert_success

  steps_run "assert" "${mocks[@]}"

  # Reports of a previous run are removed, and files of other tools are kept.
  assert_file_not_exists "reports/lh/stale.report.json"
  assert_file_not_exists "reports/lh/stale.report.html"
  assert_file_exists "reports/lh/phpunit.xml"
  assert_file_contains "reports/lh/summary.md" "## Lighthouse audit (desktop)"
  assert_file_contains "reports/lh/summary.md" "| http://web.local/node/1 | 91 | 100 | 96 | 80 |"

  popd >/dev/null || exit 1
}

@test "Lighthouse: audit pages discovered from the sitemap" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  lighthouse_env_reset

  export VORTEX_LIGHTHOUSE_SITEMAP=/sitemap.xml
  export VORTEX_LIGHTHOUSE_SITEMAP_LIMIT=3

  # Hosts are replaced with the base URL, a '<loc>' wrapped across lines is
  # read, the front page is not repeated and the 4th page is over the limit.
  declare -a STEPS=(
    '@wget -q -O - http://nginx:8080/sitemap.xml # 0 # <?xml version="1.0"?>\n<urlset>\n<url><loc>https://www.example.com/</loc></url>\n<url><loc>\n  http://example-site.docker.amazee.io/node/1\n</loc></url>\n<url><loc>https://www.example.com/search?a=1&amp;b=2</loc></url>\n<url><loc>https://www.example.com/node/4</loc></url>\n</urlset>'
    "@lighthouse http://nginx:8080/ ${LH_ARGS} --output-path=.logs/lighthouse/01-front # 0 #  # cp ./lh-fixtures/good.json .logs/lighthouse/01-front.report.json"
    "@lighthouse http://nginx:8080/node/1 ${LH_ARGS} --output-path=.logs/lighthouse/02-node-1 # 0 #  # cp ./lh-fixtures/good.json .logs/lighthouse/02-node-1.report.json"
    "@lighthouse http://nginx:8080/search?a=1&b=2 ${LH_ARGS} --output-path=.logs/lighthouse/03-search-a-1-b-2 # 0 #  # cp ./lh-fixtures/good.json .logs/lighthouse/03-search-a-1-b-2.report.json"

    "Discovering pages from the sitemap http://nginx:8080/sitemap.xml."
    "Discovered 3 page(s) from the sitemap."
    "Pages to audit: 3"
    "- /node/4"
    "Finished Lighthouse audit."
  )

  mocks="$(steps_run "setup")"

  run .vortex/tooling/src/vortex-lighthouse
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Lighthouse: follow a sitemap index" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  lighthouse_env_reset

  export VORTEX_LIGHTHOUSE_URLS="/about"
  export VORTEX_LIGHTHOUSE_SITEMAP=https://www.example.com/sitemap.xml
  export VORTEX_LIGHTHOUSE_SITEMAP_LIMIT=2

  # A child sitemap needs no '.xml' suffix, and the 3rd child is not fetched
  # because the first 2 already reach the limit.
  declare -a STEPS=(
    '@wget -q -O - https://www.example.com/sitemap.xml # 0 # <sitemapindex><sitemap><loc>https://www.example.com/sitemaps/pages</loc></sitemap><sitemap><loc>https://www.example.com/sitemap.xml?page=2</loc></sitemap><sitemap><loc>https://www.example.com/sitemap.xml?page=3</loc></sitemap></sitemapindex>'
    '@wget -q -O - http://nginx:8080/sitemaps/pages # 0 # <urlset><url><loc>https://www.example.com/node/2</loc></url></urlset>'
    '@wget -q -O - http://nginx:8080/sitemap.xml?page=2 # 0 # <urlset><url><loc>https://www.example.com/node/3</loc></url><url><loc>https://www.example.com/node/4</loc></url></urlset>'
    "@lighthouse http://nginx:8080/about ${LH_ARGS} --output-path=.logs/lighthouse/01-about # 0 #  # cp ./lh-fixtures/good.json .logs/lighthouse/01-about.report.json"
    "@lighthouse http://nginx:8080/node/2 ${LH_ARGS} --output-path=.logs/lighthouse/02-node-2 # 0 #  # cp ./lh-fixtures/good.json .logs/lighthouse/02-node-2.report.json"
    "@lighthouse http://nginx:8080/node/3 ${LH_ARGS} --output-path=.logs/lighthouse/03-node-3 # 0 #  # cp ./lh-fixtures/good.json .logs/lighthouse/03-node-3.report.json"

    "Discovered 2 page(s) from the sitemap."
    "Pages to audit: 3"
    "- /node/4"
    "- page=3"
    "Finished Lighthouse audit."
  )

  mocks="$(steps_run "setup")"

  run .vortex/tooling/src/vortex-lighthouse
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Lighthouse: fail when the sitemap cannot be fetched" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  lighthouse_env_reset

  export VORTEX_LIGHTHOUSE_SITEMAP=/sitemap.xml

  declare -a STEPS=(
    "@wget -q -O - http://nginx:8080/sitemap.xml # 8"

    "Unable to fetch the sitemap http://nginx:8080/sitemap.xml."
    "- Auditing"
  )

  mocks="$(steps_run "setup")"

  run .vortex/tooling/src/vortex-lighthouse
  assert_failure

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Lighthouse: fail when there are no pages to audit" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  lighthouse_env_reset

  export VORTEX_LIGHTHOUSE_URLS=","

  declare -a STEPS=(
    "No pages to audit. Set VORTEX_LIGHTHOUSE_URLS or VORTEX_LIGHTHOUSE_SITEMAP."
    "- Auditing"
  )

  run .vortex/tooling/src/vortex-lighthouse
  assert_failure

  steps_run "assert"

  popd >/dev/null || exit 1
}

@test "Lighthouse: fail when Lighthouse cannot audit a page" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  lighthouse_env_reset

  declare -a STEPS=(
    "@lighthouse http://nginx:8080/ ${LH_ARGS} --output-path=.logs/lighthouse/01-front # 1 # Runtime error encountered: ERRORED_DOCUMENT_REQUEST"

    "Auditing http://nginx:8080/."
    "Lighthouse could not audit http://nginx:8080/."
    "- Finished Lighthouse audit."
  )

  mocks="$(steps_run "setup")"

  run .vortex/tooling/src/vortex-lighthouse
  assert_failure

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Lighthouse: fail when Lighthouse writes no report" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  lighthouse_env_reset

  declare -a STEPS=(
    "@lighthouse http://nginx:8080/ ${LH_ARGS} --output-path=.logs/lighthouse/01-front # 0"

    "Lighthouse did not write the report .logs/lighthouse/01-front.report.json."
  )

  mocks="$(steps_run "setup")"

  run .vortex/tooling/src/vortex-lighthouse
  assert_failure

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Lighthouse: pass when every score meets its budget" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  lighthouse_env_reset

  export VORTEX_LIGHTHOUSE_BUDGET_PERFORMANCE=91
  export VORTEX_LIGHTHOUSE_BUDGET_ACCESSIBILITY=100
  export VORTEX_LIGHTHOUSE_BUDGET_BEST_PRACTICES=90
  export VORTEX_LIGHTHOUSE_BUDGET_SEO=80

  declare -a STEPS=(
    "@lighthouse http://nginx:8080/ ${LH_ARGS} --output-path=.logs/lighthouse/01-front # 0 #  # cp ./lh-fixtures/good.json .logs/lighthouse/01-front.report.json"

    "Performance: 91 (budget 91)"
    "Accessibility: 100 (budget 100)"
    "Best practices: 96 (budget 90)"
    "SEO: 80 (budget 80)"
    "- below budget"
    "Finished Lighthouse audit."
  )

  mocks="$(steps_run "setup")"

  run .vortex/tooling/src/vortex-lighthouse
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Lighthouse: fail when scores are below their budgets" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  lighthouse_env_reset

  export VORTEX_LIGHTHOUSE_URLS="/ /slow"
  export VORTEX_LIGHTHOUSE_BUDGET_PERFORMANCE=50
  export VORTEX_LIGHTHOUSE_BUDGET_ACCESSIBILITY=90
  export VORTEX_LIGHTHOUSE_BUDGET_SEO=70

  # Every page is audited before the audit fails, and a category Lighthouse
  # could not score counts as below its budget.
  declare -a STEPS=(
    "@lighthouse http://nginx:8080/ ${LH_ARGS} --output-path=.logs/lighthouse/01-front # 0 #  # cp ./lh-fixtures/good.json .logs/lighthouse/01-front.report.json"
    "@lighthouse http://nginx:8080/slow ${LH_ARGS} --output-path=.logs/lighthouse/02-slow # 0 #  # cp ./lh-fixtures/poor.json .logs/lighthouse/02-slow.report.json"

    "Performance: 91 (budget 50)"
    "Performance: 42 (budget 50) - below budget"
    "Accessibility: 87 (budget 90) - below budget"
    "Best practices: 100"
    "SEO: n/a (budget 70) - below budget"
    "Audited http://nginx:8080/slow."
    "Lighthouse found 3 score(s) below the budget."
    "- Finished Lighthouse audit."
  )

  mocks="$(steps_run "setup")"

  run .vortex/tooling/src/vortex-lighthouse
  assert_failure

  steps_run "assert" "${mocks[@]}"

  assert_file_contains ".logs/lighthouse/summary.md" "| http://nginx:8080/ | 91 | 100 | 96 | 80 |"
  assert_file_contains ".logs/lighthouse/summary.md" "| http://nginx:8080/slow | 42 (below 50) | 87 (below 90) | 100 | n/a (below 70) |"

  popd >/dev/null || exit 1
}

@test "Lighthouse: report a score Lighthouse could not compute without a budget" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  lighthouse_env_reset

  declare -a STEPS=(
    "@lighthouse http://nginx:8080/ ${LH_ARGS} --output-path=.logs/lighthouse/01-front # 0 #  # cp ./lh-fixtures/poor.json .logs/lighthouse/01-front.report.json"

    "SEO: n/a"
    "Finished Lighthouse audit."
  )

  mocks="$(steps_run "setup")"

  run .vortex/tooling/src/vortex-lighthouse
  assert_success

  steps_run "assert" "${mocks[@]}"

  popd >/dev/null || exit 1
}

@test "Lighthouse: reject invalid settings" {
  pushd "${LOCAL_REPO_DIR}" >/dev/null || exit 1

  lighthouse_env_reset

  export VORTEX_LIGHTHOUSE_FORM_FACTOR=tablet
  run .vortex/tooling/src/vortex-lighthouse
  assert_failure
  assert_output_contains "Unsupported form factor 'tablet'. Use 'mobile' or 'desktop'."

  export VORTEX_LIGHTHOUSE_FORM_FACTOR=mobile
  export VORTEX_LIGHTHOUSE_BUDGET_SEO=101
  run .vortex/tooling/src/vortex-lighthouse
  assert_failure
  assert_output_contains "SEO budget '101' must be a number from 0 to 100."

  export VORTEX_LIGHTHOUSE_BUDGET_SEO=0
  export VORTEX_LIGHTHOUSE_BUDGET_BEST_PRACTICES=high
  run .vortex/tooling/src/vortex-lighthouse
  assert_failure
  assert_output_contains "Best practices budget 'high' must be a number from 0 to 100."

  export VORTEX_LIGHTHOUSE_BUDGET_BEST_PRACTICES=0
  export VORTEX_LIGHTHOUSE_SITEMAP_LIMIT=all
  run .vortex/tooling/src/vortex-lighthouse
  assert_failure
  assert_output_contains "Sitemap limit 'all' must be a number."

  popd >/dev/null || exit 1
}
