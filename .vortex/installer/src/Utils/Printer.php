<?php

declare(strict_types=1);

namespace DrevOps\Installer\Utils;

use DrevOps\Installer\Prompts\PromptFields;
use Laravel\Prompts\Terminal;
use Symfony\Component\Console\Helper\TableSeparator;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\table;

class Printer {

  const SECTION_TITLE = '---SECTION_TITLE---';

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

    [$repo, $ref] = Downloader::parseUri($config->get(Config::REPO_URI));
    if ($ref == 'stable') {
      $content .= 'This will install the latest version of Vortex into your project.' . PHP_EOL;
    }
    else {
      $content .= sprintf('This will install Vortex into your project at commit "%s".', $ref) . PHP_EOL;
    }

    $content .= PHP_EOL;
    if ($config->isVortexProject()) {
      $content .= 'It looks like Vortex is already installed into this project.' . PHP_EOL;
      $content .= PHP_EOL;
    }

    $content .= 'Vortex installer will try to discover the settings from the environment and will install configuration relevant to your site.' . PHP_EOL;
    $content .= PHP_EOL;
    $content .= 'Existing committed files will be modified. You will need to resolve changes manually.' . PHP_EOL;

    $this->printBox($content, 'Welcome to Vortex ' . static::bold('quiet') . ' installer');
  }

  protected function headerInteractive(Config $config): void {
    $content = '';

    [$repo, $ref] = Downloader::parseUri($config->get(Config::REPO_URI));
    if ($ref == 'stable') {
      $content .= 'This will install the latest version of Vortex into your project.' . PHP_EOL;
    }
    else {
      $content .= sprintf('This will install Vortex into your project at commit "%s".', $ref) . PHP_EOL;
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

    $this->printBox($content, 'Welcome to Vortex ' . static::bold('interactive') . ' installer');
  }

  public function summary(Config $config, array $responses): void {
    $values['General information'] = static::SECTION_TITLE;
    $values['🔖 Site name'] = $responses[PromptFields::NAME];
    $values['🔖 Site machine name'] = $responses[PromptFields::MACHINE_NAME];
    $values['🏢 Organization name'] = $responses[PromptFields::ORG];
    $values['🏢 Organization machine name'] = $responses[PromptFields::ORG_MACHINE_NAME];
    $values['🌐 Public domain'] = $responses[PromptFields::DOMAIN];

    $values['Code repository'] = static::SECTION_TITLE;
    $values['Code provider'] = $responses[PromptFields::CODE_PROVIDER];
    if (PromptFields::GITHUB_TOKEN) {
      $values['🔑 GitHub access token'] = 'valid';
    }
    if (PromptFields::GITHUB_REPO) {
      $values['GitHub repository'] = $responses[PromptFields::GITHUB_REPO];
    }

    $values['Drupal'] = static::SECTION_TITLE;
    $values['📁 Webroot'] = $responses[PromptFields::WEBROOT_CUSTOM];
    $values['Use a custom profile'] = static::formatYesNo($responses[PromptFields::USE_CUSTOM_PROFILE]);
    if ($responses[PromptFields::USE_CUSTOM_PROFILE]) {
      $values['Profile'] = $responses[PromptFields::PROFILE];
    }
    $values['🧩 Module prefix'] = $responses[PromptFields::MODULE_PREFIX];
    $values['🎨 Theme machine name'] = $responses[PromptFields::THEME];

    $values['Hosting'] = static::SECTION_TITLE;
    $values['🏠 Hosting provider'] = $responses[PromptFields::HOSTING_PROVIDER];

    $values['Deployment'] = static::SECTION_TITLE;
    $values['🚚 Deployment types'] = $responses[PromptFields::DEPLOY_TYPE];

    $values['Workflow'] = static::SECTION_TITLE;
    $values['Provision type'] = $responses[PromptFields::PROVISION_TYPE];

    if ($responses[PromptFields::PROVISION_TYPE] == 'database') {
      $values['Database dump source'] = $responses[PromptFields::DATABASE_DOWNLOAD_SOURCE];

      if ($responses[PromptFields::DATABASE_DOWNLOAD_SOURCE] == 'container_registry') {
        $values['Database container image'] = $responses[PromptFields::DATABASE_STORE_TYPE_CONTAINER_IMAGE];
      }
    }

    $values['Continuous Integration'] = static::SECTION_TITLE;
    $values['🔁 CI provider'] = $responses[PromptFields::CI_PROVIDER];

    $values['Automations'] = static::SECTION_TITLE;
    $values['🔄 Dependency updates provider'] = $responses[PromptFields::DEPENDENCY_UPDATES_PROVIDER];
    $values['👤 Auto-assign PR author'] = static::formatYesNo($responses[PromptFields::ASSIGN_AUTHOR_PR]);
    $values['🎫 Auto-add a <info>CONFLICT</info> label to PRs'] = static::formatYesNo($responses[PromptFields::LABEL_MERGE_CONFLICTS_PR]);

    $values['Documentation'] = static::SECTION_TITLE;
    $values['📚 Preserve project documentation'] = static::formatYesNo($responses[PromptFields::DOCS_PROJECT]);
    $values['📋 Preserve onboarding checklist'] = static::formatYesNo($responses[PromptFields::DOCS_ONBOARDING]);

    $values['Locations'] = static::SECTION_TITLE;
    $values['Current directory'] = $config->getRoot();
    $values['Destination directory'] = $config->getDst();
    $values['Vortex repository'] = $config->get(Config::REPO_URI);

    $this->printList($values, 'Installation summary');
  }

  protected function printBox(string $content, ?string $title = NULL, int $width = 80): void {
    $rows = [];

    $width = min($width, $this->terminalWidth);
    $content = wordwrap($content, $width - 4, PHP_EOL, TRUE);

    if ($title) {
      $rows[] = [static::green($title)];
      $rows[] = [static::green(str_repeat('-', static::strlenPlain($title))) . PHP_EOL];
    }
    $rows[] = [$content];

    table([], $rows);
  }

  protected function printList(array $values, ?string $title): void {
    foreach ($values as $key => $value) {
      if (is_array($value)) {
        $values[$key] = implode(', ', $value);
      }
    }

    $header = [];
    $rows = [];
    foreach ($values as $key => $value) {
      if ($value === self::SECTION_TITLE) {
        $rows[] = [static::cyan(static::bold($key))];
        continue;
      }

      $rows[] = ['  ' . $key, $value];
    }

    if ($title) {
      //      $header[] = static::undim(static::bgGreen($title));
    }

    note(PHP_EOL . $title . PHP_EOL, 'intro');
    table($header, $rows);
  }

  protected static function formatYesNo(string|bool|int $value): string {
    return $value === '1' || $value === 1 || $value === TRUE ? 'Yes' : 'No';
  }

  /**
   * Set the text color to green.
   *
   * @param string $text
   *
   * @return string
   */
  public static function green(string $text): string {
    return "\e[32m{$text}\e[39m";
  }

  public static function bgGreen(string $text): string {
    return "\e[42m{$text}\e[49m";
  }

  public static function cyan(string $text): string {
    return "\e[36m{$text}\e[39m";
  }

  public static function bgCyan(string $text): string {
    return "\e[46m{$text}\e[49m";
  }

  public static function bold(string $text): string {
    return "\e[1m{$text}\e[22m";
  }

  /**
   * Undim the text by resetting intensity.
   */
  protected static function undim(string $text): string {
    return "\e[22m{$text}\e[22m";
  }

  protected static function strlenPlain(string $text): int {
    return strlen(preg_replace('/\e\[\d+m/', '', $text));
  }

}
