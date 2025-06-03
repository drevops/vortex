<?php

declare(strict_types=1);

namespace DrevOps\Installer\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use DrevOps\Installer\Utils\Converter;

/**
 * Tests for the Converter class.
 */
#[CoversClass(Converter::class)]
class ConverterTest extends UnitTestCase {

  #[DataProvider('dataProviderMachineExtended')]
  public function testMachineExtended(string $input, string $expected): void {
    $this->assertEquals($expected, Converter::machineExtended($input));
  }

  public static function dataProviderMachineExtended(): array {
    return [
      // Basic cases.
      ['hello world', 'hello_world'],
      ['Hello World', 'hello_world'],
      ['HELLO WORLD', 'hello_world'],

      // Multiple spaces.
      ['hello  world', 'hello__world'],
      ['hello   world', 'hello___world'],

      // Mixed case with spaces.
      ['My Project Name', 'my_project_name'],
      ['YOUR_SITE_NAME', 'your_site_name'],

      // Already underscored.
      ['hello_world', 'hello_world'],
      ['Hello_World', 'hello_world'],

      // Special characters (should be removed by strict())
      ['hello@world!', 'helloworld'],
      ['my-project#name$', 'my-projectname'],
      ['test%^&*()project', 'testproject'],

      // Numbers.
      ['project 123', 'project_123'],
      ['Project2024 Name', 'project2024_name'],

      // Unicode characters (should be replaced by strict())
      ['café münü', 'cafe_munu'],
      ['project 😀 name', 'project__name'],

      // Hyphens and underscores mixed.
      ['my-project_name', 'my-project_name'],
      ['test-case_example', 'test-case_example'],

      // Empty and edge cases.
      ['', ''],
      [' ', '_'],
      ['  ', '__'],
      ['_', '_'],
      ['-', '-'],

      // Single word.
      ['project', 'project'],
      ['PROJECT', 'project'],
      ['Project', 'project'],

      // Leading/trailing spaces.
      [' hello world ', '_hello_world_'],
      ['  test  ', '__test__'],

      // Only special characters.
      ['@#$%', ''],
      ['!!!', ''],
      ['***', ''],

      // Real-world examples.
      ['My Awesome Project', 'my_awesome_project'],
      ['DrevOps Vortex', 'drevops_vortex'],
      ['Site Name 2024', 'site_name_2024'],
      ['your_site_theme', 'your_site_theme'],
      ['YourSiteTheme', 'yoursitetheme'],
    ];
  }

}
