#!/usr/bin/env bash
##
# Create local settings files.
#
# Allows to customise CI build settings.
set -e

echo "<?php" >> docroot/sites/default/settings.local.php
# Add local settings overrides here.
# echo "\$conf['somevariable'] = '$SOME_VALUE_FROM_ENV';" >> docroot/sites/default/settings.local.php

# Add local services overrides here.
echo "---" >> docroot/sites/default/services.local.yml
# echo "parameter: value" >> docroot/sites/default/services.local.yml

if [ "$(docker-compose ps -q cli)" != "" ]; then
  docker cp -L docroot/sites/default/settings.local.php "$(docker-compose ps -q cli)":/app/docroot/sites/default/settings.local.php
  docker cp -L docroot/sites/default/settings.local.php "$(docker-compose ps -q php)":/app/docroot/sites/default/settings.local.php
  docker cp -L docroot/sites/default/services.local.yml "$(docker-compose ps -q cli)":/app/docroot/sites/default/services.local.yml
  docker cp -L docroot/sites/default/services.local.yml "$(docker-compose ps -q php)":/app/docroot/sites/default/services.local.yml
fi
