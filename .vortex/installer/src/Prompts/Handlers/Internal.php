<?php

declare(strict_types=1);

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Utils\Config;
use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\File;

class Internal extends AbstractHandler {

  public function discover(): null|string|bool|array {
    // Noop.
    return NULL;
  }

  public function process(): void {
    $version = (string) $this->config->get(Config::VERSION);
    File::replaceContentInDir($this->tmpDir, 'VORTEX_VERSION_URLENCODED', str_replace('-', '--', $version));
    File::replaceContentInDir($this->tmpDir, 'VORTEX_VERSION', $version);

    $this->processDemoMode($this->responses, $this->tmpDir);

    // Remove code required for Vortex maintenance.
    File::removeTokenInDir($this->tmpDir, 'VORTEX_DEV');

    // Remove all other comments.
    File::removeTokenInDir($this->tmpDir);

    if (file_exists($this->tmpDir . DIRECTORY_SEPARATOR . 'README.dist.md')) {
      rename($this->tmpDir . DIRECTORY_SEPARATOR . 'README.dist.md', $this->tmpDir . DIRECTORY_SEPARATOR . 'README.md');
    }

    // Remove Vortex internal files.
    File::rmdir($this->tmpDir . DIRECTORY_SEPARATOR . '.vortex');

    @unlink($this->tmpDir . '/.github/FUNDING.yml');
    @unlink($this->tmpDir . 'CODE_OF_CONDUCT.md');
    @unlink($this->tmpDir . 'CONTRIBUTING.md');
    @unlink($this->tmpDir . 'LICENSE');
    @unlink($this->tmpDir . 'SECURITY.md');

    // Remove Vortex internal GHAs.
    $files = glob($this->tmpDir . '/.github/workflows/vortex-*.yml');
    if ($files) {
      foreach ($files as $file) {
        @unlink($file);
      }
    }

    // Enable commented out code.
    File::replaceContentInDir($this->tmpDir, '##### ', '');

    // Process empty lines.
    $ignore = array_merge(File::ignoredPaths(), [
      '/web/sites/default/default.settings.php',
      '/web/sites/default/default.services.yml',
      '/.docker/config/solr/config-set/',
    ]);

    $files = File::scandirRecursive($this->tmpDir, $ignore);
    foreach ($files as $filename) {
      File::replaceContent($filename, '/(\n\s*\n)+/', "\n\n");
    }
  }

  protected function processDemoMode(array $responses, string $dir): void {
    if (is_null($this->config->get(Config::IS_DEMO_MODE))) {
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
      File::removeTokenInDir($dir, 'DEMO');
    }
  }

}
