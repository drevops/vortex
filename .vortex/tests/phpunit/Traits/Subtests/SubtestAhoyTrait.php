<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Traits\Subtests;

use AlexSkrypnyk\File\File;

/**
 * Provides Ahoy command subtests.
 */
trait SubtestAhoyTrait {

  protected function subtestAhoyBuild(string $webroot = 'web', array $env = [], bool $build_theme = TRUE): void {
    $this->logStepStart();

    $this->logSubstep('Assert lock files presence/absence before build');
    $this->assertFileDoesNotExist('composer.lock', 'Composer lock file should not exist before build');
    $this->assertFileExists('yarn.lock', 'Yarn lock file should exist before build');

    if ($build_theme) {
      $this->assertThemeFilesPresent();
    }
    else {
      $this->assertThemeFilesAbsent();
    }

    $db_file_present = file_exists('.data/db.sql');
    $this->logNote('Database file exists before build: ' . ($db_file_present ? 'Yes' : 'No'));

    $this->logSubstep('Starting Ahoy build');
    $this->dockerCleanup();
    $this->cmd(
      'ahoy build',
      inp: ['y'],
      env: array_merge([
        // Credentials for the test container registry to allow fetching public
        // images to overcome the throttle limit of Docker Hub, and also used
        // for pushing images during the build.
        'VORTEX_CONTAINER_REGISTRY_USER' => getenv('TEST_VORTEX_CONTAINER_REGISTRY_USER') ?: '',
        'VORTEX_CONTAINER_REGISTRY_PASS' => getenv('TEST_VORTEX_CONTAINER_REGISTRY_PASS') ?: '',
      ], $env),
      txt: '`ahoy build` should build stack images and stack should start successfully',
    );
    $this->syncToHost();

    $this->logSubstep('Assert lock files presence/absence after build');
    $this->assertFileExists('composer.lock', 'Composer lock file should exist after build');
    $this->assertFileExists('yarn.lock', 'Yarn lock file should exist after build');
    if ($build_theme) {
      $this->assertThemeFilesPresent();
    }
    else {
      $this->assertThemeFilesAbsent();
    }

    $this->logSubstep('Assert common files are present after build');
    $this->assertCommonFilesPresent($webroot);

    if ($build_theme) {
      $this->logSubstep('Assert only minified compiled CSS exists');
      $this->assertFileExists($webroot . '/themes/custom/star_wars/build/css/star_wars.min.css', 'Minified CSS file should exist');
      $this->assertFileNotContainsString($webroot . '/themes/custom/star_wars/build/css/star_wars.min.css', 'background: #7e57e2', 'CSS should not contain development colors');
      $this->assertFileDoesNotExist($webroot . '/themes/custom/star_wars/build/css/star_wars.css', 'Non-minified CSS should not exist');

      $this->logSubstep('Assert only minified compiled JS exists');
      $this->assertFileExists($webroot . '/themes/custom/star_wars/build/js/star_wars.min.js', 'Minified JS file should exist');
      $this->assertFileContainsString($webroot . '/themes/custom/star_wars/build/js/star_wars.min.js', '!function(Drupal){"use strict";Drupal.behaviors.star_wars', 'JS should contain expected minified content');
      $this->assertFileDoesNotExist($webroot . '/themes/custom/star_wars/build/js/star_wars.js', 'Non-minified JS should not exist');
    }
    else {
      $this->logSubstep('Assert no compiled CSS or JS exists when theme build is skipped');
      $this->assertFileDoesNotExist($webroot . '/themes/custom/star_wars/build/css/star_wars.min.css', 'Minified CSS file should not exist when theme build is skipped');
      $this->assertFileDoesNotExist($webroot . '/themes/custom/star_wars/build/css/star_wars.css', 'Non-minified CSS should not exist when theme build is skipped');
      $this->assertFileDoesNotExist($webroot . '/themes/custom/star_wars/build/js/star_wars.min.js', 'Minified JS file should not exist when theme build is skipped');
      $this->assertFileDoesNotExist($webroot . '/themes/custom/star_wars/build/js/star_wars.js', 'Non-minified JS should not exist when theme build is skipped');
    }

    $this->logStepFinish();
  }

