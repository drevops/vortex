<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Builder\FieldBuilder;
use DrevOps\Tui\Builder\PanelBuilder;
use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Utils\File;
use DrevOps\VortexCli\Utils\JsonManipulator;

/**
 * Handler for the "migration" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class Migration extends AbstractHandler implements FieldInterface {

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

  /**
   * {@inheritdoc}
   */
  public static function field(PanelBuilder $p): FieldBuilder {
    return $p->confirm('migration', 'Use a second database for migrations?')->description('Adds a second database service for Drupal migrations.')->default(FALSE)->weight(120);
  }

}
