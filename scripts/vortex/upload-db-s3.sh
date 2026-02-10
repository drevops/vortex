#!/usr/bin/env bash
##
# Upload DB dump to S3.
#
# Uses AWS Signature Version 4 with curl (no AWS CLI required).
#
# IMPORTANT! This script runs outside the container on the host system.
#
# shellcheck disable=SC1090,SC1091

t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "${t}" && rm "${t}" && unset t

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# AWS access key.
VORTEX_UPLOAD_DB_S3_ACCESS_KEY="${VORTEX_UPLOAD_DB_S3_ACCESS_KEY:-${S3_ACCESS_KEY:-}}"

# AWS secret key.
VORTEX_UPLOAD_DB_S3_SECRET_KEY="${VORTEX_UPLOAD_DB_S3_SECRET_KEY:-${S3_SECRET_KEY:-}}"

# S3 bucket name.
VORTEX_UPLOAD_DB_S3_BUCKET="${VORTEX_UPLOAD_DB_S3_BUCKET:-${S3_BUCKET:-}}"

# S3 region.
VORTEX_UPLOAD_DB_S3_REGION="${VORTEX_UPLOAD_DB_S3_REGION:-${S3_REGION:-}}"

# S3 prefix (path within the bucket).
VORTEX_UPLOAD_DB_S3_PREFIX="${VORTEX_UPLOAD_DB_S3_PREFIX:-${S3_PREFIX:-}}"

# Directory with database dump file.
VORTEX_UPLOAD_DB_S3_DB_DIR="${VORTEX_UPLOAD_DB_S3_DB_DIR:-${VORTEX_DB_DIR:-./.data}}"

# Database dump file name.
VORTEX_UPLOAD_DB_S3_DB_FILE="${VORTEX_UPLOAD_DB_S3_DB_FILE:-${VORTEX_DB_FILE:-db.sql}}"

# Remote database dump file name.
VORTEX_UPLOAD_DB_S3_REMOTE_FILE="${VORTEX_UPLOAD_DB_S3_REMOTE_FILE:-db.sql}"

