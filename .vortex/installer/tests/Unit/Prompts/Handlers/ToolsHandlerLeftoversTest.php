<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Prompts\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\Tools;
use DrevOps\VortexInstaller\Tests\Unit\UnitTestCase;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\File;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Tools::class)]
class ToolsHandlerLeftoversTest extends UnitTestCase {

  #[DataProvider('dataProviderLeftovers')]
  public function testLeftovers(array $files, array $selected, bool $is_vortex_project, array $expected): void {
    foreach ($files as $file) {
      File::dump(static::$sut . DIRECTORY_SEPARATOR . $file);
    }

    $config = new Config(static::$sut, static::$sut, static::$sut . '/tmp');
    $config->set(Config::IS_VORTEX_PROJECT, $is_vortex_project);

    $handler = new Tools($config);
    $handler->setResponses([Tools::id() => $selected]);

    $this->assertEquals($expected, $handler->leftovers());
  }

  public static function dataProviderLeftovers(): \Iterator {
    yield 'no files in destination' => [
      [],
      [],
      TRUE,
      [],
    ];
    yield 'deselected tool with a file present' => [
      ['jest.config.js'],
      [Tools::PHPCS],
      TRUE,
      ['Jest' => ['jest.config.js']],
    ];
    yield 'selected tool is not reported' => [
      ['jest.config.js'],
      [Tools::JEST],
      TRUE,
      [],
    ];
    yield 'fresh install is not reported' => [
      ['jest.config.js'],
      [],
      FALSE,
      [],
    ];
    yield 'several deselected tools' => [
      ['jest.config.js', 'phpstan.neon', 'phpcs.xml'],
      [Tools::PHPCS],
      TRUE,
      [
        'PHPStan' => ['phpstan.neon'],
        'Jest' => ['jest.config.js'],
      ],
    ];
    yield 'directory footprint' => [
      ['tests/phpunit/ExampleTest.php'],
      [],
      TRUE,
      ['PHPUnit' => ['tests/phpunit']],
    ];
    yield 'several files of a single tool' => [
      ['phpunit.xml', 'tests/phpunit/ExampleTest.php'],
      [],
      TRUE,
      ['PHPUnit' => ['phpunit.xml', 'tests/phpunit']],
    ];
    // Globbed paths resolve project-authored content, so they are used for
    // staging removal only and must never be reported for deletion.
    yield 'project-authored content is not reported' => [
      [
        'web/modules/custom/mymodule/js/mymodule.test.js',
        'web/modules/custom/mymodule/tests/src/Kernel/ExampleTest.php',
      ],
      [],
      TRUE,
      [],
    ];
    yield 'tool without a file footprint' => [
      ['.hadolint.yaml'],
      [],
      TRUE,
      [],
    ];
  }

}
