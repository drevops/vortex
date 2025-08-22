<?php

/**
 * @file
 * Robots.txt settings.
 */

declare(strict_types=1);

if ($settings['environment'] !== ENVIRONMENT_PROD) {
  $config['robots_txt.settings']['content'] = "User-agent: *\r\nDisallow:";
}
