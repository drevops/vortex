<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Traits\Steps;

use AlexSkrypnyk\File\File;

/**
 * Provides testing operation steps (lint, test).
 */
trait StepTestingTrait {

  protected function stepAhoyLint(string $webroot = 'web'): void {
    $this->logStepStart();

    $this->logSubstep('Assert that lint works');
    $this->cmd('ahoy lint', tio: 120, ito: 90);

    $this->stepAhoyLintBe($webroot);
    $this->stepAhoyLintFe($webroot);
    $this->stepAhoyLintTest();

    $this->logStepFinish();
  }

  protected function stepAhoyLintBe(string $webroot = 'web'): void {
    $this->logStepStart();

    $this->logSubstep('Assert that BE lint failure works');
    File::dump($webroot . '/modules/custom/sw_base/sw_base.module', File::read($webroot . '/modules/custom/sw_base/sw_base.module') . '$a=1;');
    $this->syncToContainer();
    $this->cmdFail('ahoy lint-be', tio: 120, ito: 90);

    $this->logSubstep('Assert that BE lint tool disabling works');
    // Replace with some valid XML element to avoid XML parsing errors.
    File::replaceContentInFile('phpcs.xml', '<file>' . $webroot . '/modules/custom</file>', '<exclude-pattern>somefile</exclude-pattern>');
    $this->syncToContainer();
    $this->cmd('ahoy lint-be', tio: 120, ito: 90);

    // @todo Add restoring of the file.
    $this->logStepFinish();
  }

  protected function stepAhoyLintFe(string $webroot = 'web'): void {
    $this->logStepStart();

    $this->logSubstep('Assert that FE lint failure works for npm lint');
    File::dump($webroot . '/themes/custom/star_wars/scss/components/_test.scss', '.abc{margin: 0px;}');
    $this->syncToContainer();
    $this->cmdFail('ahoy lint-fe', tio: 120, ito: 90);
    File::remove($webroot . '/themes/custom/star_wars/scss/components/_test.scss');
    $this->cmd('ahoy cli rm -f ' . $webroot . '/themes/custom/star_wars/scss/components/_test.scss');
    $this->syncToContainer();

    $this->logSubstep('Assert that FE lint failure works for Twig CS Fixer');
    File::dump($webroot . '/modules/custom/sw_base/templates/block/test1.twig', "{{ set a='a' }}");
    File::dump($webroot . '/themes/custom/star_wars/templates/block/test2.twig', "{{ set b='b' }}");
    $this->syncToContainer();

    $this->cmdFail('ahoy lint-fe', tio: 120, ito: 90);

    File::remove([
      $webroot . '/modules/custom/sw_base/templates/block/test1.twig',
      $webroot . '/themes/custom/star_wars/templates/block/test2.twig',
    ]);
    $this->cmd('ahoy cli rm -f ' . $webroot . '/modules/custom/sw_base/templates/block/test1.twig');
    $this->cmd('ahoy cli rm -f ' . $webroot . '/themes/custom/star_wars/templates/block/test2.twig');
    $this->syncToContainer();

    $this->logStepFinish();
  }

  protected function stepAhoyLintTest(): void {
    $this->logStepStart();

    $this->logSubstep('Assert that Test lint works for Gherkin Lint');
    $this->cmd('ahoy lint-tests');

    $this->logSubstep('Assert that Test lint failure works for Gherkin Lint');
    File::dump('tests/behat/features/test.feature', 'Feature:');
    $this->syncToContainer();
    $this->cmdFail('ahoy lint-tests');
    File::remove('tests/behat/features/test.feature');
    $this->cmd('ahoy cli rm -f tests/behat/features/test.feature');
    $this->syncToContainer();

    $this->logStepFinish();
  }

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

    // Sometimes, tests fail for random reasons. A workaround is to run BDD
    // tests to "cache" the environment and then run the tests again.
    $this->cmd('ahoy test-bdd || true');

    $this->logSubstep('Run all BDD tests');
    $this->cmd('ahoy test-bdd');

    $this->syncToHost();

    $this->assertDirectoryExists('.logs/screenshots');
    File::remove('.logs/screenshots');
    $this->cmd('ahoy cli rm -rf /app/.logs/screenshots/*');

    $this->assertDirectoryExists('.logs/test_results');
    $this->assertFileExists('.logs/test_results/behat/default.xml');

    File::remove('.logs/test_results');
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

  protected function trimFile(string $file): void {
    $content = File::read($file);
    $lines = explode("\n", $content);
    // Remove last line.
    array_pop($lines);
    File::dump($file, implode("\n", $lines));
  }

}
