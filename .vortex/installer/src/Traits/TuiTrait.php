<?php

declare(strict_types=1);

namespace DrevOps\Installer\Traits;

/**
 * TUI trait.
 */
trait TuiTrait {

  /**
   * Defines "yes" and "no" answer strings.
   */
  final const ANSWER_YES = 'y';

  final const ANSWER_NO = 'n';

  /**
   * Defines installer status message flags.
   */
  final const INSTALLER_STATUS_SUCCESS = 0;

  final const INSTALLER_STATUS_ERROR = 1;

  final const INSTALLER_STATUS_MESSAGE = 2;

  final const INSTALLER_STATUS_DEBUG = 3;

  protected function ask(string $question, ?string $default, bool $close_handle = FALSE): ?string {
    if ($this->config->isQuiet()) {
      return $default;
    }

    $question = sprintf('> %s [%s] ', $question, $default);

    $this->out($question, 'question', FALSE);
    $handle = $this->getStdinHandle();
    $answer = fgets($handle);
    if ($answer !== FALSE) {
      $answer = trim($answer);
    }

    if ($close_handle) {
      $this->closeStdinHandle();
    }

    return empty($answer) ? $default : $answer;
  }

  protected function getStdinHandle(): mixed {
    global $_stdin_handle;

    if (!$_stdin_handle) {
      $h = fopen('php://stdin', 'r');
      if (!$h) {
        throw new \RuntimeException('Unable to open stdin handle.');
      }
      $_stdin_handle = stream_isatty($h) || static::getenvOrDefault('VORTEX_INSTALLER_FORCE_TTY') ? $h : fopen('/dev/tty', 'r+');
    }

    return $_stdin_handle;
  }

  protected function closeStdinHandle(): void {
    $_stdin_handle = $this->getStdinHandle();
    fclose($_stdin_handle);
  }

  protected function printHeader(): void {
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
    if ($this->isInstalled()) {
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
    if ($this->isInstalled()) {
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
    $values[] = str_repeat('─', 80 - 2 - 2 * 2);
    $values[] = '';

    $values['Name'] = $this->getAnswer('name');
    $values['Machine name'] = $this->getAnswer('machine_name');
    $values['Organisation'] = $this->getAnswer('org');
    $values['Organisation machine name'] = $this->getAnswer('org_machine_name');
    $values['Module prefix'] = $this->getAnswer('module_prefix');
    $values['Profile'] = $this->getAnswer('profile');
    $values['Theme name'] = $this->getAnswer('theme');
    $values['URL'] = $this->getAnswer('url');
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
    $values['Preserve docs in comments'] = $this->formatYesNo($this->getAnswer('preserve_doc_comments'));
    $values['Preserve Vortex comments'] = $this->formatYesNo($this->getAnswer('preserve_vortex_info'));

    $content = $this->formatValuesList($values, '', 80 - 2 - 2 * 2);

    $this->printBox($content, 'INSTALLATION SUMMARY');
  }

  protected function printAbort(): void {
    $this->printBox('Aborting project installation. No files were changed.');
  }

  protected function printFooter(): void {
    print PHP_EOL;

    if ($this->isInstalled()) {
      $this->printBox('Finished updating Vortex. Review changes and commit required files.');
    }
    else {
      $this->printBox('Finished installing Vortex.');

      $output = '';
      $output .= PHP_EOL;
      $output .= 'Next steps:' . PHP_EOL;
      $output .= '  cd ' . $this->config->getDstDir() . PHP_EOL;
      $output .= '  git add -A                       # Add all files.' . PHP_EOL;
      $output .= '  git commit -m "Initial commit."  # Commit all files.' . PHP_EOL;
      $output .= '  ahoy build                       # Build site.' . PHP_EOL;
      $output .= PHP_EOL;
      $output .= '  See https://vortex.drevops.com/quickstart';
      $this->status($output, self::INSTALLER_STATUS_SUCCESS, TRUE, FALSE);
    }
  }

  protected function commandExists(string $command): void {
    $this->doExec('command -v ' . $command, $lines, $ret);
    if ($ret === 1) {
      throw new \RuntimeException(sprintf('Command "%s" does not exist in the current environment.', $command));
    }
  }

  /**
   * Execute command.
   *
   * @param string $command
   *   Command to execute.
   * @param array<int, string>|null $output
   *   Output of the command.
   * @param int $return_var
   *   Return code of the command.
   *
   * @return string|false
   *   Result of the command.
   */
  protected function doExec(string $command, ?array &$output = NULL, ?int &$return_var = NULL): string|false {
    if ($this->config->isInstallDebug()) {
      $this->status(sprintf('COMMAND: %s', $command), self::INSTALLER_STATUS_DEBUG);
    }

    $result = exec($command, $output, $return_var);

    if ($this->config->isInstallDebug()) {
      $this->status(sprintf('  OUTPUT: %s', implode('', $output)), self::INSTALLER_STATUS_DEBUG);
      $this->status(sprintf('  CODE  : %s', $return_var), self::INSTALLER_STATUS_DEBUG);
      $this->status(sprintf('  RESULT: %s', $result), self::INSTALLER_STATUS_DEBUG);
    }

    return $result;
  }

  /**
   * Get a named option from discovered answers for the project bing installed.
   */
  protected function getAnswer(string $name, mixed $default = NULL): ?string {
    global $_answers;

    return $_answers[$name] ?? $default;
  }

  /**
   * Set a named option for discovered answers for the project bing installed.
   */
  protected function setAnswer(string $name, mixed $value): void {
    global $_answers;
    $_answers[$name] = $value;
  }

  /**
   * Get all options from discovered answers for the project bing installed.
   *
   * @return array<string, mixed>
   *   Array of all discovered answers.
   */
  protected function getAnswers(): array {
    global $_answers;

    return $_answers;
  }

  protected function askShouldProceed(): bool {
    $proceed = self::ANSWER_YES;

    if (!$this->config->isQuiet()) {
      $proceed = $this->ask(sprintf('Proceed with installing Vortex into your project\'s directory "%s"? (Y,n)', $this->config->getDstDir()), $proceed, TRUE);
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
   * Process answers.
   */
  protected function processAnswer(string $name, string $dir): mixed {
    return $this->executeCallback('process', $name, $dir);
  }

}
