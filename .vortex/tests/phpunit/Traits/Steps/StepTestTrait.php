<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Traits\Steps;

use AlexSkrypnyk\File\File;

/**
 * Provides testing operation steps (lint, test).
 */
trait StepTestTrait {

  protected function stepAhoyTest(string $webroot = 'web', bool $is_fast = FALSE): void {
    $this->logStepStart();

    $this->stepAhoyTestUnit($webroot);
    $this->stepAhoyTestKernel($webroot);
    $this->stepAhoyTestFunctional($webroot);

    if ($is_fast) {
      $this->stepAhoyTestBddFast($webroot);
    }
    else {
      $this->stepAhoyTestBdd($webroot);
    }

    $this->logStepFinish();
  }

  protected function stepAhoyTestUnit(string $webroot = 'web'): void {
    $this->logStepStart();

    $this->logSubstep('Run all Unit tests');
    $this->cmd('ahoy test-unit --no-coverage', 'OK (');
    $this->syncToHost();
    $this->assertFileExists('.logs/test_results/phpunit/phpunit.xml');

    $this->logSubstep('Assert that Drupal Unit test failure works');
    // Prepare failing test.
    $unit_test_file = $webroot . '/modules/custom/sw_base/tests/src/Unit/ExampleTest.php';
    $content = File::read($unit_test_file);
    $content = str_replace('assertEquals', 'assertNotEquals', $content);
    File::dump($unit_test_file, $content);

    File::remove('.logs/test_results');
    $this->cmd('ahoy cli rm -rf /app/.logs/test_results/*');
    $this->syncToContainer();

    $this->cmdFail('ahoy test-unit');
    $this->syncToHost();
    $this->assertFileExists('.logs/test_results/phpunit/phpunit.xml');

    $this->logStepFinish();
  }

  protected function stepAhoyTestKernel(string $webroot = 'web'): void {
    $this->logStepStart();

    $this->logSubstep('Run all Kernel tests');
    $this->cmd('ahoy test-kernel --no-coverage', 'OK (', tio: 120, ito: 90);
    $this->syncToHost();
    $this->assertFileExists('.logs/test_results/phpunit/phpunit.xml');

    $this->logSubstep('Assert that Kernel test failure works');
    // Prepare failing test.
    $kernel_test_file = $webroot . '/modules/custom/sw_base/tests/src/Kernel/ExampleTest.php';
    $content = File::read($kernel_test_file);
    $content = str_replace('assertEquals', 'assertNotEquals', $content);
    File::dump($kernel_test_file, $content);

    File::remove('.logs/test_results');
    $this->cmd('ahoy cli rm -rf /app/.logs/test_results/*');
    $this->syncToContainer();

    $this->cmdFail('ahoy test-kernel', tio: 120, ito: 90);
    $this->syncToHost();
    $this->assertFileExists('.logs/test_results/phpunit/phpunit.xml');

    $this->logStepFinish();
  }

  protected function stepAhoyTestFunctional(string $webroot = 'web'): void {
    $this->logStepStart();

    $this->logSubstep('Run all Functional tests');
    $this->cmd('ahoy test-functional --no-coverage', 'OK (', tio: 120, ito: 90);
    $this->syncToHost();
    $this->assertFileExists('.logs/test_results/phpunit/phpunit.xml');

    $this->logSubstep('Assert that Functional test failure works');
    // Prepare failing test.
    $functional_test_file = $webroot . '/modules/custom/sw_base/tests/src/Functional/ExampleTest.php';
    $content = File::read($functional_test_file);
    $content = str_replace('assertEquals', 'assertNotEquals', $content);
    File::dump($functional_test_file, $content);

    File::remove('.logs/test_results');
    $this->cmd('ahoy cli rm -rf /app/.logs/test_results/*');
    $this->syncToContainer();

    $this->cmdFail('ahoy test-functional');
    $this->syncToHost();
    $this->assertFileExists('.logs/test_results/phpunit/phpunit.xml');

    $this->logStepFinish();
  }

