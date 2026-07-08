<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use AlexSkrypnyk\File\Replacer\Replacement;
use DrevOps\Tui\Builder\FieldBuilder;
use DrevOps\Tui\Builder\PanelBuilder;
use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Utils\File;

/**
 * Handler for the "modules" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class Modules extends AbstractHandler implements OptionsInterface, FieldInterface {

  /**
   * {@inheritdoc}
   */
  public function process(Field $field, mixed $value, Context $context): void {
    $selected_modules = is_array($value) ? array_values(array_filter($value, is_string(...))) : [];
    $all_modules = static::getAvailableModules();

    $t = $context->directory;
    $webroot = is_string($context->answers['webroot'] ?? NULL) ? $context->answers['webroot'] : 'web';

    // Process each module that was NOT selected (remove them).
    foreach (array_keys($all_modules) as $module_name) {
      if (!in_array($module_name, $selected_modules, TRUE)) {
        // Remove from composer.json.
        $pattern = '/\s*"drupal\/' . preg_quote($module_name, '/') . '":\s*"[^\"]+",?\n/';
        File::replaceContentInFile($t . '/composer.json', $pattern, "\n");

        // Remove module from settings file.
        File::remove($t . '/' . $webroot . '/sites/default/includes/modules/settings.' . $module_name . '.php');

        // Remove module from the development setup plugin install list.
        File::replaceContentInFile($t . '/' . $webroot . '/modules/custom/ys_base/src/Plugin/DeployStep/EnableDevelopmentModulesDeployStep.php', Replacement::create('module', function (string $content) use ($module_name): string {
          $pattern = "/^\s*'" . preg_quote($module_name, '/') . "',\r?\n/m";
          return preg_replace($pattern, '', $content, 1) ?? $content;
        }));

        // Remove module from the Behat tests.
        File::remove($t . '/tests/behat/features/' . $module_name . '.feature');

        // Remove module from the config tests.
        $pattern = '/\s*\$config\[\'' . preg_quote($module_name, '/') . '\..*;(\r?\n)?/';
        File::removeLineInFile($t . '/tests/phpunit/Drupal/EnvironmentSettingsTest.php', $pattern);

        // Remove module tokens.
        File::removeTokenAsync('MODULE_' . strtoupper($module_name));
      }
    }

    if (count($selected_modules) === 0) {
      File::removeTokenAsync('MODULE');
    }
  }

  /**
   * Get the full list of available Drupal contributed modules.
   *
   * This list excludes Drupal core modules and service modules.
   *
   * @return array<string, string>
   *   Array of module machine names as keys and labels as values.
   */
  public static function getAvailableModules(): array {
    return [
      'admin_toolbar' => 'Admin toolbar',
      'coffee' => 'Coffee',
      'config_split' => 'Config split',
      'config_update' => 'Config update',
      'devel' => 'Devel',
      'drupal_helpers' => 'Drupal helpers',
      'environment_indicator' => 'Environment indicator',
      'generated_content' => 'Generated content',
      'pathauto' => 'Pathauto',
      'redirect' => 'Redirect',
      'reroute_email' => 'Reroute email',
      'robotstxt' => 'Robots.txt',
      'sdc_devel' => 'SDC Devel',
      'seckit' => 'Seckit',
      'shield' => 'Shield',
      'stage_file_proxy' => 'Stage file proxy',
      'testmode' => 'Testmode',
      'xmlsitemap' => 'XML Sitemap',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function options(): array {
    return [
      'admin_toolbar' => 'Admin toolbar',
      'coffee' => 'Coffee',
      'config_split' => 'Config split',
      'config_update' => 'Config update',
      'devel' => 'Devel',
      'drupal_helpers' => 'Drupal helpers',
      'environment_indicator' => 'Environment indicator',
      'generated_content' => 'Generated content',
      'pathauto' => 'Pathauto',
      'redirect' => 'Redirect',
      'reroute_email' => 'Reroute email',
      'robotstxt' => 'Robots.txt',
      'sdc_devel' => 'SDC Devel',
      'seckit' => 'Seckit',
      'shield' => 'Shield',
      'stage_file_proxy' => 'Stage file proxy',
      'testmode' => 'Testmode',
      'xmlsitemap' => 'XML Sitemap',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function field(PanelBuilder $p): FieldBuilder {
    return $p->multiselect('modules', 'Modules')
      ->description('Optional contributed modules to include.')
      ->default(array_keys(self::options()))
      ->options(self::options())
      ->weight(240);
  }

}
