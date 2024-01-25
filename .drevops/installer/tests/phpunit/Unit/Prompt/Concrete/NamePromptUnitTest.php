<?php

namespace Drevops\Installer\Tests\Unit\Prompt\Concrete;

use DrevOps\Installer\Prompt\Concrete\NamePrompt;
use Drevops\Installer\Tests\Unit\Prompt\ConcretePromptUnitTestCase;
use DrevOps\Installer\Utils\Env;
use Opis\Closure\SerializableClosure;

/**
 * @coversDefaultClass \DrevOps\Installer\Prompt\Concrete\NamePrompt
 */
class NamePromptUnitTest extends ConcretePromptUnitTestCase {

  protected static $class = NamePrompt::class;

  /**
   * {@inheritdoc}
   */
  public static function dataProviderDefaultValue(): array {
    return [
      [[], [], NULL],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function dataProviderDiscoveredValue(): array {
    return [
      [
        NULL,
        [],
        [],
        basename(getcwd()),
      ],

      [
        static::fnw(static fn() => static::envSet(Env::PROJECT, static::PROJECT_NAME_VALID)),
        [],
        [],
        static::PROJECT_NAME_VALID,
      ],

      // There is no validation within the value discovery.
      [
        static::fnw(static fn() => static::envSet(Env::PROJECT, static::PROJECT_NAME_INVALID)),
        [],
        [],
        static::PROJECT_NAME_INVALID,
      ],

      [
        static::fnw(static fn() => static::fixturesCreateComposerjson(static::$fixtureDstDirs['installed'], static::COMPOSERJSON_DESCRIPTION_VALID)),
        [],
        [],
        basename(getcwd()),
      ],

      [
        static::fnw(static fn() => static::fixturesCreateComposerjson(static::$fixtureDstDirs['installed'], static::COMPOSERJSON_DESCRIPTION_VALID)),
        [Env::INSTALLER_DST_DIR => static::$fixtureDstDirs['installed']],
        [],
        basename((string) static::$fixtureDstDirs['installed']),
      ],

      [
        static::fnw(static function () : void {
            static::envSet(Env::PROJECT, static::PROJECT_NAME_VALID);
            static::fixturesCreateComposerjson(static::$fixtureDstDirs['installed'], static::COMPOSERJSON_DESCRIPTION_VALID);
        }),
        [Env::INSTALLER_DST_DIR => static::$fixtureDstDirs['installed']],
        [],
        static::PROJECT_NAME_VALID,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function dataProviderValidator(): array {
    return [
      [static::HUMAN_NAME_VALID, [], [], FALSE],
      [static::HUMAN_NAME_INVALID, [], [], 'The name must contain only letters, numbers, and dashes.'],
      [NULL, [], [], 'The name must contain only letters, numbers, and dashes.'],
      ['', [], [], 'The name must contain only letters, numbers, and dashes.'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function dataProviderValueNormalizer(): array {
    return [
      [static::HUMAN_NAME_VALID, [], [], static::HUMAN_NAME_VALID],
      [static::HUMAN_NAME_INVALID, [], [], static::HUMAN_NAME_VALID],
    ];
  }

}
