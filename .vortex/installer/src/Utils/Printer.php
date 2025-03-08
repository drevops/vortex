<?php

declare(strict_types=1);

namespace DrevOps\Installer\Utils;

use DrevOps\Installer\Prompts\Handlers\AssignAuthorPr;
use DrevOps\Installer\Prompts\Handlers\CiProvider;
use DrevOps\Installer\Prompts\Handlers\CodeProvider;
use DrevOps\Installer\Prompts\Handlers\DatabaseDownloadSource;
use DrevOps\Installer\Prompts\Handlers\DatabaseImage;
use DrevOps\Installer\Prompts\Handlers\DependencyUpdatesProvider;
use DrevOps\Installer\Prompts\Handlers\DeployType;
use DrevOps\Installer\Prompts\Handlers\Domain;
use DrevOps\Installer\Prompts\Handlers\GithubRepo;
use DrevOps\Installer\Prompts\Handlers\GithubToken;
use DrevOps\Installer\Prompts\Handlers\HostingProvider;
use DrevOps\Installer\Prompts\Handlers\LabelMergeConflictsPr;
use DrevOps\Installer\Prompts\Handlers\MachineName;
use DrevOps\Installer\Prompts\Handlers\ModulePrefix;
use DrevOps\Installer\Prompts\Handlers\Name;
use DrevOps\Installer\Prompts\Handlers\Org;
use DrevOps\Installer\Prompts\Handlers\OrgMachineName;
use DrevOps\Installer\Prompts\Handlers\PreserveDocsOnboarding;
use DrevOps\Installer\Prompts\Handlers\PreserveDocsProject;
use DrevOps\Installer\Prompts\Handlers\Profile;
use DrevOps\Installer\Prompts\Handlers\ProvisionType;
use DrevOps\Installer\Prompts\Handlers\Theme;
use DrevOps\Installer\Prompts\Handlers\Webroot;
use Laravel\Prompts\Terminal;
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
    $values['🔖 Site name'] = $responses[Name::id()];
    $values['🔖 Site machine name'] = $responses[MachineName::id()];
    $values['🏢 Organization name'] = $responses[Org::id()];
    $values['🏢 Organization machine name'] = $responses[OrgMachineName::id()];
    $values['🌐 Public domain'] = $responses[Domain::id()];

    $values['Code repository'] = static::SECTION_TITLE;
    $values['Code provider'] = $responses[CodeProvider::id()];

    if (!empty($responses[GithubToken::id()])) {
      $values['🔑 GitHub access token'] = 'valid';
    }
    $values['GitHub repository'] = $responses[GithubRepo::id()] ?? '<empty>';

    $values['Drupal'] = static::SECTION_TITLE;
    $values['📁 Webroot'] = $responses[Webroot::id()];
    $values['Profile'] = $responses[Profile::id()];

    $values['🧩 Module prefix'] = $responses[ModulePrefix::id()];
    $values['🎨 Theme machine name'] = $responses[Theme::id()] ?? '<empty>';

    $values['Hosting'] = static::SECTION_TITLE;
    $values['🏠 Hosting provider'] = $responses[HostingProvider::id()];

    $values['Deployment'] = static::SECTION_TITLE;
    $values['🚚 Deployment types'] = Converter::toList($responses[DeployType::id()]);

    $values['Workflow'] = static::SECTION_TITLE;
    $values['Provision type'] = $responses[ProvisionType::id()];

    if ($responses[ProvisionType::id()] == ProvisionType::DATABASE) {
      $values['Database dump source'] = $responses[DatabaseDownloadSource::id()];

      if ($responses[DatabaseDownloadSource::id()] == DatabaseDownloadSource::CONTAINER_REGISTRY) {
        $values['Database container image'] = $responses[DatabaseImage::id()];
      }
    }

    $values['Continuous Integration'] = static::SECTION_TITLE;
    $values['♻️️CI provider'] = $responses[CiProvider::id()];

    $values['Automations'] = static::SECTION_TITLE;
    $values['⬆️ Dependency updates provider'] = $responses[DependencyUpdatesProvider::id()];
    $values['👤 Auto-assign PR author'] = static::formatYesNo($responses[AssignAuthorPr::id()]);
    $values['🎫 Auto-add a <info>CONFLICT</info> label to PRs'] = static::formatYesNo($responses[LabelMergeConflictsPr::id()]);

    $values['Documentation'] = static::SECTION_TITLE;
    $values['📚 Preserve project documentation'] = static::formatYesNo($responses[PreserveDocsProject::id()]);
    $values['📋 Preserve onboarding checklist'] = static::formatYesNo($responses[PreserveDocsOnboarding::id()]);

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
