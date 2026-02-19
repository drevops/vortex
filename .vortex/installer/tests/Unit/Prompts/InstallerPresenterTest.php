<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Prompts;

use DrevOps\VortexInstaller\Downloader\Artifact;
use DrevOps\VortexInstaller\Downloader\RepositoryDownloader;
use DrevOps\VortexInstaller\Prompts\Handlers\Starter;
use DrevOps\VortexInstaller\Prompts\InstallerPresenter;
use DrevOps\VortexInstaller\Prompts\PromptManager;
use DrevOps\VortexInstaller\Tests\Unit\UnitTestCase;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\Tui;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Tests for the InstallerPresenter class.
 */
#[CoversClass(InstallerPresenter::class)]
class InstallerPresenterTest extends UnitTestCase {

  protected BufferedOutput $output;

  protected function setUp(): void {
    parent::setUp();

    $this->output = new BufferedOutput();
    Tui::init($this->output, FALSE);
  }

  public function testConstructor(): void {
    $config = new Config('/tmp/root', '/tmp/dst', '/tmp/tmp');
    $presenter = new InstallerPresenter($config);

    $this->assertInstanceOf(InstallerPresenter::class, $presenter);
  }

  public function testSetPromptManager(): void {
    $config = new Config('/tmp/root', '/tmp/dst', '/tmp/tmp');
    $presenter = new InstallerPresenter($config);

    $mock_pm = $this->createMock(PromptManager::class);
    $presenter->setPromptManager($mock_pm);

    // No exception means success - prompt manager was accepted.
    $this->addToAssertionCount(1);
  }

  #[DataProvider('dataProviderHeaderWithStableArtifact')]
  public function testHeaderWithStableArtifact(bool $is_vortex_project, bool $no_interaction): void {
    $config = new Config('/tmp/root', '/tmp/dst', '/tmp/tmp');
    $config->set(Config::IS_VORTEX_PROJECT, $is_vortex_project);
    $config->setNoInteraction($no_interaction);
    $presenter = new InstallerPresenter($config);

    $artifact = Artifact::fromUri(NULL);
    $presenter->header($artifact, '1.0.0');

    $output = $this->output->fetch();
    $this->assertStringContainsString('Vortex', $output);
    $this->assertStringContainsString('1.0.0', $output);
    $this->assertStringContainsString('stable', $output);
  }

  public static function dataProviderHeaderWithStableArtifact(): array {
    return [
      'new project, interactive' => [FALSE, FALSE],
      'new project, non-interactive' => [FALSE, TRUE],
      'existing project, interactive' => [TRUE, FALSE],
      'existing project, non-interactive' => [TRUE, TRUE],
    ];
  }

  public function testHeaderWithDevelopmentArtifact(): void {
    $config = new Config('/tmp/root', '/tmp/dst', '/tmp/tmp');
    $presenter = new InstallerPresenter($config);

    $artifact = Artifact::fromUri(RepositoryDownloader::DEFAULT_REPO . '#' . RepositoryDownloader::REF_HEAD);
    $presenter->header($artifact, '1.0.0');

    $output = $this->output->fetch();
    $this->assertStringContainsString('development', $output);
  }

  public function testHeaderWithCustomArtifact(): void {
    $config = new Config('/tmp/root', '/tmp/dst', '/tmp/tmp');
    $presenter = new InstallerPresenter($config);

    $artifact = Artifact::fromUri('https://github.com/drevops/vortex.git#abc123');
    $presenter->header($artifact, '1.0.0');

    $output = $this->output->fetch();
    $this->assertStringContainsString('custom', $output);
    $this->assertStringContainsString('abc123', $output);
  }

  public function testHeaderVersionPlaceholderReplacement(): void {
    $config = new Config('/tmp/root', '/tmp/dst', '/tmp/tmp');
    $presenter = new InstallerPresenter($config);

    $artifact = Artifact::fromUri(NULL);
    $presenter->header($artifact, '@vortex-installer-version@');

    $output = $this->output->fetch();
    $this->assertStringContainsString('development', $output);
    $this->assertStringNotContainsString('@vortex-installer-version@', $output);
  }

