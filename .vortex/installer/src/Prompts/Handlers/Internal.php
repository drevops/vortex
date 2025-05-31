<?php

declare(strict_types=1);

namespace DrevOps\Installer\Prompts\Handlers;

use AlexSkrypnyk\File\ExtendedSplFileInfo;
use DrevOps\Installer\Utils\Config;
use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\File;

class Internal extends AbstractHandler {

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

      // Process empty lines, but exclude specific files that should not have
      // empty line processing.
      $ignore_empty_line_processing = [
        '/web/sites/default/default.settings.php',
        '/web/sites/default/default.services.yml',
        '/.docker/config/solr/config-set/',
      ];
      $relative_path = str_replace($t, '', $file->getPathname());
      if (!in_array($relative_path, $ignore_empty_line_processing)) {
        $content = File::replaceContent($content, '/(\n\s*\n)+/', "\n\n");
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

    // Execute all queued batch tasks from all handlers.
    File::runTaskDirectory($this->config->get(Config::TMP));
  }

  protected function processDemoMode(array $responses, string $dir): void {
    if (is_null($is_demo_mode = $this->config->get(Config::IS_DEMO_MODE))) {
      if ($responses[ProvisionType::id()] === ProvisionType::DATABASE) {
        $download_source = $responses[DatabaseDownloadSource::id()];
        $db_file = Env::get('VORTEX_DB_DIR', './.data') . DIRECTORY_SEPARATOR . Env::get('VORTEX_DB_FILE', 'db.sql');
        $has_comment = File::contains($this->dstDir . '/.env', 'Override project-specific values for demonstration purposes');

        // Enable Vortex demo mode if download source is file AND
        // there is no downloaded file present OR if there is a demo comment in
        // destination .env file.
        if ($download_source !== DatabaseDownloadSource::CONTAINER_REGISTRY) {
          if ($has_comment || !file_exists($db_file)) {
            $this->config->set(Config::IS_DEMO_MODE, TRUE);
          }
          else {
            $this->config->set(Config::IS_DEMO_MODE, FALSE);
          }
        }
        elseif ($has_comment) {
          $this->config->set(Config::IS_DEMO_MODE, TRUE);
        }
        else {
          $this->config->set(Config::IS_DEMO_MODE, FALSE);
        }
      }
      else {
        $this->config->set(Config::IS_DEMO_MODE, FALSE);
      }
    }

    if (!$this->config->get(Config::IS_DEMO_MODE)) {
      File::removeTokenAsync('DEMO');
    }
  }

}
