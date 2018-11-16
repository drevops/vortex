#!/usr/bin/env bash
#
# Test runner for project initialisation tests.
#

CURDIR="$(cd "$(dirname "$(dirname "${BASH_SOURCE[0]}")")/.." && pwd)"
BUILD_DIR=${BUILD_DIR:-/tmp/drupal-dev-init}
DRUPAL_VERSION=${DRUPAL_VERSION:-7}

echo "==> Starting INIT tests for Drupal ${DRUPAL_VERSION} in build directory ${BUILD_DIR}"

# Using 'Star Wars' as a name of the initialised site. It is used in all tests.
tests=(
  # Default answers to all questions.
  'Star Wars\n\n\n\n\n\n\n\n' 'all'
  # Remove all integrations.
  'Star Wars\n\n\n\n\nno\nno\n\n' 'none'
)

count=0
while [ "x${tests[count]}" != "x" ]
do
  if ! ((count % 2)); then
    input="${tests[count]}"
  else
    suffix="${tests[count]}"

    file=$CURDIR/.drupal-dev/tests/goss/goss.${suffix}.yml
    if [ ! -f $file ]; then
      continue
    fi

    echo "==> Starting '$suffix' test"

    # Prepare build directory.
    rm -Rf $BUILD_DIR > /dev/null
    mkdir -p $BUILD_DIR

    # Copy latest commit to the build directory.
    git archive --format=tar HEAD | (cd $BUILD_DIR && tar -xf -)

    pushd $BUILD_DIR > /dev/null

    # Initialise the project.
    printf "$input" | ahoy init

    # Run assertions.
    BUILD_DIR=$BUILD_DIR goss --gossfile $file validate

    popd > /dev/null

    echo "==> Finished '$suffix' test"
  fi
   count=$(( $count + 1 ))
done
