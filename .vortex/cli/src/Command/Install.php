<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Command;

use DrevOps\Customizer\Customizer;
use DrevOps\Customizer\Engine\EngineException;
use DrevOps\Customizer\Handler\Context;
use DrevOps\VortexCli\Downloader\Downloader;
use DrevOps\VortexCli\Form\VortexForm;
use DrevOps\VortexCli\Process\Processor;
use DrevOps\VortexCli\Downloader\RepositoryDownloader;
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
 * Installs Vortex: downloads the template, customizes it and copies it out.
 *
 * @package DrevOps\VortexCli\Command
 */
class Install extends Command {

  /**
   * The namespace the engine searches for handler classes.
   */
  protected const HANDLER_NAMESPACE = 'DrevOps\\VortexCli\\Handler';

  /**
   * The prefix for per-question environment variable overrides.
   */
  protected const ENV_PREFIX = 'VORTEX_';

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
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this
      ->setName('install')
      ->setDescription('Install Vortex from a remote or local repository.')
      ->addOption('destination', NULL, InputOption::VALUE_REQUIRED, 'Destination directory.')
      ->addOption('root', NULL, InputOption::VALUE_REQUIRED, 'Root directory for resolving relative paths.')
      ->addOption('uri', 'l', InputOption::VALUE_REQUIRED, 'Remote or local repository URI with an optional ref after "#".')
      ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'A JSON string with options or a path to a JSON file.')
      ->addOption('prompts', 'p', InputOption::VALUE_REQUIRED, 'Answers as a JSON string or a path to a JSON file.')
      ->addOption('no-cleanup', NULL, InputOption::VALUE_NONE, 'Do not remove the installer after installation.')
      ->addOption('build', 'b', InputOption::VALUE_NONE, 'Run the site build after installation.');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
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

    $customizer = new Customizer(VortexForm::create(), [static::HANDLER_NAMESPACE], static::ENV_PREFIX);

    $prompts = $input->getOption('prompts');
    $prompts = is_string($prompts) ? $prompts : '';

    try {
      $answers = $customizer->collect($prompts, $dst, $update, $version);
    }
    catch (EngineException $engine_exception) {
      $output->writeln('<error>' . $engine_exception->getMessage() . '</error>');

      return Command::FAILURE;
    }

    (new Processor())->apply($customizer->config(), $customizer->registry(), $answers->values, new Context($tmp, $answers->values, $update, $version, $dst), VortexForm::PROCESSORS);

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
