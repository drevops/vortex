<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Command;

use DrevOps\VortexInstaller\Runner\CommandRunner;
use DrevOps\VortexInstaller\Runner\ProcessRunner;
use DrevOps\VortexInstaller\Runner\RunnerInterface;
use DrevOps\VortexInstaller\Task\Task;
use DrevOps\VortexInstaller\Utils\Tui;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Build command.
 */
class BuildCommand extends Command {

  const string OPTION_PROFILE = 'profile';

  const string OPTION_SKIP_REQUIREMENTS_CHECK = 'skip-requirements-check';

  const string TROUBLESHOOTING_URL = 'https://vortex.drevops.com/troubleshooting';

  /**
   * Defines default command name.
   *
   * @var string
   */
  public static $defaultName = 'build';

  /**
   * The process runner.
   */
  protected ?ProcessRunner $runner = NULL;

  /**
   * Whether to build from profile.
   */
  protected bool $isProfile;

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this->setName('build');
    $this->setDescription('Build the site using ahoy build.');
    $this->setHelp('Checks requirements and runs ahoy build to set up the local site.');
    $this->addOption(static::OPTION_PROFILE, 'p', InputOption::VALUE_NONE, 'Build from install profile instead of loading database.');
    $this->addOption(static::OPTION_SKIP_REQUIREMENTS_CHECK, NULL, InputOption::VALUE_NONE, 'Skip checking for required tools.');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    Tui::init($output);

    $this->isProfile = (bool) $input->getOption(static::OPTION_PROFILE);
    $cwd = getcwd() ?: '.';

    if (!$input->getOption(static::OPTION_SKIP_REQUIREMENTS_CHECK)) {
      $requirements_ok = Task::action(
        label: 'Checking requirements',
        action: function (): bool {
          $runner = (new CommandRunner($this->getApplication()))->disableLog();
          $runner->run('check-requirements', [], ['--no-summary' => '1']);

          return $runner->getExitCode() === RunnerInterface::EXIT_SUCCESS;
        },
        failure: 'Missing requirements. Run: ./installer.php check-requirements',
        streaming: TRUE,
      );

      if (!$requirements_ok) {
        return Command::FAILURE;
      }
    }

    $build_ok = Task::action(
      label: 'Building site',
      action: function () use ($cwd): bool {
        $env = [
          'AHOY_CONFIRM_RESPONSE' => 'y',
          'AHOY_CONFIRM_WAIT_SKIP' => '1',
        ];

        if ($this->isProfile) {
          $env['VORTEX_PROVISION_TYPE'] = 'profile';
        }

        $this->runner = $this->getRunner()->setCwd($cwd);
        $this->runner->run('ahoy build', env: $env);

        return $this->runner->getExitCode() === RunnerInterface::EXIT_SUCCESS;
      },
      success: fn(bool $result): string => $result ? 'Build completed' : 'Build failed',
      failure: 'Build failed',
      streaming: TRUE,
    );

    if ($build_ok) {
      $this->showSuccessSummary();
      return Command::SUCCESS;
    }

    $this->showFailureSummary();
    return Command::FAILURE;
  }

  /**
   * Get the project machine name from .env.
   */
  protected function getProjectMachineName(): string {
    $cwd = getcwd() ?: '.';
    $env_file = $cwd . '/.env';

    if (file_exists($env_file)) {
      $content = file_get_contents($env_file);
      if ($content !== FALSE && preg_match('/^VORTEX_PROJECT=(.+)$/m', $content, $matches)) {
        return trim($matches[1]);
      }
    }

    return basename($cwd);
  }

  /**
   * Display success summary.
   */
  protected function showSuccessSummary(): void {
    $output = '';
    $title = 'Build completed successfully!';

    $output .= 'Site URL: http://' . $this->getProjectMachineName() . '.docker.amazee.io' . PHP_EOL;
    $output .= 'Login:    ahoy login' . PHP_EOL;
    $output .= PHP_EOL;

    $log_path = $this->runner->getLogger()->getPath();
    if ($log_path) {
      $output .= 'Log file: ' . $log_path . PHP_EOL;
      $output .= PHP_EOL;
    }

    $output .= 'Next steps:' . PHP_EOL;
    if ($this->isProfile) {
      $output .= '  - Export database: ahoy export-db' . PHP_EOL;
    }
    $output .= '  - Review hosting/provisioning docs' . PHP_EOL;

    Tui::box($output, $title);
  }

  /**
   * Display failure summary.
   */
  protected function showFailureSummary(): void {
    Tui::line('');

    $command = $this->runner->getCommand();
    if ($command) {
      Tui::line('Failed at:  ' . $command);
    }

    $exit_code = $this->runner->getExitCode();
    Tui::line('Exit code:  ' . $exit_code);

    $log_path = $this->runner->getLogger()->getPath();
    if ($log_path) {
      Tui::line('Log file:   ' . $log_path);
    }

    Tui::line('');

    // Show last 10 lines of output for context.
    $runner_output = $this->runner->getOutput(as_array: TRUE);

    if (!is_array($runner_output)) {
      throw new \RuntimeException('Runner output is not an array.');
    }

    $last_lines = array_slice($runner_output, -10);
    if (!empty($last_lines)) {
      Tui::line('Last output:');
      foreach ($last_lines as $last_line) {
        Tui::line('  ' . $last_line);
      }
    }
  }

  /**
   * Get the process runner.
   *
   * Factory method that returns existing runner or creates new one.
   */
  protected function getRunner(): ProcessRunner {
    // Return already-set runner if available (for testing).
    return $this->runner ?? (new ProcessRunner());
  }

  /**
   * Set the process runner.
   *
   * Allows dependency injection for testing.
   */
  public function setRunner(ProcessRunner $runner): void {
    $this->runner = $runner;
  }

}
