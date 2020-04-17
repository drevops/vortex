#!/usr/bin/env bash
##
# Download DB dump from FTP.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# The FTP user.
FTP_USER="${FTP_USER:-}"

# The FTP password.
FTP_PASS="${FTP_PASS:-}"

# The FTP host.
FTP_HOST="${FTP_HOST:-}"

# The FTP port.
FTP_PORT="${FTP_PORT:-}"

# The file name, including any directories.
FTP_FILE="${FTP_FILE:-}"

# Directory with database dump file.
DB_DIR="${DB_DIR:-./.data}"

# Database dump file name.
DB_FILE="${DB_FILE:-db.sql}"

#-------------------------------------------------------------------------------

# Check all required values.
[ -z "${FTP_USER}" ] && echo "Missing required value for FTP_USER." && exit 1
[ -z "${FTP_PASS}" ] && echo "Missing required value for FTP_PASS." && exit 1
[ -z "${FTP_HOST}" ] && echo "Missing required value for FTP_HOST." && exit 1
[ -z "${FTP_PORT}" ] && echo "Missing required value for FTP_PORT." && exit 1
[ -z "${FTP_FILE}" ] && echo "Missing required value for FTP_FILE." && exit 1

curl -u "${FTP_USER}":"${FTP_PASS}" "ftp://${FTP_HOST}:${FTP_PORT}/${FTP_FILE}" -o "${DB_DIR}/${DB_FILE}"
