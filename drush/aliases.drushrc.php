<?php

/**
 * @file
 * Drush aliases.
 */

// Only enable aliases if enabled or this is running in Lagoon.
if (!getenv('DREVOPS_LAGOON_ENABLE_DRUSH_ALIASES') && !getenv('LAGOON_GIT_BRANCH')) {
  return;
}

// Don't change anything here, it's magic!
// @codingStandardsIgnoreFile
global $aliases_stub;
if (empty($aliases_stub)) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_URL, 'https://drush-alias.lagoon.amazeeio.cloud/aliases.drushrc.php.stub');
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
  $aliases_stub = curl_exec($ch);
  curl_close($ch);
}
eval($aliases_stub);
