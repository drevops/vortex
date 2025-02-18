<?php

declare(strict_types=1);

namespace DrevOps\Installer\Utils;

use DrevOps\Installer\Prompts\PromptFields;
use Laravel\Prompts\Terminal;
use function Laravel\Prompts\note;
use function Laravel\Prompts\table;

class Printer {

  const EMPTY_LINE = '---EMPTY---';

  protected $output;

  /**
   * Width of the TUI.
   */
  protected int $terminalWidth;

  public function __construct() {
    $this->terminalWidth = (new Terminal())->cols();
  }

  public function header(Config $config): void {
    $logo = <<<EOT
-------------------------------------------------------------------------------

              ██╗   ██╗ ██████╗ ██████╗ ████████╗███████╗██╗  ██╗
              ██║   ██║██╔═══██╗██╔══██╗╚══██╔══╝██╔════╝╚██╗██╔╝
              ██║   ██║██║   ██║██████╔╝   ██║   █████╗   ╚███╔╝
              ╚██╗ ██╔╝██║   ██║██╔══██╗   ██║   ██╔══╝   ██╔██╗
               ╚████╔╝ ╚██████╔╝██║  ██║   ██║   ███████╗██╔╝ ██╗
                ╚═══╝   ╚═════╝ ╚═╝  ╚═╝   ╚═╝   ╚══════╝╚═╝  ╚═╝

                           Drupal project template

                                                                   by DrevOps
-------------------------------------------------------------------------------
EOT;

    // Print the logo only if the terminal is wide enough.
    if ($this->terminalWidth >= 80) {
      note(static::green($logo));
    }

    if ($config->isQuiet()) {
      $this->headerQuiet($config);
    }
    else {
      $this->headerInteractive($config);
    }
  }

  protected function headerQuiet(Config $config): void {
    $content = '';

    $commit = $config->get('VORTEX_INSTALL_COMMIT');
    if ($commit == 'HEAD') {
      $content .= 'This will install the latest version of Vortex into your project.' . PHP_EOL;
    }
    else {
      $content .= sprintf('This will install Vortex into your project at commit "%s".', $commit) . PHP_EOL;
    }

    $content .= PHP_EOL;
    if ($config->isVortexProject()) {
      $content .= 'It looks like Vortex is already installed into this project.' . PHP_EOL;
      $content .= PHP_EOL;
    }

    $content .= 'Vortex installer will try to discover the settings from the environment and will install configuration relevant to your site.' . PHP_EOL;
    $content .= PHP_EOL;
    $content .= 'Existing committed files will be modified. You will need to resolve changes manually.' . PHP_EOL;

    table(['Welcome to Vortex quiet installer'], [$content]);
  }

  protected function headerInteractive(Config $config): void {
    $content = '';

    $commit = $config->get('VORTEX_INSTALL_COMMIT');
    if ($commit == 'HEAD') {
      $content .= 'This will install the latest version of Vortex into your project.' . PHP_EOL;
    }
    else {
      $content .= sprintf('This will install Vortex into your project at commit "%s".', $commit) . PHP_EOL;
    }
    $content .= PHP_EOL;

    if ($config->isVortexProject()) {
      $content .= 'It looks like Vortex is already installed into this project.' . PHP_EOL;
      $content .= PHP_EOL;
    }

    $content .= 'Please answer the questions below to install configuration relevant to your site.' . PHP_EOL;
    $content .= 'No changes will be applied until the last confirmation step.' . PHP_EOL;
    $content .= PHP_EOL;
    $content .= 'Existing committed files will be modified. You will need to resolve changes manually.' . PHP_EOL;
    $content .= PHP_EOL;
    $content .= 'Press Ctrl+C at any time to exit this installer.' . PHP_EOL;

    table(['Welcome to Vortex interactive installer'], [$content]);
  }

