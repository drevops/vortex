#!/usr/bin/env bash
##
# Download DB dump from Lagoon environment.
#
# IMPORTANT! This script runs outside the container on the host system.
#
# This script will create a backup from in the specified environment and
# download it into specified directory.
#
# It will also remove previously created DB dumps.
#
# It does not rely on 'lagoon-cli', which makes it capable of
# running on hosts without installed lagooncli.
#
# It does require using SSH key added to one of the users in Lagoon who has
# SSH access.
# shellcheck disable=SC2029,SC1091,SC2124,SC2140

t=$(mktemp) && export -p >"$t" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "$t" && rm "$t" && unset t

set -eu
[ -n "${DREVOPS_DEBUG:-}" ] && set -x

# Flag to download a fresh copy of the database.
DREVOPS_DB_DOWNLOAD_REFRESH="${DREVOPS_DB_DOWNLOAD_REFRESH:-}"

# Lagoon project name.
LAGOON_PROJECT="${LAGOON_PROJECT:?Missing required environment variable LAGOON_PROJECT.}"

# The source environment for the database source.
DREVOPS_DB_DOWNLOAD_LAGOON_ENVIRONMENT="${DREVOPS_DB_DOWNLOAD_LAGOON_ENVIRONMENT:-main}"

# Remote DB dump directory location.
DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_DIR="/tmp"

# Remote DB dump file name. Cached by the date suffix.
DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_FILE="${DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_FILE:-db_$(date +%Y_%m_%d).sql}"

# Wildcard file name to cleanup previously created dump files.
# Cleanup runs only if the variable is set and DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_FILE does not
# exist.
DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_FILE_CLEANUP="${DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_FILE_CLEANUP:-db_*.sql}"

# The SSH key used to SSH into Lagoon.
DREVOPS_DB_DOWNLOAD_SSH_KEY_FILE="${DREVOPS_DB_DOWNLOAD_SSH_KEY_FILE:-}"

# The SSH key fingerprint. If provided - the key will be looked-up and loaded
# into ssh client.
DREVOPS_DB_DOWNLOAD_SSH_FINGERPRINT="${DREVOPS_DB_DOWNLOAD_SSH_FINGERPRINT:-}"

# The SSH host of the Lagoon environment.
DREVOPS_DB_DOWNLOAD_LAGOON_SSH_HOST="${DREVOPS_DB_DOWNLOAD_LAGOON_SSH_HOST:-ssh.lagoon.amazeeio.cloud}"

# The SSH port of the Lagoon environment.
DREVOPS_DB_DOWNLOAD_LAGOON_SSH_PORT="${DREVOPS_DB_DOWNLOAD_LAGOON_SSH_PORT:-32222}"

# The SSH user of the Lagoon environment.
DREVOPS_DB_DOWNLOAD_LAGOON_SSH_USER="${DREVOPS_DB_DOWNLOAD_LAGOON_SSH_USER:-${LAGOON_PROJECT}-${DREVOPS_DB_DOWNLOAD_LAGOON_ENVIRONMENT}}"

# Directory where DB dumps are stored on the host.
DREVOPS_DB_DIR="${DREVOPS_DB_DIR:-./.data}"

# Database dump file name on the host.
DREVOPS_DB_FILE="${DREVOPS_DB_FILE:-db.sql}"

# Path to the root of the project inside the container.
DREVOPS_APP=/app

# Name of the webroot directory with Drupal installation.
DREVOPS_WEBROOT="${DREVOPS_WEBROOT:-web}"

