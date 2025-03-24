<?php

declare(strict_types=1);

namespace DrevOps\Installer\Tests\Unit;

use AlexSkrypnyk\File\File;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UnitTestBase::class)]
class SelfTest extends UnitTestBase {

  public function testVersionReplacement(): void {
    $baseline = File::dir($this->locationsFixtureDir() . DIRECTORY_SEPARATOR . 'baseline');
    $expected = File::dir($this->locationsFixtureDir() . DIRECTORY_SEPARATOR . 'expected');
    File::sync($baseline, static::$sut);

    static::replaceVersions(static::$sut);

    $this->assertDirectoriesEqual($expected, static::$sut);
  }

}
