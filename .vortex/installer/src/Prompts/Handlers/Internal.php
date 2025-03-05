<?php

namespace DrevOps\Installer\Prompts\Handlers;

use DrevOps\Installer\Config\ConfigInterface;
use DrevOps\Installer\Prompts\PromptFields;
use DrevOps\Installer\Util;
use DrevOps\Installer\Utils\Converter;
use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\File;

class Internal extends AbstractHandler {

  public function discover(): ?string {
    // Noop.
    return NULL;
  }

  public function process(): void {
    $this->processStringTokens($responses, $dir);

    $this->processDemoMode($responses, $dir);

    // Remove code required for Vortex maintenance.
    File::removeTokenWithContent('VORTEX_DEV', $dir);

    // Remove all other comments.
    File::removeTokenLine('#;', $dir);

    if (file_exists($dir . DIRECTORY_SEPARATOR . 'README.dist.md')) {
      rename($dir . DIRECTORY_SEPARATOR . 'README.dist.md', $dir . DIRECTORY_SEPARATOR . 'README.md');
    }

    // Remove Vortex internal files.
    File::rmdirRecursive($dir . DIRECTORY_SEPARATOR . '.vortex');

    @unlink($dir . '/.github/FUNDING.yml');
    @unlink($dir . 'CODE_OF_CONDUCT.md');
    @unlink($dir . 'CONTRIBUTING.md');
    @unlink($dir . 'LICENSE');
    @unlink($dir . 'SECURITY.md');

    // Remove Vortex internal GHAs.
    $files = glob($dir . '/.github/workflows/vortex-*.yml');
    if ($files) {
      foreach ($files as $file) {
        @unlink($file);
      }
    }

    // Remove other unhandled tokenized comments.
    File::removeTokenLine('#;<', $dir);
    File::removeTokenLine('#;>', $dir);

    // Enable commented out code.
    File::dirReplaceContent('##### ', '', $dir);

    // Process empty lines.
    $ignore = array_merge(File::ignorePaths(), [
      '/web/sites/default/default.settings.php',
      '/web/sites/default/default.services.yml',
      '/.docker/config/solr/config-set/',
    ]);

    $files = File::scandirRecursive($dir, $ignore);
    foreach ($files as $filename) {
      File::fileReplaceContent('/(\n\s*\n)+/', "\n\n", $filename);
    }
  }

