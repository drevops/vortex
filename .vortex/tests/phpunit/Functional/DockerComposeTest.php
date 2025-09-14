<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Functional;

use AlexSkrypnyk\File\File;

class DockerComposeTest extends FunctionalTestCase {

  public function testDockerCompose(): void {
    $this->logSubstep('Building stack with Docker Compose');
    $this->cmd('docker compose build --no-cache', txt: 'Build stack images', tio: 15 * 60);
    $this->cmd('docker compose up -d --force-recreate', txt: 'Start stack', tio: 15 * 60);
    $this->syncToHost();

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

    $this->substepDownloadDb(TRUE);

    $this->logSubstep('Provisioning with direct script execution');
    $this->cmd('docker compose exec -T cli ./scripts/vortex/provision.sh', txt: 'Run ./scripts/vortex/provision.sh in container', tio: 10 * 60);

    $this->logSubstep('Run tests');
    $this->cmd('docker compose exec -T cli vendor/bin/phpunit', txt: 'Run PHPUnit tests');
    $this->cmd('docker compose exec -T cli vendor/bin/behat', txt: 'Run Behat tests');
  }

  /**
   * Test Package token handling during build.
   *
   * Make sure to run with TEST_PACKAGE_TOKEN=working_test_token or this test
   * will fail.
   */
  public function testPackageToken(): void {
    $package_token = getenv('TEST_PACKAGE_TOKEN');
    $this->assertNotEmpty($package_token, 'TEST_PACKAGE_TOKEN environment variable must be set');

    $this->logSubstep('Adding private package to test GitHub token');
    File::remove('composer.lock');
    $this->cmd('composer config repositories.test-private-package vcs git@github.com:drevops/test-private-package.git');
    $this->cmd('composer require --no-update drevops/test-private-package:^1');

    $this->logSubstep('Building without PACKAGE_TOKEN - should fail');
    $this->cmdFail('docker compose build cil --no-cache', txt: 'Build stack images without token', tio: 15 * 60);

    $this->logSubstep('Building with PACKAGE_TOKEN - should succeed');
    $this->cmd('docker compose build cli --no-cache', txt: 'Build stack images with token', env: ['PACKAGE_TOKEN' => $package_token], tio: 15 * 60);
  }

}
