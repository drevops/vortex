<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Command;

use DrevOps\Tui\Engine\EngineException;
use DrevOps\Tui\Handler\Context;
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

    $tui = new Tui(VortexForm::create(), [static::HANDLER_NAMESPACE]);

    $prompts = $input->getOption('prompts');
    $prompts = is_string($prompts) ? $prompts : '';

    try {
      $answers = $tui->collect($prompts, $dst, $update, $version);
    }
    catch (EngineException $engine_exception) {
      $output->writeln('<error>' . $engine_exception->getMessage() . '</error>');

      return Command::FAILURE;
    }

    (new Processor())->apply($tui->config(), $tui->registry(), $answers->values, new Context($tmp, $answers->values, $update, $version, $dst), VortexForm::PROCESSORS);

    $file_manager = new FileManager($config);
    $file_manager->prepareDestination();
    $file_manager->copyFiles();
    $file_manager->prepareDemo($this->getFileDownloader());

    return Command::SUCCESS;
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
