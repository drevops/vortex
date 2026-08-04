<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional\Command;

use DrevOps\VortexCli\Command\UpdateCommand;
use DrevOps\VortexCli\Downloader\Artifact;
use DrevOps\VortexCli\Downloader\RepositoryDownloader;
use DrevOps\VortexCli\Tests\Functional\FunctionalTestCase;
use DrevOps\VortexCli\Utils\Config;
use DrevOps\VortexCli\Utils\Env;
use DrevOps\VortexCli\Utils\File;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\ExecutableFinder;

/**
 * Functional tests for UpdateCommand.
 */
#[CoversClass(UpdateCommand::class)]
class UpdateCommandTest extends FunctionalTestCase {

  #[DataProvider('dataProviderTargetUri')]
  public function testTargetUri(mixed $to, mixed $uri, ?string $expected): void {
    $this->assertSame($expected, (new UpdateCommand())->targetUri($to, $uri));
  }

  /**
   * Data provider for testTargetUri().
   *
   * @return \Iterator<string, array{mixed, mixed, (string | null)}>
   *   Test data.
   */
  public static function dataProviderTargetUri(): \Iterator {
    $repo = RepositoryDownloader::DEFAULT_REPO;

    yield 'neither given' => [NULL, NULL, NULL];
    yield 'both empty' => ['', '', NULL];
    yield 'version only' => ['1.2.3', NULL, $repo . '#1.2.3'];
    yield 'version only, empty uri' => ['1.2.3', '', $repo . '#1.2.3'];
    yield 'branch as version' => ['main', NULL, $repo . '#main'];
    yield 'uri only' => [NULL, 'https://example.com/fork.git#main', 'https://example.com/fork.git#main'];
    yield 'explicit uri wins over version' => ['1.2.3', 'https://example.com/fork.git#main', 'https://example.com/fork.git#main'];
    yield 'non-string version ignored' => [123, NULL, NULL];
    yield 'non-string uri falls through to version' => ['1.2.3', TRUE, $repo . '#1.2.3'];
  }

  /**
   * The named version reaches the downloader as the reference to fetch.
   */
  public function testUpdateDownloadsTargetVersion(): void {
    $command = $this->updateCommand();

    $artifact = NULL;
    $downloader = $this->createMock(RepositoryDownloader::class);
    $downloader->method('download')->willReturnCallback(function (Artifact $downloaded) use (&$artifact): string {
      $artifact = $downloaded;
      throw new \RuntimeException('Failed to download Vortex.');
    });
    $command->setRepositoryDownloader($downloader);

    static::applicationInitFromCommand($command);
    $this->applicationRun([
      '--' . UpdateCommand::OPTION_NO_INTERACTION => TRUE,
      '--' . UpdateCommand::OPTION_TO => '1.2.3',
      '--' . UpdateCommand::OPTION_DESTINATION => self::$sut,
    ], [], TRUE);

    $this->assertInstanceOf(Artifact::class, $artifact);
    $this->assertSame(RepositoryDownloader::DEFAULT_REPO, $artifact->getRepo());
    $this->assertSame('1.2.3', $artifact->getRef());
  }

  /**
   * Updating a project of another major is refused, naming the right release.
   */
  public function testUpdateRefusesForeignMajor(): void {
    $command = $this->updateCommand();
    $this->markSutAsVortexProject('{"require": {"drevops/vortex-tooling": "^2.0.0"}}');

    static::applicationInitFromCommand($command);
    $this->applicationGet()->setVersion('1.40.0');

    $this->applicationRun([
      '--' . UpdateCommand::OPTION_NO_INTERACTION => TRUE,
      '--' . UpdateCommand::OPTION_URI => File::dir(static::$root),
      '--' . UpdateCommand::OPTION_DESTINATION => self::$sut,
    ], [], TRUE);

    $this->assertApplicationAnyOutputContainsOrNot(['* https://www.vortextemplate.com/v2/install']);
  }

  /**
   * An existing project is framed as an update rather than an install.
   */
  public function testUpdateFramesTheRunAsAnUpdate(): void {
    $command = $this->updateCommand();
    $this->markSutAsVortexProject('{"require": {"drevops/vortex-tooling": "^1.0.0"}}');

    $downloader = $this->createMock(RepositoryDownloader::class);
    $downloader->method('download')->willThrowException(new \RuntimeException('Failed to download Vortex.'));
    $command->setRepositoryDownloader($downloader);

    static::applicationInitFromCommand($command);
    $this->applicationRun([
      '--' . UpdateCommand::OPTION_NO_INTERACTION => TRUE,
      '--' . UpdateCommand::OPTION_DESTINATION => self::$sut,
    ], [], TRUE);

    $this->assertApplicationAnyOutputContainsOrNot([
      '* Welcome to the Vortex CLI non-interactive update',
      '* It looks like Vortex is already installed into this project.',
      '! Welcome to the Vortex CLI non-interactive install',
    ]);
  }