  public function testHeaderInteractiveShowsControls(): void {
    $config = new Config('/tmp/root', '/tmp/dst', '/tmp/tmp');
    $config->setNoInteraction(FALSE);
    $presenter = new InstallerPresenter($config);

    $artifact = Artifact::fromUri(NULL);
    $presenter->header($artifact, '1.0.0');

    $output = $this->output->fetch();
    $this->assertStringContainsString('Ctrl+C', $output);
    $this->assertStringContainsString('Ctrl+U', $output);
  }

  public function testHeaderNonInteractiveHidesControls(): void {
    $config = new Config('/tmp/root', '/tmp/dst', '/tmp/tmp');
    $config->setNoInteraction(TRUE);
    $presenter = new InstallerPresenter($config);

    $artifact = Artifact::fromUri(NULL);
    $presenter->header($artifact, '1.0.0');

    $output = $this->output->fetch();
    $this->assertStringNotContainsString('Ctrl+C', $output);
    $this->assertStringNotContainsString('Ctrl+U', $output);
    $this->assertStringContainsString('non-interactive', $output);
  }

  public function testHeaderExistingVortexProject(): void {
    $config = new Config('/tmp/root', '/tmp/dst', '/tmp/tmp');
    $config->set(Config::IS_VORTEX_PROJECT, TRUE);
    $presenter = new InstallerPresenter($config);

    $artifact = Artifact::fromUri(NULL);
    $presenter->header($artifact, '1.0.0');

    $output = $this->output->fetch();
    $this->assertStringContainsString('already installed', $output);
  }

  public function testFooterNewProject(): void {
    $config = new Config('/tmp/root', '/tmp/dst', '/tmp/tmp');
    $config->set(Config::IS_VORTEX_PROJECT, FALSE);
    $presenter = new InstallerPresenter($config);

    $presenter->footer();

    $output = $this->output->fetch();
    $this->assertStringContainsString('Finished installing Vortex', $output);
    $this->assertStringContainsString('git add -A', $output);
    $this->assertStringContainsString('git commit', $output);
  }

  public function testFooterExistingProject(): void {
    $config = new Config('/tmp/root', '/tmp/dst', '/tmp/tmp');
    $config->set(Config::IS_VORTEX_PROJECT, TRUE);
    $presenter = new InstallerPresenter($config);

    $presenter->footer();

    $output = $this->output->fetch();
    $this->assertStringContainsString('Finished updating Vortex', $output);
    $this->assertStringContainsString('review the changes', $output);
    $this->assertStringNotContainsString('git add -A', $output);
  }

  public function testFooterBuildSucceeded(): void {
    $config = new Config('/tmp/root', '/tmp/dst', '/tmp/tmp');
    $presenter = new InstallerPresenter($config);

    $mock_pm = $this->createMock(PromptManager::class);
    $mock_pm->method('runPostBuild')
      ->with(InstallerPresenter::BUILD_RESULT_SUCCESS)
      ->willReturn('');
    $presenter->setPromptManager($mock_pm);

    $presenter->footerBuildSucceeded();

    $output = $this->output->fetch();
    $this->assertStringContainsString('Site is ready', $output);
    $this->assertStringContainsString('ahoy info', $output);
    $this->assertStringContainsString('ahoy login', $output);
  }

  public function testFooterBuildSucceededWithHandlerOutput(): void {
    $config = new Config('/tmp/root', '/tmp/dst', '/tmp/tmp');
    $presenter = new InstallerPresenter($config);

    $mock_pm = $this->createMock(PromptManager::class);
    $mock_pm->method('runPostBuild')
      ->with(InstallerPresenter::BUILD_RESULT_SUCCESS)
      ->willReturn('Setup GitHub Actions: ...');
    $presenter->setPromptManager($mock_pm);

    $presenter->footerBuildSucceeded();

    $output = $this->output->fetch();
    $this->assertStringContainsString('Setup GitHub Actions', $output);
  }

