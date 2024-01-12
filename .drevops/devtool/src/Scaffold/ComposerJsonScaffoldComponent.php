<?php

namespace DrevOps\DevTool\Scaffold;

use DrevOps\DevTool\Composer\ComposerJsonManipulator;
use DrevOps\DevTool\Docker\DockerCommand;
use DrevOps\DevTool\Docker\DockerfileParser;

/**
 * Class ComposerJsonScaffoldComponent.
 *
 * Scaffold component for composer.json.
 *
 * @package DrevOps\DevTool\Scaffold
 */
class ComposerJsonScaffoldComponent extends AbstractScaffoldComponent {

  /**
   * {@inheritdoc}
   */
  protected function resourceUrls():array {
    return [
      'composer.json' => 'https://raw.githubusercontent.com/drupal-composer/drupal-project/10.x/composer.json',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function processResources() {
    $manipulator = new ComposerJsonManipulator(file_get_contents($this->files['composer.json']));

    $this->alter($manipulator);

    $manipulator->save($this->files['composer.json']);
  }

  /**
   * Alter composer.json.
   *
   * @param \DrevOps\DevTool\Composer\ComposerJsonManipulator $m
   *   ComposerJsonManipulator instance.
   */
  protected function alter(ComposerJsonManipulator $m) {
    $m->addProperty('name', 'your_org/your_site');
    $m->addProperty('description', 'Drupal 10 implementation of YOURSITE for YOURORG');
    $m->addProperty('license', 'proprietary');
    $m->removeProperty('authors');

    $m->addRepository('asset-packagist', [
      'type' => 'composer',
      'url' => 'https://asset-packagist.org',
    ]);

    $m->addDependency('php', '>=8.1');
    $m->addDependency('drupal/admin_toolbar', '^3.1');
    $m->addDependency('drupal/clamav', '^2.0.2');
    $m->addDependency('drupal/coffee', '^1.2');
    $m->addDependency('drupal/config_split', '^1');
    $m->addDependency('drupal/config_update', '^2@alpha');
    $m->addDependency('drupal/environment_indicator', '^4.0');
    $m->addDependency('drupal/pathauto', '^1.10');
    $m->addDependency('drupal/redirect', '^1.7');
    $m->addDependency('drupal/redis', '^1.6');
    $m->addDependency('drupal/search_api', '^1.29');
    $m->addDependency('drupal/search_api_solr', '^4.2');
    $m->addDependency('drupal/shield', '^1.6');
    $m->addDependency('drupal/stage_file_proxy', '^2');
    $m->addDependency('drush/drush', '^12');
    $m->addDependency('oomphinc/composer-installers-extender', '^2.0');

    $m->addDevDependency('behat/behat', '^3.10');
    $m->addDevDependency('dealerdirect/phpcodesniffer-composer-installer', '^0.7');
    $m->addDevDependency('drevops/behat-format-progress-fail', '^1');
    $m->addDevDependency('drevops/behat-screenshot', '^1');
    $m->addDevDependency('drevops/behat-steps', '^2');
    $m->addDevDependency('drupal/drupal-extension', '^5@rc');
    $m->addDevDependency('friendsoftwig/twigcs', '^6.2');
    $m->addDevDependency('mglaman/phpstan-drupal', '^1.2');
    $m->addDevDependency('palantirnet/drupal-rector', '^0.18');
    $m->addDevDependency('phpcompatibility/php-compatibility', '^9.3');
    $m->addDevDependency('phpmd/phpmd', '^2.13');
    $m->addDevDependency('phpspec/prophecy-phpunit', '^2.0');
    $m->addDevDependency('phpstan/extension-installer', '^1.3');
    $m->addDevDependency('pyrech/composer-changelogs', '^1.8');

    $m->addProperty('minimum-stability', 'stable');

    $m->addConfigSetting('platform', ['php' => $this->getPlatformPhpVersion() ?: '8.2.13']);

    $m->mergeProperty('config.allow-plugins', [
      'oomphinc/composer-installers-extender' => TRUE,
      'pyrech/composer-changelogs' => TRUE,
    ], TRUE);

    // @see https://github.com/drevops/drevops/issues/806
    $m->removeSubNode('autoload', 'files');

    $m->addPropertyAfter('autoload-dev', [
      'classmap' => [
        'tests/phpunit/',
      ],
    ], 'autoload');

    $m->mergeProperty('extra.drupal-scaffold', [
      'file-mapping' => [
        '[project-root]/.editorconfig' => FALSE,
        '[project-root]/.gitattributes' => FALSE,
        '[web-root]/.htaccess' => FALSE,
        '[web-root]/.ht.router.php' => FALSE,
        '[web-root]/example.gitignore' => FALSE,
        '[web-root]/INSTALL.txt' => FALSE,
        '[web-root]/README.txt' => FALSE,
        '[web-root]/sites/example.settings.local.php' => FALSE,
        '[web-root]/sites/example.sites.php' => FALSE,
        '[web-root]/web.config' => FALSE,
      ],
    ]);

    $m->mergeProperty('extra.installer-paths', [
      'web/libraries/{$name}' => [
        'type:bower-asset',
        'type:npm-asset',
      ],
      'web/modules/custom/{$name}' => [
        'type:drupal-custom-module',
      ],
      'web/themes/custom/{$name}' => [
        'type:drupal-custom-theme',
      ],
    ]);

    $m->addPropertyAfter('extra.installer-types', [
      "bower-asset",
      "npm-asset",
      "drupal-library",
    ], 'extra.installer-paths');
  }

  /**
   * Get the PHP version from the platform.
   */
  protected function getPlatformPhpVersion() {
    $version = NULL;

    // Parse the CLI Dockerfile for commands.
    $commands = DockerfileParser::parse($this->rootDir . '/.docker/cli.dockerfile');
    $from_commands = array_filter($commands, static function (DockerCommand $command) : bool {
        return $command->getKeyword() === 'FROM';
    });

    if (!empty($from_commands)) {
      $from_command = reset($from_commands);
      $image_name = $from_command->getArguments();

      // Get the PHP version from the image.
      $output = shell_exec('docker run --rm ' . $image_name . ' bash -c "echo \$PHP_VERSION"');

      // Parse the version number from the output.
      if (!empty($output) && !preg_match('/\b\d+\.\d+\.\d+\b/', $output, $matches)) {
        // Returns the first match which should be the version number.
        $version = $matches[0];
      }
    }

    return $version;
  }

}
