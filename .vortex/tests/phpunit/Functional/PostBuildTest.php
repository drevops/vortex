<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Functional;

use DrevOps\Vortex\Tests\Traits\CircleCiTrait;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests CircleCI post-build artifacts and test results.
 *
 * These tests verify that the build job properly generates and stores
 * artifacts and test results in CircleCI.
 */
class PostBuildTest extends FunctionalTestCase {

  use CircleCiTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    if (empty(getenv('CIRCLECI'))) {
      $this->markTestSkipped('This test is only run on CircleCI');
    }

    // Verify required environment variables are set.
    $this->assertNotEmpty(getenv('TEST_CIRCLECI_TOKEN'), 'CircleCI token is not set');
    $this->assertNotEmpty(getenv('CIRCLE_PROJECT_REPONAME'), 'CircleCI project repo name is not set');
    $this->assertNotEmpty(getenv('CIRCLE_PROJECT_USERNAME'), 'CircleCI project username is not set');
    $this->assertNotEmpty(getenv('CIRCLE_BUILD_NUM'), 'CircleCI build number is not set');

    // Skip the parent setUp as we don't need to prepare SUT for these tests.
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    // Skip parent tearDown as we didn't set up SUT.
  }

  /**
   * Test that CircleCI artifacts are saved correctly.
   *
   * Verifies that:
   * - PHPUnit coverage reports are generated for both parallel runners
   * - Behat feature files are saved for both parallel runners
   * - Feature files are split correctly between runners (e.g., clamav.feature
   *   on runner 0 but not runner 1, search.feature on runner 1 but not
   *   runner 0)
   */
  #[Group('postbuild')]
  public function testCircleCiArtifactsAreSaved(): void {
    $current_job_number = (int) getenv('CIRCLE_BUILD_NUM');
    $previous_job_numbers = $this->circleCiGetPreviousJobNumbers($current_job_number);

    $this->assertNotEmpty($previous_job_numbers, 'No previous job numbers found');

    foreach ($previous_job_numbers as $previou_job_number) {
      $artifacts_data = $this->circleCiGetJobArtifacts($previou_job_number);

      // Verify runner 0 artifacts.
      $artifact_paths_runner0 = $this->circleCiExtractArtifactPaths($artifacts_data, 0);
      $artifact_paths_runner0_str = implode("\n", $artifact_paths_runner0);

      $this->assertStringContainsString('coverage/phpunit/cobertura.xml', $artifact_paths_runner0_str, 'Runner 0 should have PHPUnit cobertura coverage');
      $this->assertStringContainsString('coverage/phpunit/.coverage-html/index.html', $artifact_paths_runner0_str, 'Runner 0 should have PHPUnit HTML coverage');

      $this->assertStringContainsString('homepage.feature', $artifact_paths_runner0_str, 'Runner 0 should have homepage.feature');
      $this->assertStringContainsString('login.feature', $artifact_paths_runner0_str, 'Runner 0 should have login.feature');
      $this->assertStringContainsString('clamav.feature', $artifact_paths_runner0_str, 'Runner 0 should have clamav.feature');
      $this->assertStringNotContainsString('search.feature', $artifact_paths_runner0_str, 'Runner 0 should NOT have search.feature');

      // Verify runner 1 artifacts.
      $artifact_paths_runner1 = $this->circleCiExtractArtifactPaths($artifacts_data, 1);
      $artifact_paths_runner1_str = implode("\n", $artifact_paths_runner1);

      $this->assertStringContainsString('coverage/phpunit/cobertura.xml', $artifact_paths_runner1_str, 'Runner 1 should have PHPUnit cobertura coverage');
      $this->assertStringContainsString('coverage/phpunit/.coverage-html/index.html', $artifact_paths_runner1_str, 'Runner 1 should have PHPUnit HTML coverage');

      $this->assertStringContainsString('homepage.feature', $artifact_paths_runner1_str, 'Runner 1 should have homepage.feature');
      $this->assertStringContainsString('login.feature', $artifact_paths_runner1_str, 'Runner 1 should have login.feature');
      $this->assertStringNotContainsString('clamav.feature', $artifact_paths_runner1_str, 'Runner 1 should NOT have clamav.feature');
      $this->assertStringContainsString('search.feature', $artifact_paths_runner1_str, 'Runner 1 should have search.feature');
    }
  }

  /**
   * Test that CircleCI test results are saved correctly.
   *
   * Verifies that:
   * - PHPUnit test results from various test suites are recorded
   * - Behat feature test results are recorded.
   */
  #[Group('postbuild')]
  public function testCircleCiTestResultsAreSaved(): void {
    $current_job_number = (int) getenv('CIRCLE_BUILD_NUM');
    $previous_job_numbers = $this->circleCiGetPreviousJobNumbers($current_job_number);

    $this->assertNotEmpty($previous_job_numbers, 'No previous job numbers found');

    foreach ($previous_job_numbers as $previou_job_number) {
      $tests_data = $this->circleCiGetJobTestMetadata($previou_job_number);
      $test_paths = $this->circleCiExtractTestPaths($tests_data);
      $test_paths_str = implode("\n", $test_paths);

      // Verify PHPUnit test results.
      $this->assertStringContainsString('tests/phpunit/CircleCiConfigTest.php', $test_paths_str, 'Should have CircleCiConfigTest results');
      $this->assertStringContainsString('tests/phpunit/Drupal/DatabaseSettingsTest.php', $test_paths_str, 'Should have DatabaseSettingsTest results');
      $this->assertStringContainsString('tests/phpunit/Drupal/EnvironmentSettingsTest.php', $test_paths_str, 'Should have EnvironmentSettingsTest results');
      $this->assertStringContainsString('tests/phpunit/Drupal/SwitchableSettingsTest.php', $test_paths_str, 'Should have SwitchableSettingsTest results');
      $this->assertStringContainsString('web/modules/custom/ys_base/tests/src/Functional/ExampleTest.php', $test_paths_str, 'Should have custom module Functional test results');
      $this->assertStringContainsString('web/modules/custom/ys_base/tests/src/Kernel/ExampleTest.php', $test_paths_str, 'Should have custom module Kernel test results');
      $this->assertStringContainsString('web/modules/custom/ys_base/tests/src/Unit/ExampleTest.php', $test_paths_str, 'Should have custom module Unit test results');

      // Verify Behat test results.
      $this->assertStringContainsString('homepage.feature', $test_paths_str, 'Should have homepage.feature results');
      $this->assertStringContainsString('login.feature', $test_paths_str, 'Should have login.feature results');
      $this->assertStringContainsString('clamav.feature', $test_paths_str, 'Should have clamav.feature results');
      $this->assertStringContainsString('search.feature', $test_paths_str, 'Should have search.feature results');
    }
  }

}
