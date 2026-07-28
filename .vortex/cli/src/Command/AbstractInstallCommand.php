<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Command;

use DrevOps\Tui\Engine\EngineException;
use DrevOps\Tui\InterruptException;
use DrevOps\Tui\Tui;
use DrevOps\VortexCli\Downloader\Downloader;
use DrevOps\VortexCli\Downloader\RepositoryDownloader;
use DrevOps\VortexCli\Form\VortexForm;
use DrevOps\VortexCli\Process\Processor;
use DrevOps\VortexCli\Utils\Config;
use DrevOps\VortexCli\Utils\FileManager;
use DrevOps\VortexCli\Utils\OptionsResolver;
use DrevOps\VortexCli\Utils\Version;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ExecutableFinder;

/**
 * Shared machinery for the install and update commands.
 *
 * Both verbs are thin facades over the same interactive flow: resolve options,
 * download the template, collect answers through the TUI form and apply them.
 * The only difference is framing - install starts from a version, update points
 * at a target one - so update mode is detected from the destination and the
 * template flow itself lives here, in doInstall().
 *
 * @package DrevOps\VortexCli\Command
 */
abstract class AbstractInstallCommand extends Command {

  /**
   * The namespace the engine searches for handler classes.
   */
  protected const HANDLER_NAMESPACE = 'DrevOps\\VortexCli\\Handler';

  /**
   * The version stamped into placeholders when the app version is unset.
   */
  protected const VERSION = '__VERSION__';

  /**
   * The repository downloader (overridable for tests).
   */
  protected ?RepositoryDownloader $repositoryDownloader = NULL;

  /**
   * The file downloader (overridable for tests).
   */
  protected ?Downloader $fileDownloader = NULL;

