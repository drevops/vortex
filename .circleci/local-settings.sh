#!/usr/bin/env bash
##
# Create local settings files.
#
# Allows to customise CI build settings.

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

echo "<?php" >> docroot/sites/default/settings.local.php
# Add local settings overrides here.
# echo "\$conf['somevariable'] = '$SOME_VALUE_FROM_ENV';" >> docroot/sites/default/settings.local.php

if [ "$(docker-compose ps -q cli)" != "" ]; then
  docker cp -L docroot/sites/default/settings.local.php "$(docker-compose ps -q cli)":/app/docroot/sites/default/settings.local.php
  docker cp -L docroot/sites/default/settings.local.php "$(docker-compose ps -q php)":/app/docroot/sites/default/settings.local.php
fi