  protected function subtestAhoyContainerLogs(): void {
    $this->logStepStart();

    $this->cmd('ahoy logs', ['* cli', '! Containers are not running.'], '`ahoy logs` can be executed');

    $this->logStepFinish();
  }

  protected function subtestAhoyDotEnv(): void {
    $this->logStepStart();

    $this->assertFileNotContainsString('.env', 'MY_CUSTOM_VAR', '.env does not contain test values');
    $this->assertFileNotContainsString('.env', 'my_custom_var_value', '.env does not contain test values');
    $this->cmdFail('ahoy cli "printenv | grep -q MY_CUSTOM_VAR"', txt: 'Custom variable does not exist inside of container.');
    $this->cmdFail('ahoy cli \'echo $MY_CUSTOM_VAR | grep -q my_custom_var_value\'', '! my_custom_var_value', txt: 'Custom variable does not exist and has no value inside of container.');
    // Add variable to the .env file and apply the change to container.
    $this->addVarToFile('.env', 'MY_CUSTOM_VAR', 'my_custom_var_value');
    $this->cmd('ahoy up cli');
    $this->syncToContainer();

    $this->assertFileContainsString('.env', 'MY_CUSTOM_VAR', '.env contains test values');
    $this->assertFileContainsString('.env', 'my_custom_var_value', '.env contains test values');
    $this->cmd('ahoy cli "printenv | grep MY_CUSTOM_VAR"', 'my_custom_var_value', 'Custom variable set in .env exists inside of container.');
    $this->cmd('ahoy cli \'echo $MY_CUSTOM_VAR | grep my_custom_var_value\'', 'my_custom_var_value', 'Custom variable set in .env exists and has a value inside of container.');

    $this->restoreFile('.env');
    $this->cmd('ahoy up cli');
    $this->syncToContainer();

    $this->logStepFinish();
  }

  protected function subtestAhoyCli(): void {
    $this->logStepStart();

    $this->cmd(
      'ahoy cli "echo Test from inside of the container"',
      [
        '! Containers are not running.',
        '* Test from inside of the container',
      ],
      '`ahoy cli` can execute commands inside the container'
    );

    putenv('DRUPAL_UNFILTERED_VAR=drupalvar');
    putenv('OTHER_FILTERED_VAR=othervar');
    $this->cmd(
      'ahoy cli "echo $DRUPAL_UNFILTERED_VAR"',
      ['* drupalvar', '! othervar'],
      env: [
        'DRUPAL_UNFILTERED_VAR' => 'drupalvar',
        'OTHER_FILTERED_VAR' => 'othervar',
      ],
      txt: '`ahoy cli` passes only allowed environment variables into the container'
    );

    $this->logStepFinish();
  }

  protected function subtestAhoyComposer(): void {
    $this->logStepStart();

    $this->cmd(
      'ahoy composer about',
      [
        'Composer - Dependency Manager for PHP - version 2.',
        'Composer is a dependency manager tracking local dependencies of your projects and libraries.',
      ],
      '`ahoy composer` can execute composer commands inside the container'
    );

    $this->logStepFinish();
  }

  protected function subtestAhoyDrush(): void {
    $this->logStepStart();

    $this->cmd('ahoy drush st', '! Containers are not running.', '`ahoy drush` can execute drush commands inside the container');

    $this->logStepFinish();
  }

  protected function subtestAhoyInfo(string $webroot = 'web', string $db_image = ''): void {
    $this->logStepStart();

    $this->cmd(
      'ahoy info',
      [
        '* Project name                : star_wars',
        '* Docker Compose project name : star_wars',
        '* Site local URL              : http://star_wars.docker.amazee.io',
        '* Path to web root            : /app/' . $webroot,
        '* DB host                     : database',
        '* DB username                 : drupal',
        '* DB password                 : drupal',
        '* DB port                     : 3306',
        '* DB port on host             :',
        '* Solr URL on host            :',
        '* Selenium VNC URL on host    :',
        '* Mailhog URL                 : http://mailhog.docker.amazee.io/',
        "* Xdebug                      : Disabled ('ahoy debug' to enable)",
        ($db_image !== '' && $db_image !== '0' ? '*' : '!') . ' DB-in-image                 : ' . $db_image,
        '! Containers are not running.',
      ],
      '`ahoy info` shows correct project information'
    );

    $this->logStepFinish();
  }

