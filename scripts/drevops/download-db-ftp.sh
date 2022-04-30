#!/usr/bin/env bash
##
# Download DB dump from FTP.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# The FTP user.
DREVOPS_DB_FTP_USER="${DREVOPS_DB_FTP_USER:-}"

# The FTP password.
DREVOPS_DB_FTP_PASS="${DREVOPS_DB_FTP_PASS:-}"

# The FTP host.
DREVOPS_DB_FTP_HOST="${DREVOPS_DB_FTP_HOST:-}"

# The FTP port.
FTP_PORT="${FTP_PORT:-}"

# The file name, including any directories.
FTP_FILE="${FTP_FILE:-}"

# Directory with database dump file.
DREVOPS_DB_DIR="${DREVOPS_DB_DIR:-./.data}"

# Database dump file name.
DREVOPS_DB_FILE="${DREVOPS_DB_FILE:-db.sql}"

#-------------------------------------------------------------------------------

# Check all required values.
[ -z "${DREVOPS_DB_FTP_USER}" ] && echo "Missing required value for DREVOPS_DB_FTP_USER." && exit 1
[ -z "${DREVOPS_DB_FTP_PASS}" ] && echo "Missing required value for DREVOPS_DB_FTP_PASS." && exit 1
[ -z "${DREVOPS_DB_FTP_HOST}" ] && echo "Missing required value for DREVOPS_DB_FTP_HOST." && exit 1
[ -z "${FTP_PORT}" ] && echo "Missing required value for FTP_PORT." && exit 1
[ -z "${FTP_FILE}" ] && echo "Missing required value for FTP_FILE." && exit 1

curl -u "${DREVOPS_DB_FTP_USER}":"${DREVOPS_DB_FTP_PASS}" "ftp://${DREVOPS_DB_FTP_HOST}:${FTP_PORT}/${FTP_FILE}" -o "${DREVOPS_DB_DIR}/${DREVOPS_DB_FILE}"
