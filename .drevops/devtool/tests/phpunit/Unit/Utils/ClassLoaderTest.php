<?php

namespace DrevOps\DevTool\Tests\Unit\Utils;

use DrevOps\DevTool\Tests\Traits\VfsTrait;
use DrevOps\DevTool\Utils\ClassLoader;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \DrevOps\DevTool\Utils\ClassLoader
 */
class ClassLoaderTest extends TestCase {

  use VfsTrait;

  /**
   * Test path.
   */
  protected string $testPath;

  protected function setUp(): void {
    $this->testPath = $this->vfsCreateDirectory('classLoaderTest')->url();

    $this->vfsCreateFile($this->testPath . '/AbstractTestClass.php', <<<'EOD'
<?php
  namespace DrevOps\DevTool\Utils\Tests;
  abstract class AbstractTestClass {}
EOD
    );

    $this->vfsCreateFile($this->testPath . '/Test1Class.php', <<<'EOD'
<?php
  namespace DrevOps\DevTool\Utils\Tests;
  class Test1Class extends AbstractTestClass {}
EOD
    );

    $this->vfsCreateFile($this->testPath . '/Test2Class.php', <<<'EOD'
<?php
  namespace DrevOps\DevTool\Utils\Tests;
  class Test2Class {}
EOD
    );
  }

  /**
   * @covers ::load
   * @covers ::glob
   */
  public function testLoadClassesFromPath(): void {
    $classes = ClassLoader::load($this->testPath);
    $this->assertContains('DrevOps\DevTool\Utils\Tests\AbstractTestClass', $classes);
    $this->assertContains('DrevOps\DevTool\Utils\Tests\Test1Class', $classes);
    $this->assertContains('DrevOps\DevTool\Utils\Tests\Test2Class', $classes);
  }

  /**
   * @covers ::load
   * @covers ::glob
   */
  public function testLoadClassesFromPathFilterByParent(): void {
    $classes = ClassLoader::load($this->testPath, 'DrevOps\DevTool\Utils\Tests\AbstractTestClass');
    $this->assertNotContains('DrevOps\DevTool\Utils\Tests\AbstractTestClass', $classes);
    $this->assertContains('DrevOps\DevTool\Utils\Tests\Test1Class', $classes);
    $this->assertNotContains('DrevOps\DevTool\Utils\Tests\Test2Class', $classes);
  }

  /**
   * @covers ::filterByClass
   */
  public function testFilterByClass(): void {
    $classes = [
      'DrevOps\DevTool\Utils\Tests\TestClass',
      'DrevOps\DevTool\Utils\AnotherClass',
    ];
    $filtered = ClassLoader::filterByClass(TestCase::class, $classes);
    $this->assertNotContains('DrevOps\DevTool\Utils\Tests\TestClass', $filtered);
  }

}
