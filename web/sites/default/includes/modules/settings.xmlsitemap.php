<?php

/**
 * @file
 * XML Sitemap settings.
 */

declare(strict_types=1);

// Disable submitting sitemap to search engines in non-production environments.
if ($settings['environment'] !== ENVIRONMENT_PROD) {
  $config['xmlsitemap.settings']['disable_cron_regeneration'] = TRUE;
  $config['xmlsitemap_engines.settings']['submit'] = FALSE;
}
