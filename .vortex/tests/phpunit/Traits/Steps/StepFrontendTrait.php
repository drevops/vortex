<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Traits\Steps;

use AlexSkrypnyk\File\File;
use DrevOps\Vortex\Tests\Traits\LoggerTrait;

/**
 * Provides frontend development testing steps.
 */
trait StepFrontendTrait {

  use LoggerTrait;

  protected function stepAhoyFei(string $webroot = 'web'): void {
    $this->logStepStart();

    $this->logSubstep('Remove existing Node modules');
    File::remove($webroot . '/themes/custom/star_wars/node_modules');
    $this->assertDirectoryDoesNotExist($webroot . '/themes/custom/star_wars/node_modules');
    $this->syncToContainer();

    $this->logSubstep('Install Node modules');
    $this->processRun('ahoy fei');
    $this->assertProcessSuccessful();
    $this->syncToHost();
    $this->assertDirectoryExists($webroot . '/themes/custom/star_wars/node_modules');

    $this->logStepFinish();
  }

  protected function stepAhoyFe(string $webroot = 'web'): void {
    $this->logStepStart();

    $this->logSubstep('Build FE assets for production');

    $test_color1 = '#7e57e2';
    $test_color2 = '#91ea5e';
    $variables_file = $webroot . '/themes/custom/star_wars/scss/_variables.scss';
    $minified_file = $webroot . '/themes/custom/star_wars/build/css/star_wars.min.css';

    $this->assertFileNotContainsString($test_color1, $webroot . '/themes/custom/star_wars/build/css/star_wars.min.css');

    $original_content = File::read($variables_file);
    $new_content = $original_content . "\n\$color-tester: {$test_color1};\n\$color-primary: \$color-tester;\n";
    File::remove($variables_file);
    File::dump($variables_file, $new_content);

    $this->syncToContainer();
    $this->processRun('ahoy fe');
    $this->syncToHost();
    // Assets compiled for production are minified (no spaces between
    // properties and their values).
    $this->assertFileContainsString("background:{$test_color1}", $minified_file);

    $this->logSubstep('Build FE assets for development');

    $this->assertFileNotContainsString($test_color2, $minified_file);

    $dev_content = $new_content . "\n\$color-please: {$test_color2};\n\$color-primary: \$color-please;\n";
    File::remove($variables_file);
    File::dump($variables_file, $dev_content);

    $this->syncToContainer();
    $this->processRun('ahoy fed');
    $this->syncToHost();
    // Note that assets compiled for development are not minified (contains spaces
    // between properties and their values).
    $this->assertFileContainsString("background: {$test_color2}", $minified_file);

    $this->logStepFinish();
  }

}