  protected function subtestAhoyLogin(): void {
    $this->logStepStart();

    $this->cmd('ahoy login', ['* http', '! Containers are not running.'], '`ahoy login` can be executed');

    $this->logStepFinish();
  }

  protected function subtestAhoyDoctor(): void {
    $this->logStepStart();

    $this->cmd('ahoy doctor info', [
      'System information report',
      'OPERATING SYSTEM',
      'DOCKER',
      'DOCKER COMPOSE',
      'PYGMY',
      'AHOY',
    ]);

    $this->logStepFinish();
  }

  protected function subtestAhoyProvision(): void {
    $this->logStepStart();

    $this->logSubstep('Run initial provision');

    $this->cmd(
      'ahoy provision',
      [
        'Provisioning site from the database dump file.',
        "Running deployment operations via 'drush deploy:hook'.",
        'Running database updates.',
      ],
      'Initial provision should complete successfully'
    );

    $this->logSubstep('Run follow-up provision with exported config files matching DB');

    $this->cmd('ahoy drush cex -y', '* ../config/default', 'Export configuration should complete successfully');
    $this->cmd('ahoy export-db db.sql', '* Exported database dump saved', 'Export database should complete successfully');
    $this->syncToHost();

    $this->cmd(
      'ahoy provision',
      [
        '* Provisioning site from the database dump file.',
        // @note 'drush deploy:hook' runs only if config files exist.
        "* Running deployment operations via 'drush deploy'.",
        '! Import the listed configuration changes',
        '* There are no changes to import',
      ],
      'Follow-up provision with matching config should complete successfully'
    );

    $this->cmd('ahoy drush config:status', '! Different', 'Config files should match the DB');

    $this->logSubstep('Run provision with exported config files different to DB');

    $this->logNote('Make a change to the configuration.');
    File::replaceContentInFile('config/default/system.site.yml', 'admin_compact_mode: false', 'admin_compact_mode: true');
    $this->syncToContainer();

    $this->cmd('ahoy drush config:status', 'Different', 'Config files should differ from the DB');

    $this->cmd(
      'ahoy provision',
      [
        'Provisioning site from the database dump file.',
        "Running deployment operations via 'drush deploy'.",
        'Import the listed configuration changes',
      ],
      'Provision with updated config should complete successfully'
    );

    $this->logSubstep('Test that provision works without DB');
    $this->cmd('ahoy drush sql:drop -y', txt: 'Database should be dropped successfully');

    $this->cmd(
      'ahoy provision',
      [
        'Provisioning site from the database dump file.',
        "Running deployment operations via 'drush deploy'.",
      ],
      'Provision without DB should complete successfully'
    );

    $this->logStepFinish();
  }

  protected function subtestAhoyExportDb(string $filename = ''): void {
    $this->logStepStart();

    $has_argument = $filename !== '';

    $this->logSubstep('Testing ahoy export-db command');
    $this->cmd(
      'ahoy export-db',
      arg: $has_argument ? [$filename] : [],
      out: [
        '* Exported database dump saved',
        '! Containers are not running.',
      ],
      txt: 'Export database dump ' . ($has_argument ? sprintf("to file '%s'", $filename) : 'to a default file')
    );

    $this->syncToHost();

    if ($has_argument) {
      $this->assertFileExists('.data/' . $filename, 'Export file should exist after export');
    }
    else {
      $this->assertFilesWildcardExists('.data/export_db_*');
    }

    $this->logStepFinish();
  }

