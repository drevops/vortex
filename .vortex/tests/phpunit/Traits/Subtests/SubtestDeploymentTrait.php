<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Traits\Subtests;

use AlexSkrypnyk\File\File;

/**
 * Steps and assertions for testing deployment workflows.
 */
trait SubtestDeploymentTrait {

  /**
   * Prepare deployment source directory.
   */
  protected function prepareDeploymentSource(string $src_dir): void {
    $this->logNote('Preparing deployment source at: ' . $src_dir);
    File::mkdir($src_dir);
  }

  /**
   * Prepare remote repository for artifact deployment.
   */
  protected function prepareRemoteRepository(string $remote_dir): void {
    $this->logNote('Preparing remote repository at: ' . $remote_dir);
    File::mkdir($remote_dir);
    $this->gitInitRepo($remote_dir);
    // Configure git to accept pushes to the checked-out branch.
    shell_exec('git -C ' . escapeshellarg($remote_dir) . ' config receive.denyCurrentBranch updateInstead');
    // Create an initial file so we can commit.
    File::dump($remote_dir . '/.gitkeep', '');
    $this->gitCommitAll($remote_dir, 'Initial commit');
  }

  /**
   * Assert deployment artifact files are present.
   *
   * These are the files that should exist in a deployment artifact after
   * the build process has completed.
   */
  protected function assertDeploymentFilesPresent(string $dir, string $webroot = 'web'): void {
    $this->logNote('Asserting deployment files are present in: ' . $dir);

    // CI/CD directories should not exist in deployment.
    $this->assertDirectoryDoesNotExist($dir . '/.circleci', 'CircleCI directory should not exist in deployment');
    $this->assertDirectoryDoesNotExist($dir . '/.data', 'Data directory should not exist in deployment');
    $this->assertDirectoryDoesNotExist($dir . '/.docker', 'Docker directory should not exist in deployment');
    $this->assertDirectoryDoesNotExist($dir . '/.github', 'GitHub directory should not exist in deployment');
    $this->assertDirectoryDoesNotExist($dir . '/.logs/screenshots', 'Screenshots directory should not exist in deployment');
    $this->assertDirectoryDoesNotExist($dir . '/node_modules', 'node_modules should not exist in deployment');
    $this->assertDirectoryDoesNotExist($dir . '/patches', 'Patches directory should not exist in deployment');
    $this->assertDirectoryDoesNotExist($dir . '/tests', 'Tests directory should not exist in deployment');

    // Development files should not exist in deployment.
    $this->assertFileDoesNotExist($dir . '/.ahoy.yml', '.ahoy.yml should not exist in deployment');
    $this->assertFileDoesNotExist($dir . '/.dockerignore', '.dockerignore should not exist in deployment');
    $this->assertFileDoesNotExist($dir . '/.editorconfig', '.editorconfig should not exist in deployment');
    $this->assertFileDoesNotExist($dir . '/.eslintrc.json', '.eslintrc.json should not exist in deployment');
    $this->assertFileDoesNotExist($dir . '/.lagoon.yml', '.lagoon.yml should not exist in deployment');
    $this->assertFileDoesNotExist($dir . '/.stylelintrc.json', '.stylelintrc.json should not exist in deployment');
    $this->assertFileDoesNotExist($dir . '/LICENSE', 'LICENSE should not exist in deployment');
    $this->assertFileDoesNotExist($dir . '/README.md', 'README.md should not exist in deployment');
    $this->assertFileDoesNotExist($dir . '/behat.yml', 'behat.yml should not exist in deployment');
    $this->assertFileDoesNotExist($dir . '/composer.lock', 'composer.lock should not exist in deployment');
    $this->assertFileDoesNotExist($dir . '/docker-compose.yml', 'docker-compose.yml should not exist in deployment');
    $this->assertFileDoesNotExist($dir . '/gherkinlint.json', 'gherkinlint.json should not exist in deployment');
    $this->assertFileDoesNotExist($dir . '/phpcs.xml', 'phpcs.xml should not exist in deployment');
    $this->assertFileDoesNotExist($dir . '/phpstan.neon', 'phpstan.neon should not exist in deployment');
    $this->assertFileDoesNotExist($dir . '/renovate.json', 'renovate.json should not exist in deployment');
    $this->assertFileDoesNotExist($dir . '/.gitignore.artifact', '.gitignore.artifact should not exist in deployment');

    // Required directories should exist.
    $this->assertDirectoryExists($dir . '/scripts', 'Scripts directory should exist in deployment');
    $this->assertDirectoryExists($dir . '/vendor', 'Vendor directory should exist in deployment');

    // .env configs should exist to allow project control.
    $this->assertFileExists($dir . '/.env', '.env should exist in deployment');

    // Site core module should be present.
    $this->assertDirectoryExists($dir . '/' . $webroot . '/modules/custom/sw_base', 'sw_base module directory should exist');
    $this->assertFileExists($dir . '/' . $webroot . '/modules/custom/sw_base/sw_base.info.yml', 'sw_base info file should exist');
    $this->assertFileExists($dir . '/' . $webroot . '/modules/custom/sw_base/sw_base.module', 'sw_base module file should exist');
    $this->assertFileExists($dir . '/' . $webroot . '/modules/custom/sw_base/sw_base.deploy.php', 'sw_base deploy file should exist');

    // Site theme should be present.
    $this->assertDirectoryExists($dir . '/' . $webroot . '/themes/custom/star_wars', 'star_wars theme directory should exist');
    $this->assertFileExists($dir . '/' . $webroot . '/themes/custom/star_wars/.gitignore', 'Theme .gitignore should exist');
    $this->assertFileExists($dir . '/' . $webroot . '/themes/custom/star_wars/star_wars.info.yml', 'Theme info file should exist');
    $this->assertFileExists($dir . '/' . $webroot . '/themes/custom/star_wars/star_wars.libraries.yml', 'Theme libraries file should exist');
    $this->assertFileExists($dir . '/' . $webroot . '/themes/custom/star_wars/star_wars.theme', 'Theme file should exist');

    // Theme development files should not exist.
    $this->assertFileDoesNotExist($dir . '/' . $webroot . '/themes/custom/star_wars/postcss.config.js', 'Theme postcss.config.js should not exist in deployment');
    $this->assertFileDoesNotExist($dir . '/' . $webroot . '/themes/custom/star_wars/package.json', 'Theme package.json should not exist in deployment');
    $this->assertFileDoesNotExist($dir . '/' . $webroot . '/themes/custom/star_wars/yarn.lock', 'Theme yarn.lock should not exist in deployment');
    $this->assertFileDoesNotExist($dir . '/' . $webroot . '/themes/custom/star_wars/.eslintrc.json', 'Theme .eslintrc.json should not exist in deployment');
    $this->assertDirectoryDoesNotExist($dir . '/' . $webroot . '/themes/custom/star_wars/node_modules', 'Theme node_modules should not exist in deployment');

    // Drupal scaffold files should be present.
    $this->assertFileExists($dir . '/' . $webroot . '/autoload.php', 'autoload.php should exist');
    $this->assertFileExists($dir . '/' . $webroot . '/index.php', 'index.php should exist');
    $this->assertFileDoesNotExist($dir . '/' . $webroot . '/robots.txt', 'robots.txt should not exist in deployment');
    $this->assertFileDoesNotExist($dir . '/' . $webroot . '/update.php', 'update.php should not exist in deployment');

    // Settings files should be present.
    $this->assertFileExists($dir . '/' . $webroot . '/sites/default/settings.php', 'settings.php should exist');
    $this->assertFileExists($dir . '/' . $webroot . '/sites/default/services.yml', 'services.yml should exist');
    $this->assertFileDoesNotExist($dir . '/' . $webroot . '/sites/default/example.settings.local.php', 'example.settings.local.php should not exist in deployment');
    $this->assertFileDoesNotExist($dir . '/' . $webroot . '/sites/default/example.services.local.yml', 'example.services.local.yml should not exist in deployment');
    $this->assertFileDoesNotExist($dir . '/' . $webroot . '/sites/default/default.settings.local.php', 'default.settings.local.php should not exist in deployment');
    $this->assertFileDoesNotExist($dir . '/' . $webroot . '/sites/default/default.services.local.yml', 'default.services.local.yml should not exist in deployment');

    // Only minified compiled CSS should be present.
    $this->assertFileExists($dir . '/' . $webroot . '/themes/custom/star_wars/build/css/star_wars.min.css', 'Minified CSS should exist');
    $this->assertFileDoesNotExist($dir . '/' . $webroot . '/themes/custom/star_wars/build/css/star_wars.css', 'Non-minified CSS should not exist in deployment');
    $this->assertDirectoryDoesNotExist($dir . '/' . $webroot . '/themes/custom/star_wars/scss', 'SCSS directory should not exist in deployment');
    $this->assertDirectoryDoesNotExist($dir . '/' . $webroot . '/themes/custom/star_wars/css', 'CSS source directory should not exist in deployment');

    // Only minified compiled JS should exist.
    $this->assertFileExists($dir . '/' . $webroot . '/themes/custom/star_wars/build/js/star_wars.min.js', 'Minified JS should exist');
    $this->assertFileContainsString($dir . '/' . $webroot . '/themes/custom/star_wars/build/js/star_wars.min.js', '!function(', 'JS should contain expected minified content');
    $this->assertFileDoesNotExist($dir . '/' . $webroot . '/themes/custom/star_wars/build/js/star_wars.js', 'Non-minified JS should not exist in deployment');
    $this->assertDirectoryDoesNotExist($dir . '/' . $webroot . '/themes/custom/star_wars/js', 'JS source directory should not exist in deployment');

    // Other source asset files should not exist.
    $this->assertDirectoryDoesNotExist($dir . '/' . $webroot . '/themes/custom/star_wars/fonts', 'Fonts source directory should not exist in deployment');
    $this->assertDirectoryDoesNotExist($dir . '/' . $webroot . '/themes/custom/star_wars/images', 'Images source directory should not exist in deployment');

    // Config directory should exist.
    $this->assertDirectoryExists($dir . '/config/default', 'Config directory should exist');

    // Composer.json should exist for autoloading.
    $this->assertFileExists($dir . '/composer.json', 'composer.json should exist');
  }

}
