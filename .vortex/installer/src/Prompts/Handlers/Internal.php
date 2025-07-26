<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use AlexSkrypnyk\File\ExtendedSplFileInfo;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\Env;
use DrevOps\VortexInstaller\Utils\File;

class Internal extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'Internal processing';
  }

  public function discover(): null|string|bool|array {
    // Noop.
    return NULL;
  }

  public function process(): void {
    $t = $this->tmpDir;

    $version = (string) $this->config->get(Config::VERSION);

    $this->processDemoMode($this->responses, $t);

    // Replace version placeholders.
    File::replaceContentAsync([
      'VORTEX_VERSION_URLENCODED' => str_replace('-', '--', $version),
      'VORTEX_VERSION' => $version,
    ]);

    // Remove code required for Vortex maintenance.
    File::removeTokenAsync('VORTEX_DEV');

    // Enable commented out code and process complex content transformations.
    File::replaceContentAsync(function (string $content, ExtendedSplFileInfo $file) use ($t): string {
      // Remove all other comments.
      $content = File::removeToken($content, '#;', '#;');

      // Enable commented out code.
      $content = File::replaceContent($content, '##### ', '');

      // Process empty lines, but exclude specific files and directories.
      $ignore_empty_line_processing = [
        '/web/sites/default/default.settings.php',
        '/web/sites/default/default.services.yml',
        '/.docker/config/solr/config-set/',
      ];
      $relative_path = str_replace($t, '', $file->getPathname());
      $should_ignore = FALSE;
      foreach ($ignore_empty_line_processing as $item_path) {
        if (str_starts_with($relative_path, $item_path)) {
          $should_ignore = TRUE;
          break;
        }
      }

      if (!$should_ignore) {
        $content = File::collapseRepeatedEmptyLines($content);
        $content = File::collapseYamlEmptyLinesInLiteralBlocks($content);
      }

      return $content;
    });

    if (file_exists($t . DIRECTORY_SEPARATOR . 'README.dist.md')) {
      rename($t . DIRECTORY_SEPARATOR . 'README.dist.md', $t . DIRECTORY_SEPARATOR . 'README.md');
    }

    // Remove Vortex internal files.
    File::rmdir($t . DIRECTORY_SEPARATOR . '.vortex');

    @unlink($t . '/.github/FUNDING.yml');
    @unlink($t . 'CODE_OF_CONDUCT.md');
    @unlink($t . 'CONTRIBUTING.md');
    @unlink($t . 'LICENSE');
    @unlink($t . 'SECURITY.md');

    // Remove Vortex internal GHAs.
    $files = glob($t . '/.github/workflows/vortex-*.yml');
    if ($files) {
      foreach ($files as $file) {
        @unlink($file);
      }
    }

    // Remove private package from composer.json.
    $composer_json_path = $t . DIRECTORY_SEPARATOR . 'composer.json';
    if (file_exists($composer_json_path)) {
      $content = file_get_contents($composer_json_path);
      $composer_json = json_decode((string) $content, FALSE);
      if ($composer_json !== NULL) {
        if (isset($composer_json->require->{'drevops/generic-private-package'})) {
          unset($composer_json->require->{'drevops/generic-private-package'});
        }

        if (isset($composer_json->repositories)) {
          $composer_json->repositories = array_values(array_filter($composer_json->repositories, function ($repo): bool {
            return !isset($repo->url) || !str_contains($repo->url, 'drevops/generic-private-package');
          }));
        }

        file_put_contents($composer_json_path, json_encode($composer_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
      }
    }

    // Execute all queued batch tasks from all handlers.
    File::runTaskDirectory($this->config->get(Config::TMP));
  }

  protected function processDemoMode(array $responses, string $dir): void {
    $is_demo = $this->config->get(Config::IS_DEMO);

    // If demo mode is not set, check if it should be enabled based on
    // provision type and database download source.
    if (is_null($is_demo)) {
      if ($responses[ProvisionType::id()] === ProvisionType::DATABASE) {
        $db_file_exists = file_exists(Env::get('VORTEX_DB_DIR', './.data') . DIRECTORY_SEPARATOR . Env::get('VORTEX_DB_FILE', 'db.sql'));
        $has_comment = File::contains($this->dstDir . '/.env', 'Override project-specific values for demonstration purposes');

        // Demo mode can only be used if the user selected a URL or a container
        // registry download source. This is because the demo mode would not
        // have access to integrations with providers to pull the database
        // from.
        if ($responses[DatabaseDownloadSource::id()] === DatabaseDownloadSource::URL) {
          // For a downloading from URL, demo mode is enabled if the database
          // file does not exist or if there is an explicit comment in the
          // destination .env file that indicates that this is a demo mode.
          $is_demo = !$db_file_exists || $has_comment;
        }
        elseif ($responses[DatabaseDownloadSource::id()] === DatabaseDownloadSource::CONTAINER_REGISTRY) {
          // For a downloading from container registry, demo mode is enabled if
          // there is an explicit comment in the destination .env file that
          // indicates that this is a demo mode.
          $is_demo = $has_comment;
        }
        else {
          // For any other download source, demo mode is not applicable.
          $is_demo = FALSE;
        }
      }
      else {
        // Not a database-driven provision type (a profile-driven), so demo is
        // not applicable.
        $is_demo = FALSE;
      }
    }

    if (!$is_demo) {
      File::removeTokenAsync('DEMO');
    }

    $this->config->set(Config::IS_DEMO, $is_demo);
  }

}