  #[DataProvider('dataProviderFooterBuildSkipped')]
  public function testFooterBuildSkipped(string $starter, bool $expect_profile_command, bool $expect_export_db): void {
    $config = new Config('/tmp/root', '/tmp/dst', '/tmp/tmp');
    $presenter = new InstallerPresenter($config);

    $mock_pm = $this->createMock(PromptManager::class);
    $mock_pm->method('getResponses')->willReturn([Starter::id() => $starter]);
    $mock_pm->method('runPostBuild')
      ->with(InstallerPresenter::BUILD_RESULT_SKIPPED)
      ->willReturn('');
    $presenter->setPromptManager($mock_pm);

    $presenter->footerBuildSkipped();

    $output = $this->output->fetch();
    $this->assertStringContainsString('Ready to build', $output);
    $this->assertStringContainsString('Build the site', $output);

    if ($expect_profile_command) {
      $this->assertStringContainsString('VORTEX_PROVISION_TYPE=profile ahoy build', $output);
    }
    else {
      $this->assertStringNotContainsString('VORTEX_PROVISION_TYPE=profile', $output);
    }

    if ($expect_export_db) {
      $this->assertStringContainsString('ahoy export-db', $output);
    }
    else {
      $this->assertStringNotContainsString('ahoy export-db', $output);
    }
  }

  public static function dataProviderFooterBuildSkipped(): array {
    return [
      'demo database starter' => [
        Starter::LOAD_DATABASE_DEMO,
        FALSE,
        FALSE,
      ],
      'core profile starter' => [
        Starter::INSTALL_PROFILE_CORE,
        TRUE,
        TRUE,
      ],
      'drupalcms profile starter' => [
        Starter::INSTALL_PROFILE_DRUPALCMS,
        TRUE,
        TRUE,
      ],
    ];
  }

  public function testFooterBuildSkippedDefaultsToDemo(): void {
    $config = new Config('/tmp/root', '/tmp/dst', '/tmp/tmp');
    $presenter = new InstallerPresenter($config);

    $mock_pm = $this->createMock(PromptManager::class);
    $mock_pm->method('getResponses')->willReturn([]);
    $mock_pm->method('runPostBuild')->willReturn('');
    $presenter->setPromptManager($mock_pm);

    $presenter->footerBuildSkipped();

    $output = $this->output->fetch();
    // Default is demo mode - no profile command.
    $this->assertStringNotContainsString('VORTEX_PROVISION_TYPE=profile', $output);
  }

  public function testFooterBuildFailed(): void {
    $config = new Config('/tmp/root', '/tmp/dst', '/tmp/tmp');
    $presenter = new InstallerPresenter($config);

    $mock_pm = $this->createMock(PromptManager::class);
    $mock_pm->method('runPostBuild')
      ->with(InstallerPresenter::BUILD_RESULT_FAILED)
      ->willReturn('');
    $presenter->setPromptManager($mock_pm);

    $presenter->footerBuildFailed();

    $output = $this->output->fetch();
    $this->assertStringContainsString('Build encountered errors', $output);
    $this->assertStringContainsString('build process failed', $output);
    $this->assertStringContainsString('Troubleshooting', $output);
    $this->assertStringContainsString('ahoy logs', $output);
    $this->assertStringContainsString('ahoy build', $output);
    $this->assertStringContainsString('ahoy doctor', $output);
  }

  public function testFooterBuildFailedWithHandlerOutput(): void {
    $config = new Config('/tmp/root', '/tmp/dst', '/tmp/tmp');
    $presenter = new InstallerPresenter($config);

    $mock_pm = $this->createMock(PromptManager::class);
    $mock_pm->method('runPostBuild')
      ->with(InstallerPresenter::BUILD_RESULT_FAILED)
      ->willReturn('Check hosting config');
    $presenter->setPromptManager($mock_pm);

    $presenter->footerBuildFailed();

    $output = $this->output->fetch();
    $this->assertStringContainsString('Check hosting config', $output);
  }

  #[DataProvider('dataProviderBuildResultConstants')]
  public function testBuildResultConstants(string $constant, string $expected): void {
    $this->assertSame($expected, $constant);
  }

  public static function dataProviderBuildResultConstants(): array {
    return [
      'success' => [InstallerPresenter::BUILD_RESULT_SUCCESS, 'success'],
      'skipped' => [InstallerPresenter::BUILD_RESULT_SKIPPED, 'skipped'],
      'failed' => [InstallerPresenter::BUILD_RESULT_FAILED, 'failed'],
    ];
  }

}
