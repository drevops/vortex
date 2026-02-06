#!/usr/bin/env bash
##
# Update docs.
#
# @usage
# cd .vortex/docs && ./update-docs.sh
#
# shellcheck disable=SC2129

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../../" && pwd)"

sed_opts=(-i) && [ "$(uname)" = "Darwin" ] && sed_opts=(-i '')

OUTPUT_FILE="./content/development/variables.mdx"
sed "${sed_opts[@]}" '/## Variables list/,$d' "${OUTPUT_FILE}"

echo "## Variables list" >>"${OUTPUT_FILE}"
echo "" >>"${OUTPUT_FILE}"
echo "The list below is automatically generated with [Shellvar](https://github.com/alexSkrypnyk/shellvar) from all Shell scripts. " >>"${OUTPUT_FILE}"
echo >>"${OUTPUT_FILE}"

docker run -v "${ROOT_DIR}:/app" drevops/shellvar:1.3.0 extract \
  --skip-text="@docs:skip" \
  --skip-description-prefix=";<" \
  --skip-description-prefix=";>" \
  --exclude-local \
  --exclude-from-file=.vortex/docs/.utils/variables/variables.excluded.txt \
  --sort \
  --unset "UNDEFINED" \
  --format=md-table \
  --md-inline-code-extra-file=.vortex/docs/.utils/variables/variables.inline-code-extra.txt \
  --md-link-vars \
  --md-link-vars-anchor-case=lower \
  --path-strip-prefix="/app/" \
  --column-order="Name,Description,Default value,Defined or used in" \
  --fields='name=Name;description=Description;default_value=Default value;paths=Defined or used in' \
  .env \
  .env.local.example \
  scripts/vortex \
  scripts/custom \
  .vortex/docs/.utils/variables/extra \
  >>"${OUTPUT_FILE}"

sed "${sed_opts[@]}" "s/.vortex\/docs\/.utils\/variables\/extra\/environment.variables.sh/ENVIRONMENT/g" "${OUTPUT_FILE}"
sed "${sed_opts[@]}" "s/.vortex\/docs\/.utils\/variables\/extra\/acquia.variables.sh/ACQUIA ENVIRONMENT/g" "${OUTPUT_FILE}"
sed "${sed_opts[@]}" "s/.vortex\/docs\/.utils\/variables\/extra\/lagoon.variables.sh/LAGOON ENVIRONMENT/g" "${OUTPUT_FILE}"
sed "${sed_opts[@]}" "s/.vortex\/docs\/.utils\/variables\/extra\/.env.local.example.variables.sh/.env.local.example/g" "${OUTPUT_FILE}"
sed "${sed_opts[@]}" "s/.vortex\/docs\/.utils\/variables\/extra\/.env.variables.sh/.env/g" "${OUTPUT_FILE}"
sed "${sed_opts[@]}" "s/.vortex\/docs\/.utils\/variables\/extra\/docker-compose.variables.sh/docker-compose.yml/g" "${OUTPUT_FILE}"
sed "${sed_opts[@]}" "s/.vortex\/docs\/.utils\/variables\/extra\/ci.variables.sh/CI config/g" "${OUTPUT_FILE}"

echo "---" >>"${OUTPUT_FILE}"
echo "Variable list generated with [Shellvar - Utility to work with shell variables](https://github.com/AlexSkrypnyk/shellvar)" >>"${OUTPUT_FILE}"
