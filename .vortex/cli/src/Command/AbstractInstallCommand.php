<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Command;

use DrevOps\Tui\Answers\Answers;
use DrevOps\Tui\Engine\EngineException;
use DrevOps\Tui\InterruptException;
use DrevOps\Tui\Tui;
use DrevOps\VortexCli\Downloader\Downloader;
use DrevOps\VortexCli\Downloader\RepositoryDownloader;
use DrevOps\VortexCli\Form\VortexForm;
use DrevOps\VortexCli\Handler\Starter;
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
use Symfony\Component\Process\Process;

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
   * The build outcomes handlers tailor their closing guidance to.
   */
  protected const BUILD_SUCCESS = 'success';

  protected const BUILD_FAILED = 'failed';

  protected const BUILD_SKIPPED = 'skipped';

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
      ->addOption('schema', NULL, InputOption::VALUE_NONE, 'Print the question schema as JSON and exit.')
      ->addOption('agent-help', NULL, InputOption::VALUE_NONE, 'Print instructions for driving the form non-interactively.')
      ->addOption('no-cleanup', NULL, InputOption::VALUE_NONE, 'Do not remove the Vortex CLI after installation.')
      ->addOption('build', 'b', InputOption::VALUE_NONE, 'Run the site build after installation.');
  }

  /**
   * Print the form metadata an agent needs to drive the questions, if asked.
   *
   * The questions are declared by this build, so they can be described without
   * a destination, a template download or any of the install machinery.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   The input.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output.
   *
   * @return bool
   *   TRUE when metadata was printed and the command should stop.
   */
  protected function describeForm(InputInterface $input, OutputInterface $output): bool {
    $schema = (bool) $input->getOption('schema');
    $agent_help = (bool) $input->getOption('agent-help');

    if (!$schema && !$agent_help) {
      return FALSE;
    }

    $tui = new Tui(VortexForm::create(new Config()), [static::HANDLER_NAMESPACE]);

    $output->writeln($schema ? (string) json_encode($tui->schema()) : $tui->agentHelp());

    return TRUE;
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
    if ($this->describeForm($input, $output)) {
      return Command::SUCCESS;
    }

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

    $processor = new Processor();
    $processor->apply($answers, $tui->registry(), $config, VortexForm::PROCESSORS, VortexForm::WEIGHTS);

    $file_manager = new FileManager($config);
    $file_manager->prepareDestination();
    $file_manager->copyFiles();
    $file_manager->prepareDemo($this->getFileDownloader());

    if ((bool) $config->get(Config::BUILD_NOW)) {
      return $this->build($tui, $processor, $answers, $config, $dst, $output, $interactive);
    }

    // Guidance is for the person who just answered the questions; a scripted
    // run has no one to read it and its stdout belongs to the caller.
    if ($interactive) {
      $this->footer($tui, $processor, $answers, $config, $update);
    }

    return Command::SUCCESS;
  }

  /**
   * Build the site in place and report how it went.
   *
   * @param \DrevOps\Tui\Tui $tui
   *   The TUI facade providing the themed output.
   * @param \DrevOps\VortexCli\Process\Processor $processor
   *   The processor collecting the handlers' post-build guidance.
   * @param \DrevOps\Tui\Answers\Answers $answers
   *   The collected answers.
   * @param \DrevOps\VortexCli\Utils\Config $config
   *   The resolved configuration.
   * @param string $destination
   *   The installed project directory.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output.
   * @param bool $interactive
   *   Whether a person is watching.
   *
   * @return int
   *   The command exit code.
   */
  protected function build(Tui $tui, Processor $processor, Answers $answers, Config $config, string $destination, OutputInterface $output, bool $interactive): int {
    $env = $this->isProfileStarter($answers) ? ['VORTEX_PROVISION_TYPE' => 'profile'] : [];

    $process = new Process(['ahoy', 'build'], $destination, $env, timeout: NULL);
    $process->run(static function (string $type, string $buffer) use ($output): void {
      $output->write($buffer);
    });

    if (!$process->isSuccessful()) {
      $output->writeln('<error>Vortex was installed, but the build process failed.</error>');
      $output->writeln('Troubleshooting:');
      $output->writeln('  Check logs:   ahoy logs');
      $output->writeln('  Retry build:  ahoy build');
      $output->writeln('  Diagnostics:  ahoy doctor');
      $output->write($processor->postBuild($answers, $tui->registry(), $config, static::BUILD_FAILED));

      return Command::FAILURE;
    }

    if ($interactive) {
      $lines = ['Get site info: ahoy info', 'Login:         ahoy login', ''];
      $lines[] = $processor->postBuild($answers, $tui->registry(), $config, static::BUILD_SUCCESS);

      $tui->output()->box($lines, 'Site is ready');
    }

    return Command::SUCCESS;
  }

  /**
   * Show what to do next once the files are in place.
   *
   * @param \DrevOps\Tui\Tui $tui
   *   The TUI facade providing the themed output.
   * @param \DrevOps\VortexCli\Process\Processor $processor
   *   The processor collecting the handlers' post-build guidance.
   * @param \DrevOps\Tui\Answers\Answers $answers
   *   The collected answers.
   * @param \DrevOps\VortexCli\Utils\Config $config
   *   The resolved configuration.
   * @param bool $update
   *   Whether an existing project was updated rather than created.
   */
  protected function footer(Tui $tui, Processor $processor, Answers $answers, Config $config, bool $update): void {
    if ($update) {
      $tui->output()->box('Please review the changes and commit the required files.', 'Finished updating Vortex');

      return;
    }

    $is_profile = $this->isProfileStarter($answers);
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
    $lines[] = $is_profile ? '  VORTEX_PROVISION_TYPE=profile ahoy build' : '  ahoy build';

    if ($is_profile) {
      $lines[] = '';
      $lines[] = 'Export database after build:';
      $lines[] = '  ahoy export-db db.sql';
    }

    $lines[] = $processor->postBuild($answers, $tui->registry(), $config, static::BUILD_SKIPPED);

    $tui->output()->box($lines, 'Finished installing Vortex');
  }

  /**
   * Whether the site is created from an install profile rather than a database.
   *
   * @param \DrevOps\Tui\Answers\Answers $answers
   *   The collected answers.
   *
   * @return bool
   *   TRUE when the starter installs a profile.
   */
  protected function isProfileStarter(Answers $answers): bool {
    $starter = $answers->values[Starter::id()] ?? Starter::LOAD_DATABASE_DEMO;

    return in_array($starter, [Starter::INSTALL_PROFILE_CORE, Starter::INSTALL_PROFILE_DRUPALCMS], TRUE);
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
