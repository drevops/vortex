<?php

/**
 * @file
 * Robots.txt settings.
 */

declare(strict_types=1);

if ($settings['environment'] !== ENVIRONMENT_PROD) {
  $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
}