  /**
   * Add the options shared by the install and update commands.
   */
  protected function addCommonOptions(): void {
    $this
      ->addOption('destination', NULL, InputOption::VALUE_REQUIRED, 'Destination directory.')
      ->addOption('root', NULL, InputOption::VALUE_REQUIRED, 'Root directory for resolving relative paths.')
      ->addOption('uri', 'l', InputOption::VALUE_REQUIRED, 'Remote or local repository URI with an optional ref after "#".')
      ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'A JSON string with options or a path to a JSON file.')
      ->addOption('prompts', 'p', InputOption::VALUE_REQUIRED, 'Answers as a JSON string or a path to a JSON file.')
      ->addOption('no-cleanup', NULL, InputOption::VALUE_NONE, 'Do not remove the installer after installation.')
      ->addOption('build', 'b', InputOption::VALUE_NONE, 'Run the site build after installation.');
  }

  /**
   * Download the template, collect answers via the TUI and apply them.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   The input.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output.
   *
   * @return int
   *   The command exit code.
   */
  protected function doInstall(InputInterface $input, OutputInterface $output): int {
    try {
      OptionsResolver::checkRequirements(new ExecutableFinder());
      [$config, $artifact] = OptionsResolver::resolve($input->getOptions());
    }
    catch (\Exception $exception) {
      $output->writeln('<error>' . $exception->getMessage() . '</error>');

      return Command::FAILURE;
    }

    $tmp_dir = $config->get(Config::TMP);
    $tmp = is_string($tmp_dir) ? $tmp_dir : '';
    $dst_dir = $config->get(Config::DST);
    $dst = is_string($dst_dir) ? $dst_dir : '';
    $update = (bool) $config->get(Config::IS_VORTEX_PROJECT);

    try {
      $version = $this->getRepositoryDownloader()->download($artifact, $tmp, Version::releasePrefix($this->version()));
    }
    catch (\Exception $exception) {
      $output->writeln('<error>' . $exception->getMessage() . '</error>');

      return Command::FAILURE;
    }

    $config->set(Config::VERSION, $version);
    // The handlers operate on the extracted template before it is copied.
    $config->set(Config::TMP, $tmp, TRUE);

    $tui = new Tui(VortexForm::create($config), [static::HANDLER_NAMESPACE]);

    try {
      $this->assertMajorCompatibility($config, $dst);
    }
    catch (\RuntimeException $runtime_exception) {
      $output->writeln('<error>' . $runtime_exception->getMessage() . '</error>');

      return Command::FAILURE;
    }

    $prompts = $input->getOption('prompts');
    $prompts = is_string($prompts) ? $prompts : '';

    // Answers are gathered through the panel TUI on a terminal and headlessly
    // otherwise, so a scripted run and a piped one behave the same.
    $interactive = $input->isInteractive() && $prompts === '';

    try {
      $answers = $tui->run($prompts, $version, $dst, $interactive, $update);
    }
    catch (InterruptException) {
      // A Ctrl-C abort or the Cancel button: nothing has been written yet.
      return Command::FAILURE;
    }
    catch (EngineException $engine_exception) {
      $output->writeln('<error>' . $engine_exception->getMessage() . '</error>');

      return Command::FAILURE;
    }

    (new Processor())->apply($answers, $tui->registry(), $config, VortexForm::PROCESSORS, VortexForm::WEIGHTS);

    $file_manager = new FileManager($config);
    $file_manager->prepareDestination();
    $file_manager->copyFiles();
    $file_manager->prepareDemo($this->getFileDownloader());

    // Guidance is for the person who just answered the questions; a scripted
    // run has no one to read it and its stdout belongs to the caller.
    if ($interactive) {
      $this->footer($tui, $update);
    }

    return Command::SUCCESS;
  }

  /**
   * Show what to do next once the files are in place.
   *
   * @param \DrevOps\Tui\Tui $tui
   *   The TUI facade providing the themed output.
   * @param bool $update
   *   Whether an existing project was updated rather than created.
   */
  protected function footer(Tui $tui, bool $update): void {
    if ($update) {
      $tui->output()->box('Please review the changes and commit the required files.', 'Finished updating Vortex');

      return;
    }

    $lines = [];

    $missing = $this->missingTools();
    if ($missing !== []) {
      $lines[] = 'Install required tools:';
      foreach ($missing as $tool => $instructions) {
        $lines[] = sprintf('  %s: %s', $tool, $instructions);
      }
      $lines[] = '';
    }

    $lines[] = 'Add and commit all files:';
    $lines[] = '  git add -A';
    $lines[] = '  git commit -m "Initial commit."';
    $lines[] = '';
    $lines[] = 'Build the site:';
    $lines[] = '  ahoy build';

    $tui->output()->box($lines, 'Finished installing Vortex');
  }

  /**
   * The tools the project needs locally that are not installed.
   *
   * @return array<string,string>
   *   Installation instructions, keyed by tool name.
   */
  protected function missingTools(): array {
    $tools = [
      'Docker' => ['docker', 'https://www.docker.com/get-started'],
      'Pygmy' => ['pygmy', 'https://github.com/pygmystack/pygmy'],
      'Ahoy' => ['ahoy', 'https://github.com/ahoy-cli/ahoy'],
    ];

    $finder = new ExecutableFinder();
    $missing = [];

    foreach ($tools as $name => [$binary, $instructions]) {
      if ($finder->find($binary) === NULL) {
        $missing[$name] = $instructions;
      }
    }

    return $missing;
  }

  /**
   * Refuse to operate across major versions.
   *
   * Each build serves a single major line, so updating a project of a different
   * major would cross a breaking boundary. Fresh installs and projects whose
   * major cannot be determined are treated as compatible.
   *
   * @param \DrevOps\VortexCli\Utils\Config $config
   *   The resolved configuration.
   * @param string $destination
   *   The destination directory.
   *
   * @throws \RuntimeException
   *   When the destination project's major differs from this build's major.
   */
  protected function assertMajorCompatibility(Config $config, string $destination): void {
    if (!$config->isVortexProject()) {
      return;
    }

    $current = Version::major($this->version());
    if ($current === NULL) {
      return;
    }

    $project = Version::detectProjectMajor($destination);
    if ($project === NULL || $project === $current) {
      return;
    }

    throw new \RuntimeException(sprintf(
      'This build targets Vortex %1$d.x, but the destination is a Vortex %2$d.x project. Update it with the %2$d.x release instead: https://www.vortextemplate.com/v%2$d/install',
      $current,
      $project,
    ));
  }

  /**
   * Resolve the version string used to stamp version placeholders.
   *
   * @return string
   *   The application version, or the placeholder when it is unset.
   */
  protected function version(): string {
    $version = (string) $this->getApplication()?->getVersion();

    return $version === '' || $version === 'UNKNOWN' ? static::VERSION : $version;
  }

  /**
   * Get the repository downloader.
   *
   * @return \DrevOps\VortexCli\Downloader\RepositoryDownloader
   *   The repository downloader.
   */
  protected function getRepositoryDownloader(): RepositoryDownloader {
    return $this->repositoryDownloader ??= new RepositoryDownloader();
  }

  /**
   * Set the repository downloader.
   *
   * @param \DrevOps\VortexCli\Downloader\RepositoryDownloader $downloader
   *   The repository downloader.
   */
  public function setRepositoryDownloader(RepositoryDownloader $downloader): void {
    $this->repositoryDownloader = $downloader;
  }

  /**
   * Get the file downloader.
   *
   * @return \DrevOps\VortexCli\Downloader\Downloader
   *   The file downloader.
   */
  protected function getFileDownloader(): Downloader {
    return $this->fileDownloader ??= new Downloader();
  }

  /**
   * Set the file downloader.
   *
   * @param \DrevOps\VortexCli\Downloader\Downloader $downloader
   *   The file downloader.
   */
  public function setFileDownloader(Downloader $downloader): void {
    $this->fileDownloader = $downloader;
  }

}