  protected function subtestAhoyImportDb(string $filename = ''): void {
    $this->logStepStart();

    $has_argument = $filename !== '';

    $this->cmd(
      'ahoy import-db',
      arg: $has_argument ? [$filename] : [],
      out: [
        '* Provisioning site from the database dump file.',
        "! Running deployment operations via 'drush deploy:hook'.",
        '! Running database updates.',
      ],
      txt: 'Import database dump ' . ($has_argument ? sprintf("from file '%s'", $filename) : 'from the default file')
    );

    $this->logStepFinish();
  }

  protected function subtestAhoyLint(string $webroot = 'web'): void {
    $this->logStepStart();

    $this->cmd('ahoy lint', tio: 120, ito: 90, txt: '`ahoy lint` runs successfully');

    $this->logStepFinish();
  }

  protected function subtestAhoyLintBe(string $webroot = 'web'): void {
    $this->logStepStart();

    $this->logSubstep('Assert that BE lint failure works');
    File::dump($webroot . '/modules/custom/sw_base/sw_base.module', File::read($webroot . '/modules/custom/sw_base/sw_base.module') . '$a=1;');
    $this->syncToContainer();
    $this->cmdFail('ahoy lint-be', tio: 120, ito: 90, txt: '`ahoy lint-be` fails as expected on code with linting issues');

    $this->logSubstep('Assert that BE lint tool disabling works');
    // Replace with some valid XML element to avoid XML parsing errors.
    File::replaceContentInFile('phpcs.xml', '<file>' . $webroot . '/modules/custom</file>', '<exclude-pattern>somefile</exclude-pattern>');
    $this->syncToContainer();
    $this->cmd('ahoy lint-be', tio: 120, ito: 90, txt: '`ahoy lint-be` runs successfully after disabling the custom module folder in phpcs.xml');

    // @todo Add restoring of the file.
    $this->logStepFinish();
  }

  protected function subtestAhoyLintFe(string $webroot = 'web'): void {
    $this->logStepStart();

    $this->logSubstep('Assert that FE lint failure works for npm lint');
    File::dump($webroot . '/themes/custom/star_wars/scss/components/_test.scss', '.abc{margin: 0px;}');
    $this->syncToContainer();
    $this->cmdFail('ahoy lint-fe', tio: 120, ito: 90, txt: '`ahoy lint-fe` fails as expected for NPM linters on code with linting issues');
    // Cleanup.
    File::remove($webroot . '/themes/custom/star_wars/scss/components/_test.scss');
    $this->cmd('ahoy cli rm -f ' . $webroot . '/themes/custom/star_wars/scss/components/_test.scss');
    $this->syncToContainer();

    $this->logSubstep('Assert that FE lint failure works for Twig CS Fixer');
    File::dump($webroot . '/modules/custom/sw_base/templates/block/test1.twig', "{{ set a='a' }}");
    File::dump($webroot . '/themes/custom/star_wars/templates/block/test2.twig', "{{ set b='b' }}");
    $this->syncToContainer();
    $this->cmdFail('ahoy lint-fe', tio: 120, ito: 90, txt: '`ahoy lint-fe` fails for Twig CS Fixer as expected on code with linting issues');
    // Cleanup.
    File::remove([
      $webroot . '/modules/custom/sw_base/templates/block/test1.twig',
      $webroot . '/themes/custom/star_wars/templates/block/test2.twig',
    ]);
    $this->cmd('ahoy cli rm -f ' . $webroot . '/modules/custom/sw_base/templates/block/test1.twig');
    $this->cmd('ahoy cli rm -f ' . $webroot . '/themes/custom/star_wars/templates/block/test2.twig');
    $this->syncToContainer();

    $this->logStepFinish();
  }

  protected function subtestAhoyLintTests(): void {
    $this->logStepStart();

    $this->logSubstep('Assert that Test lint works for Gherkin Lint');
    $this->cmd('ahoy lint-tests', txt: '`ahoy lint-tests` runs successfully');

    $this->logSubstep('Assert that Test lint failure works for Gherkin Lint');
    File::dump('tests/behat/features/test.feature', 'Feature:');
    $this->syncToContainer();
    $this->cmdFail('ahoy lint-tests', txt: '`ahoy lint-tests` fails as expected on code with linting issues');
    // Cleanup.
    File::remove('tests/behat/features/test.feature');
    $this->cmd('ahoy cli rm -f tests/behat/features/test.feature');
    $this->syncToContainer();

    $this->logStepFinish();
  }

