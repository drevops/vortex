<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Config\FieldType;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Utils\File;

/**
 * Handler for the "custom_modules" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class CustomModules extends AbstractFieldHandler implements OptionsInterface {

  const BASE = 'base';

  const DEMO = 'demo';

  const SEARCH = 'search';

  /**
   * {@inheritdoc}
   */
  public function process(Field $field, mixed $value, Context $context): void {
    $selected = is_array($value) ? array_values(array_filter($value, is_string(...))) : [];
    $t = $context->directory;
    $w = is_string($context->answers['webroot'] ?? NULL) ? $context->answers['webroot'] : 'web';

    // Safety net: if search was selected but Solr service was not, force-remove
    // search module since it cannot function without Solr.
    if (in_array('search', $selected, TRUE) && isset($context->answers['services'])) {
      $services = $context->answers['services'];
      if (is_array($services) && !in_array('solr', $services, TRUE)) {
        $selected = array_values(array_filter($selected, fn($x): bool => $x !== 'search'));
      }
    }

    if (!in_array('base', $selected, TRUE)) {
      File::removeTokenAsync('CUSTOM_MODULE_BASE');

      $locations = [
        $t . sprintf('/%s/modules/custom/*_base', $w),
        $t . sprintf('/%s/sites/all/modules/custom/*_base', $w),
        $t . sprintf('/%s/profiles/*/modules/*_base', $w),
        $t . sprintf('/%s/profiles/*/modules/custom/*_base', $w),
        $t . sprintf('/%s/profiles/custom/*/modules/*_base', $w),
        $t . sprintf('/%s/profiles/custom/*/modules/custom/*_base', $w),
      ];

      $path = File::findMatchingPath($locations);
      if ($path) {
        File::remove($path);
      }
    }

    if (!in_array('demo', $selected, TRUE)) {
      File::removeTokenAsync('CUSTOM_MODULE_DEMO');

      $locations = [
        $t . sprintf('/%s/modules/custom/*_demo', $w),
        $t . sprintf('/%s/sites/all/modules/custom/*_demo', $w),
        $t . sprintf('/%s/profiles/*/modules/*_demo', $w),
        $t . sprintf('/%s/profiles/*/modules/custom/*_demo', $w),
        $t . sprintf('/%s/profiles/custom/*/modules/*_demo', $w),
        $t . sprintf('/%s/profiles/custom/*/modules/custom/*_demo', $w),
      ];

      $path = File::findMatchingPath($locations);
      if ($path) {
        File::remove($path);
      }

      static::removeDemoBehatFeatures($t);
    }

    if (!in_array('search', $selected, TRUE)) {
      File::removeTokenAsync('CUSTOM_MODULE_SEARCH');

      $locations = [
        $t . sprintf('/%s/modules/custom/*_search', $w),
        $t . sprintf('/%s/sites/all/modules/custom/*_search', $w),
        $t . sprintf('/%s/profiles/*/modules/*_search', $w),
        $t . sprintf('/%s/profiles/*/modules/custom/*_search', $w),
        $t . sprintf('/%s/profiles/custom/*/modules/*_search', $w),
        $t . sprintf('/%s/profiles/custom/*/modules/custom/*_search', $w),
      ];

      $path = File::findMatchingPath($locations);
      if ($path) {
        File::remove($path);
      }
    }
  }

  /**
   * Remove Behat feature files tagged with @demo.
   *
   * Scans the Behat features directory for .feature files whose first line
   * contains the @demo tag and removes them.
   *
   * @param string $dir
   *   The base directory to search in.
   */
  protected static function removeDemoBehatFeatures(string $dir): void {
    $features_dir = $dir . '/tests/behat/features';

    if (!is_dir($features_dir)) {
      return;
    }

    $files = File::findContainingInDir($features_dir, '@demo');

    foreach ($files as $file) {
      File::remove($file);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function options(): array {
    return [
      self::BASE => 'Base - starter module with utilities and test scaffolding',
      self::SEARCH => 'Search - custom Solr search integration',
      self::DEMO => 'Demo - counter block and example tests to demonstrate tooling',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function id(): string {
    return 'custom_modules';
  }

  /**
   * {@inheritdoc}
   */
  public static function label(): string {
    return 'Custom modules';
  }

  /**
   * {@inheritdoc}
   */
  public static function type(): FieldType {
    return FieldType::MultiSelect;
  }

  /**
   * {@inheritdoc}
   */
  public static function description(): string {
    return 'Which scaffolded custom modules to keep.';
  }

  /**
   * {@inheritdoc}
   */
  public static function default(): mixed {
    return [self::BASE, self::SEARCH, self::DEMO];
  }

  /**
   * {@inheritdoc}
   */
  public static function weight(): int {
    return 300;
  }

}
