#!/usr/bin/env bash
##
# Download DB dump from S3.
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
VORTEX_DB_DOWNLOAD_S3_ACCESS_KEY="${VORTEX_DB_DOWNLOAD_S3_ACCESS_KEY:-${S3_ACCESS_KEY:-}}"

# AWS secret key.
VORTEX_DB_DOWNLOAD_S3_SECRET_KEY="${VORTEX_DB_DOWNLOAD_S3_SECRET_KEY:-${S3_SECRET_KEY:-}}"

# S3 bucket name.
VORTEX_DB_DOWNLOAD_S3_BUCKET="${VORTEX_DB_DOWNLOAD_S3_BUCKET:-${S3_BUCKET:-}}"

# S3 region.
VORTEX_DB_DOWNLOAD_S3_REGION="${VORTEX_DB_DOWNLOAD_S3_REGION:-${S3_REGION:-}}"

# S3 prefix (path within the bucket).
VORTEX_DB_DOWNLOAD_S3_PREFIX="${VORTEX_DB_DOWNLOAD_S3_PREFIX:-${S3_PREFIX:-}}"

# Directory with database dump file.
VORTEX_DB_DIR="${VORTEX_DB_DIR:-./.data}"

# Database dump file name.
VORTEX_DB_FILE="${VORTEX_DB_FILE:-db.sql}"

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

[ -z "${VORTEX_DB_DOWNLOAD_S3_ACCESS_KEY}" ] && fail "Missing required value for VORTEX_DB_DOWNLOAD_S3_ACCESS_KEY." && exit 1
[ -z "${VORTEX_DB_DOWNLOAD_S3_SECRET_KEY}" ] && fail "Missing required value for VORTEX_DB_DOWNLOAD_S3_SECRET_KEY." && exit 1
[ -z "${VORTEX_DB_DOWNLOAD_S3_BUCKET}" ] && fail "Missing required value for VORTEX_DB_DOWNLOAD_S3_BUCKET." && exit 1
[ -z "${VORTEX_DB_DOWNLOAD_S3_REGION}" ] && fail "Missing required value for VORTEX_DB_DOWNLOAD_S3_REGION." && exit 1

info "Started database dump download from S3."

# Ensure prefix ends with a trailing slash if non-empty.
[ -n "${VORTEX_DB_DOWNLOAD_S3_PREFIX}" ] && VORTEX_DB_DOWNLOAD_S3_PREFIX="${VORTEX_DB_DOWNLOAD_S3_PREFIX%/}/"

mkdir -p "${VORTEX_DB_DIR}"

request_type="GET"
auth_type="AWS4-HMAC-SHA256"
service="s3"
content_type="application/octet-stream"

host="${service}.${VORTEX_DB_DOWNLOAD_S3_REGION}.amazonaws.com"
uri="/${VORTEX_DB_DOWNLOAD_S3_BUCKET}/${VORTEX_DB_DOWNLOAD_S3_PREFIX}${VORTEX_DB_FILE}"
object_url="https://${host}${uri}"
date_short="$(date -u '+%Y%m%d')"
date_long="${date_short}T$(date -u '+%H%M%S')Z"

note "Remote file: ${VORTEX_DB_DOWNLOAD_S3_PREFIX}${VORTEX_DB_FILE}"
note "Local path:  ${VORTEX_DB_DIR}/${VORTEX_DB_FILE}"
note "S3 bucket:   ${VORTEX_DB_DOWNLOAD_S3_BUCKET}"
note "S3 region:   ${VORTEX_DB_DOWNLOAD_S3_REGION}"
[ -n "${VORTEX_DB_DOWNLOAD_S3_PREFIX}" ] && note "S3 prefix:   ${VORTEX_DB_DOWNLOAD_S3_PREFIX}"

# shellcheck disable=SC2059
hash_sha256() { printf "${1}" | openssl dgst -sha256 | sed 's/^.* //'; }
# shellcheck disable=SC2059
hmac_sha256() { printf "${2}" | openssl dgst -sha256 -mac HMAC -macopt "${1}" | sed 's/^.* //'; }

payload_hash="$(printf "" | openssl dgst -sha256 | sed 's/^.* //')"

headers="content-type:${content_type}
host:${host}
x-amz-content-sha256:${payload_hash}
x-amz-date:${date_long}"

signed_headers="content-type;host;x-amz-content-sha256;x-amz-date"
request="${request_type}
${uri}\n
${headers}\n
${signed_headers}
${payload_hash}"

# Create the signature.
# shellcheck disable=SC2059
create_signature() {
  string_to_sign="${auth_type}\n${date_long}\n${date_short}/${VORTEX_DB_DOWNLOAD_S3_REGION}/${service}/aws4_request\n$(hash_sha256 "${request}")"
  date_key=$(hmac_sha256 key:"AWS4${VORTEX_DB_DOWNLOAD_S3_SECRET_KEY}" "${date_short}")
  region_key=$(hmac_sha256 hexkey:"${date_key}" "${VORTEX_DB_DOWNLOAD_S3_REGION}")
  service_key=$(hmac_sha256 hexkey:"${region_key}" "${service}")
  signing_key=$(hmac_sha256 hexkey:"${service_key}" "aws4_request")

  printf "${string_to_sign}" | openssl dgst -sha256 -mac HMAC -macopt hexkey:"${signing_key}" | sed 's/(stdin)= //' | sed 's/SHA2-256//'
}

signature="$(create_signature)"
auth_header="\
${auth_type} Credential=${VORTEX_DB_DOWNLOAD_S3_ACCESS_KEY}/${date_short}/\
${VORTEX_DB_DOWNLOAD_S3_REGION}/${service}/aws4_request, \
SignedHeaders=${signed_headers}, Signature=${signature}"

curl "${object_url}" \
  -H "Authorization: ${auth_header}" \
  -H "content-type: ${content_type}" \
  -H "x-amz-content-sha256: ${payload_hash}" \
  -H "x-amz-date: ${date_long}" \
  -f -S -o "${VORTEX_DB_DIR}/${VORTEX_DB_FILE}"

pass "Finished database dump download from S3."
