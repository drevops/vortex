<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Functional;

use AlexSkrypnyk\File\File;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests that 'composer/installers' handles the 'drupal-library' package type.
 *
 * Vortex relies on 'composer/installers' alone (without an installer-extender
 * plugin) to place 'drupal-library' packages into 'web/libraries/{$name}'.
 * This guards that the install path keeps working using the template's own
 * installer configuration.
 */
class ComposerInstallerTest extends FunctionalTestCase {

  protected function setUp(): void {
    // Only locations are required: the test reads the template 'composer.json'
    // and runs Composer in a temporary directory. No SUT or Docker stack is
    // involved, so the heavy parent setUp is intentionally not called.
    self::locationsInit(File::cwd() . '/../..');
  }

  protected function tearDown(): void {
    if ($this->tearDownShouldCleanup()) {
      static::locationsTearDown();
    }
  }

  #[Group('p1')]
  public function testDrupalLibraryInstallsToWebLibraries(): void {
    $this->logStepStart();

    // Reuse the template's real installer configuration so this guards the
    // shipped 'composer.json', not a hand-written copy.
    $raw = file_get_contents(static::locationsRoot() . '/composer.json');
    $template = is_string($raw) ? json_decode($raw, TRUE) : NULL;
    if (!is_array($template)) {
      throw new \RuntimeException('Unable to read the template composer.json.');
    }

    $require = $template['require'] ?? NULL;
    $extra = $template['extra'] ?? NULL;
    if (!is_array($require) || !is_array($extra)) {
      throw new \RuntimeException('The template composer.json has no "require" or "extra" section.');
    }

    $installers_constraint = $require['composer/installers'] ?? NULL;
    $installer_paths = $extra['installer-paths'] ?? NULL;
    if (!is_string($installers_constraint) || !is_array($installer_paths)) {
      throw new \RuntimeException('The template composer.json is missing "composer/installers" or "installer-paths".');
    }

    $library_name = 'vortex_test_library';

    $this->logSubstep('Create a local fixture package of type "drupal-library"');
    $library_dir = static::$tmp . '/fixture_library';
    File::dump($library_dir . '/composer.json', json_encode([
      'name' => 'vortex-test/' . $library_name,
      'type' => 'drupal-library',
      'version' => '1.0.0',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
    File::dump($library_dir . '/library.js', "// Fixture drupal-library payload.\n");

    $this->logSubstep('Create a project using the template installer configuration');
    $project_dir = static::$tmp . '/project';
    File::dump($project_dir . '/composer.json', json_encode([
      'name' => 'vortex-test/project',
      'require' => [
        'composer/installers' => $installers_constraint,
        'vortex-test/' . $library_name => '*',
      ],
      'repositories' => [
        ['type' => 'path', 'url' => '../fixture_library', 'options' => ['symlink' => FALSE]],
      ],
      'extra' => ['installer-paths' => $installer_paths],
      'config' => ['allow-plugins' => ['composer/installers' => TRUE]],
      'minimum-stability' => 'stable',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

    $this->cmd(
      'composer --working-dir=' . escapeshellarg($project_dir) . ' update --no-interaction --no-progress',
      txt: 'Install a "drupal-library" package using composer/installers',
    );

    $this->logSubstep('Assert composer/installers placed the package into web/libraries');
    $installed_dir = $project_dir . '/web/libraries/' . $library_name;
    $this->assertDirectoryExists($installed_dir, 'The "drupal-library" package is installed into web/libraries/{$name}.');
    $this->assertFileExists($installed_dir . '/library.js', 'The "drupal-library" payload exists at the expected path.');
    $this->assertDirectoryDoesNotExist($project_dir . '/vendor/vortex-test/' . $library_name, 'The "drupal-library" package is not installed into vendor/.');

    $this->logStepFinish();
  }

}
