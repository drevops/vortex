<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Builder\FieldBuilder;
use DrevOps\Tui\Builder\PanelBuilder;
use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Derive\Derive;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Utils\Converter;
use DrevOps\VortexCli\Utils\File;

/**
 * Handler for the "module_prefix" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class ModulePrefix extends AbstractHandler implements FieldInterface {

  /**
   * Validate the collected value.
   *
   * @param mixed $value
   *   The value.
   *
   * @return string|null
   *   An error message, or NULL when valid.
   */
  public static function validate(mixed $value): ?string {
    return is_string($value) && Validate::isMachineName($value) ? NULL : 'Please enter a valid module prefix: only lowercase letters, numbers, and underscores are allowed.';
  }

  /**
   * Normalize the collected value.
   *
   * @param mixed $value
   *   The value.
   *
   * @return mixed
   *   The normalized value.
   */
  public static function transform(mixed $value): mixed {
    return is_string($value) ? trim($value) : $value;
  }

  /**
   * {@inheritdoc}
   */
  public function process(Field $field, mixed $value, Context $context): void {
    $prefix = is_string($value) ? $value : '';
    $webroot = is_string($context->answers['webroot'] ?? NULL) ? $context->answers['webroot'] : 'web';

    File::replaceContentAsync([
      'ys_demo' => $prefix . '_demo',
      'ys-demo' => Converter::kebab($prefix) . '-demo',
      'ys_base' => $prefix . '_base',
      'ys-base' => Converter::kebab($prefix) . '-base',
      'ys_search' => $prefix . '_search',
      'ys-search' => Converter::kebab($prefix) . '-search',
      'YsDemo' => Converter::pascal($prefix) . 'Demo',
      'YsBase' => Converter::pascal($prefix) . 'Base',
      'YsSearch' => Converter::pascal($prefix) . 'Search',
      'YSBASE' => Converter::cobol($prefix),
      'YSSEARCH' => Converter::cobol($prefix),
    ]);

    $modules = $context->directory . sprintf('/%s/modules/custom', $webroot);

    File::renameInDir($modules, 'ys_demo', $prefix . '_demo');
    File::renameInDir($modules, 'ys-demo', Converter::kebab($prefix) . '-demo');
    File::renameInDir($modules, 'ys_base', $prefix . '_base');
    File::renameInDir($modules, 'ys-base', Converter::kebab($prefix) . '-base');
    File::renameInDir($modules, 'ys_search', $prefix . '_search');
    File::renameInDir($modules, 'ys-search', Converter::kebab($prefix) . '-search');
    File::renameInDir($modules, 'YsDemo', Converter::pascal($prefix) . 'Demo');
    File::renameInDir($modules, 'YsBase', Converter::pascal($prefix) . 'Base');
    File::renameInDir($modules, 'YsSearch', Converter::pascal($prefix) . 'Search');
    File::renameInDir($context->directory . sprintf('/%s/sites/default/includes', $webroot), 'ys_base', $prefix . '_base');
  }

  /**
   * {@inheritdoc}
   */
  public static function field(PanelBuilder $p): FieldBuilder {
    return $p->text('module_prefix', 'Custom modules prefix')
      ->description('We will use this name in custom modules.')
      ->required()
      ->derive(new Derive('{{machine_name}}', 'initials'))
      ->weight(310);
  }

}