  /**
   * A target version from another major line is refused.
   *
   * The destination gate compares the project against this build and says
   * nothing about the version being asked for, so the request is checked too.
   */
  #[DataProvider('dataProviderTargetMajorGate')]
  public function testTargetMajorGate(string $version, string $to, bool $expect_failure, array $assertions): void {
    $command = $this->updateCommand();

    $downloader = $this->createMock(RepositoryDownloader::class);
    $downloader->method('download')->willThrowException(new \RuntimeException('Failed to download Vortex.'));
    $command->setRepositoryDownloader($downloader);

    static::applicationInitFromCommand($command);
    $this->applicationGet()->setVersion($version);

    $this->applicationRun([
      '--' . UpdateCommand::OPTION_NO_INTERACTION => TRUE,
      '--' . UpdateCommand::OPTION_TO => $to,
      '--' . UpdateCommand::OPTION_DESTINATION => self::$sut,
    ], [], $expect_failure);

    $this->assertApplicationAnyOutputContainsOrNot($assertions);
  }

  /**
   * Data provider for testTargetMajorGate().
   *
   * @return \Iterator<string, array{string, string, bool, array<string>}>
   *   Test data.
   */
  public static function dataProviderTargetMajorGate(): \Iterator {
    yield 'foreign major refused' => [
      '1.40.0',
      '2.0.0',
      TRUE,
      ['* https://www.vortextemplate.com/v2/install', '! Failed to download Vortex.'],
    ];
    yield 'same major allowed' => [
      '1.40.0',
      '1.2.3',
      TRUE,
      ['* Failed to download Vortex.'],
    ];
    yield 'branch carries no major' => [
      '1.40.0',
      'main',
      TRUE,
      ['* Failed to download Vortex.'],
    ];
    yield 'unstamped build skips the gate' => [
      'UNKNOWN',
      '2.0.0',
      TRUE,
      ['* Failed to download Vortex.'],
    ];
  }

  /**
   * The agent surface answers on the update verb as well.
   */
  public function testUpdateExposesAgentSurface(): void {
    $command = $this->updateCommand();

    static::applicationInitFromCommand($command);

    $this->assertJson($this->applicationRun(['--' . UpdateCommand::OPTION_SCHEMA => TRUE]));
  }

  /**
   * An unreadable answers file is reported as such, not as broken JSON.
   */
  public function testUnreadablePromptsFileIsReported(): void {
    $path = self::$tmp . '/unreadable_' . uniqid() . '.json';
    $this->assertNotFalse(file_put_contents($path, '{"name":"Star Wars"}'));
    chmod($path, 0000);

    static::applicationInitFromCommand($this->updateCommand());

    $this->applicationRun([
      '--' . UpdateCommand::OPTION_VALIDATE => TRUE,
      '--' . UpdateCommand::OPTION_PROMPTS => $path,
    ], [], TRUE);

    chmod($path, 0644);

    $this->assertApplicationAnyOutputContainsOrNot([
      '* Cannot read --prompts file:',
      '! Invalid JSON in --prompts.',
    ]);
  }

  /**
   * Build an update command with the executable finder mocked.
   */
  protected function updateCommand(): UpdateCommand {
    $executable_finder = $this->createMock(ExecutableFinder::class);
    $executable_finder->method('find')->willReturnCallback(fn(string $command): string => '/usr/bin/' . $command);

    $command = new UpdateCommand();
    $command->setExecutableFinder($executable_finder);

    Env::put(Config::IS_DEMO_DB_FETCH_SKIP, '1');

    return $command;
  }

  /**
   * Make the destination look like an installed project of a given major.
   */
  protected function markSutAsVortexProject(string $composer_json): void {
    $this->assertNotFalse(file_put_contents(self::$sut . '/README.md', '[![Vortex](https://img.shields.io/badge/Vortex-1.40.0-65ACBC.svg)](https://github.com/drevops/vortex)'));
    $this->assertNotFalse(file_put_contents(self::$sut . '/composer.json', $composer_json));
  }

}
