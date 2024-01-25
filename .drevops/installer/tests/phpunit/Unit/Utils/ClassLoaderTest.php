<?php

namespace DrevOps\Installer\Tests\Unit\Utils;

use DrevOps\Installer\Utils\ClassLoader;
use DrevOps\Installer\Utils\ConstantsLoader;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \DrevOps\Installer\Utils\ClassLoader
 */
class ClassLoaderTest extends TestCase {

  protected $testPath;

  protected function setUp(): void {
    // Create a temporary directory for our test classes.
    $this->testPath = sys_get_temp_dir() . '/classLoaderTest';
    if (!is_dir($this->testPath)) {
      mkdir($this->testPath);
    }

    // Create a test class in our temporary directory.
    $testClassContent = <<<'EOD'
<?php
namespace DrevOps\Installer\Utils\Tests;
class TestClass {}
EOD;
    file_put_contents($this->testPath . '/TestClass.php', $testClassContent);
  }

  protected function tearDown(): void {
    // Cleanup after tests.
    unlink($this->testPath . '/TestClass.php');
    rmdir($this->testPath);
  }

  /**
   * @covers ::load
   */
  public function testLoadClassesFromPath(): void {
    $classes = ClassLoader::load($this->testPath);
    $this->assertContains('DrevOps\Installer\Utils\Tests\TestClass', $classes);
  }

  /**
   * @covers ::filterByClass
   */
  public function testFilterByClass(): void {
    $classes = [
      'DrevOps\Installer\Utils\Tests\TestClass',
      'DrevOps\Installer\Utils\AnotherClass'
    ];
    $filtered = ClassLoader::filterByClass(TestCase::class, $classes);
    $this->assertNotContains('DrevOps\Installer\Utils\Tests\TestClass', $filtered);
  }

}
