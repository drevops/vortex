#!/usr/bin/env bash
#
# Test runner for artefact tests.

CURDIR="$(cd "$(dirname "$(dirname "${BASH_SOURCE[0]}")")/.." && pwd)"
BUILD_DIR=${BUILD_DIR:-/tmp/drupal-dev-artefact}
SRC_DIR=${1:-}
REMOTE_DIR=${REMOTE_DIR:-/tmp/drupal-dev-artefact-remote}
DRUPAL_VERSION=${DRUPAL_VERSION:-7}

[ ! "$SRC_DIR" ] && echo "Source directory is a required argument" && exit 1

echo '***************************************************************************'
echo CURDIR         : $CURDIR
echo BUILD_DIR      : $BUILD_DIR
echo SRC_DIR        : $SRC_DIR
echo REMOTE_DIR     : $REMOTE_DIR
echo DRUPAL_VERSION : $DRUPAL_VERSION
echo '***************************************************************************'

# Prepare build directory.
rm -Rf $BUILD_DIR > /dev/null
mkdir -p $BUILD_DIR
git archive --format=tar HEAD | (cd $BUILD_DIR && tar -xf -)

# Prepare remote repo directory.
rm -Rf $REMOTE_DIR > /dev/null
mkdir -p $REMOTE_DIR
DEPLOY_REMOTE=$REMOTE_DIR/.git
git --git-dir=$DEPLOY_REMOTE --work-tree=$REMOTE_DIR init

pushd $BUILD_DIR > /dev/null

# Install dev dependencies.
composer install -n --ansi --prefer-dist --ignore-platform-reqs
cp -a $CURDIR/.git $SRC_DIR/
cp -a $CURDIR/.gitignore.artefact $SRC_DIR

# Push artefact to remote repository.
vendor/bin/robo --ansi --load-from vendor/integratedexperts/robo-git-artefact/RoboFile.php artefact $DEPLOY_REMOTE --root=$BUILD_DIR --src=$SRC_DIR --gitignore=$SRC_DIR/.gitignore.artefact --push

# Checkout currently pushed branch on remote.
git --git-dir=$DEPLOY_REMOTE --work-tree=$REMOTE_DIR branch|xargs git --git-dir=$DEPLOY_REMOTE --work-tree=$REMOTE_DIR checkout

# Run tests.
BUILD_DIR=$REMOTE_DIR goss --gossfile $CURDIR/.drupal-dev/tests/goss/goss.artefact.yml validate
RESULT=$?

popd > /dev/null

exit $RESULT
