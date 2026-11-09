#!/usr/bin/env bash
##
# Lint the Vortex release signing key.
#
# Passes when '.vortex/installer/PUBLIC_KEY.asc' has the fingerprint that
# 'vortex-release-installer.yml' pins, the installation docs show that
# fingerprint, and the key does not expire within 60 days.
#
# LCOV_EXCL_START

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

ROOT_DIR="$(dirname "$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)")"

WORKFLOW="${ROOT_DIR}/.github/workflows/vortex-release-installer.yml"

PUBLIC_KEY="${ROOT_DIR}/.vortex/installer/PUBLIC_KEY.asc"

DOCS="${ROOT_DIR}/.vortex/docs/content/installation.mdx"

# Days before the expiry of the key when this check starts to fail.
EXPIRY_NOTICE_DAYS=60

echo "==> Linting the release signing key."

fingerprint="$(sed -n 's/^  RELEASE_GPG_FINGERPRINT: \([0-9A-F]\{40\}\)$/\1/p' "${WORKFLOW}")"
[ -n "${fingerprint}" ] || { echo "ERROR: ${WORKFLOW} pins no RELEASE_GPG_FINGERPRINT."; exit 1; }

key="$(gpg --batch --show-keys --with-colons "${PUBLIC_KEY}" 2>/dev/null)"

grep -q "^fpr:::::::::${fingerprint}:$" <<<"${key}" || { echo "ERROR: ${PUBLIC_KEY} does not hold the key ${fingerprint}."; exit 1; }

grep -qF "${fingerprint}" "${DOCS}" || { echo "ERROR: ${DOCS} does not show the fingerprint ${fingerprint}."; exit 1; }

expires="$(awk -F: '$1 == "pub" { print $7; exit }' <<<"${key}")"
if [ -n "${expires}" ] && [ "${expires}" -lt "$(($(date +%s) + EXPIRY_NOTICE_DAYS * 86400))" ]; then
  echo "ERROR: The key ${fingerprint} expires within ${EXPIRY_NOTICE_DAYS} days. Extend it with 'gpg --quick-set-expire ${fingerprint} 2y' and export the public key to ${PUBLIC_KEY} again."
  exit 1
fi

echo "  > The release signing key ${fingerprint} matches the workflow and the docs."

# LCOV_EXCL_STOP
