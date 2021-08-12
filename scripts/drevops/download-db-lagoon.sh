#!/usr/bin/env bash
##
# Download DB dump from Lagoon environment.
#
# This script will create a backup from in the specified environment and
# download it into specified directory.
#
# It will also remove previsously created DB dumps.
#
# It does not rely on 'lagoon-cli', which makes it capable of
# running on hosts without installed laggon-cli.
#
# It does require to use SSH key added to one of the users in Lagoon who has
# SSH access.
# shellcheck disable=SC2029,SC1091,SC2124,SC2140

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

#-------------------------------------------------------------------------------
#                             VARIABLES
#-------------------------------------------------------------------------------

# Directory where DB dumps are stored on the host.
DB_DIR="${DB_DIR:-./.data}"

# Database dump file name on the host.
DB_FILE="${DB_FILE:-db.sql}"

# Lagoon project name.
LAGOON_PROJECT="${LAGOON_PROJECT:?Missing required environment variable LAGOON_PROJECT.}"

# The source environment for the database source.
LAGOON_DB_ENVIRONMENT="${LAGOON_DB_ENVIRONMENT:-master}"

# Remote DB dump directory location.
LAGOON_REMOTE_DB_DIR="/tmp"

# Remote DB dump file name. Cached by the date suffix.
LAGOON_REMOTE_DB_FILE="${LAGOON_REMOTE_DB_FILE:-db_$(date +%Y_%m_%d).sql}"

# Wildcard file name to cleanup previously created dump files.
# Cleanup runs only if the variable is set and LAGOON_REMOTE_DB_FILE does not
# exist.
LAGOON_REMOTE_DB_FILE_CLEANUP="${LAGOON_REMOTE_DB_FILE_CLEANUP:-db_*.sql}"

# The SSH key used to SSH into Lagoon.
LAGOON_SSH_KEY_FILE="${LAGOON_SSH_KEY_FILE:-}"

# The SSH key fingerprint. If provided - the key will be looked-up and loaded
# into ssh client.
DATABASE_SSH_FINGERPRINT="${DATABASE_SSH_FINGERPRINT:-}"

# The SSH host of the Lagoon environment.
LAGOON_SSH_HOST="${LAGOON_SSH_HOST:-ssh.lagoon.amazeeio.cloud}"

# The SSH port of the Lagoon environment.
LAGOON_SSH_PORT="${LAGOON_SSH_PORT:-32222}"

# The SSH user of the Lagoon environment.
LAGOON_SSH_USER="${LAGOON_SSH_USER:-${LAGOON_PROJECT}-${LAGOON_DB_ENVIRONMENT}}"

#-------------------------------------------------------------------------------
#                       DO NOT CHANGE ANYTHING BELOW THIS LINE
#-------------------------------------------------------------------------------

# Try to read credentials from the credentials file.
if [ -f ".env.local" ]; then
  # shellcheck disable=SC1090
  t=$(mktemp) && export -p > "$t" && set -a && . ".env.local" && set +a && . "$t" && rm "$t" && unset t
fi

# Discover and load a custom database dump key if fingerprint is provided.
if [ -n "${DATABASE_SSH_FINGERPRINT}" ]; then
  echo "==> Custom database dump key is provided."
  LAGOON_SSH_KEY_FILE="${DATABASE_SSH_FINGERPRINT//:}"
  LAGOON_SSH_KEY_FILE="${HOME}/.ssh/id_rsa_${LAGOON_SSH_KEY_FILE//\"}"

  [ ! -f "${LAGOON_SSH_KEY_FILE}" ] && echo "ERROR: SSH key file ${LAGOON_SSH_KEY_FILE} does not exist." && exit 1

  if ssh-add -l | grep -q "${LAGOON_SSH_KEY_FILE}"; then
    echo "==> SSH agent has ${LAGOON_SSH_KEY_FILE} key loaded."
  else
    echo "==> SSH agent does not have default key loaded. Trying to load."
    # Remove all other keys and add SSH key from provided fingerprint into SSH agent.
    ssh-add -D > /dev/null
    ssh-add "${LAGOON_SSH_KEY_FILE}"
  fi
fi

ssh_opts=(-o "UserKnownHostsFile=/dev/null")
ssh_opts+=(-o "StrictHostKeyChecking=no")
ssh_opts+=(-o "LogLevel=error")
ssh_opts+=(-p "${LAGOON_SSH_PORT}")
if [ "${LAGOON_SSH_KEY_FILE}" != false ]; then
  ssh_opts+=(-i "${LAGOON_SSH_KEY_FILE}")
fi

ssh \
 "${ssh_opts[@]}" \
  "${LAGOON_SSH_USER}@${LAGOON_SSH_HOST}" service=cli container=cli \
  "if [ ! -f \"${LAGOON_REMOTE_DB_DIR}/${LAGOON_REMOTE_DB_FILE}\" ]; then \
     [ -n \"${LAGOON_REMOTE_DB_FILE_CLEANUP}\" ] && rm -f \"${LAGOON_REMOTE_DB_DIR}\"\/${LAGOON_REMOTE_DB_FILE_CLEANUP} && echo \"Removed previously created DB dumps.\"; \
     echo \"   > Creating a backup ${LAGOON_REMOTE_DB_DIR}/${LAGOON_REMOTE_DB_FILE}.\"; \
     /app/vendor/bin/drush --root=/app/docroot sql-dump --structure-tables-key=common --structure-tables-list=ban,event_log_track,flood,login_security_track,purge_queue,queue,webform_submission,webform_submission_data,webform_submission_log,cache* --extra-dump=--no-tablespaces > \"${LAGOON_REMOTE_DB_DIR}/${LAGOON_REMOTE_DB_FILE}\"; \
   else \
     echo \"   > Using existing dump ${LAGOON_REMOTE_DB_DIR}/${LAGOON_REMOTE_DB_FILE}.\"; \
   fi"

echo "==> Downloading a backup."
ssh_opts_string="${ssh_opts[@]}"
rsync_opts=(-e "ssh $ssh_opts_string")
rsync "${rsync_opts[@]}" "${LAGOON_SSH_USER}@${LAGOON_SSH_HOST}":"${LAGOON_REMOTE_DB_DIR}"/"${LAGOON_REMOTE_DB_FILE}" "${DB_DIR}/${DB_FILE}"
