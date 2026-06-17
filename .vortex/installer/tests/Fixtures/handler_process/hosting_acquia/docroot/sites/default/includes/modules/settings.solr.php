<?php

/**
 * @file
 * Solr search settings.
 *
 * The search backend host is overridden here so that the active configuration -
 * which may originate from an imported database where the host was baked in -
 * always points at the current search service.
 */

declare(strict_types=1);

if (file_exists($contrib_path . '/search_api_solr')) {
  $config['search_api.server.solr']['backend_config']['connector_config']['host'] = getenv('SOLR_HOST') ?: 'search';
  $config['search_api.server.solr']['backend_config']['connector_config']['port'] = getenv('SOLR_PORT') ?: 8983;
}