  protected function processStringTokens(array $responses, string $dir): void {
    $machine_name_kebab = Converter::kebab($responses[PromptFields::MACHINE_NAME]);
    $machine_name_pascal = Converter::pascal($responses[PromptFields::MACHINE_NAME]);
    $module_prefix_pascal = Converter::pascal($responses[PromptFields::MODULE_PREFIX]);
    $module_prefix_cobol = Converter::cobol($module_prefix_pascal);
    $theme_pascal = Converter::pascal($responses[PromptFields::THEME]);
    $vortex_version_urlencoded = str_replace('-', '--', (string) $this->config->get(Config::VORTEX_VERSION));
    $webroot = $responses[WebrootCustom::id()];

    // @formatter:off
    // phpcs:disable Generic.Functions.FunctionCallArgumentSpacing.TooMuchSpaceAfterComma
    // phpcs:disable Drupal.WhiteSpace.Comma.TooManySpaces
    File::dirReplaceContent('your_site_theme',           $this->getAnswer('theme'),                     $dir);
    File::dirReplaceContent('YourSiteTheme',             $theme_pascal,                                 $dir);
    File::dirReplaceContent('your_org',                  $this->getAnswer('org_machine_name'),          $dir);
    File::dirReplaceContent('YOURORG',                   $this->getAnswer('org'),                       $dir);
    File::dirReplaceContent('your-site-domain.example',  $this->getAnswer('domain'),                    $dir);
    File::dirReplaceContent('ys_core',                   $this->getAnswer('module_prefix') . '_core',   $dir . sprintf('/%s/modules/custom', $webroot));
    File::dirReplaceContent('ys_search',                 $this->getAnswer('module_prefix') . '_search', $dir . sprintf('/%s/modules/custom', $webroot));
    File::dirReplaceContent('ys_core',                   $this->getAnswer('module_prefix') . '_core',   $dir . sprintf('/%s/themes/custom',  $webroot));
    File::dirReplaceContent('ys_core',                   $this->getAnswer('module_prefix') . '_core',   $dir . '/scripts/custom');
    File::dirReplaceContent('ys_search',                 $this->getAnswer('module_prefix') . '_search', $dir . '/scripts/custom');
    File::dirReplaceContent('YsCore',                    $module_prefix_pascal . 'Core',                $dir . sprintf('/%s/modules/custom', $webroot));
    File::dirReplaceContent('YsSearch',                  $module_prefix_pascal . 'Search',              $dir . sprintf('/%s/modules/custom', $webroot));
    File::dirReplaceContent('YSCODE',                    $module_prefix_cobol,                          $dir);
    File::dirReplaceContent('YSSEARCH',                  $module_prefix_cobol,                          $dir);
    File::dirReplaceContent('your-site',                 $machine_name_kebab,                           $dir);
    File::dirReplaceContent('your_site',                 $this->getAnswer('machine_name'),              $dir);
    File::dirReplaceContent('YOURSITE',                  $this->getAnswer('name'),                      $dir);
    File::dirReplaceContent('YourSite',                  $machine_name_pascal,                          $dir);

    File::replaceStringFilename('YourSiteTheme',         $theme_pascal,                                 $dir);
    File::replaceStringFilename('your_site_theme',       $this->getAnswer('theme'),                     $dir);
    File::replaceStringFilename('YourSite',              $machine_name_pascal,                          $dir);
    File::replaceStringFilename('ys_core',               $this->getAnswer('module_prefix') . '_core',   $dir . sprintf('/%s/modules/custom', $webroot));
    File::replaceStringFilename('ys_search',             $this->getAnswer('module_prefix') . '_search', $dir . sprintf('/%s/modules/custom', $webroot));
    File::replaceStringFilename('YsCore',                $module_prefix_pascal . 'Core',                $dir . sprintf('/%s/modules/custom', $webroot));
    File::replaceStringFilename('your_org',              $this->getAnswer('org_machine_name'),          $dir);
    File::replaceStringFilename('your_site',             $this->getAnswer('machine_name'),              $dir);

    File::dirReplaceContent('VORTEX_VERSION_URLENCODED', $vortex_version_urlencoded,                    $dir);
    File::dirReplaceContent('VORTEX_VERSION',            $this->config->get(Config::VORTEX_VERSION), $dir);
    // @formatter:on
    // phpcs:enable Generic.Functions.FunctionCallArgumentSpacing.TooMuchSpaceAfterComma
    // phpcs:enable Drupal.WhiteSpace.Comma.TooManySpaces
  }


  protected function processDemoMode(array $responses, string $dir): void {
    // @todo Review and refactor this logic.
    if (is_null($this->config->get(Config::IS_DEMO_MODE))) {
      if ($responses[PromptFields::PROVISION_TYPE] === 'database') {
        $download_source = $responses[PromptFields::DATABASE_DOWNLOAD_SOURCE];
        $db_file = Env::get('VORTEX_DB_DIR', './.data') . DIRECTORY_SEPARATOR . Env::get('VORTEX_DB_FILE', 'db.sql');
        $has_comment = File::contains('Override project-specific values for demonstration purposes', $this->config->getDst() . '/.env');

        // Enable Vortex demo mode if download source is file AND
        // there is no downloaded file present OR if there is a demo comment in
        // destination .env file.
        if ($download_source !== 'container_registry') {
          if ($has_comment || !file_exists($db_file)) {
            $this->config->set(Config::IS_DEMO_MODE, TRUE);
          }
          else {
            $this->config->set(Config::IS_DEMO_MODE, FALSE);
          }
        }
        elseif ($has_comment) {
          $this->config->set(Config::IS_DEMO_MODE, TRUE);
        }
        else {
          $this->config->set(Config::IS_DEMO_MODE, FALSE);
        }
      }
      else {
        $this->config->set(Config::IS_DEMO_MODE, FALSE);
      }
    }

    if (!$this->config->get(Config::IS_DEMO_MODE)) {
      File::removeTokenWithContent('DEMO', $dir);
    }
  }

}
