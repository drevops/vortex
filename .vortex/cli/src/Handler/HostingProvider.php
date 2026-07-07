<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Handler\AbstractHandler;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Utils\File;
use DrevOps\VortexCli\Utils\JsonManipulator;

/**
 * Handler for the "hosting_provider" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class HostingProvider extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function process(Field $field, mixed $value, Context $context): void {
    $v = is_string($value) ? $value : '';
    $t = $context->directory;
    $w = is_string($context->answers['webroot'] ?? NULL) ? $context->answers['webroot'] : 'web';

    if ($v === 'acquia') {
      File::removeTokenAsync('!HOSTING_ACQUIA');
      File::removeTokenAsync('!SETTINGS_PROVIDER_ACQUIA');

      $this->removeLagoon($t);
    }
    elseif ($v === 'lagoon') {
      File::removeTokenAsync('!HOSTING_LAGOON');
      File::removeTokenAsync('!SETTINGS_PROVIDER_LAGOON');

      $this->removeAcquia($t);

      File::remove(sprintf('%s/%s/.htaccess', $t, $w));

      $cj = JsonManipulator::fromFile($t . '/composer.json');

      if (!$cj instanceof JsonManipulator) {
        return;
      }

      $cj->addLink('require', 'drupal/lagoon_logs', '^3', TRUE);
      file_put_contents($t . '/composer.json', $cj->getContents());
    }
    else {
      $this->removeAcquia($t);
      $this->removeLagoon($t);

      File::removeTokenAsync('HOSTING');

      File::remove(sprintf('%s/%s/.htaccess', $t, $w));
    }
  }

  /**
   * Remove Acquia hosting integration files and tokens.
   *
   * @param string $directory
   *   The destination project directory.
   */
  protected function removeAcquia(string $directory): void {
    File::remove($directory . '/hooks');

    File::removeTokenAsync('HOSTING_ACQUIA');
    File::removeTokenAsync('SETTINGS_PROVIDER_ACQUIA');
  }

  /**
   * Remove Lagoon hosting integration files and tokens.
   *
   * @param string $directory
   *   The destination project directory.
   */
  protected function removeLagoon(string $directory): void {
    File::remove($directory . '/drush/sites/lagoon.site.yml');
    File::remove($directory . '/.lagoon.yml');
    File::remove($directory . '/.github/workflows/close-pull-request.yml');

    File::removeTokenAsync('HOSTING_LAGOON');
    File::removeTokenAsync('SETTINGS_PROVIDER_LAGOON');
  }

}
