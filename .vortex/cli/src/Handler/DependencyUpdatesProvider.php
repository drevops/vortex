<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Config\FieldType;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Utils\File;

/**
 * Handler for the "dependency_updates_provider" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class DependencyUpdatesProvider extends AbstractFieldHandler implements OptionsInterface {

  const NONE = 'none';

  const RENOVATEBOT_CI = 'renovatebot_ci';

  const RENOVATEBOT_APP = 'renovatebot_app';

  /**
   * {@inheritdoc}
   */
  public function process(Field $field, mixed $value, Context $context): void {
    if ($value === 'renovatebot_ci') {
      File::removeTokenAsync('!DEPS_UPDATE_PROVIDER_CI');
      File::removeTokenAsync('DEPS_UPDATE_PROVIDER_APP');
      File::replaceContentInFile($context->directory . '/renovate.json', '/\s*"ignorePaths":\s*\[\s*"[^"]*"\s*\],?\n/s', "\n");
    }
    elseif ($value === 'renovatebot_app') {
      File::removeTokenAsync('!DEPS_UPDATE_PROVIDER_APP');
      File::removeTokenAsync('DEPS_UPDATE_PROVIDER_CI');
      File::replaceContentInFile($context->directory . '/renovate.json', '/\s*"ignorePaths":\s*\[\s*"[^"]*"\s*\],?\n/s', "\n");
      File::remove($context->directory . '/.github/workflows/update-dependencies.yml');
      File::remove($context->directory . '/.circleci/update-dependencies.yml');
    }
    else {
      File::removeTokenAsync('DEPS_UPDATE_PROVIDER_APP');
      File::removeTokenAsync('DEPS_UPDATE_PROVIDER_CI');
      File::removeTokenAsync('DEPS_UPDATE_PROVIDER');
      File::remove($context->directory . '/renovate.json');
      File::remove($context->directory . '/.circleci/update-dependencies.yml');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function options(): array {
    return [
      self::RENOVATEBOT_APP => 'Renovate GitHub app',
      self::RENOVATEBOT_CI => 'Renovate self-hosted in CI',
      self::NONE => 'None',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function id(): string {
    return 'dependency_updates_provider';
  }

  /**
   * {@inheritdoc}
   */
  public static function label(): string {
    return 'Dependency updates provider';
  }

  /**
   * {@inheritdoc}
   */
  public static function type(): FieldType {
    return FieldType::Select;
  }

  /**
   * {@inheritdoc}
   */
  public static function description(): string {
    return 'The dependency updates provider.';
  }

  /**
   * {@inheritdoc}
   */
  public static function default(): mixed {
    return self::RENOVATEBOT_APP;
  }

  /**
   * {@inheritdoc}
   */
  public static function weight(): int {
    return 70;
  }

}
