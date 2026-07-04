<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use AlexSkrypnyk\File\ContentFile\ContentFile;
use DrevOps\Customizer\Config\Field;
use DrevOps\Customizer\Handler\AbstractHandler;
use DrevOps\Customizer\Handler\Context;
use DrevOps\VortexCli\Utils\Env;
use DrevOps\VortexCli\Utils\File;
use DrevOps\VortexCli\Utils\Strings;
use DrevOps\VortexCli\Utils\Yaml;

/**
 * Final processor: version stamping, global content cleanup and task flush.
 *
 * @package DrevOps\VortexCli\Handler
 */
class Internal extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function process(Field $field, mixed $value, Context $context): void {
    $t = $context->directory;

    $version = $context->version;

    $this->processDemoMode($context->answers, $context->destination);

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

    if (file_exists($t . DIRECTORY_SEPARATOR . 'README.dist.md')) {
      rename($t . DIRECTORY_SEPARATOR . 'README.dist.md', $t . DIRECTORY_SEPARATOR . 'README.md');
    }

    // Remove Vortex internal files.
    File::remove($t . DIRECTORY_SEPARATOR . '.vortex');
    File::remove($t . DIRECTORY_SEPARATOR . '.claude' . DIRECTORY_SEPARATOR . 'skills');

    File::remove($t . '/.github/FUNDING.yml');
    File::remove($t . 'CODE_OF_CONDUCT.md');
    File::remove($t . 'CONTRIBUTING.md');
    File::remove($t . 'LICENSE');
    File::remove($t . 'SECURITY.md');

    // Remove Vortex internal CircleCI configs.
    $files = glob($t . '/.circleci/vortex-*.yml');
    if ($files) {
      foreach ($files as $file) {
        @unlink($file);
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
    $composer_json_path = $t . DIRECTORY_SEPARATOR . 'composer.json';
    if (file_exists($composer_json_path)) {
      $content = file_get_contents($composer_json_path);
      $composer_json = json_decode((string) $content, FALSE);
      if (is_object($composer_json)) {
        if (isset($composer_json->require->{'drevops/generic-private-package'})) {
          unset($composer_json->require->{'drevops/generic-private-package'});
        }

        if (isset($composer_json->repositories) && is_array($composer_json->repositories)) {
          $composer_json->repositories = array_values(array_filter($composer_json->repositories, $this->keepRepository(...)));
        }

        file_put_contents($composer_json_path, json_encode($composer_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
      }
    }

    // Execute all queued batch tasks from all handlers.
    File::runDirectoryTasks($t);
  }

  /**
   * Decide whether a composer repository entry is kept.
   *
   * @param mixed $repo
   *   The repository entry decoded from composer.json.
   *
   * @return bool
   *   TRUE to keep the entry, FALSE to drop it.
   */
  protected function keepRepository(mixed $repo): bool {
    if (!is_object($repo)) {
      return TRUE;
    }

    $url = property_exists($repo, 'url') && is_string($repo->url) ? $repo->url : NULL;
    $type = property_exists($repo, 'type') && is_string($repo->type) ? $repo->type : NULL;

    if ($url !== NULL && str_contains($url, 'drevops/generic-private-package')) {
      return FALSE;
    }

    return !($type === 'path' && $url === '.vortex/tooling');
  }

  /**
   * Remove the demo-mode token unless the answer set enables demo mode.
   *
   * @param array<string,mixed> $answers
   *   The collected answers.
   * @param string $dir
   *   The destination project directory.
   */
  protected function processDemoMode(array $answers, string $dir): void {
    $starter = $answers['starter'] ?? NULL;
    $provision_type = $answers['provision_type'] ?? NULL;
    $source = $answers['database_fetch_source'] ?? NULL;

    if ($starter !== 'load_demodb') {
      $is_demo = FALSE;
    }
    elseif ($provision_type === 'database') {
      $db_dir = Env::get('VORTEX_DB_DIR', './.data');
      $db_file = Env::get('VORTEX_DB_FILE', 'db.sql');
      $db_dir = is_string($db_dir) ? $db_dir : './.data';
      $db_file = is_string($db_file) ? $db_file : 'db.sql';
      $db_file_exists = file_exists($db_dir . DIRECTORY_SEPARATOR . $db_file);
      $has_comment = File::contains($dir . '/.env', 'Override project-specific values for demonstration purposes');

      if ($source === 'url') {
        $is_demo = !$db_file_exists || $has_comment;
      }
      elseif ($source === 'container_registry') {
        $is_demo = $has_comment;
      }
      else {
        $is_demo = FALSE;
      }
    }
    else {
      $is_demo = FALSE;
    }

    if (!$is_demo) {
      File::removeTokenAsync('DEMO_MODE');
    }
  }

}
