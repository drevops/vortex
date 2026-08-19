#!/usr/bin/env bash
##
# Update docs.
#
# @usage
# cd .vortex/docs && ./update-docs.sh
#
# The assertion markers below contain literal backticks: the table renders paths
# as inline code.
#
# shellcheck disable=SC2129,SC2016

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../../" && pwd)"

sed_opts=(-i) && [ "$(uname)" = "Darwin" ] && sed_opts=(-i '')

OUTPUT_FILE="./content/development/variables.mdx"

TOOLING_DIR=".vortex/tooling/src"

# Shellvar descends only into '*.sh' and '*.bash' files when handed a directory,
# and the shipped tooling scripts are extensionless, so they are passed as
# explicit file paths.
tooling_paths=()
for tooling_file in "${ROOT_DIR}/${TOOLING_DIR}/"*; do
  [ -f "${tooling_file}" ] || continue
  tooling_paths+=("${TOOLING_DIR}/$(basename "${tooling_file}")")
done

[ "${#tooling_paths[@]}" -gt 0 ] || {
  echo "ERROR: No scripts found in ${TOOLING_DIR}." >&2
  exit 1
}

table="$(docker run -v "${ROOT_DIR}:/app" drevops/shellvar:1.7.0 extract \
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
  "${tooling_paths[@]}" \
  scripts \
  .vortex/docs/.utils/variables/extra)"

# An input that yields no variables leaves the table unchanged, which the CI
# diff check reports as up-to-date docs. Each input is asserted to be present in
# the extracted table instead, before the file is written.
#
# $1 - substring marking the input in the "Defined or used in" column.
# $2 - input path to name in the failure message.
assert_source_present() {
  case "${table}" in
    *"${1}"*) return 0 ;;
  esac

  echo "ERROR: Shellvar extracted no variables from '${2}'." >&2
  exit 1
}

# The 'sed' replacements below alias the 'extra' stub paths onto display labels,
# so the assertions run while the paths are still verbatim.
assert_source_present '`.env`' '.env'
assert_source_present '`.env.local.example`' '.env.local.example'
assert_source_present "\`${TOOLING_DIR}/" "${TOOLING_DIR}"
assert_source_present '`scripts/' 'scripts'
assert_source_present '`.vortex/docs/.utils/variables/extra/' '.vortex/docs/.utils/variables/extra'

sed "${sed_opts[@]}" '/## Variables list/,$d' "${OUTPUT_FILE}"

echo "## Variables list" >>"${OUTPUT_FILE}"
echo "" >>"${OUTPUT_FILE}"
echo "The list below is automatically generated with [Shellvar](https://github.com/alexSkrypnyk/shellvar) from all Shell scripts. " >>"${OUTPUT_FILE}"
echo >>"${OUTPUT_FILE}"

printf '%s\n' "${table}" >>"${OUTPUT_FILE}"

sed "${sed_opts[@]}" "s/.vortex\/docs\/.utils\/variables\/extra\/environment.variables.sh/ENVIRONMENT/g" "${OUTPUT_FILE}"
sed "${sed_opts[@]}" "s/.vortex\/docs\/.utils\/variables\/extra\/acquia.variables.sh/ACQUIA ENVIRONMENT/g" "${OUTPUT_FILE}"
sed "${sed_opts[@]}" "s/.vortex\/docs\/.utils\/variables\/extra\/lagoon.variables.sh/LAGOON ENVIRONMENT/g" "${OUTPUT_FILE}"
sed "${sed_opts[@]}" "s/.vortex\/docs\/.utils\/variables\/extra\/.env.local.example.variables.sh/.env.local.example/g" "${OUTPUT_FILE}"
sed "${sed_opts[@]}" "s/.vortex\/docs\/.utils\/variables\/extra\/.env.variables.sh/.env/g" "${OUTPUT_FILE}"
sed "${sed_opts[@]}" "s/.vortex\/docs\/.utils\/variables\/extra\/docker-compose.variables.sh/docker-compose.yml/g" "${OUTPUT_FILE}"
sed "${sed_opts[@]}" "s/.vortex\/docs\/.utils\/variables\/extra\/ci.variables.sh/CI config/g" "${OUTPUT_FILE}"
