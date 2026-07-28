<?php

/**
 * @file
 * Robots.txt settings.
 */

declare(strict_types=1);

use DrevOps\EnvironmentDetector\Environment;

if ($settings['environment'] !== Environment::PRODUCTION) {
  $config['robotstxt.settings']['content'] = "User-agent: *\nDisallow: /";
}