# S3 storage class.
VORTEX_UPLOAD_DB_S3_STORAGE_CLASS="${VORTEX_UPLOAD_DB_S3_STORAGE_CLASS:-STANDARD}"

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "${1}"; }
task() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[TASK] %s\033[0m\n" "${1}" || printf "[TASK] %s\n" "${1}"; }
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[36m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

for cmd in curl openssl; do command -v "${cmd}" >/dev/null || {
  fail "Command ${cmd} is not available"
  exit 1
}; done

[ -z "${VORTEX_UPLOAD_DB_S3_ACCESS_KEY}" ] && fail "Missing required value for VORTEX_UPLOAD_DB_S3_ACCESS_KEY." && exit 1
[ -z "${VORTEX_UPLOAD_DB_S3_SECRET_KEY}" ] && fail "Missing required value for VORTEX_UPLOAD_DB_S3_SECRET_KEY." && exit 1
[ -z "${VORTEX_UPLOAD_DB_S3_BUCKET}" ] && fail "Missing required value for VORTEX_UPLOAD_DB_S3_BUCKET." && exit 1
[ -z "${VORTEX_UPLOAD_DB_S3_REGION}" ] && fail "Missing required value for VORTEX_UPLOAD_DB_S3_REGION." && exit 1

local_file="${VORTEX_UPLOAD_DB_S3_DB_DIR}/${VORTEX_UPLOAD_DB_S3_DB_FILE}"
[ ! -f "${local_file}" ] && fail "Database dump file ${local_file} does not exist." && exit 1

info "Started database dump upload to S3."

# Ensure prefix ends with a trailing slash if non-empty.
[ -n "${VORTEX_UPLOAD_DB_S3_PREFIX}" ] && VORTEX_UPLOAD_DB_S3_PREFIX="${VORTEX_UPLOAD_DB_S3_PREFIX%/}/"

request_type="PUT"
auth_type="AWS4-HMAC-SHA256"
service="s3"
base_url=".${service}.${VORTEX_UPLOAD_DB_S3_REGION}.amazonaws.com"
date_short=$(date -u +'%Y%m%d')
date_long=$(date -u +'%Y%m%dT%H%M%SZ')
object_url="https://${VORTEX_UPLOAD_DB_S3_BUCKET}${base_url}/${VORTEX_UPLOAD_DB_S3_PREFIX}${VORTEX_UPLOAD_DB_S3_REMOTE_FILE}"

note "Local file:     ${local_file}"
note "Remote file:    ${VORTEX_UPLOAD_DB_S3_PREFIX}${VORTEX_UPLOAD_DB_S3_REMOTE_FILE}"
note "S3 bucket:      ${VORTEX_UPLOAD_DB_S3_BUCKET}"
note "S3 region:      ${VORTEX_UPLOAD_DB_S3_REGION}"
[ -n "${VORTEX_UPLOAD_DB_S3_PREFIX}" ] && note "S3 prefix:      ${VORTEX_UPLOAD_DB_S3_PREFIX}"
note "Storage class:  ${VORTEX_UPLOAD_DB_S3_STORAGE_CLASS}"

if hash file 2>/dev/null; then
  content_type="$(file --brief --mime-type "${local_file}")"
else
  content_type='application/octet-stream'
fi

payload_hash=$(openssl dgst -sha256 -hex <"${local_file}" 2>/dev/null | sed 's/^.* //')

aws_sign4() {
  l_date=$(printf '%s' "$2" | openssl dgst -sha256 -hex -mac HMAC -macopt "key:AWS4$1" 2>/dev/null | sed 's/^.* //')
  l_region=$(printf '%s' "$3" | openssl dgst -sha256 -hex -mac HMAC -macopt "hexkey:${l_date}" 2>/dev/null | sed 's/^.* //')
  l_service=$(printf '%s' "$4" | openssl dgst -sha256 -hex -mac HMAC -macopt "hexkey:${l_region}" 2>/dev/null | sed 's/^.* //')
  l_signing=$(printf 'aws4_request' | openssl dgst -sha256 -hex -mac HMAC -macopt "hexkey:${l_service}" 2>/dev/null | sed 's/^.* //')
  printf '%s' "$5" | openssl dgst -sha256 -hex -mac HMAC -macopt "hexkey:${l_signing}" 2>/dev/null | sed 's/^.* //'
}

header_list='content-type;host;x-amz-content-sha256;x-amz-date;x-amz-server-side-encryption;x-amz-storage-class'

canonical_request="\
${request_type}
/${VORTEX_UPLOAD_DB_S3_PREFIX}${VORTEX_UPLOAD_DB_S3_REMOTE_FILE}

content-type:${content_type}
host:${VORTEX_UPLOAD_DB_S3_BUCKET}${base_url}
x-amz-content-sha256:${payload_hash}
x-amz-date:${date_long}
x-amz-server-side-encryption:AES256
x-amz-storage-class:${VORTEX_UPLOAD_DB_S3_STORAGE_CLASS}

${header_list}
${payload_hash}"

canonical_request_hash=$(printf '%s' "${canonical_request}" | openssl dgst -sha256 -hex 2>/dev/null | sed 's/^.* //')

string_to_sign="\
${auth_type}
${date_long}
${date_short}/${VORTEX_UPLOAD_DB_S3_REGION}/${service}/aws4_request
${canonical_request_hash}"

signature=$(aws_sign4 "${VORTEX_UPLOAD_DB_S3_SECRET_KEY}" "${date_short}" "${VORTEX_UPLOAD_DB_S3_REGION}" "${service}" "${string_to_sign}")

curl --silent --location --proto-redir =https --request "${request_type}" --upload-file "${local_file}" \
  --header "Content-Type: ${content_type}" \
  --header "Host: ${VORTEX_UPLOAD_DB_S3_BUCKET}${base_url}" \
  --header "X-Amz-Content-SHA256: ${payload_hash}" \
  --header "X-Amz-Date: ${date_long}" \
  --header "X-Amz-Server-Side-Encryption: AES256" \
  --header "X-Amz-Storage-Class: ${VORTEX_UPLOAD_DB_S3_STORAGE_CLASS}" \
  --header "Authorization: ${auth_type} Credential=${VORTEX_UPLOAD_DB_S3_ACCESS_KEY}/${date_short}/${VORTEX_UPLOAD_DB_S3_REGION}/${service}/aws4_request, SignedHeaders=${header_list}, Signature=${signature}" \
  "${object_url}"

pass "Finished database dump upload to S3."
