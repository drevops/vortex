<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Functional;

use AlexSkrypnyk\File\File;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the 'scripts/vortex-tooling.sh' host-side bootstrap.
 *
 * The ahoy entrypoint runs the bootstrap before every command, so it must
 * exit successfully from any starting state of 'vendor/' and converge it to
 * a usable state: the tooling package present and the 'vendor/bin/vortex-*'
 * proxies linked. A bootstrap failure aborts the entrypoint and blocks every
 * ahoy command, including the ones needed to recover.
 */
class ToolingBootstrapTest extends FunctionalTestCase {

  protected string $originalCwd = '';

  protected function setUp(): void {
    // Only locations are required: the test copies the bootstrap script and
    // the in-repo tooling package into a temporary project and runs Composer
    // there. No SUT or Docker stack is involved, so the heavy parent setUp is
    // intentionally not called.
    self::locationsInit(File::cwd() . '/../..');
    $this->originalCwd = File::cwd();
  }

  protected function tearDown(): void {
    // Tests chdir into the fixture project; restore the working directory so
    // the location discovery of subsequent tests is not affected.
    if ($this->originalCwd !== '') {
      chdir($this->originalCwd);
    }

    if ($this->tearDownShouldCleanup()) {
      static::locationsTearDown();
    }
  }

  #[DataProvider('dataProviderBootstrap')]
  #[Group('p1')]
  public function testBootstrap(bool $package_present, bool $binaries_present): void {
    $this->logStepStart();

    $project_dir = $this->prepareProject();
    chdir($project_dir);

    $package_marker = $project_dir . '/vendor/drevops/vortex-tooling/pre-existing-package.txt';
    $stale_bin = $project_dir . '/vendor/bin/vortex-provision';

    if ($package_present) {
      $this->logSubstep('Simulate an already present tooling package without linked binaries');
      File::dump($package_marker, "pre-existing package\n");
    }

    if ($binaries_present) {
      $this->logSubstep('Simulate already linked binaries');
      File::dump($stale_bin, "pre-existing binary\n");
    }

    $this->cmd('bash scripts/vortex-tooling.sh', txt: 'Bootstrap must succeed regardless of the pre-existing vendor state');

    $this->assertDirectoryDoesNotExist($project_dir . '/vendor-temp', 'The throwaway Composer project is removed.');

    if ($package_present && $binaries_present) {
      $this->logSubstep('Assert the bootstrap short-circuited and did not touch the existing state');
      $this->assertFileExists($package_marker, 'The existing package copy is left untouched.');
      $this->assertFileContainsString($stale_bin, 'pre-existing binary', 'The existing binary is left untouched.');
      $this->assertFileDoesNotExist($project_dir . '/vendor/drevops/vortex-tooling/src/vortex-provision', 'No installation took place.');
    }
    else {
      $this->logSubstep('Assert the bootstrap installed the package and linked the binaries');
      $this->assertToolingInstalled($project_dir);

      if ($package_present) {
        $this->assertFileDoesNotExist($package_marker, 'The pre-existing package copy is replaced, not merged.');
      }

      if ($binaries_present) {
        $this->assertFileNotContainsString($stale_bin, 'pre-existing binary', 'The stale binary is replaced with the generated proxy.');
      }
    }

    $this->logSubstep('Assert a repeated run short-circuits without reinstalling');
    $canary = $project_dir . '/vendor/drevops/vortex-tooling/canary.txt';
    File::dump($canary, "canary\n");
    $this->cmd('bash scripts/vortex-tooling.sh', txt: 'Repeated bootstrap run must exit early');
    $this->assertFileExists($canary, 'The repeated run does not reinstall the package.');

    $this->logStepFinish();
  }

  public static function dataProviderBootstrap(): array {
    return [
      'fresh vendor' => [FALSE, FALSE],
      'package present, binaries missing' => [TRUE, FALSE],
      'binaries present, package missing' => [FALSE, TRUE],
      'package and binaries present' => [TRUE, TRUE],
    ];
  }

