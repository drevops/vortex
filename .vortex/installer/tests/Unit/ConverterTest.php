<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use DrevOps\VortexInstaller\Utils\Converter;

/**
 * Tests for the Converter class.
 */
#[CoversClass(Converter::class)]
class ConverterTest extends UnitTestCase {

  #[DataProvider('dataProviderMachineExtended')]
  public function testMachineExtended(string $input, string $expected): void {
    $this->assertEquals($expected, Converter::machineExtended($input));
  }

  public static function dataProviderMachineExtended(): \Iterator {
    // Basic cases.
    yield ['hello world', 'hello_world'];
    yield ['Hello World', 'hello_world'];
    yield ['HELLO WORLD', 'hello_world'];
    // Multiple spaces.
    yield ['hello  world', 'hello__world'];
    yield ['hello   world', 'hello___world'];
    // Mixed case with spaces.
    yield ['My Project Name', 'my_project_name'];
    yield ['YOUR_SITE_NAME', 'your_site_name'];
    // Already underscored.
    yield ['hello_world', 'hello_world'];
    yield ['Hello_World', 'hello_world'];
    // Special characters (should be removed by strict())
    yield ['hello@world!', 'helloworld'];
    yield ['my-project#name$', 'my-projectname'];
    yield ['test%^&*()project', 'testproject'];
    // Numbers.
    yield ['project 123', 'project_123'];
    yield ['Project2024 Name', 'project2024_name'];
    // Unicode characters (should be replaced by strict())
    yield ['café münü', 'cafe_munu'];
    yield ['project 😀 name', 'project__name'];
    // Hyphens and underscores mixed.
    yield ['my-project_name', 'my-project_name'];
    yield ['test-case_example', 'test-case_example'];
    // Empty and edge cases.
    yield ['', ''];
    yield [' ', '_'];
    yield ['  ', '__'];
    yield ['_', '_'];
    yield ['-', '-'];
    // Single word.
    yield ['project', 'project'];
    yield ['PROJECT', 'project'];
    yield ['Project', 'project'];
    // Leading/trailing spaces.
    yield [' hello world ', '_hello_world_'];
    yield ['  test  ', '__test__'];
    // Only special characters.
    yield ['@#$%', ''];
    yield ['!!!', ''];
    yield ['***', ''];
    // Real-world examples.
    yield ['My Awesome Project', 'my_awesome_project'];
    yield ['DrevOps Vortex', 'drevops_vortex'];
    yield ['Site Name 2024', 'site_name_2024'];
    yield ['your_site_theme', 'your_site_theme'];
    yield ['YourSiteTheme', 'yoursitetheme'];
  }

}
