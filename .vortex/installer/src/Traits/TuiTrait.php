<?php

declare(strict_types=1);

namespace DrevOps\Installer\Traits;

use DrevOps\Installer\Utils\Callback;

/**
 * TUI trait.
 */
trait TuiTrait {

  /**
   * Defines installer status message flags.
   */
  final const INSTALLER_STATUS_SUCCESS = 0;

  final const INSTALLER_STATUS_ERROR = 1;

  final const INSTALLER_STATUS_MESSAGE = 2;

  final const INSTALLER_STATUS_DEBUG = 3;

  /**
   * Width of the TUI.
   */
  protected int $tuiWidth;

  /**
   * Is Vortex initially installed.
   */
  protected bool $isInitiallyInstalled = FALSE;

  protected function printHeader(): void {
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

    if ($this->getTuiWidth() >= 80) {
      $this->out($logo, 'green');
    }

    $this->isInitiallyInstalled = $this->isInstalled();

    if ($this->config->isQuiet()) {
      $this->printHeaderQuiet();
    }
    else {
      $this->printHeaderInteractive();
    }
    print PHP_EOL;
  }

  protected function printHeaderInteractive(): void {
    $commit = $this->config->get('VORTEX_INSTALL_COMMIT');

    $content = '';
    if ($commit == 'HEAD') {
      $content .= 'This will install the latest version of Vortex into your project.' . PHP_EOL;
    }
    else {
      $content .= sprintf('This will install Vortex into your project at commit "%s".', $commit) . PHP_EOL;
    }
    $content .= PHP_EOL;
    if ($this->isInitiallyInstalled) {
      $content .= 'It looks like Vortex is already installed into this project.' . PHP_EOL;
      $content .= PHP_EOL;
    }
    $content .= 'Please answer the questions below to install configuration relevant to your site.' . PHP_EOL;
    $content .= 'No changes will be applied until the last confirmation step.' . PHP_EOL;
    $content .= PHP_EOL;
    $content .= 'Existing committed files will be modified. You will need to resolve changes manually.' . PHP_EOL;
    $content .= PHP_EOL;
    $content .= 'Press Ctrl+C at any time to exit this installer.' . PHP_EOL;

    $this->printBox($content, 'WELCOME TO VORTEX INTERACTIVE INSTALLER');
  }

  protected function printHeaderQuiet(): void {
    $commit = $this->config->get('VORTEX_INSTALL_COMMIT');

    $content = '';
    if ($commit == 'HEAD') {
      $content .= 'This will install the latest version of Vortex into your project.' . PHP_EOL;
    }
    else {
      $content .= sprintf('This will install Vortex into your project at commit "%s".', $commit) . PHP_EOL;
    }

    $content .= PHP_EOL;
    if ($this->isInitiallyInstalled) {
      $content .= 'It looks like Vortex is already installed into this project.' . PHP_EOL;
      $content .= PHP_EOL;
    }

    $content .= 'Vortex installer will try to discover the settings from the environment and will install configuration relevant to your site.' . PHP_EOL;
    $content .= PHP_EOL;
    $content .= 'Existing committed files will be modified. You will need to resolve changes manually.' . PHP_EOL;

    $this->printBox($content, 'WELCOME TO VORTEX QUIET INSTALLER');
  }

  protected function printSummary(): void {
    $values['Current directory'] = $this->fsGetRootDir();
    $values['Destination directory'] = $this->config->getDstDir();
    $values['Vortex version'] = $this->config->get('VORTEX_VERSION');
    $values['Vortex commit'] = $this->formatNotEmpty($this->config->get('VORTEX_INSTALL_COMMIT'), 'Latest');

    $values[] = '';
    $values[] = str_repeat('─', $this->getTuiWidth() - 2 - 2 * 2);
    $values[] = '';

    $values['Name'] = $this->getAnswer('name');
    $values['Machine name'] = $this->getAnswer('machine_name');
    $values['Organisation'] = $this->getAnswer('org');
    $values['Organisation machine name'] = $this->getAnswer('org_machine_name');
    $values['Module prefix'] = $this->getAnswer('module_prefix');
    $values['Profile'] = $this->getAnswer('profile');
    $values['Theme name'] = $this->getAnswer('theme');
    $values['Domain'] = $this->getAnswer('domain');
    $values['Web root'] = $this->getAnswer('webroot');

    $values['Install from profile'] = $this->formatYesNo($this->getAnswer('provision_use_profile'));

    $values['Database download source'] = $this->getAnswer('database_download_source');
    $image = $this->getAnswer('database_image');
    $values['Database store type'] = empty($image) ? 'file' : 'container_image';

    if ($image !== '' && $image !== '0') {
      $values['Database image name'] = $image;
    }

    $values['Override existing database'] = $this->formatYesNo($this->getAnswer('override_existing_db'));
    $values['CI provider'] = $this->formatNotEmpty($this->getAnswer('ci_provider'), 'None');
    $values['Deployment'] = $this->formatNotEmpty($this->getAnswer('deploy_type'), 'Disabled');
    $values['FTP integration'] = $this->formatEnabled($this->getAnswer('preserve_ftp'));
    $values['Acquia integration'] = $this->formatEnabled($this->getAnswer('preserve_acquia'));
    $values['Lagoon integration'] = $this->formatEnabled($this->getAnswer('preserve_lagoon'));
    $values['RenovateBot integration'] = $this->formatEnabled($this->getAnswer('preserve_renovatebot'));
    $values['Preserve onboarding checklist'] = $this->formatYesNo($this->getAnswer('preserve_onboarding'));
    $values['Preserve docs in comments'] = $this->formatYesNo($this->getAnswer('preserve_doc_comments'));
    $values['Preserve Vortex comments'] = $this->formatYesNo($this->getAnswer('preserve_vortex_info'));

    $content = $this->formatValuesList($values, '', $this->getTuiWidth() - 2 - 2 * 2);

    $this->printBox($content, 'INSTALLATION SUMMARY');
  }