#-------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "$1"; }
info() { [ -z "${TERM_NO_COLOR:-}" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "$1" || printf "[INFO] %s\n" "$1"; }
pass() { [ -z "${TERM_NO_COLOR:-}" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "$1" || printf "[ OK ] %s\n" "$1"; }
fail() { [ -z "${TERM_NO_COLOR:-}" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "$1" || printf "[FAIL] %s\n" "$1"; }
# @formatter:on

info "Started database dump download from Lagoon."

mkdir -p "${DREVOPS_DB_DIR}"

# Try to read credentials from the credentials file.
if [ -f ".env.local" ]; then
  # shellcheck disable=SC1090
  t=$(mktemp) && export -p >"$t" && set -a && . ".env.local" && set +a && . "$t" && rm "$t" && unset t
fi

# Discover and load a custom database dump key if fingerprint is provided.
if [ -n "${DREVOPS_DB_DOWNLOAD_SSH_FINGERPRINT}" ]; then
  note "Custom database dump key is provided."
  DREVOPS_DB_DOWNLOAD_SSH_KEY_FILE="${DREVOPS_DB_DOWNLOAD_SSH_FINGERPRINT//:/}"
  DREVOPS_DB_DOWNLOAD_SSH_KEY_FILE="${HOME}/.ssh/id_rsa_${DREVOPS_DB_DOWNLOAD_SSH_KEY_FILE//\"/}"

  [ ! -f "${DREVOPS_DB_DOWNLOAD_SSH_KEY_FILE}" ] && fail "SSH key file ${DREVOPS_DB_DOWNLOAD_SSH_KEY_FILE} does not exist." && exit 1

  if ssh-add -l | grep -q "${DREVOPS_DB_DOWNLOAD_SSH_KEY_FILE}"; then
    note "SSH agent has ${DREVOPS_DB_DOWNLOAD_SSH_KEY_FILE} key loaded."
  else
    note "SSH agent does not have default key loaded. Trying to load."
    # Remove all other keys and add SSH key from provided fingerprint into SSH agent.
    ssh-add -D >/dev/null
    ssh-add "${DREVOPS_DB_DOWNLOAD_SSH_KEY_FILE}"
  fi
fi

ssh_opts=(-o "UserKnownHostsFile=/dev/null")
ssh_opts+=(-o "StrictHostKeyChecking=no")
ssh_opts+=(-o "LogLevel=error")
ssh_opts+=(-o "IdentitiesOnly=yes")
ssh_opts+=(-p "${DREVOPS_DB_DOWNLOAD_LAGOON_SSH_PORT}")
if [ "${DREVOPS_DB_DOWNLOAD_SSH_KEY_FILE}" != false ]; then
  ssh_opts+=(-i "${DREVOPS_DB_DOWNLOAD_SSH_KEY_FILE}")
fi

ssh \
  "${ssh_opts[@]}" \
  "${DREVOPS_DB_DOWNLOAD_LAGOON_SSH_USER}@${DREVOPS_DB_DOWNLOAD_LAGOON_SSH_HOST}" service=cli container=cli \
  "if [ ! -f \"${DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_DIR}/${DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_FILE}\" ] || [ \"${DREVOPS_DB_DOWNLOAD_REFRESH}\" == \"1\" ] ; then \
     [ -n \"${DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_FILE_CLEANUP}\" ] && rm -f \"${DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_DIR}\"\/${DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_FILE_CLEANUP} && echo \"Removed previously created DB dumps.\"; \
     echo \"      > Creating a backup ${DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_DIR}/${DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_FILE}.\"; \
     /app/vendor/bin/drush --root=${DREVOPS_APP}/${DREVOPS_WEBROOT} sql-dump --structure-tables-key=common --structure-tables-list=ban,event_log_track,flood,login_security_track,purge_queue,queue,webform_submission,webform_submission_data,webform_submission_log,cache* --extra-dump=--no-tablespaces > \"${DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_DIR}/${DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_FILE}\"; \
   else \
     echo \"      > Using existing dump ${DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_DIR}/${DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_FILE}.\"; \
   fi"

note "Downloading a backup."
ssh_opts_string="${ssh_opts[@]}"
rsync_opts=(-e "ssh $ssh_opts_string")
rsync "${rsync_opts[@]}" "${DREVOPS_DB_DOWNLOAD_LAGOON_SSH_USER}@${DREVOPS_DB_DOWNLOAD_LAGOON_SSH_HOST}":"${DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_DIR}"/"${DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_FILE}" "${DREVOPS_DB_DIR}/${DREVOPS_DB_FILE}"

pass "Finished database dump download from Lagoon."
