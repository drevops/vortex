<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Customizer\Config\Field;
use DrevOps\Customizer\Handler\AbstractHandler;
use DrevOps\Customizer\Handler\Context;
use DrevOps\VortexCli\Utils\File;
use DrevOps\VortexCli\Utils\JsonManipulator;

/**
 * Handler for the "migration" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class Migration extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function process(Field $field, mixed $value, Context $context): void {
    $webroot = is_string($context->answers['webroot'] ?? NULL) ? $context->answers['webroot'] : 'web';

    if ($value === TRUE) {
      File::removeTokenAsync('!MIGRATION');
    }
    else {
      File::removeTokenAsync('MIGRATION');
      File::remove($context->directory . '/' . $webroot . '/sites/default/settings.migration.php');
      File::remove($context->directory . '/' . $webroot . '/modules/custom/ys_migrate');

      $cj = JsonManipulator::fromFile($context->directory . '/composer.json');
      if ($cj instanceof JsonManipulator) {
        $cj->removeSubNode('require', 'drupal/migrate_plus');
        $cj->removeSubNode('require', 'drupal/migrate_tools');
        file_put_contents($context->directory . '/composer.json', $cj->getContents());
      }
    }
  }

}