  protected function stepAhoyTestBddFast(string $webroot = 'web'): void {
    $this->logStepStart();

    $this->stepWarmCaches();

    $this->logSubstep('Run all BDD tests');
    $process = $this->processRun('ahoy test-bdd');

    if (!$process->isSuccessful()) {
      $this->logSubstep('Re-run all BDD tests after random failure');
      $this->cmd('ahoy test-bdd');
    }

    $this->syncToHost();

    $this->logSubstep('Check that BDD tests have created screenshots and test results');
    $this->assertDirectoryContainsString('.logs/screenshots', 'html', message: 'Screenshots directory should not be empty after BDD tests');
    $this->assertFileExists('.logs/test_results/behat/default.xml', 'Behat test results XML file should exist');

    $this->logSubstep('Clean up after the test');
    File::remove(['.logs/screenshots', '.logs/test_results/behat']);
    $this->cmd('ahoy cli rm -rf /app/.logs/screenshots/*');
    $this->cmd('ahoy cli rm -rf /app/.logs/test_results/*');

    $this->logStepFinish();
  }

  protected function stepAhoyTestBdd(string $webroot = 'web'): void {
    $this->logStepStart();

    $this->logSubstep('Run all BDD tests');

    // Sometimes, tests fail for random reasons. A workaround is to run BDD
    // tests to "cache" the environment and then run the tests again.
    $this->cmd('ahoy test-bdd || true');

    $this->cmd('ahoy test-bdd', tio: 120, ito: 90);
    $this->syncToHost();
    $this->assertDirectoryExists('.logs/screenshots');

    $this->logSubstep('Assert that screenshots and test results are created');
    $this->assertFileExists('.logs/screenshots/behat-test-screenshot.html');
    $this->assertFileContainsString('.logs/screenshots/behat-test-screenshot.html', 'Current URL: http://nginx:8080/');
    $this->assertFileContainsString('.logs/screenshots/behat-test-screenshot.html', 'Feature: Behat configuration');
    $this->assertFileContainsString('.logs/screenshots/behat-test-screenshot.html', 'Step: save screenshot with name');
    $this->assertFileContainsString('.logs/screenshots/behat-test-screenshot.html', 'Datetime:');

    File::remove('.logs/screenshots');
    $this->cmd('ahoy cli rm -rf /app/.logs/screenshots/*');
    $this->assertDirectoryExists('.logs/test_results');
    $this->assertFileExists('.logs/test_results/behat/default.xml');
    File::remove('.logs/test_results');
    $this->cmd('ahoy cli rm -rf /app/.logs/test_results/*');

    $this->logSubstep('Run tagged BDD tests');

    $this->cmd('ahoy test-bdd -- --tags=smoke');
    $this->syncToHost();
    $this->assertDirectoryExists('.logs/test_results');
    $this->assertFileExists('.logs/test_results/behat/default.xml');
    File::remove('.logs/test_results');
    $this->cmd('ahoy cli rm -rf /app/test_results/*');
    $this->assertDirectoryExists('.logs/screenshots');
    $this->assertFilesWildcardExists('.logs/screenshots/*html');
    $this->assertFilesWildcardExists('.logs/screenshots/*png');
    File::remove('.logs/screenshots');
    $this->cmd('ahoy cli rm -rf /app/.logs/screenshots/*');

    $this->logSubstep('Assert that Behat tests failure works');
    File::dump('tests/behat/features/homepage.feature', File::read('tests/behat/features/homepage.feature') . "\nAnd the path should be \"some-non-existing-page\"");
    $this->syncToContainer();

    $this->cmdFail('ahoy test-bdd');
    $this->syncToHost();
    $this->assertDirectoryExists('.logs/test_results');
    $this->assertFileExists('.logs/test_results/behat/default.xml');
    File::remove('.logs/test_results');
    $this->cmd('ahoy cli rm -rf /app/test_results/*');
    $this->assertDirectoryExists('.logs/screenshots');
    File::remove('.logs/screenshots');
    $this->cmd('ahoy cli rm -rf /app/.logs/screenshots/*');

    // Remove failing step from the feature.
    $this->trimFile('tests/behat/features/homepage.feature');
    $this->syncToContainer();
    $this->restoreFile('.env');
    $this->cmd('ahoy up cli');
    $this->syncToContainer();

    $this->logStepFinish();
  }

}
