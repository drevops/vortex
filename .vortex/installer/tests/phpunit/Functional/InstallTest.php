<?php

declare(strict_types=1);

namespace DrevOps\Installer\Tests\Functional;

use DrevOps\Installer\Prompts\Handlers\CodeProvider;
use DrevOps\Installer\Prompts\PromptManager;
use DrevOps\Installer\Utils\Config;
use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\File;
use Symfony\Component\Finder\Finder;

/**
 * Test the initial installation.
 *
 * @coversDefaultClass \DrevOps\Installer\Command\InstallCommand
 */
class InstallTest extends FunctionalTestBase {

  /**
   * Test the initial installation.
   *
   * @runInSeparateProcess
   * @group install
   *
   * @covers ::execute
   */
  public function testInstallInteractiveDefaults(): void {
    $this->runInteractiveInstall(static::fill());

    $this->assertTesterSuccessOutputContains('Welcome to Vortex interactive installer');

    $this->assertCommon();
  }

  /**
   * Test the initial installation.
   *
   * @runInSeparateProcess
   * @group install
   *
   * @covers ::execute
   */
  public function testInstallNonInteractiveDefaults(): void {
    $this->runNonInteractiveInstall();

    $this->assertTesterSuccessOutputContains('Welcome to Vortex non-interactive installer');

    $this->assertCommon();
  }

  protected function assertCommon() {
    $this->assertFixtureDirectoryEqualsSut('post_install');

    $this->assertDirectoriesEqual(static::$root . '/scripts/vortex', static::$sut . '/scripts/vortex', 'Vortex scripts were not modified.');
    $this->assertFileEquals(static::$root . '/tests/behat/fixtures/image.jpg', static::$sut . '/tests/behat/fixtures/image.jpg', 'Binary files were not modified.');
  }

  /**
   * @dataProvider dataProviderInstallProcessing
   * @covers ::execute
   */
  public function testInstallProcessing(callable $before_callback) {
    $before_callback($this);
    $this->runNonInteractiveInstall();

    $this->assertFixtureDiffDirectoryEqualsSut();
  }

  // Allows to use base and diff directories for fixtures.
  // Supports files with negative markers to show absence.
  protected function assertFixtureDiffDirectoryEqualsSut() {
    $base = dirname(static::$fixtures) . '/base';
    $tmp = File::tmpdir();
    File::copyRecursive($base, $tmp);
    File::copyRecursive(static::$fixtures, $tmp);

    foreach ((new Finder())->in($tmp)->files()->name('-*') as $file) {
      if ($file->isFile()) {
        $relative = str_replace($tmp . DIRECTORY_SEPARATOR, '', $file->getRealPath());
        $path = $tmp . DIRECTORY_SEPARATOR . substr($relative, 1);
        // Remove negative file or directory.
        if (is_file($path)) {
          @unlink($path);
        }
        else {
          File::rmdirRecursive($path);
        }
        // Remove negative marker file.
        @unlink($file->getRealPath());
      }
    }

    $this->assertDirectoriesEqual($tmp, static::$sut);
  }

  public static function dataProviderInstallProcessing() {
    return [
      'code provider, github' => [
        fn() => Env::put(PromptManager::makeEnvName(CodeProvider::id()), CodeProvider::GITHUB),
      ],
      'code provider, other' => [
        fn() => Env::put(PromptManager::makeEnvName(CodeProvider::id()), CodeProvider::OTHER),
      ],
    ];
  }

}
