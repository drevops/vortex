<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional\Command;

use DrevOps\VortexCli\Command\ConfigureCommand;
use DrevOps\VortexCli\Command\InstallCommand;
use DrevOps\VortexCli\Prompts\Handlers\AiCodeInstructions;
use DrevOps\VortexCli\Prompts\Handlers\Name;
use DrevOps\VortexCli\Tests\Functional\FunctionalTestCase;
use DrevOps\VortexCli\Utils\Config;
use DrevOps\VortexCli\Utils\Env;
use DrevOps\VortexCli\Utils\File;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Functional tests for ConfigureCommand.
 *
 * A run is scripted by the flag or by answers given up front, and both reach
 * the same code, so each is exercised here rather than assumed equivalent.
 */
#[CoversClass(ConfigureCommand::class)]
class ConfigureCommandTest extends FunctionalTestCase {

  protected function setUp(): void {
    parent::setUp();

    static::envUnsetPrefix('VORTEX_');
    static::envUnsetPrefix('DRUPAL_');

    // A two-word directory name so the derived answers are recognisable.
    static::$sut = File::mkdir(static::$workspace . DIRECTORY_SEPARATOR . 'star_wars');

    chdir(static::$sut);
  }

  /**
   * Answers reach the project on both scripted routes when --apply is given.
   */
  #[DataProvider('dataProviderApplyWritesToTheProject')]
  public function testApplyWritesToTheProject(bool $no_interaction): void {
    $this->installProject();
    $this->assertFileExists(self::$sut . '/AGENTS.md');

    $this->runConfigure($this->configureOptions($no_interaction, [
      '--' . ConfigureCommand::OPTION_APPLY => TRUE,
    ]));

    $this->assertFileDoesNotExist(self::$sut . '/AGENTS.md', 'Turning the answer off should remove the file it controls');
    $this->assertFileDoesNotExist(self::$sut . '/CLAUDE.md');
  }

  /**
   * Data provider for testApplyWritesToTheProject().
   *
   * @return \Iterator<string, array{bool}>
   *   Test data.
   */
  public static function dataProviderApplyWritesToTheProject(): \Iterator {
    yield 'flag' => [TRUE];
    yield 'answers up front' => [FALSE];
  }

  /**
   * Without --apply nothing is written on either scripted route.
   */
  #[DataProvider('dataProviderWithoutApplyNothingChanges')]
  public function testWithoutApplyNothingChanges(bool $no_interaction): void {
    $this->installProject();

    $before = $this->snapshotOf(self::$sut);

    $this->runConfigure($this->configureOptions($no_interaction));

    $this->assertSame($before, $this->snapshotOf(self::$sut), 'No file should change without --apply');
    $this->assertFileExists(self::$sut . '/AGENTS.md');
  }

  /**
   * Data provider for testWithoutApplyNothingChanges().
   *
   * @return \Iterator<string, array{bool}>
   *   Test data.
   */
  public static function dataProviderWithoutApplyNothingChanges(): \Iterator {
    yield 'flag' => [TRUE];
    yield 'answers up front' => [FALSE];
  }

  /**
   * A scripted run emits the answers as JSON and nothing else.
   */
  public function testScriptedRunEmitsAnswersAsJson(): void {
    $this->installProject();

    $output = $this->runConfigure([
      '--' . ConfigureCommand::OPTION_DESTINATION => self::$sut,
      '--' . ConfigureCommand::OPTION_NO_INTERACTION => TRUE,
    ]);

    $this->assertJson(trim($output), 'A scripted run should emit only the answers as JSON');

    $answers = json_decode(trim($output), TRUE);
    $this->assertIsArray($answers);
    $this->assertArrayHasKey(Name::id(), $answers);
  }

  /**
   * Answers given up front make the whole run scripted, not just the form.
   */
  #[DataProvider('dataProviderAnswersUpFrontRunHeadless')]
  public function testAnswersUpFrontRunHeadless(bool $apply): void {
    $this->installProject();

    $options = [
      '--' . ConfigureCommand::OPTION_DESTINATION => self::$sut,
      '--' . ConfigureCommand::OPTION_PROMPTS => (string) json_encode([Name::id() => 'Star Wars']),
    ];

    if ($apply) {
      $options['--' . ConfigureCommand::OPTION_APPLY] = TRUE;
    }

    $output = $this->runConfigure($options);

    $this->assertStringNotContainsString('Apply the answers to the project?', $output, 'A run answered up front should not stop to confirm');

    if ($apply) {
      return;
    }

    $this->assertJson(trim($output), 'A run answered up front should emit only the answers as JSON');
  }

  /**
   * Data provider for testAnswersUpFrontRunHeadless().
   *
   * @return \Iterator<string, array{bool}>
   *   Test data.
   */
  public static function dataProviderAnswersUpFrontRunHeadless(): \Iterator {
    yield 'collect only' => [FALSE];
    yield 'apply' => [TRUE];
  }

  /**
   * An interactive run reports a summary instead of raw JSON.
   */
  public function testInteractiveRunReportsSummary(): void {
    $this->installProject();

    $this->runConfigure(['--' . ConfigureCommand::OPTION_DESTINATION => self::$sut]);

    $this->assertApplicationAnyOutputContainsOrNot([
      '* Configuration summary',
      '* Finished collecting answers',
      '* Re-run with --apply',
    ]);
  }

