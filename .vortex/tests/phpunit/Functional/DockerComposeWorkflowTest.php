<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Functional;

use AlexSkrypnyk\File\File;
use DrevOps\Vortex\Tests\Traits\Subtests\SubtestDockerComposeTrait;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Docker Compose workflows.
 */
class DockerComposeWorkflowTest extends FunctionalTestCase {

  use SubtestDockerComposeTrait;

  protected function setUp(): void {
    parent::setUp();

    static::$sutInstallerEnv = [];

    // Docker Compose tests replicate read-only environments.
    $this->forceVolumesUnmounted();

    $this->dockerCleanup();
  }

  #[Group('p0')]
  public function testDockerComposeWorkflowFull(): void {
    $this->prepareSut();

    $this->logSubstep('Building stack with Docker Compose');
    $this->subtestDockerComposeBuild();

    $this->subtestDockerComposeDotEnv();

    $this->subtestDockerComposeTimezone();

    $this->subtestSolr();

    $this->logSubstep('Installing development dependencies');
    $this->cmd('docker compose exec -T cli composer install --prefer-dist', txt: 'Install development dependencies with Composer', tio: 10 * 60);
    $this->cmd('docker compose exec -T cli bash -cl "yarn install --frozen-lockfile"', txt: 'Install development dependencies with Yarn', tio: 10 * 60);

    $this->logSubstep('Linting backend code');
    $this->cmd('docker compose exec -T cli vendor/bin/phpcs', txt: 'Lint code with PHPCS', tio: 10 * 60);
    $this->cmd('docker compose exec -T cli vendor/bin/phpstan', txt: 'Lint code with PHPStan', tio: 10 * 60);
    $this->cmd('docker compose exec -T cli vendor/bin/rector', txt: 'Lint code with Rector', tio: 10 * 60);
    $this->cmd('docker compose exec -T cli vendor/bin/phpmd . text phpmd.xml', txt: 'Lint code with PHPMD', tio: 10 * 60);

    $this->logSubstep('Linting front-end code');
    $this->cmd('docker compose exec -T cli vendor/bin/twig-cs-fixer lint', txt: 'Lint code with TwigCS', tio: 10 * 60);
    $this->cmd('docker compose exec -T cli yarn run lint', txt: 'Lint code with module linters', tio: 10 * 60);
    $this->cmd('docker compose exec -T cli bash -cl "yarn run --cwd=\${WEBROOT}/themes/custom/\${DRUPAL_THEME} lint"', txt: 'Lint code with theme linters', tio: 10 * 60);

    $this->downloadDatabase(TRUE);

    $this->logSubstep('Provisioning with direct script execution');
    $this->cmd('docker compose exec -T cli ./scripts/vortex/provision.sh', txt: 'Run ./scripts/vortex/provision.sh in container', tio: 10 * 60);

    $this->logSubstep('Run tests');
    $this->cmd('docker compose exec -T cli vendor/bin/phpunit', txt: 'Run PHPUnit tests');
    $this->cmd('docker compose exec -T cli vendor/bin/behat', txt: 'Run Behat tests');
  }

  #[Group('p0')]
  public function testDockerComposeWorkflowNoTheme(): void {
    static::$sutInstallerEnv = ['VORTEX_INSTALLER_PROMPT_THEME' => 'olivero'];
    $this->prepareSut();

    $this->logSubstep('Building stack with Docker Compose');
    $this->subtestDockerComposeBuild(build_theme: FALSE);

    $this->logSubstep('Installing development dependencies');
    $this->cmd('docker compose exec -T cli composer install --prefer-dist', txt: 'Install development dependencies with Composer', tio: 10 * 60);
    $this->cmd('docker compose exec -T cli bash -cl "yarn install --frozen-lockfile"', txt: 'Install development dependencies with Yarn', tio: 10 * 60);

    $this->logSubstep('Linting backend code');
    $this->cmd('docker compose exec -T cli vendor/bin/phpcs', txt: 'Lint code with PHPCS', tio: 10 * 60);
    $this->cmd('docker compose exec -T cli vendor/bin/phpstan', txt: 'Lint code with PHPStan', tio: 10 * 60);
    $this->cmd('docker compose exec -T cli vendor/bin/rector', txt: 'Lint code with Rector', tio: 10 * 60);
    $this->cmd('docker compose exec -T cli vendor/bin/phpmd . text phpmd.xml', txt: 'Lint code with PHPMD', tio: 10 * 60);

    $this->logSubstep('Linting front-end code');
    $this->cmd('docker compose exec -T cli vendor/bin/twig-cs-fixer lint', txt: 'Lint code with TwigCS', tio: 10 * 60);
    $this->cmd('docker compose exec -T cli yarn run lint', txt: 'Lint code with module linters', tio: 10 * 60);

    $this->downloadDatabase(TRUE);

    $this->logSubstep('Provisioning with direct script execution');
    $this->cmd('docker compose exec -T cli ./scripts/vortex/provision.sh', txt: 'Run ./scripts/vortex/provision.sh in container', tio: 10 * 60);

    $this->logSubstep('Run tests');
    $this->cmd('docker compose exec -T cli vendor/bin/phpunit', txt: 'Run PHPUnit tests');
    $this->cmd('docker compose exec -T cli vendor/bin/behat', txt: 'Run Behat tests');
  }

  #[Group('p0')]
  public function testDockerComposeWorkflowNoFe(): void {
    $this->prepareSut();

    $this->logSubstep('Building stack with Docker Compose');
    $this->subtestDockerComposeBuild(env: ['VORTEX_FRONTEND_BUILD_SKIP' => '1'], build_theme: FALSE);
  }

  /**
   * Test Package token handling during build.
   *
   * Make sure to run with
   * export TEST_PACKAGE_TOKEN=real_github_token_with_access_to_private_package
   * or this test will fail.
   */
  #[Group('p0')]
  public function testDockerComposePackageToken(): void {
    $this->prepareSut();

    $package_token = getenv('TEST_PACKAGE_TOKEN');
    $this->assertNotEmpty($package_token, 'TEST_PACKAGE_TOKEN environment variable must be set');

    $this->logSubstep('Adding private package to composer.json');
    File::remove('composer.lock');
    $this->cmd('composer config repositories.test-private-package vcs git@github.com:drevops/test-private-package.git');
    $this->cmd('composer require --no-update drevops/test-private-package:^1');

    $this->logSubstep('Building without PACKAGE_TOKEN - should fail');
    $this->cmdFail('docker compose build cli --no-cache', txt: 'Build stack images without token should fail', tio: 15 * 60);

    $this->logSubstep('Building with PACKAGE_TOKEN - should succeed');
    $this->cmd('docker compose build cli --no-cache', txt: 'Build stack images with token should succeed', env: ['PACKAGE_TOKEN' => $package_token], tio: 15 * 60);
  }

}
