<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Traits\Steps;

use AlexSkrypnyk\File\File;

/**
 * Provides lint operation steps.
 */
trait StepLintTrait {

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

}
