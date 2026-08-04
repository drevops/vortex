<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional\Command;

use AlexSkrypnyk\File\File as UpstreamFile;
use DrevOps\VortexCli\Command\ConfigureCommand;
use DrevOps\VortexCli\Command\InstallCommand;
use DrevOps\VortexCli\Command\RouteCommand;
use DrevOps\VortexCli\Downloader\RepositoryDownloader;
use DrevOps\VortexCli\Tests\Functional\FunctionalTestCase;
use DrevOps\VortexCli\Utils\File;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\ExecutableFinder;

/**
 * Functional tests for RouteCommand.
 */
#[CoversClass(RouteCommand::class)]
class RouteCommandTest extends FunctionalTestCase {

  protected function setUp(): void {
    parent::setUp();

    static::$sut = File::mkdir(static::$workspace . DIRECTORY_SEPARATOR . 'star_wars');

    chdir(static::$sut);
  }

  #[DataProvider('dataProviderTarget')]
  public function testTarget(?string $readme, string $expected): void {
    $dir = self::$tmp . '/routed_' . uniqid();
    UpstreamFile::mkdir($dir);

    if ($readme !== NULL) {
      UpstreamFile::dump($dir . '/README.md', $readme);
    }

    $this->assertSame($expected, (new RouteCommand())->target($dir));
  }

  /**
   * Data provider for testTarget().
   *
   * @return \Iterator<string, array{(string | null), string}>
   *   Test data.
   */
  public static function dataProviderTarget(): \Iterator {
    yield 'empty directory installs' => [NULL, 'install'];
    yield 'unrelated project installs' => ['# Some other project', 'install'];
    yield 'vortex project reconfigures' => ['[![Vortex](https://img.shields.io/badge/Vortex-1.40.0-blue.svg)]', 'configure'];
  }

  public function testTargetForMissingDirectory(): void {
    $this->assertSame('install', (new RouteCommand())->target(self::$tmp . '/never_created_' . uniqid()));
  }

  /**
   * A bare invocation in an empty directory can describe its own questions.
   *
   * This is the entry point a downloaded binary is run from, so the agent
   * surface has to be reachable through it.
   */
  public function testBareInvocationDescribesQuestionsWhenInstalling(): void {
    $output = $this->runRouted(['--schema' => TRUE]);

    $this->assertJson(trim($output));
    $this->assertStringContainsString('"prompts"', $output);
  }

  /**
   * The same holds once the directory holds a project and routes elsewhere.
   */
  public function testBareInvocationDescribesQuestionsWhenConfiguring(): void {
    $this->markSutAsVortexProject();

    $output = $this->runRouted(['--schema' => TRUE]);

    $this->assertJson(trim($output));
    $this->assertStringContainsString('"prompts"', $output);
  }

  /**
   * Agent instructions are reachable from a bare invocation too.
   */
  public function testBareInvocationPrintsAgentHelp(): void {
    $this->assertStringContainsString('AI Agent Instructions', $this->runRouted(['--agent-help' => TRUE]));
  }

  /**
   * A destination holding a project is routed to the configure verb.
   */
  public function testRoutesToConfigureForExistingProject(): void {
    $this->markSutAsVortexProject();

    $output = $this->runRouted([
      '--destination' => self::$sut,
      '--no-interaction' => TRUE,
    ]);

    // The configure verb reports the collected answers as JSON; the install
    // verb never would.
    $this->assertJson(trim($output));
  }

  /**
   * A destination without a project is routed to the install verb.
   */
  public function testRoutesToInstallForEmptyDirectory(): void {
    // The download is stubbed to fail: reaching it at all is what proves the
    // install verb was selected, without paying for a real download.
    $downloader = $this->createMock(RepositoryDownloader::class);
    $downloader->method('download')->willThrowException(new \RuntimeException('Failed to download Vortex.'));

    $executable_finder = $this->createMock(ExecutableFinder::class);
    $executable_finder->method('find')->willReturnCallback(fn(string $command): string => '/usr/bin/' . $command);

    $install = new InstallCommand();
    $install->setRepositoryDownloader($downloader);
    $install->setExecutableFinder($executable_finder);

    $this->runRouted([
      '--destination' => self::$sut,
      '--no-interaction' => TRUE,
    ], TRUE, $install);

    $this->assertApplicationAnyOutputContainsOrNot([
      '* Welcome to the Vortex CLI non-interactive install',
      '* Failed to download Vortex.',
    ]);
  }

  /**
   * Run the application with the route command as the default.
   */
  protected function runRouted(array $options, bool $expect_failure = FALSE, ?InstallCommand $install = NULL): string {
    static::applicationInitFromCommand(new RouteCommand(), FALSE);

    $application = $this->applicationGet();
    $application->add($install ?? new InstallCommand());
    $application->add(new ConfigureCommand());
    $application->setDefaultCommand('route');

    return $this->applicationRun($options, [], $expect_failure);
  }

  /**
   * Make the destination look like an installed project.
   */
  protected function markSutAsVortexProject(): void {
    UpstreamFile::dump(self::$sut . '/README.md', '[![Vortex](https://img.shields.io/badge/Vortex-1.40.0-65ACBC.svg)](https://github.com/drevops/vortex)');
  }

}