  public function summary(Config $config, array $responses): void {
    $values['General information'] = '';
    $values['Current directory'] = $config->getRoot();
    $values['Destination directory'] = $config->getDst();
    $values['Vortex version'] = $config->get('VORTEX_VERSION');
    $values['Vortex commit'] = $config->get('VORTEX_INSTALL_COMMIT', 'Latest');

    $values[] = self::EMPTY_LINE;

    $values['🔖 Site name'] = $responses[PromptFields::NAME];
    $values['🔖 Site machine name'] = $responses[PromptFields::MACHINE_NAME];
    $values['🏢 Organization name'] = $responses[PromptFields::ORG];
    $values['🏢 Organization machine name'] = $responses[PromptFields::ORG_MACHINE_NAME];
    $values['🌐 Public domain'] = $responses[PromptFields::DOMAIN];

    $values['Code repository'] = '';
    $values['Code provider'] = $responses[PromptFields::CODE_PROVIDER];
    if (PromptFields::GITHUB_TOKEN) {
      $values['🔑 GitHub access token'] = 'valid';
    }
    if (PromptFields::GITHUB_REPO) {
      $values['GitHub repository'] = $responses[PromptFields::GITHUB_REPO];
    }

    $values['Drupal'] = '';
    $values['📁 Webroot'] = $responses[PromptFields::WEBROOT_CUSTOM];
    $values['Use a custom profile'] = static::formatYesNo($responses[PromptFields::USE_CUSTOM_PROFILE]);
    if ($responses[PromptFields::USE_CUSTOM_PROFILE]) {
      $values['Profile'] = $responses[PromptFields::PROFILE];
    }
    $values['🧩 Module prefix'] = $responses[PromptFields::MODULE_PREFIX];
    $values['🎨 Theme machine name'] = $responses[PromptFields::THEME];

    $values['Hosting'] = '';
    $values['🏠 Hosting provider'] = $responses[PromptFields::HOSTING_PROVIDER];

    $values['Deployment'] = '';
    $values['🚚 Deployment types'] = $responses[PromptFields::DEPLOY_TYPE];

    $values['Workflow'] = '';
    $values['Provision type'] = $responses[PromptFields::PROVISION_TYPE];

    if ($responses[PromptFields::PROVISION_TYPE] == 'database') {
      $values['Database dump source'] = $responses[PromptFields::DATABASE_DOWNLOAD_SOURCE];

      if ($responses[PromptFields::DATABASE_DOWNLOAD_SOURCE] == 'container_registry') {
        $values['Database container image'] = $responses[PromptFields::DATABASE_STORE_TYPE_CONTAINER_IMAGE];
      }
    }

    $values['Continuous Integration'] = '';
    $values['CI provider'] = $responses[PromptFields::CI_PROVIDER];

    $values['Automations'] = '';
    $values['🔄 Dependency updates provider'] = $responses[PromptFields::DEPENDENCY_UPDATES_PROVIDER];
    $values['👤 Auto-assign the author to their PR'] = static::formatYesNo($responses[PromptFields::ASSIGN_AUTHOR_PR]);
    $values['🎫 Auto-add a <info>CONFLICT</info> label to a PR when conflicts occur'] = static::formatYesNo($responses[PromptFields::LABEL_MERGE_CONFLICTS_PR]);

    $values['Documentation'] = '';
    $values['📚 Preserve project documentation'] = static::formatYesNo($responses[PromptFields::DOCS_PROJECT]);
    $values['📋 Preserve onboarding checklist'] = static::formatYesNo($responses[PromptFields::DOCS_ONBOARDING]);

    static::printList($values);
  }

  protected static function printList(array $values): void {
    $rows = array_map(function ($key, $value) {
      return $value == Printer::EMPTY_LINE ? [] : [$key, $value];
    }, array_keys($values), array_values($values));

    table([], $rows);
  }

  protected static function formatYesNo(string $value): string {
    return $value === '1' || $value === 1 || $value === TRUE ? 'Yes' : 'No';
  }

  /**
   * Set the text color to green.
   */
  public function green(string $text): string
  {
    return "\e[32m{$text}\e[39m";
  }

}
