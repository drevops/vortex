<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use AlexSkrypnyk\File\ContentFile\ContentFile;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\Env;
use DrevOps\VortexInstaller\Utils\File;
use DrevOps\VortexInstaller\Utils\JsonManipulator;
use DrevOps\VortexInstaller\Utils\Strings;
use DrevOps\VortexInstaller\Utils\Yaml;

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

    // The CircleCI deploy filter is a single-line regex, so the Vortex-only
    // '*.x' branch deploy target cannot be wrapped in a line-based VORTEX_DEV
    // fence. Strip the inline alternative here instead.
    File::replaceContentAsync('|^[0-9]+\.x$', '');

    // Enable commented out code and process complex content transformations.
    File::replaceContentAsync(function (string $content, ContentFile $file) use ($t): string {
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
        $content = File::collapseEmptyLines($content);
        if (in_array($file->getExtension(), ['yml', 'yaml'], TRUE)) {
          $content = Yaml::collapseFirstEmptyLinesInLiteralBlock($content);
        }
        $content = Strings::removeTrailingSpaces($content);
      }

      return $content;
    });

    if (file_exists($t . '/README.dist.md')) {
      rename($t . '/README.dist.md', $t . '/README.md');
    }

    // Remove Vortex internal files.
    File::remove($t . '/.vortex');
    File::remove($t . '/.claude/skills');

    File::remove($t . '/.github/FUNDING.yml');
    File::remove($t . '/CODE_OF_CONDUCT.md');
    File::remove($t . '/CONTRIBUTING.md');
    File::remove($t . '/LICENSE');
    File::remove($t . '/SECURITY.md');

    // Remove Vortex internal CircleCI configs.
    $files = glob($t . '/.circleci/vortex-*.yml');
    if ($files) {
      foreach ($files as $file) {
        File::remove($file);
      }
    }

    // Remove Vortex internal GHAs.
    $files = glob($t . '/.github/workflows/vortex-*.yml');
    if ($files) {
      foreach ($files as $file) {
        File::remove($file);
      }
    }

    // Remove private package from composer.json.
    // Also remove the path repository that points at the in-tree
    // .vortex/tooling package - consumer sites get drevops/vortex-tooling
    // from packagist instead.
    JsonManipulator::updateFile($t . '/composer.json', function (JsonManipulator $cj): void {
      $cj->removeSubNode('require', 'drevops/generic-private-package');

      $repositories = $cj->getProperty('repositories');
      if (!is_array($repositories)) {
        return;
      }

      // Removing an item shifts the indexes of the ones that follow it.
      foreach (array_reverse(array_keys($repositories)) as $index) {
        if (self::isInternalRepository($repositories[$index])) {
          $cj->removeListItem('repositories', (int) $index);
        }
      }
    });

    // Execute all queued batch tasks from all handlers.
    File::runDirectoryTasks($this->config->get(Config::TMP));
  }

  /**
   * Check whether a composer.json repository entry is internal to Vortex.
   *
   * @param mixed $repository
   *   The decoded repository entry.
   *
   * @return bool
   *   TRUE if the entry must not be shipped to a consumer site.
   */
  protected static function isInternalRepository(mixed $repository): bool {
    if (!is_array($repository)) {
      return FALSE;
    }

    $url = $repository['url'] ?? '';
    if (!is_string($url)) {
      return FALSE;
    }

    if (str_contains($url, 'drevops/generic-private-package')) {
      return TRUE;
    }

    return ($repository['type'] ?? '') === 'path' && $url === '.vortex/tooling';
  }

  protected function processDemoMode(array $responses, string $dir): void {
    $is_demo = $this->config->get(Config::IS_DEMO);

    if ($is_demo === NULL) {
      if ($responses[Starter::id()] !== Starter::LOAD_DATABASE_DEMO) {
        $is_demo = FALSE;
      }
      // Check if it should be enabled based on the provision type and database
      // download source.
      elseif ($responses[ProvisionType::id()] === ProvisionType::DATABASE) {
        $db_file_exists = file_exists(Env::get('VORTEX_DB_DIR', './.data') . '/' . Env::get('VORTEX_DB_FILE', 'db.sql'));
        $has_comment = File::contains($this->destinationDir . '/.env', 'Override project-specific values for demonstration purposes');

        // Demo mode can only be used if the user selected a URL or a container
        // registry download source. This is because the demo mode would not
        // have access to integrations with providers to pull the database
        // from.
        if ($responses[DatabaseFetchSource::id()] === DatabaseFetchSource::URL) {
          // For fetching from URL, demo mode is enabled if the database
          // file does not exist or if there is an explicit comment in the
          // destination .env file that indicates that this is a demo mode.
          $is_demo = !$db_file_exists || $has_comment;
        }
        elseif ($responses[DatabaseFetchSource::id()] === DatabaseFetchSource::CONTAINER_REGISTRY) {
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
      File::removeTokenAsync('DEMO_MODE');
    }

    $this->config->set(Config::IS_DEMO, $is_demo);
  }

  /**
   * {@inheritdoc}
   */
  public function postInstall(): ?string {
    $output = '';

    if (!$this->isInstalled()) {
      $output .= PHP_EOL;
      $output .= 'Add and commit all files:' . PHP_EOL;
      $output .= '  cd ' . $this->config->getDestination() . PHP_EOL;
      $output .= '  git add -A' . PHP_EOL;
      $output .= '  git commit -m "Initial commit."' . PHP_EOL;
      $output .= PHP_EOL;
    }

    return $output;
  }

}
