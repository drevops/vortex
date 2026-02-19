<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts;

use DrevOps\VortexInstaller\Downloader\Artifact;
use DrevOps\VortexInstaller\Prompts\Handlers\Starter;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\Strings;
use DrevOps\VortexInstaller\Utils\Tui;

/**
 * Presents installer headers, footers, and post-build messages.
 *
 * @package DrevOps\VortexInstaller\Prompts
 */
class InstallerPresenter {

  const BUILD_RESULT_SUCCESS = 'success';

  const BUILD_RESULT_SKIPPED = 'skipped';

  const BUILD_RESULT_FAILED = 'failed';

  /**
   * The prompt manager.
   */
  protected ?PromptManager $promptManager = NULL;

  public function __construct(
    protected Config $config,
  ) {}

  public function setPromptManager(PromptManager $prompt_manager): void {
    $this->promptManager = $prompt_manager;
  }

  public function header(Artifact $artifact, string $version): void {
    $logo_large = <<<EOT

██╗   ██╗  ██████╗  ██████╗  ████████╗ ███████╗ ██╗  ██╗
██║   ██║ ██╔═══██╗ ██╔══██╗ ╚══██╔══╝ ██╔════╝ ╚██╗██╔╝
██║   ██║ ██║   ██║ ██████╔╝    ██║    █████╗    ╚███╔╝
╚██╗ ██╔╝ ██║   ██║ ██╔══██╗    ██║    ██╔══╝    ██╔██╗
 ╚████╔╝  ╚██████╔╝ ██║  ██║    ██║    ███████╗ ██╔╝ ██╗
  ╚═══╝    ╚═════╝  ╚═╝  ╚═╝    ╚═╝    ╚══════╝ ╚═╝  ╚═╝

               Drupal project template

                                              by DrevOps
EOT;

    $logo_small = <<<EOT
▗▖  ▗▖ ▗▄▖ ▗▄▄▖▗▄▄▄▖▗▄▄▄▖▗▖  ▗▖
▐▌  ▐▌▐▌ ▐▌▐▌ ▐▌ █  ▐▌    ▝▚▞▘
▐▌  ▐▌▐▌ ▐▌▐▛▀▚▖ █  ▐▛▀▀▘  ▐▌
 ▝▚▞▘ ▝▚▄▞▘▐▌ ▐▌ █  ▐▙▄▄▖▗▞▘▝▚▖

   Drupal project template

                     by DrevOps
EOT;

    $max_header_width = 200;

    $logo = Tui::terminalWidth() >= 80 ? $logo_large : $logo_small;
    $logo = Tui::center($logo, Tui::terminalWidth($max_header_width), '─');
    $logo = Tui::cyan($logo);

    // Depending on how the installer is run, the version may be set to
    // the placeholder value or actual version (PHAR packager will replace
    // the placeholder with the actual version).
    // We need to fence the replacement below only if the version is still set
    // to the placeholder value.
    if (str_contains($version, 'vortex-installer-version')) {
      $version = str_replace('@vortex-installer-version@', 'development', $version);
    }

    $logo .= PHP_EOL . Tui::dim(str_pad(sprintf('Installer version: %s', $version), Tui::terminalWidth($max_header_width) - 2, ' ', STR_PAD_LEFT));

    Tui::note($logo);

    $title = 'Welcome to the Vortex interactive installer';
    $content = '';

    if ($artifact->isStable()) {
      $content .= 'This tool will guide you through installing the latest ' . Tui::underscore('stable') . ' version of Vortex into your project.' . PHP_EOL;
    }
    elseif ($artifact->isDevelopment()) {
      $content .= 'This tool will guide you through installing the latest ' . Tui::underscore('development') . ' version of Vortex into your project.' . PHP_EOL;
    }
    else {
      $content .= sprintf('This tool will guide you through installing a ' . Tui::underscore('custom') . ' version of Vortex into your project at commit "%s".', $artifact->getRef()) . PHP_EOL;
    }

    $content .= PHP_EOL;

    if ($this->config->isVortexProject()) {
      $content .= 'It looks like Vortex is already installed into this project.' . PHP_EOL;
      $content .= PHP_EOL;
    }

    if ($this->config->getNoInteraction()) {
      $content .= 'Vortex installer will try to discover the settings from the environment and will install configuration relevant to your site.' . PHP_EOL;
      $content .= PHP_EOL;
      $content .= 'Existing committed files may be modified. You may need to resolve some of the changes manually.' . PHP_EOL;

      $title = 'Welcome to the Vortex non-interactive installer';
    }
    else {
      $content .= 'You will be asked a few questions to tailor the configuration to your site.' . PHP_EOL;
      $content .= 'No changes will be made until you confirm everything at the end.' . PHP_EOL;
      $content .= PHP_EOL;

      if ($this->config->isVortexProject()) {
        $content .= 'If you proceed, some committed files may be modified after confirmation, and you may need to resolve some of the changes manually.' . PHP_EOL;
        $content .= PHP_EOL;
      }

      $content .= 'Press ' . Tui::yellow('Ctrl+C') . ' at any time to exit the installer.' . PHP_EOL;
      $content .= 'Press ' . Tui::yellow('Ctrl+U') . ' at any time to go back to the previous step.' . PHP_EOL;
    }

    Tui::box($content, $title);
  }