  protected function subtestAhoyTest(): void {
    $this->logStepStart();

    $this->cmd('ahoy test', tio: 300, ito: 240, txt: 'All tests passed');
    $this->syncToHost();
    $this->assertFileExists('.logs/test_results/phpunit/phpunit.xml', 'PHPUnit test results XML file should exist');
    $this->assertDirectoryExists('.logs/screenshots', 'Screenshots directory should exist after BDD tests');

    $this->logStepFinish();
  }

  protected function subtestAhoyTestUnit(string $webroot = 'web'): void {
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

  protected function subtestAhoyTestKernel(string $webroot = 'web'): void {
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

  protected function subtestAhoyTestFunctional(string $webroot = 'web'): void {
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

  protected function subtestAhoyTestBdd(string $webroot = 'web'): void {
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

  protected function subtestAhoyTestBddFast(string $webroot = 'web'): void {
    $this->logStepStart();

    $this->substepWarmCaches();

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

  protected function subtestAhoyFei(string $webroot = 'web'): void {
    $this->logStepStart();

    $this->logSubstep('Remove existing node_modules');
    File::remove('node_modules');
    $this->assertDirectoryDoesNotExist('node_modules', 'Root node_modules should not exist before `ahoy fei`');
    File::remove($webroot . '/themes/custom/star_wars/node_modules');
    $this->assertDirectoryDoesNotExist($webroot . '/themes/custom/star_wars/node_modules', 'Theme node_modules should not exist before `ahoy fei`');
    $this->syncToContainer();

    $this->logSubstep('Run `ahoy fei` to install all frontend dependencies');
    $this->cmd('ahoy fei');
    $this->syncToHost();
    $this->assertDirectoryExists('node_modules', 'Root node_modules should exist after `ahoy fei`');
    $this->assertDirectoryExists($webroot . '/themes/custom/star_wars/node_modules', 'Theme node_modules should exist after `ahoy fei`');

    $this->logStepFinish();
  }

  protected function subtestAhoyFe(string $webroot = 'web'): void {
    $this->logStepStart();

    $this->logSubstep('Build FE assets for production');

    $test_color1 = '#7e57e2';
    $test_color2 = '#91ea5e';
    $variables_file = $webroot . '/themes/custom/star_wars/scss/_variables.scss';
    $this->backupFile($variables_file);
    $minified_file = $webroot . '/themes/custom/star_wars/build/css/star_wars.min.css';
    $this->backupFile($minified_file);

    $this->assertFileNotContainsString($minified_file, $test_color1, 'Minified CSS file should not contain test color before build');

    $original_content = File::read($variables_file);
    $new_content = $original_content . "\$color-tester: {$test_color1};\n\$color-primary: \$color-tester;\n";
    File::remove($variables_file);
    File::dump($variables_file, $new_content);
    $this->syncToContainer();

    $this->cmd('ahoy fe');
    $this->syncToHost();
    $this->assertFileContainsString($minified_file, 'background:' . $test_color1, 'Assets compiled for production are minified (no spaces between properties and their values)');

    $this->logSubstep('Build FE assets for development');

    $this->assertFileNotContainsString($minified_file, $test_color2, 'Minified CSS file should not contain second test color before development build');

    $dev_content = $new_content . "\$color-please: {$test_color2};\n\$color-primary: \$color-please;\n";
    File::remove($variables_file);
    File::dump($variables_file, $dev_content);
    $this->syncToContainer();

    $this->cmd('ahoy fed');
    $this->syncToHost();
    $this->assertFileContainsString($minified_file, 'background: ' . $test_color2, 'Assets compiled for development are not minified (contains spaces between properties and their values)');

    $this->restoreFile($variables_file);
    $this->restoreFile($minified_file);

    $this->logStepFinish();
  }

  protected function subtestAhoyDebug(): void {
    $this->logStepStart();

    $this->logSubstep('Assert default Xdebug state');
    $this->cmd('ahoy cli "php -v"', '! Xdebug', 'Xdebug is not initially enabled in the container.');
    $this->cmd('ahoy info', ['* Xdebug', '* Disabled', '! Enabled'], '`ahoy info` shows that Xdebug is initially disabled.');

    $this->logSubstep('Enable Xdebug');
    $this->cmd('ahoy debug', '* Enabled debug', '`ahoy debug` enables Xdebug and restarts the stack.');
    $this->cmd('ahoy cli "php -v"', '* Xdebug', 'Xdebug is enabled in the container.');
    $this->cmd('ahoy info', ['! Disabled', '* Enabled'], '`ahoy info` shows that Xdebug is enabled.');

    $this->logSubstep('Assert repeated call does not restart the stack');
    $this->cmd('ahoy debug', '* Debug configuration is already enabled.', '`ahoy debug` does not restart the stack when Xdebug is already enabled.');

    $this->logSubstep('Disable Xdebug');
    $this->cmd('ahoy up', '! debug', txt: 'Restart the stack to disable Xdebug.');
    $this->cmd('ahoy cli "php -v"', '! Xdebug', 'Xdebug is not enabled in the container after a restart.');
    $this->cmd('ahoy info', ['* Xdebug', '* Disabled', '! Enabled'], '`ahoy info` shows that Xdebug is disabled after a restart.');

    $this->logStepFinish();
  }

  protected function subtestAhoyReset(string $webroot = 'web'): void {
    $this->logStepStart();

    $this->logSubstep('Create untracked files and directories');
    File::dump('untracked_file.txt', 'test content');
    $this->assertFileExists('untracked_file.txt');

    $this->assertFileExists('.idea/idea_file.txt');

    $this->createDevelopmentSettings($webroot);

    File::mkdir('.logs/screenshots');
    $this->assertDirectoryExists('.logs/screenshots');

    $this->logSubstep('Run reset');
    $this->cmd('ahoy reset');
    sleep(10);

    $this->logSubstep('Assert expected files and directories present or absent after reset');
    // Assert that initial Vortex files have not been removed.
    $this->assertCommonFilesPresent($webroot);

    $this->assertDirectoryDoesNotExist($webroot . '/modules/contrib', 'Contributed modules directory has been removed.');
    $this->assertDirectoryDoesNotExist($webroot . '/themes/contrib', 'Contributed themes directory has been removed.');
    $this->assertDirectoryDoesNotExist('vendor', 'Vendor directory has been removed.');
    $this->assertDirectoryDoesNotExist($webroot . '/themes/custom/star_wars/node_modules', 'Theme node_modules directory has been removed.');
    $this->assertDirectoryExists('.logs/screenshots', 'Screenshots directory still exists.');
    $this->assertFileExists($webroot . '/sites/default/settings.local.php', 'Manually created local settings file exists.');
    $this->assertFileExists($webroot . '/sites/default/services.local.yml', 'Manually created local services file exists.');
    $this->assertFileExists('untracked_file.txt', 'Untracked file still exists.');
    $this->assertFileExists('.idea/idea_file.txt', 'IDE config file still exists.');
    $this->assertDirectoryExists('.git', 'Project is still a Git repository.');

    // Cleanup.
    $this->removeDevelopmentSettings($webroot);

    $this->logStepFinish();
  }

  protected function subtestAhoyResetHard(string $webroot = 'web'): void {
    $this->logStepStart();

    $this->logSubstep('Create untracked files and directories');
    File::dump('untracked_file.txt', 'test content');
    $this->assertFileExists('untracked_file.txt');

    $this->assertFileExists('.idea/idea_file.txt');

    $this->createDevelopmentSettings($webroot);

    File::mkdir('.logs/screenshots');
    $this->assertDirectoryExists('.logs/screenshots');

    $this->logSubstep('Run hard reset');
    $this->cmd('ahoy reset hard');
    sleep(10);

    $this->logSubstep('Assert expected files and directories present or absent after reset');
    $this->assertCommonFilesPresent($webroot);

    $this->assertDirectoryDoesNotExist($webroot . '/modules/contrib', 'Contributed modules directory has been removed.');
    $this->assertDirectoryDoesNotExist($webroot . '/themes/contrib', 'Contributed themes directory has been removed.');
    $this->assertDirectoryDoesNotExist('vendor', 'Vendor directory has been removed.');
    $this->assertDirectoryDoesNotExist($webroot . '/themes/custom/star_wars/node_modules', 'Theme node_modules directory has been removed.');
    $this->assertDirectoryDoesNotExist('.logs/screenshots', 'Screenshots directory has been removed.');
    $this->assertFileDoesNotExist($webroot . '/sites/default/settings.local.php', 'Manually created local settings file has been removed.');
    $this->assertFileDoesNotExist($webroot . '/sites/default/services.local.yml', 'Manually created local services file has been removed.');
    $this->assertFileDoesNotExist('untracked_file.txt', 'Untracked file has been removed.');
    $this->assertFileExists('.idea/idea_file.txt', 'IDE config file still exists.');
    $this->assertDirectoryExists('.git', 'Project is still a Git repository.');

    // Cleanup.
    $this->removeDevelopmentSettings($webroot);

    $this->logStepFinish();
  }

  protected function subtestAhoySolr(): void {
    $this->logStepStart();

    $this->cmd(
      'ahoy cli "curl -s \"http://solr:8983/solr/drupal/select?q=*:*&rows=0&wt=json\""',
      'response',
      'Solr is running and responding to queries'
    );

    $this->logStepFinish();
  }

  protected function subtestAhoyRedis(): void {
    $this->logStepStart();

    $this->logSubstep('Redis service is running');
    $this->cmd('ahoy flush-redis', 'OK', 'Redis service should be running initially');

    $this->logSubstep('Disable Redis Drupal integration');
    $this->addVarToFile('.env', 'DRUPAL_REDIS_ENABLED', '0');
    $this->syncToContainer();
    $this->cmd('ahoy up');
    sleep(10);
    $this->cmd('ahoy flush-redis', txt: 'Redis service should be running after integration was disabled');

    $this->logSubstep('Assert that Redis Drupal integration is not working when disabled');
    $this->substepWarmCaches();
    $this->cmd('docker compose exec -T redis redis-cli --scan', '! config', 'Redis should be empty after caches are warmed with integration disabled');
    $this->cmd('docker compose exec -T cli drush core:requirements --filter="title~=#(Redis)#i" --field=severity', 'Warning', 'Redis should not be connected in Drupal');

    $this->restoreFile('.env');
    $this->syncToContainer();

    $this->logSubstep('Enable Redis Drupal integration');
    $this->addVarToFile('.env', 'DRUPAL_REDIS_ENABLED', '1');
    $this->syncToContainer();

    $this->cmd('ahoy up');
    sleep(10);
    $this->cmd('ahoy flush-redis', txt: 'Redis service should be running after integration was enabled');

    $this->logSubstep('Assert that Redis Drupal integration is working when enabled');
    $this->substepWarmCaches();
    $this->cmd('docker compose exec -T redis redis-cli --scan', 'config', 'Redis should have keys after caches are warmed with integration enabled');
    $this->cmd('docker compose exec -T cli drush core:requirements --filter="title~=#(Redis)#i" --field=severity', 'OK', 'Redis should be connected in Drupal');

    $this->logSubstep('Cleanup after test');
    $this->restoreFile('.env');
    $this->syncToContainer();
    $this->cmd('ahoy up cli');

    $this->logStepFinish();
  }

  protected function substepWarmCaches(): void {
    $this->logNote('Warming up caches');
    $this->cmd('ahoy drush cr');
    $this->cmd('ahoy cli curl -- -sSL -o /dev/null -w "%{http_code}" http://nginx:8080 | grep -q 200');
  }

}
