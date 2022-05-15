#!/usr/bin/env bash
##
# Download DB dump from Lagoon environment.
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

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

#-------------------------------------------------------------------------------
#                             VARIABLES
#-------------------------------------------------------------------------------

# Flag to download a fresh copy of the database.
DREVOPS_DB_DOWNLOAD_REFRESH="${DREVOPS_DB_DOWNLOAD_REFRESH:-}"

# Lagoon project name.
DREVOPS_DB_DOWNLOAD_LAGOON_PROJECT="${DREVOPS_DB_DOWNLOAD_LAGOON_PROJECT:?Missing required environment variable DREVOPS_DB_DOWNLOAD_LAGOON_PROJECT.}"

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
DREVOPS_DB_DOWNLOAD_LAGOON_SSH_KEY_FILE="${DREVOPS_DB_DOWNLOAD_LAGOON_SSH_KEY_FILE:-}"

# The SSH key fingerprint. If provided - the key will be looked-up and loaded
# into ssh client.
DREVOPS_DB_DOWNLOAD_LAGOON_SSH_FINGERPRINT="${DREVOPS_DB_DOWNLOAD_LAGOON_SSH_FINGERPRINT:-}"

# The SSH host of the Lagoon environment.
DREVOPS_DB_DOWNLOAD_LAGOON_SSH_HOST="${DREVOPS_DB_DOWNLOAD_LAGOON_SSH_HOST:-ssh.lagoon.amazeeio.cloud}"

# The SSH port of the Lagoon environment.
DREVOPS_DB_DOWNLOAD_LAGOON_SSH_PORT="${DREVOPS_DB_DOWNLOAD_LAGOON_SSH_PORT:-32222}"

# The SSH user of the Lagoon environment.
DREVOPS_DB_DOWNLOAD_LAGOON_SSH_USER="${DREVOPS_DB_DOWNLOAD_LAGOON_SSH_USER:-${DREVOPS_DB_DOWNLOAD_LAGOON_PROJECT}-${DREVOPS_DB_DOWNLOAD_LAGOON_ENVIRONMENT}}"

# Directory where DB dumps are stored on the host.
DREVOPS_DB_DIR="${DREVOPS_DB_DIR:-./.data}"

# Database dump file name on the host.
DREVOPS_DB_FILE="${DREVOPS_DB_FILE:-db.sql}"

#-------------------------------------------------------------------------------

# Try to read credentials from the credentials file.
if [ -f ".env.local" ]; then
  # shellcheck disable=SC1090
  t=$(mktemp) && export -p > "$t" && set -a && . ".env.local" && set +a && . "$t" && rm "$t" && unset t
fi

# Discover and load a custom database dump key if fingerprint is provided.
if [ -n "${DREVOPS_DB_DOWNLOAD_LAGOON_SSH_FINGERPRINT}" ]; then
  echo "==> Custom database dump key is provided."
  DREVOPS_DB_DOWNLOAD_LAGOON_SSH_KEY_FILE="${DREVOPS_DB_DOWNLOAD_LAGOON_SSH_FINGERPRINT//:}"
  DREVOPS_DB_DOWNLOAD_LAGOON_SSH_KEY_FILE="${HOME}/.ssh/id_rsa_${DREVOPS_DB_DOWNLOAD_LAGOON_SSH_KEY_FILE//\"}"

  [ ! -f "${DREVOPS_DB_DOWNLOAD_LAGOON_SSH_KEY_FILE}" ] && echo "ERROR: SSH key file ${DREVOPS_DB_DOWNLOAD_LAGOON_SSH_KEY_FILE} does not exist." && exit 1

  if ssh-add -l | grep -q "${DREVOPS_DB_DOWNLOAD_LAGOON_SSH_KEY_FILE}"; then
    echo "==> SSH agent has ${DREVOPS_DB_DOWNLOAD_LAGOON_SSH_KEY_FILE} key loaded."
  else
    echo "==> SSH agent does not have default key loaded. Trying to load."
    # Remove all other keys and add SSH key from provided fingerprint into SSH agent.
    ssh-add -D > /dev/null
    ssh-add "${DREVOPS_DB_DOWNLOAD_LAGOON_SSH_KEY_FILE}"
  fi
fi

ssh_opts=(-o "UserKnownHostsFile=/dev/null")
ssh_opts+=(-o "StrictHostKeyChecking=no")
ssh_opts+=(-o "LogLevel=error")
ssh_opts+=(-o "IdentitiesOnly=yes")
ssh_opts+=(-p "${DREVOPS_DB_DOWNLOAD_LAGOON_SSH_PORT}")
if [ "${DREVOPS_DB_DOWNLOAD_LAGOON_SSH_KEY_FILE}" != false ]; then
  ssh_opts+=(-i "${DREVOPS_DB_DOWNLOAD_LAGOON_SSH_KEY_FILE}")
fi

ssh \
 "${ssh_opts[@]}" \
  "${DREVOPS_DB_DOWNLOAD_LAGOON_SSH_USER}@${DREVOPS_DB_DOWNLOAD_LAGOON_SSH_HOST}" service=cli container=cli \
  "if [ ! -f \"${DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_DIR}/${DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_FILE}\" ] || [ \"${DREVOPS_DB_DOWNLOAD_REFRESH}\" == \"1\" ] ; then \
     [ -n \"${DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_FILE_CLEANUP}\" ] && rm -f \"${DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_DIR}\"\/${DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_FILE_CLEANUP} && echo \"Removed previously created DB dumps.\"; \
     echo \"   > Creating a backup ${DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_DIR}/${DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_FILE}.\"; \
     /app/vendor/bin/drush --root=/app/docroot sql-dump --structure-tables-key=common --structure-tables-list=ban,event_log_track,flood,login_security_track,purge_queue,queue,webform_submission,webform_submission_data,webform_submission_log,cache* --extra-dump=--no-tablespaces > \"${DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_DIR}/${DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_FILE}\"; \
   else \
     echo \"   > Using existing dump ${DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_DIR}/${DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_FILE}.\"; \
   fi"

echo "==> Downloading a backup."
ssh_opts_string="${ssh_opts[@]}"
rsync_opts=(-e "ssh $ssh_opts_string")
rsync "${rsync_opts[@]}" "${DREVOPS_DB_DOWNLOAD_LAGOON_SSH_USER}@${DREVOPS_DB_DOWNLOAD_LAGOON_SSH_HOST}":"${DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_DIR}"/"${DREVOPS_DB_DOWNLOAD_LAGOON_REMOTE_FILE}" "${DREVOPS_DB_DIR}/${DREVOPS_DB_FILE}"