  public function footer(): void {
    $output = '';
    $prefix = '  ';

    if ($this->config->isVortexProject()) {
      $title = 'Finished updating Vortex';
      $output .= 'Please review the changes and commit the required files.';
    }
    else {
      $title = 'Finished installing Vortex';

      // Check for required tools and provide conditional instructions.
      $missing_tools = $this->checkRequiredTools();
      if (!empty($missing_tools)) {
        $tools_output = 'Install required tools:' . PHP_EOL;
        foreach ($missing_tools as $tool => $instructions) {
          $tools_output .= sprintf('  %s: %s', $tool, $instructions) . PHP_EOL;
        }
        $output .= Strings::wrapLines($tools_output, $prefix);
        $output .= PHP_EOL;
      }

      $output .= 'Add and commit all files:' . PHP_EOL;
      $output .= $prefix . 'git add -A' . PHP_EOL;
      $output .= $prefix . 'git commit -m "Initial commit."' . PHP_EOL;
    }

    Tui::box($output, $title);
  }

  /**
   * Display footer after build succeeded.
   */
  public function footerBuildSucceeded(): void {
    $output = '';

    $output .= 'Get site info: ahoy info' . PHP_EOL;
    $output .= 'Login:         ahoy login' . PHP_EOL;
    $output .= PHP_EOL;

    $handler_output = $this->promptManager->runPostBuild(self::BUILD_RESULT_SUCCESS);
    if (!empty($handler_output)) {
      $output .= $handler_output;
    }

    Tui::box($output, 'Site is ready');
  }

  /**
   * Display footer after build was skipped.
   */
  public function footerBuildSkipped(): void {
    $output = '';
    $prefix = '  ';

    $responses = $this->promptManager->getResponses();
    $starter = $responses[Starter::id()] ?? Starter::LOAD_DATABASE_DEMO;
    $is_profile = in_array($starter, [Starter::INSTALL_PROFILE_CORE, Starter::INSTALL_PROFILE_DRUPALCMS], TRUE);

    $output .= 'Build the site:' . PHP_EOL;
    if ($is_profile) {
      $output .= $prefix . 'VORTEX_PROVISION_TYPE=profile ahoy build' . PHP_EOL;
    }
    else {
      $output .= $prefix . 'ahoy build' . PHP_EOL;
    }
    $output .= PHP_EOL;

    if ($is_profile) {
      $output .= 'Export database after build:' . PHP_EOL;
      $output .= $prefix . 'ahoy export-db db.sql' . PHP_EOL;
      $output .= PHP_EOL;
    }

    $handler_output = $this->promptManager->runPostBuild(self::BUILD_RESULT_SKIPPED);
    if (!empty($handler_output)) {
      $output .= $handler_output;
    }

    Tui::box($output, 'Ready to build');
  }

  /**
   * Display footer after build failed.
   */
  public function footerBuildFailed(): void {
    $output = '';
    $prefix = '  ';

    $output .= 'Vortex was installed, but the build process failed.' . PHP_EOL;
    $output .= PHP_EOL;
    $output .= 'Troubleshooting:' . PHP_EOL;
    $output .= $prefix . 'Check logs:' . $prefix . $prefix . 'ahoy logs' . PHP_EOL;
    $output .= $prefix . 'Retry build:' . $prefix . 'ahoy build' . PHP_EOL;
    $output .= $prefix . 'Diagnostics:' . $prefix . 'ahoy doctor' . PHP_EOL;
    $output .= PHP_EOL;

    $handler_output = $this->promptManager->runPostBuild(self::BUILD_RESULT_FAILED);
    if (!empty($handler_output)) {
      $output .= $handler_output;
    }

    Tui::box($output, 'Build encountered errors');
  }

  /**
   * Check for required development tools.
   *
   * @return array<string, string>
   *   Array of missing tools with installation instructions.
   */
  protected function checkRequiredTools(): array {
    $tools = [
      'docker' => [
        'name' => 'Docker',
        'command' => 'docker',
        'instructions' => 'https://www.docker.com/get-started',
      ],
      'pygmy' => [
        'name' => 'Pygmy',
        'command' => 'pygmy',
        'instructions' => 'https://github.com/pygmystack/pygmy',
      ],
      'ahoy' => [
        'name' => 'Ahoy',
        'command' => 'ahoy',
        'instructions' => 'https://github.com/ahoy-cli/ahoy',
      ],
    ];

    $missing = [];

    foreach ($tools as $tool) {
      // Use exec with output capture to avoid output to console.
      $output = [];
      $return_code = 0;
      exec(sprintf('command -v %s 2>/dev/null', $tool['command']), $output, $return_code);

      if ($return_code !== 0) {
        $missing[$tool['name']] = $tool['instructions'];
      }
    }

    return $missing;
  }

}
