<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit;

use AlexSkrypnyk\File\File;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UnitTestCase::class)]
class SelfTest extends UnitTestCase {

  public function testVersionReplacement(): void {
    $baseline = File::dir($this->locationsFixtureDir() . DIRECTORY_SEPARATOR . 'baseline');
    $expected = File::dir($this->locationsFixtureDir() . DIRECTORY_SEPARATOR . 'expected');
    File::sync($baseline, static::$sut);

    static::replaceVersions(static::$sut);

    $this->assertDirectoryEqualsDirectory(static::$sut, $expected);
  }

}
