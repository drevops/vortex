<?php

namespace Drevops\Installer\Tests\Functional;

use DrevOps\Installer\InstallerApp;
use Symfony\Component\Console\Tester\ApplicationTester;

abstract class ApplicationTestCase extends FunctionalTestCase {

  /**
   * @var \Symfony\Component\Console\Tester\ApplicationTester
   */
  protected $tester;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $application = new InstallerApp();
    $application->setAutoExit(FALSE);

    $this->tester = new ApplicationTester($application);
  }

  /**
   * Run the application.
   *
   * @param array $input
   *   The input.
   * @param array $options
   *   The options.
   *
   * @return string
   *   The output.
   */
  protected function execute(array $input, $options = []): string {
    $this->tester->run($input, $options + [
      'interactive' => FALSE,
    ]);

    return $this->tester->getDisplay();
  }

}
