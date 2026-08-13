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
    $destination = static::$sut . '/destination';
    $tmp = static::$sut . '/tmp';

    foreach ($files as $file) {
      File::dump($destination . '/' . $file);
    }

    $this->stubTemplate($tmp);

    $config = new Config(static::$sut, $destination, $tmp);
    $config->set(Config::IS_VORTEX_PROJECT, $is_vortex_project, TRUE);

    $handler = new Tools($config);
    $handler->setResponses([Tools::id() => $selected]);
    $handler->process();

    $this->assertEquals($expected, $handler->getLeftovers());
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

  /**
   * Create the staged template files that tool processing writes to.
   */
  protected function stubTemplate(string $dir): void {
    File::dump($dir . '/composer.json', (string) json_encode(['require-dev' => [], 'config' => ['allow-plugins' => []]], JSON_PRETTY_PRINT));
    File::dump($dir . '/package.json', (string) json_encode(['devDependencies' => [], 'scripts' => []], JSON_PRETTY_PRINT));
    File::dump($dir . '/.ahoy.yml', 'commands:' . PHP_EOL);
  }

}