  /**
   * Answers are pre-filled from the project being reconfigured.
   */
  public function testDiscoveryPreFillsFromTheProject(): void {
    $this->installProject([InstallCommand::OPTION_PROMPTS => (string) json_encode([Name::id() => 'Star Wars'])]);

    $output = $this->runConfigure([
      '--' . ConfigureCommand::OPTION_DESTINATION => self::$sut,
      '--' . ConfigureCommand::OPTION_NO_INTERACTION => TRUE,
    ]);

    $answers = json_decode(trim($output), TRUE);
    $this->assertIsArray($answers);
    $this->assertSame('Star Wars', $answers[Name::id()], 'The site name should be discovered from the project rather than defaulted');
  }

  /**
   * A relative destination is resolved before any value is derived from it.
   */
  public function testRelativeDestinationIsResolved(): void {
    $this->installProject();

    $cwd = getcwd();
    $this->assertNotFalse($cwd);
    chdir(self::$sut);

    try {
      $output = $this->runConfigure([
        '--' . ConfigureCommand::OPTION_DESTINATION => '.',
        '--' . ConfigureCommand::OPTION_NO_INTERACTION => TRUE,
      ]);
    }
    finally {
      chdir($cwd);
    }

    $answers = json_decode(trim($output), TRUE);
    $this->assertIsArray($answers);
    $this->assertNotSame('.', $answers[Name::id()], 'A relative destination should not reach the derived values literally');
  }

  /**
   * Applying to a directory that is not a Vortex project is refused.
   */
  public function testApplyRefusesNonVortexProject(): void {
    $this->runConfigure([
      '--' . ConfigureCommand::OPTION_DESTINATION => self::$sut,
      '--' . ConfigureCommand::OPTION_NO_INTERACTION => TRUE,
      '--' . ConfigureCommand::OPTION_APPLY => TRUE,
    ], TRUE);

    $this->assertApplicationAnyOutputContainsOrNot([
      '* is not a Vortex project, so there is nothing to reconfigure',
    ]);
  }

  /**
   * The agent surface answers on the configure verb as well.
   */
  #[DataProvider('dataProviderConfigureExposesAgentSurface')]
  public function testConfigureExposesAgentSurface(string $option, string $expected): void {
    $output = $this->runConfigure(['--' . $option => TRUE]);

    $this->assertStringContainsString($expected, $output);
  }

  /**
   * Data provider for testConfigureExposesAgentSurface().
   *
   * @return \Iterator<string, array{string, string}>
   *   Test data.
   */
  public static function dataProviderConfigureExposesAgentSurface(): \Iterator {
    yield 'schema' => [ConfigureCommand::OPTION_SCHEMA, '"prompts"'];
    yield 'agent help' => [ConfigureCommand::OPTION_AGENT_HELP, 'AI Agent Instructions'];
  }

  /**
   * Options that answer one file-controlling question with "no".
   *
   * The answer is one the handlers can act on in an already-installed project,
   * so a run that honours it leaves a visible trace on disk. Each route carries
   * the answer the way that route is meant to: the flag route takes it from the
   * environment, so it is not silently also carrying the option that makes the
   * other route scripted.
   *
   * @param bool $no_interaction
   *   Whether the run is scripted by the flag rather than by answers.
   * @param array<string, mixed> $extra
   *   Options to add.
   *
   * @return array<string, mixed>
   *   The command options.
   */
  protected function configureOptions(bool $no_interaction, array $extra = []): array {
    $options = ['--' . ConfigureCommand::OPTION_DESTINATION => self::$sut] + $extra;

    if (!$no_interaction) {
      $options['--' . ConfigureCommand::OPTION_PROMPTS] = (string) json_encode([AiCodeInstructions::id() => FALSE]);

      return $options;
    }

    Env::put(AiCodeInstructions::envName(), '0');
    $options['--' . ConfigureCommand::OPTION_NO_INTERACTION] = TRUE;

    return $options;
  }

  /**
   * Run the configure command against a fresh application.
   */
  protected function runConfigure(array $options, bool $expect_failure = FALSE): string {
    $command = new ConfigureCommand();
    static::applicationInitFromCommand($command);

    return $this->applicationRun($options, [], $expect_failure);
  }

  /**
   * Install Vortex into the destination so there is a project to reconfigure.
   */
  protected function installProject(array $options = []): void {
    Env::put(Config::IS_DEMO_DB_FETCH_SKIP, '1');

    static::applicationInitFromCommand(InstallCommand::class);

    $this->runNonInteractiveInstall(options: $options);
  }

  /**
   * Capture the content of every file in a directory, keyed by relative path.
   *
   * @return array<string, string>
   *   File contents keyed by path.
   */
  protected function snapshotOf(string $dir): array {
    $snapshot = [];

    foreach (File::scandir($dir, File::ignoredPaths()) as $path) {
      $snapshot[str_replace($dir, '', (string) $path)] = (string) file_get_contents((string) $path);
    }

    return $snapshot;
  }

}
