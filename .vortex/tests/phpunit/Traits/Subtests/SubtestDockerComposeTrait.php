<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Traits\Subtests;

/**
 * Provides Docker Compose subtests.
 */
trait SubtestDockerComposeTrait {

  protected function subtestDockerComposeBuild(string $webroot = 'web', array $env = [], bool $build_theme = TRUE): void {
    $this->logStepStart();

    $this->logSubstep('Assert lock files presence/absence before build');
    $this->assertFileDoesNotExist('composer.lock', 'Composer lock file should not exist before build');
    $this->assertFileExists('yarn.lock', 'Yarn lock file should exist before build');

    if ($build_theme) {
      $this->assertThemeFilesPresent($webroot);
    }
    else {
      $this->assertThemeFilesAbsent($webroot);
    }

    $db_file_present = file_exists('.data/db.sql');
    $this->logNote('Database file exists before build: ' . ($db_file_present ? 'Yes' : 'No'));

    $this->logSubstep('Starting Docker Compose build');
    $this->cmd('docker compose up -d --force-recreate --build --renew-anon-volumes', env: $env, tio: 15 * 60, txt: 'Stack images should be built and stack should start successfully');
    $this->syncToHost();

    $this->logSubstep('Assert lock files presence/absence after build');
    $this->assertFileExists('composer.lock', 'Composer lock file should exist after build');
    $this->assertFileExists('yarn.lock', 'Yarn lock file should exist after build');
    if ($build_theme) {
      $this->assertThemeFilesPresent($webroot);
    }
    else {
      $this->assertThemeFilesAbsent($webroot);
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

  protected function subtestDockerComposeDotEnv(): void {
    $this->logStepStart();

    // Lagoon images have a ~/.bashrc that loads changes made to .env file on
    // every new shell session like `docker compose exec bash -c "..."`.
    // This is a bit different from the usual Docker behaviour where env
    // variables are loaded only by Docker Compose and only on container start.
    //
    // The order of variables loading is:
    // - Docker (re-)start: variables defined in docker-compose.yml file are
    //   loaded on container (re-)start and are available to all processes in
    //   the container. If any variables are defined in the .env file, they
    //   override the values defined in the docker-compose.yml file.
    // - Process start:
    //   - if the process is started via bash (like
    //     `docker compose exec -T cli bash -c "..."`), then the ~/.bashrc is
    //     loaded on every new shell session like `docker compose exec bash -c`
    //     This reads the .env file and loads any variables defined there.
    //     This allows to avoid container restart to pick up changes
    //     to the .env file for processes started via bash.
    //   - if the process is started directly by Docker Compose (like
    //     `docker compose exec -T cli php -r "..."`), then the
    //     ~/.bashrc is NOT loaded as the entrypoint in the container is bash
    //     and not php. This means that only variables loaded by Docker Compose
    //     (defined in the docker-compose.yml and .env) are available to the
    //     process.
    //
    // We need to test a matrix of:
    // - variable type: docker-compose-mapped vs custom
    //   - docker-compose-mapped variable is set in the docker-compose.yml
    //     file and is automatically populated from the .env file on container
    //     (re-)start. If the variable is changed in the .env file, the
    //     container needs to be restarted to pick up the change for the
    //     *running* processes to see the change (they will be restarted).
    //   - custom variable is not mentioned in the docker-compose.yml file
    //     and is only available if the .env file is read by the shell.
    // - shell type: interactive vs non-interactive
    //   - interactive shell is started with `docker compose exec -T cli bash`
    //     and loads ~/.bashrc which reads the .env file on every new shell
    //     session.
    //   - non-interactive shell is started with `docker compose exec -T cli
    //     bash -c "..."` and also loads ~/.bashrc which reads the .env file
    //     on every new shell session as the entrypoint in container is bash.
    // - container restart: before vs after
    //   - before: asserting that changes to .env file are visible without
    //     container restart (they should be visible in both interactive and
    //     non-interactive shells as they load ~/.bashrc on every new shell
    //     session).
    //   - after: asserting that changes to .env file are visible after
    //     container restart (they should be visible in both interactive and
    //     non-interactive shells as they load ~/.bashrc on every new shell
    //     session, and docker-compose-mapped variables should also be
    //     visible as container has been restarted).
    //
    // Phase 1.
    //
    $this->logSubstep('Asserting variables values in non-interactive shells before container restarts.');

    $this->logNote('Asserting preconditions.');
    $this->assertFileNotContainsString('.env', 'DRUPAL_SHIELD_USER', '.env does not contain docker-compose-mapped variable');
    $this->assertFileNotContainsString('.env', 'my_custom_shield_user1', '.env does not contain docker-compose-mapped variable value');
    $this->assertFileNotContainsString('.env', 'MY_CUSTOM_VAR1', '.env does not contain custom variable');
    $this->assertFileNotContainsString('.env', 'my_custom_var_value1', '.env does not contain custom variable value');

    $this->logNote('Asserting initial variable values.');
    $this->cmd('docker compose exec -T cli bash -c "printenv|sort"', 'DRUPAL_SHIELD_PASS', 'Docker-compose-mapped variable exists.');
    $this->cmd('docker compose exec -T cli bash -c \'echo $DRUPAL_SHIELD_USER\'', '! my_custom_shield_user1', 'Docker-compose-mapped variable has no value.');
    $this->cmd('docker compose exec -T cli php -r "echo getenv(\'DRUPAL_SHIELD_USER\') ?: \'Not set\';"', ['! my_custom_shield_user1', '* Not set'], 'Docker-compose-mapped variable does not exist and has no value in PHP script.');
    $this->cmd('docker compose exec -T cli bash -c "printenv|sort"', '! MY_CUSTOM_VAR1', 'Custom variable does not exist.');
    $this->cmd('docker compose exec -T cli bash -c \'echo $MY_CUSTOM_VAR1\'', '! my_custom_var_value1', 'Custom variable does not exist and has no value.');
    $this->cmd('docker compose exec -T cli php -r "echo getenv(\'MY_CUSTOM_VAR1\') ?: \'Not set\';"', ['! my_custom_var_value1', '* Not set'], 'Custom variable does not exist and has no value in PHP script.');

    $this->logNote('Adding variables to the .env file.');
    $this->fileAddVar('.env', 'DRUPAL_SHIELD_USER', 'my_custom_shield_user1');
    $this->assertFileContainsString('.env', 'DRUPAL_SHIELD_USER', '.env contains docker-compose-mapped variable');
    $this->assertFileContainsString('.env', 'my_custom_shield_user1', '.env contains docker-compose-mapped variable value');
    $this->fileAddVar('.env', 'MY_CUSTOM_VAR1', 'my_custom_var_value1');
    $this->assertFileContainsString('.env', 'MY_CUSTOM_VAR1', '.env contains test variable');
    $this->assertFileContainsString('.env', 'my_custom_var_value1', '.env contains test variable value');

    $this->syncToContainer('.env');

    $this->logNote('Asserting variables before container restarts.');
    $this->cmd('docker compose exec -T cli bash -c "printenv|sort"', 'DRUPAL_SHIELD_PASS', 'Docker-compose-mapped variable exists before container restarts.');
    $this->cmd('docker compose exec -T cli bash -c \'echo $DRUPAL_SHIELD_USER\'', '! my_custom_shield_user1', 'Docker-compose-mapped variable has no value before container restarts.');
    $this->cmd('docker compose exec -T cli php -r "echo getenv(\'DRUPAL_SHIELD_USER\') ?: \'Not set\';"', ['! my_custom_shield_user1', '* Not set'], 'Docker-compose-mapped variable does not exist and has no value in PHP script before container restarts.');
    $this->cmd('docker compose exec -T cli bash -c "printenv|sort"', 'my_custom_var_value1', 'Custom variable exists inside of container before container restarts.');
    $this->cmd('docker compose exec -T cli bash -c \'echo $MY_CUSTOM_VAR1\'', 'my_custom_var_value1', 'Custom variable exists and has a value inside of container before container restarts.');
    $this->cmd('docker compose exec -T cli php -r "echo getenv(\'MY_CUSTOM_VAR1\') ?: \'Not set\';"', ['! my_custom_var_value1', '* Not set'], 'Custom variable does not exist and has no value in PHP script before container restarts.');

    $this->logSubstep('Asserting .env file is read by non-interactive shells after container restarts.');

    $this->cmd('docker compose up -d cli', txt: 'Restarting CLI container to pick up changes to .env file.');

    $this->logNote('Asserting variables after container restarts.');
    $this->cmd('docker compose exec -T cli bash -c "printenv|sort"', 'DRUPAL_SHIELD_PASS', 'Docker-compose-mapped variable exists after container restarts.');
    $this->cmd('docker compose exec -T cli bash -c \'echo $DRUPAL_SHIELD_USER\'', 'my_custom_shield_user1', 'Docker-compose-mapped variable has value after container restarts.');
    $this->cmd('docker compose exec -T cli php -r "echo getenv(\'DRUPAL_SHIELD_USER\') ?: \'Not set\';"', ['* my_custom_shield_user1', '! Not set'], 'Docker-compose-mapped variable has value in PHP script after container restarts.');
    $this->cmd('docker compose exec -T cli bash -c "printenv|sort"', 'my_custom_var_value1', 'Custom variable exists inside of container after container restarts.');
    $this->cmd('docker compose exec -T cli bash -c \'echo $MY_CUSTOM_VAR1\'', 'my_custom_var_value1', 'Custom variable exists and has a value inside of container after container restarts.');
    // Important: getenv() uses variables available in the environments when the
    // PHP process starts. Using `docker compose exec -T cli php` rather than
    // `docker compose exec -T cli bash -c "php"` means that PHP is
    // started directly by Docker Compose and not via bash, so the ~/.bashrc
    // is not loaded and the custom variable is not available in the PHP
    // process environment.
    $this->cmd('docker compose exec -T cli php -r "echo getenv(\'MY_CUSTOM_VAR1\') ?: \'Not set\';"', ['! my_custom_var_value1', '* Not set'], 'Custom variable does not exist and has no value in PHP script after container restarts.');

    $this->fileRestore('.env');

    //
    // Phase 2.
    //
    $this->logSubstep('Asserting variables values in interactive shells before container restarts.');

    $this->logNote('Asserting preconditions.');
    $this->assertFileNotContainsString('.env', 'DRUPAL_SHIELD_PASS', '.env does not contain docker-compose-mapped variable');
    $this->assertFileNotContainsString('.env', 'my_custom_shield_pass1', '.env does not contain docker-compose-mapped variable value');
    $this->assertFileNotContainsString('.env', 'MY_CUSTOM_VAR2', '.env does not contain custom variable');
    $this->assertFileNotContainsString('.env', 'my_custom_var_value2', '.env does not contain custom variable value');

    $this->logNote('Asserting initial variable values.');
    $this->cmd('docker compose exec -T cli bash -c "printenv|sort"', 'DRUPAL_SHIELD_PASS', 'Docker-compose-mapped variable exists.');
    $this->cmd('docker compose exec -T cli bash -c \'echo $DRUPAL_SHIELD_PASS\'', '! my_custom_shield_pass1', 'Docker-compose-mapped variable has no value.');
    $this->cmd('docker compose exec -T cli php -r "echo getenv(\'DRUPAL_SHIELD_PASS\') ?: \'Not set\';"', ['! my_custom_shield_pass1', '* Not set'], 'Docker-compose-mapped variable does not exist and has no value in PHP script.');
    $this->cmd('docker compose exec -T cli bash -c "printenv|sort"', '! MY_CUSTOM_VAR2', 'Custom variable does not exist.');
    $this->cmd('docker compose exec -T cli bash -c \'echo $MY_CUSTOM_VAR2\'', '! my_custom_var_value2', 'Custom variable does not exist and has no value.');
    $this->cmd('docker compose exec -T cli php -r "echo getenv(\'MY_CUSTOM_VAR2\') ?: \'Not set\';"', ['! my_custom_var_value2', '* Not set'], 'Custom variable does not exist and has no value in PHP script.');

    $this->logNote('Adding variables to the .env file.');
    $this->fileAddVar('.env', 'DRUPAL_SHIELD_PASS', 'my_custom_shield_pass1');
    $this->assertFileContainsString('.env', 'DRUPAL_SHIELD_PASS', '.env contains docker-compose-mapped variable');
    $this->assertFileContainsString('.env', 'my_custom_shield_pass1', '.env contains docker-compose-mapped variable value');
    $this->fileAddVar('.env', 'MY_CUSTOM_VAR2', 'my_custom_var_value2');
    $this->assertFileContainsString('.env', 'MY_CUSTOM_VAR2', '.env contains test variable');
    $this->assertFileContainsString('.env', 'my_custom_var_value2', '.env contains test variable value');

    $this->syncToContainer('.env');

    $this->logNote('Asserting variables before container restarts.');
    $this->cmd('docker compose exec -T cli bash -c "printenv|sort"', 'DRUPAL_SHIELD_PASS', 'Docker-compose-mapped variable exists before container restarts.');
    $this->cmd('docker compose exec -T cli bash -c \'echo $DRUPAL_SHIELD_PASS\'', '! my_custom_shield_pass1', 'Docker-compose-mapped variable has no value before container restarts.');
    $this->cmd('docker compose exec -T cli php -r "echo getenv(\'DRUPAL_SHIELD_PASS\') ?: \'Not set\';"', ['! my_custom_shield_pass1', '* Not set'], 'Docker-compose-mapped variable does not exist and has no value in PHP script before container restarts.');
    $this->cmd('docker compose exec -T cli bash -c "printenv|sort"', 'my_custom_var_value2', 'Custom variable exists inside of container before container restarts.');
    $this->cmd('docker compose exec -T cli bash -c \'echo $MY_CUSTOM_VAR2\'', 'my_custom_var_value2', 'Custom variable exists and has a value inside of container before container restarts.');
    $this->cmd('docker compose exec -T cli php -r "echo getenv(\'MY_CUSTOM_VAR2\') ?: \'Not set\';"', ['! my_custom_var_value2', '* Not set'], 'Custom variable does not exist and has no value in PHP script before container restarts.');

    $this->logSubstep('Asserting .env file is read by interactive shells after container restarts.');

    $this->cmd('docker compose up -d cli', txt: 'Restarting CLI container to pick up changes to .env file.');

    $this->logNote('Asserting variables after container restarts.');
    $this->cmd('docker compose exec -T cli bash -c "printenv|sort"', 'DRUPAL_SHIELD_PASS', 'Docker-compose-mapped variable exists after container restarts.');
    $this->cmd('docker compose exec -T cli bash -c \'echo $DRUPAL_SHIELD_PASS\'', 'my_custom_shield_pass1', 'Docker-compose-mapped variable has value after container restarts.');
    $this->cmd('docker compose exec -T cli php -r "echo getenv(\'DRUPAL_SHIELD_PASS\') ?: \'Not set\';"', ['* my_custom_shield_pass1', '! Not set'], 'Docker-compose-mapped variable has value in PHP script after container restarts.');
    $this->cmd('docker compose exec -T cli bash -c "printenv|sort"', 'my_custom_var_value2', 'Custom variable exists inside of container after container restarts.');
    $this->cmd('docker compose exec -T cli bash -c \'echo $MY_CUSTOM_VAR2\'', 'my_custom_var_value2', 'Custom variable exists and has a value inside of container after container restarts.');
    // Important: getenv() uses variables available in the environments when the
    // PHP process starts. Using `docker compose exec -T cli php` rather than
    // `docker compose exec -T cli bash -c "php"` means that PHP is
    // started directly by Docker Compose and not via bash, so the ~/.bashrc
    // is not loaded and the custom variable is not available in the PHP
    // process environment.
    $this->cmd('docker compose exec -T cli php -r "echo getenv(\'MY_CUSTOM_VAR2\') ?: \'Not set\';"', ['! my_custom_var_value2', '* Not set'], 'Custom variable does not exist and has no value in PHP script after container restarts.');

    $this->fileRestore('.env');
    $this->cmd('docker compose up -d');

    $this->logStepFinish();
  }

  protected function subtestDockerComposeTimezone(): void {
    $this->logStepStart();

    $this->logSubstep('Assert default timezone values.');
    $this->assertFileContainsString('.env', 'TZ=UTC', '.env contains a default value.');
    $this->cmd('docker compose exec -T cli date', 'UTC', 'Date is in default timezone inside CLI container by default');
    $this->cmd('docker compose exec -T php date', 'UTC', 'Date is in default timezone inside PHP container by default');
    $this->cmd('docker compose exec -T nginx date', 'UTC', 'Date is in default timezone inside Nginx container by default');
    $this->cmd('docker compose exec -T database date', 'UTC', 'Date is in default timezone inside Database container by default');

    $this->logSubstep('Add variable to the .env file and apply the change to container.');
    $this->fileAddVar('.env', 'TZ', '"Australia/Perth"');
    $this->cmd('docker compose up -d');

    $this->logSubstep('Assert custom timezone values.');
    $this->cmd('docker compose exec -T cli date', 'AWST', 'Date is in custom timezone inside CLI container');
    $this->cmd('docker compose exec -T php date', 'AWST', 'Date is in custom timezone inside PHP container');
    $this->cmd('docker compose exec -T nginx date', 'AWST', 'Date is in custom timezone inside Nginx container');
    $this->cmd('docker compose exec -T database date', 'AWST', 'Date is in custom timezone inside Database container');

    $this->logSubstep('Restore file, apply changes and assert that original behaviour has been restored.');
    $this->fileRestore('.env');
    $this->cmd('docker compose up -d');

    $this->logStepFinish();
  }

  protected function subtestSolr(): void {
    $this->logStepStart();

    $this->cmd(
      'docker compose exec -T cli bash -c "curl -s \"http://solr:8983/solr/drupal/select?q=*:*&rows=0&wt=json\""',
      'response',
      'Solr is running and responding to queries'
    );

    $this->logStepFinish();
  }

}
