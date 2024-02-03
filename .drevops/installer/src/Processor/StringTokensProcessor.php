<?php

namespace DrevOps\Installer\Processor;

use DrevOps\Installer\Bag\Config;
use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\Files;
use Symfony\Component\Console\Output\OutputInterface;
use function Symfony\Component\String\u;

/**
 * String tokens processor.
 */
class StringTokensProcessor extends AbstractProcessor {

  /**
   * {@inheritdoc}
   */
  protected static $weight = 300;

  /**
   * {@inheritdoc}
   */
  public function run(Config $config, string $dir, OutputInterface $output): void {
    $machine_name_hyphenated = str_replace('_', '-', (string) $config->get('machine_name'));
    $machine_name_camel_cased = u($config->get('machine_name'))->camel()->title();
    $module_prefix_camel_cased = u($config->get('module_prefix'))->camel()->title();
    $module_prefix_uppercase = u($module_prefix_camel_cased)->upper();
    $theme_camel_cased = u($config->get('theme'))->camel()->title();
    $drevops_version_urlencoded = str_replace('-', '--', (string) $config->get(Env::DREVOPS_VERSION));

    $webroot = $config->get('webroot');

    // @formatter:off
    // phpcs:disable Generic.Functions.FunctionCallArgumentSpacing.TooMuchSpaceAfterComma
    // phpcs:disable Drupal.WhiteSpace.Comma.TooManySpaces
    Files::dirReplaceContent('your_site_theme',       $config->get('theme'),                   $dir);
    Files::dirReplaceContent('YourSiteTheme',         $theme_camel_cased,                           $dir);
    Files::dirReplaceContent('your_org',              $config->get('org_machine_name'),        $dir);
    Files::dirReplaceContent('YOURORG',               $config->get('org'),                     $dir);
    Files::dirReplaceContent('your-site-url.example', $config->get('url'),                     $dir);
    Files::dirReplaceContent('ys_core',               $config->get('module_prefix') . '_core', $dir . sprintf('/%s/modules/custom', $webroot));
    Files::dirReplaceContent('ys_core',               $config->get('module_prefix') . '_core', $dir . '/scripts/custom');
    Files::dirReplaceContent('YsCore',                $module_prefix_camel_cased . 'Core',          $dir . sprintf('/%s/modules/custom', $webroot));
    Files::dirReplaceContent('YSCODE',                $module_prefix_uppercase,                     $dir);
    Files::dirReplaceContent('your-site',             $machine_name_hyphenated,                     $dir);
    Files::dirReplaceContent('your_site',             $config->get('machine_name'),            $dir);
    Files::dirReplaceContent('YOURSITE',              $config->get('name'),                    $dir);
    Files::dirReplaceContent('YourSite',              $machine_name_camel_cased,                    $dir);

    Files::replaceStringFilename('YourSiteTheme',     $theme_camel_cased,                           $dir);
    Files::replaceStringFilename('your_site_theme',   $config->get('theme'),                   $dir);
    Files::replaceStringFilename('YourSite',          $machine_name_camel_cased,                    $dir);
    Files::replaceStringFilename('ys_core',           $config->get('module_prefix') . '_core', $dir . sprintf('/%s/modules/custom', $webroot));
    Files::replaceStringFilename('YsCore',            $module_prefix_camel_cased . 'Core',          $dir . sprintf('/%s/modules/custom', $webroot));
    Files::replaceStringFilename('your_org',          $config->get('org_machine_name'),        $dir);
    Files::replaceStringFilename('your_site',         $config->get('machine_name'),            $dir);

    Files::dirReplaceContent(Env::DREVOPS_VERSION_URLENCODED, $drevops_version_urlencoded,             $dir);
    Files::dirReplaceContent(Env::DREVOPS_VERSION,            $config->get(Env::DREVOPS_VERSION),   $dir);
    // @formatter:on
    // phpcs:enable Generic.Functions.FunctionCallArgumentSpacing.TooMuchSpaceAfterComma
    // phpcs:enable Drupal.WhiteSpace.Comma.TooManySpaces
  }

}