  #[Group('p2')]
  public function testComposerInstallAfterBootstrap(): void {
    $this->logStepStart();

    $project_dir = $this->prepareProject(minimal_composer_json: TRUE);
    chdir($project_dir);

    $this->cmd('bash scripts/vortex-tooling.sh', txt: 'Bootstrap the tooling before the full Composer install');
    $this->assertToolingInstalled($project_dir);

    // The full install re-installs the package into a vendor directory that
    // is not yet tracked by Composer and reports every pre-linked binary as
    // a name conflict. This is expected and cosmetic: the pre-linked proxies
    // are identical to the ones Composer would create, so the install must
    // succeed and the binaries must remain usable.
    $this->cmd('composer install --no-interaction --no-progress', ['* Skipped installation of bin', '* name conflicts with an existing file'], txt: 'Full Composer install succeeds over the bootstrapped vendor', env: ['SHELL_VERBOSITY' => 0]);

    $this->assertToolingInstalled($project_dir);
    $this->assertFileExists($project_dir . '/vendor/composer/installed.json', 'The full install produced a tracked vendor directory.');

    $this->cmd('composer install --no-interaction --no-progress', ['! Skipped installation of bin'], txt: 'Subsequent Composer install does not report binary conflicts', env: ['SHELL_VERBOSITY' => 0]);

    $this->logStepFinish();
  }

  protected function prepareProject(bool $minimal_composer_json = FALSE): string {
    $root = static::locationsRoot();
    $project_dir = static::$tmp . '/project';

    // Take the script under test from the working tree so changes are
    // exercised without an intermediate commit.
    File::copy($root . '/scripts/vortex-tooling.sh', $project_dir . '/scripts/vortex-tooling.sh');

    // Provide the in-repo tooling package targeted by the path repository,
    // limited to the files the published package ships.
    File::copy($root . '/.vortex/tooling/composer.json', $project_dir . '/.vortex/tooling/composer.json');
    File::copy($root . '/.vortex/tooling/src', $project_dir . '/.vortex/tooling/src');

    if (!$minimal_composer_json) {
      // Reuse the template's real manifest so the version resolution guards
      // the shipped 'composer.json', not a hand-written copy.
      File::copy($root . '/composer.json', $project_dir . '/composer.json');

      return $project_dir;
    }

    // A minimal manifest with only the tooling package allows running a full
    // 'composer install' in the fixture without pulling the entire Drupal
    // dependency tree. The constraint and the path repository are sourced
    // from the template so the fixture follows it.
    $raw = file_get_contents($root . '/composer.json');
    $template = is_string($raw) ? json_decode($raw, TRUE) : NULL;
    if (!is_array($template)) {
      throw new \RuntimeException('Unable to read the template composer.json.');
    }

    $require = $template['require'] ?? NULL;
    $constraint = is_array($require) ? ($require['drevops/vortex-tooling'] ?? NULL) : NULL;
    if (!is_string($constraint)) {
      throw new \RuntimeException('The template composer.json does not require the tooling package.');
    }

    $path_repository = NULL;
    $repositories = $template['repositories'] ?? [];
    foreach (is_array($repositories) ? $repositories : [] as $repository) {
      if (is_array($repository) && ($repository['type'] ?? NULL) === 'path') {
        $path_repository = $repository;
        break;
      }
    }

    if (!is_array($path_repository)) {
      throw new \RuntimeException('The template composer.json has no path repository for the tooling package.');
    }

    File::dump($project_dir . '/composer.json', json_encode([
      'name' => 'vortex-test/project',
      'require' => ['drevops/vortex-tooling' => $constraint],
      'repositories' => [$path_repository, ['packagist.org' => FALSE]],
      'minimum-stability' => 'stable',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

    return $project_dir;
  }

  protected function assertToolingInstalled(string $project_dir): void {
    $package_json = $project_dir . '/vendor/drevops/vortex-tooling/composer.json';
    $this->assertFileExists($package_json, 'The tooling package is installed.');

    $raw = file_get_contents($package_json);
    $package = is_string($raw) ? json_decode($raw, TRUE) : NULL;
    $bins = is_array($package) ? ($package['bin'] ?? NULL) : NULL;
    if (!is_array($bins) || $bins === []) {
      throw new \RuntimeException('The installed tooling package does not declare binaries.');
    }

    foreach ($bins as $bin) {
      if (!is_string($bin)) {
        throw new \RuntimeException('The tooling package declares a non-string binary entry.');
      }

      $this->assertFileExists($project_dir . '/vendor/drevops/vortex-tooling/' . $bin, sprintf('The shipped script "%s" exists in the package.', $bin));

      $proxy = $project_dir . '/vendor/bin/' . basename($bin);
      $this->assertFileExists($proxy, sprintf('The binary proxy for "%s" is linked.', $bin));
      $this->assertTrue(is_executable($proxy), sprintf('The binary proxy for "%s" is executable.', $bin));
    }
  }

}
