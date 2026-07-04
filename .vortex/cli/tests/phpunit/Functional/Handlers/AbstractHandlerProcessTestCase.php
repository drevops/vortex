<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional\Handlers;

use AlexSkrypnyk\File\File as UpstreamFile;
use AlexSkrypnyk\File\Replacer\Replacement;
use AlexSkrypnyk\File\Testing\DirectoryAssertionsTrait;
use AlexSkrypnyk\File\Testing\FileAssertionsTrait;
use AlexSkrypnyk\PhpunitHelpers\Traits\ApplicationTrait;
use AlexSkrypnyk\PhpunitHelpers\Traits\EnvTrait;
use AlexSkrypnyk\PhpunitHelpers\Traits\SerializableClosureTrait;
use AlexSkrypnyk\PhpunitHelpers\UnitTestCase;
use AlexSkrypnyk\Snapshot\Testing\SnapshotTrait;
use DrevOps\VortexCli\Command\Install;
use DrevOps\VortexCli\Utils\Config;
use DrevOps\VortexCli\Utils\Env;
use DrevOps\VortexCli\Utils\File;
use Laravel\SerializableClosure\SerializableClosure;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

/**
 * Base class for handler-process snapshot tests.
 *
 * Each scenario installs the local template into a fresh SUT and compares the
 * result against the baseline plus the scenario's diff. Run
 * `ahoy update-snapshots` from `.vortex/` to update snapshots.
 */
abstract class AbstractHandlerProcessTestCase extends UnitTestCase {

  use SerializableClosureTrait;
  use DirectoryAssertionsTrait;
  use FileAssertionsTrait;
  use SnapshotTrait;
  use EnvTrait;
  use ApplicationTrait;

  /**
   * Options passed to the install command.
   *
   * @var array<string, mixed>
   */
  public array $installOptions = [];

  /**
   * Prompt overrides passed via the --prompts option, keyed by field id.
   *
   * @var array<string, mixed>
   */
  public array $prompts = [];

  protected function setUp(): void {
    $cwd = getcwd();
    if ($cwd === FALSE) {
      throw new \RuntimeException('Failed to determine current working directory.');
    }

    self::locationsInit($cwd . '/../../');

    static::envUnsetPrefix('VORTEX_');
    static::envUnsetPrefix('DRUPAL_');
    static::envUnsetPrefix('LAGOON_');
    static::envUnset('WEBROOT');
    static::envUnset('TZ');

    static::applicationInitFromCommand(Install::class);

    static::$sut = File::mkdir(static::$workspace . DIRECTORY_SEPARATOR . 'star_wars');
    chdir(static::$sut);
  }

  protected function tearDown(): void {
    static::envReset();

    if (!empty(static::$fixtures) && str_contains(static::$fixtures, DIRECTORY_SEPARATOR . 'handler_process' . DIRECTORY_SEPARATOR)) {
      $this->snapshotUpdateOnFailure(static::$fixtures, static::$sut, static::$tmp);
    }

    parent::tearDown();
  }

  public static function locationsFixturesDir(): string {
    return '.vortex/cli/tests/phpunit/Fixtures';
  }

  protected function snapshotUpdateBefore(string $actual): void {
    $this->replaceVersions($actual);
  }

  #[DataProvider('dataProviderHandlerProcess')]
  #[RunInSeparateProcess]
  public function testHandlerProcess(?SerializableClosure $before = NULL, ?SerializableClosure $after = NULL, array $expected = []): void {
    static::$fixtures = static::locationsFixtureDir();

    if ($before instanceof SerializableClosure) {
      $before = static::cu($before);
      $before($this);
    }

    if (!empty($this->prompts)) {
      $this->installOptions['prompts'] = (string) json_encode($this->prompts);
    }

    $this->runInstall($this->installOptions);

    $baseline = File::dir(static::$fixtures . '/../' . self::BASELINE_DIR);
    $this->replaceVersions(static::$sut);
    $this->assertSnapshotMatchesBaseline(static::$sut, $baseline, (string) static::$fixtures);

    if ($after instanceof SerializableClosure) {
      $after = static::cu($after);
      $after($this);
    }
  }

  abstract public static function dataProviderHandlerProcess(): \Iterator;

  protected function runInstall(array $options = []): void {
    $defaults = [
      'no-interaction' => TRUE,
      'uri' => File::dir(static::$root),
      'destination' => static::$sut,
    ];

    $options += $defaults;

    $args = [];
    foreach ($options as $option => $value) {
      $args['--' . $option] = $value;
    }

    // Skip the demo database fetch, which is not needed for snapshot tests.
    Env::put(Config::IS_DEMO_DB_FETCH_SKIP, '1');

    $this->applicationRun($args);
  }

  protected function replaceVersions(string $dir): void {
    UpstreamFile::getReplacer()
      ->addVersionReplacements()
      ->addReplacement(Replacement::create('phpstan_version', '/(phpVersion:\s)\d{5,6}/', '${1}' . Replacement::VERSION))
      ->addExclusions(['127.0.0.1'])
      ->setMaxReplacements(5)
      ->replaceInDir($dir, ['scripts/vortex']);
  }

}
