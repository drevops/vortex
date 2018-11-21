#!/usr/bin/env bash
#
# Test runner for workflow tests.
#
# The test steps are sequential here as if a developer would run them.
# Using Goss to check the state of the system at the different point in the
# workflow.
set -e

# keep track of the last executed command
trap 'last_command=$current_command; current_command=$BASH_COMMAND' DEBUG
# echo an error message before exiting
trap '[ "$?" != "0" ] && echo "\"${last_command}\" command failed with exit code $?."' EXIT

CURDIR="$(cd "$(dirname "$(dirname "${BASH_SOURCE[0]}")")/.." && pwd)"
BUILD_DIR=${BUILD_DIR:-/tmp/drupal-dev-workflow}
DRUPAL_VERSION=${DRUPAL_VERSION:-7}
VOLUMES_MOUNTED=${VOLUMES_MOUNTED:-1}

# Print step.
step(){
  echo
  echo "==> STEP: $1"
}

# Sync files to host in case if volumes are not mounted from host.
sync_to_host(){
  export $(grep -v '^#' .env | xargs)
  [ "$VOLUMES_MOUNTED" == "1" ] && return
  echo "Syncing from $(docker-compose ps -q cli) to $BUILD_DIR"
  docker cp -L $(docker-compose ps -q cli):/app/. $BUILD_DIR
}

# Sync files to container in case if volumes are not mounted from host.
sync_to_container(){
  export $(grep -v '^#' .env | xargs)
  [ "$VOLUMES_MOUNTED" == "1" ] && return
  echo "Syncing from $1 to $(docker-compose ps -q cli)"
  docker cp -L $1 $(docker-compose ps -q cli):/app/
}

echo "==> Starting WORKFLOW tests for Drupal ${DRUPAL_VERSION} in build directory ${BUILD_DIR}"

# Prepare build directory.
rm -Rf $BUILD_DIR > /dev/null
mkdir -p $BUILD_DIR
git archive --format=tar HEAD | (cd $BUILD_DIR && tar -xf -)
# Special treatment for cases where volumes are not mounted from host.
[ "$VOLUMES_MOUNTED" != "1" ] && sed -i -e "/###/d" $BUILD_DIR/docker-compose.yml

pushd $BUILD_DIR > /dev/null

step "Initialise the project"
printf 'Star Wars\n\n\n\n\nno\n\n\n' | ahoy init

step "Create .env.local file"
echo FTP_HOST=$DB_FTP_HOST >> .env.local
echo FTP_USER=$DB_FTP_USER >> .env.local
echo FTP_PASS=$DB_FTP_PASS >> .env.local
echo FTP_FILE=db_d${DRUPAL_VERSION}.dist.sql >> .env.local

step "Add all files to new git repo"
git init
git config user.name "someone"
git config user.email "someone@someplace.com"
git add -A
git commit -m "First commit" > /dev/null

step "Download the database"
ahoy download-db

step "Build project"
ahoy build
sync_to_host
BUILD_DIR=$BUILD_DIR goss --gossfile $CURDIR/.drupal-dev/tests/goss/goss.build.yml validate
# @todo: Try moving this before test.
sync_to_container behat.yml
sync_to_container phpcs.xml
sync_to_container phpunit.xml
sync_to_container tests

step "Enable development settings"
cp docroot/sites/default/default.settings.local.php docroot/sites/default/settings.local.php

step "Run generic command"
ahoy cli "echo Test"

step "Run drush command"
ahoy drush st

step "Generate one-time login link"
ahoy login

step "Export DB"
ahoy export-db mydb.sql
[ ! -f .data/mydb.sql ] && echo "Exported file does not exist" && exit 1

step "Run single Behat test"
ahoy test-behat tests/behat/features/homepage.feature
[ -z "$(ls -A screenshots)" ] && "Behat screenshots were not created" && exit 1
sync_to_host

step "Build FE assets"
echo "\$color-silver-chalice: #ff0000;" >> docroot/sites/all/themes/custom/star_wars/scss/_variables.scss
ahoy fe-dev
sync_to_host

step "Re-import DB"
rm -Rf .data/*
echo "DB_EXPORT_BEFORE_IMPORT=1" >> .env.local
ahoy download-db
ahoy import-db
ls .data/db_export_* > /dev/null

step "Clean"
ahoy clean
BUILD_DIR=$BUILD_DIR goss --gossfile $CURDIR/.drupal-dev/tests/goss/goss.clean.yml validate

popd > /dev/null