  protected function askShouldProceed(): bool {
    $proceed = self::ANSWER_YES;

    if (!$this->config->isQuiet()) {
      $proceed = $this->ask(sprintf('Proceed with installing Vortex into your project\'s directory "%s"?', $this->config->getDstDir()), $proceed, TRUE);
    }

    // Kill-switch to not proceed with install. If false, the install will not
    // proceed despite the answer received above.
    if (!$this->config->get('VORTEX_INSTALL_PROCEED')) {
      $proceed = self::ANSWER_NO;
    }

    return strtolower((string) $proceed) === self::ANSWER_YES;
  }

  protected function askForAnswer(string $name, string $question): void {
    $discovered = $this->discoverValue($name);
    $answer = $this->ask($question, $discovered);
    $answer = $this->normaliseAnswer($name, $answer);

    $this->setAnswer($name, $answer);
  }


  /**
   * Format values list.
   *
   * @param array<int|string, mixed> $values
   *   Array of values to format.
   * @param string $delim
   *   Delimiter to use.
   * @param int $width
   *   Width of the line.
   *
   * @return string
   *   Formatted values list.
   */
  protected function formatValuesList(array $values, string $delim = '', int $width = 80): string {
    $width = $this->getTuiWidth($width);

    // Only keep the keys that are not numeric.
    $keys = array_filter(array_keys($values), static fn($key): bool => !is_numeric($key));

    // Line width - length of delimiters * 2 - 2 spacers.
    $line_width = $width - strlen($delim) * 2 - 2;

    // Max name length + spaced on the sides + colon.
    $max_name_width = max(array_map(static fn(string $key): int => strlen($key), $keys)) + 2 + 1;

    // Whole width - (name width + 2 delimiters on the sides + 1 delimiter in
    // the middle + 2 spaces on the sides + 2 spaces for the center delimiter).
    $value_width = max($width - ($max_name_width + strlen($delim) * 2 + strlen($delim) + 2 + 2), 1);

    $mask1 = sprintf('%s %%%ds %s %%-%s.%ss %s', $delim, $max_name_width, $delim, $value_width, $value_width, $delim) . PHP_EOL;
    $mask2 = sprintf('%s%%2$%ss%s', $delim, $line_width, $delim) . PHP_EOL;

    $output = [];
    foreach ($values as $name => $value) {
      $is_multiline_value = strlen((string) $value) > $value_width;

      if (is_numeric($name)) {
        $name = '';
        $mask = $mask2;
        $is_multiline_value = FALSE;
      }
      else {
        $name .= ':';
        $mask = $mask1;
      }

      if ($is_multiline_value) {
        $lines = array_filter(explode(PHP_EOL, chunk_split(strval($value), $value_width, PHP_EOL)));
        $first_line = array_shift($lines);
        $output[] = sprintf($mask, $name, $first_line);
        foreach ($lines as $line) {
          $output[] = sprintf($mask, '', $line);
        }
      }
      else {
        $output[] = sprintf($mask, $name, $value);
      }
    }

    return implode('', $output);
  }

  protected function formatEnabled(mixed $value): string {
    return $value && strtolower((string) $value) !== 'n' ? 'Enabled' : 'Disabled';
  }

  protected function formatYesNo(string $value): string {
    return $value === self::ANSWER_YES ? 'Yes' : 'No';
  }

  protected function formatNotEmpty(mixed $value, mixed $default): mixed {
    return empty($value) ? $default : $value;
  }

  protected function formatBold(string $text): string {
    return "\033[1m" . $text . "\033[0m";
  }

  protected function debug(mixed $value, string $name = ''): void {
    print PHP_EOL;
    print trim($name . ' DEBUG START') . PHP_EOL;
    print print_r($value, TRUE) . PHP_EOL;
    print trim($name . ' DEBUG FINISH') . PHP_EOL;
    print PHP_EOL;
  }


}
