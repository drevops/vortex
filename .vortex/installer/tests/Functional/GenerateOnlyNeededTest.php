<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional;

use DrevOps\VortexInstaller\Command\InstallCommand;
use DrevOps\VortexInstaller\Prompts\Handlers\CiProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\DeployTypes;
use DrevOps\VortexInstaller\Prompts\Handlers\HostingProvider;
use DrevOps\VortexInstaller\Utils\File;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

/**
 * Asserts the "generate only what the project needs" constraint.
 *
 * A generated project must contain only the configuration relevant to the
 * selected options: no variables, steps, or commands of unselected options
 * may remain anywhere in the generated codebase.
 */
#[CoversClass(CiProvider::class)]
#[CoversClass(DeployTypes::class)]
#[CoversClass(HostingProvider::class)]
class GenerateOnlyNeededTest extends FunctionalTestCase {

  protected function setUp(): void {
    parent::setUp();

    static::envUnsetPrefix('VORTEX_');
    static::envUnsetPrefix('DRUPAL_');
    static::envUnsetPrefix('LAGOON_');
    static::envUnset('WEBROOT');
    static::envUnset('TZ');

    static::applicationInitFromCommand(InstallCommand::class);

    static::$sut = File::mkdir(static::$workspace . DIRECTORY_SEPARATOR . 'star_wars');

    chdir(static::$sut);
  }

  #[DataProvider('dataProviderGenerateOnlyNeeded')]
  #[RunInSeparateProcess]
  public function testGenerateOnlyNeeded(array $prompts, array $expected_absent, array $expected_present = []): void {
    $this->runNonInteractiveInstall(options: [InstallCommand::OPTION_PROMPTS => json_encode($prompts)]);

    $this->assertSutNotContains($expected_absent);

    if (!empty($expected_present)) {
      $this->assertSutContains($expected_present);
    }
  }

  public static function dataProviderGenerateOnlyNeeded(): \Iterator {
    yield 'lagoon hosting, lagoon deploy, gha' => [
      [
        HostingProvider::id() => HostingProvider::LAGOON,
        DeployTypes::id() => [DeployTypes::LAGOON],
        CiProvider::id() => CiProvider::GITHUB_ACTIONS,
      ],
      ['/VORTEX_DEPLOY_ARTIFACT_/', 'VORTEX_DEPLOY_WEBHOOK_URL', 'acquia'],
      ['LAGOON_PROJECT'],
    ];

    yield 'acquia hosting, artifact deploy, gha' => [
      [
        HostingProvider::id() => HostingProvider::ACQUIA,
        DeployTypes::id() => [DeployTypes::ARTIFACT],
        CiProvider::id() => CiProvider::GITHUB_ACTIONS,
      ],
      ['VORTEX_DEPLOY_WEBHOOK_URL', 'LAGOON_PROJECT', 'lagoon_logs'],
      ['/VORTEX_DEPLOY_ARTIFACT_/', '/VORTEX_ACQUIA_/'],
    ];

    yield 'no hosting, no deploy, circleci' => [
      [
        HostingProvider::id() => HostingProvider::NONE,
        DeployTypes::id() => [],
        CiProvider::id() => CiProvider::CIRCLECI,
      ],
      ['/VORTEX_DEPLOY_/', 'acquia', 'LAGOON_PROJECT'],
    ];
  }

}
