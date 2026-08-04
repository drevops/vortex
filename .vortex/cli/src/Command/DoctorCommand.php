<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Command;

use DrevOps\VortexCli\Runner\ExecutableFinderAwareInterface;
use DrevOps\VortexCli\Runner\ExecutableFinderAwareTrait;
use DrevOps\VortexCli\Runner\ProcessRunner;
use DrevOps\VortexCli\Runner\ProcessRunnerAwareInterface;
use DrevOps\VortexCli\Runner\ProcessRunnerAwareTrait;
use DrevOps\VortexCli\Runner\RunnerInterface;
use DrevOps\VortexCli\Task\Task;
use DrevOps\VortexCli\Utils\Tui;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Doctor command.
 *
 * Diagnoses the local environment: reports which tools a Vortex project needs,
 * whether each is installed and running, and how to install the ones that are
 * not. Read-only - it never changes the project.
 */
class DoctorCommand extends Command implements ProcessRunnerAwareInterface, ExecutableFinderAwareInterface {

  use ProcessRunnerAwareTrait;
  use ExecutableFinderAwareTrait;
  use DestinationAwareTrait;

  const OPTION_ONLY = 'only';

  const OPTION_NO_SUMMARY = 'no-summary';

  const REQ_DOCKER = 'docker';

  const REQ_DOCKER_COMPOSE = 'docker-compose';

  const REQ_AHOY = 'ahoy';

  const REQ_PYGMY = 'pygmy';

  const REQUIREMENTS = [
    self::REQ_DOCKER,
    self::REQ_DOCKER_COMPOSE,
    self::REQ_AHOY,
    self::REQ_PYGMY,
  ];

  /**
   * Defines default command name.
   *
   * @var string
   */
  public static $defaultName = 'doctor';

  /**
   * Present tools.
   *
   * @var array<string, string>
   */
  protected array $present = [];

  /**
   * Missing tools with installation instructions.
   *
   * @var array<string, string>
   */
  protected array $missing = [];

  /**
   * The working directory for checks.
   */
  protected string $cwd;

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this->setName('doctor');
    $this->setDescription('Diagnose the local environment for common problems.');
    $this->setHelp('Checks that Docker, Docker Compose, Ahoy and Pygmy are installed and running, and reports how to install the ones that are missing.');
    $this->addDestinationOption();
    $this->addOption(static::OPTION_ONLY, 'o', InputOption::VALUE_REQUIRED, sprintf('Comma-separated list of requirements to check. Available: %s.', implode(', ', static::REQUIREMENTS)));
    $this->addOption(static::OPTION_NO_SUMMARY, NULL, InputOption::VALUE_NONE, 'Hide summary with tool versions.');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    Tui::init($output);

    $this->cwd = $this->getDestination($input);

    $only = $input->getOption(static::OPTION_ONLY);
    $requirements = $this->validateRequirements($only ? array_map(trim(...), explode(',', (string) $only)) : NULL);

    // Assigned before the working directory is applied so an injected runner
    // is configured too, rather than being left pointing elsewhere.
    $this->processRunner = $this->getProcessRunner();
    $this->processRunner->setCwd($this->cwd);

    $this->present = [];
    $this->missing = [];

    if (in_array(static::REQ_DOCKER, $requirements, TRUE)) {
      Task::action(
        label: 'Checking Docker',
        action: fn(): bool => $this->checkDocker(),
        success: fn(bool $result): string => $result ? 'Docker is available' : 'Docker is missing',
      );
    }

    if (in_array(static::REQ_DOCKER_COMPOSE, $requirements, TRUE)) {
      Task::action(
        label: 'Checking Docker Compose',
        action: fn(): bool => $this->checkDockerCompose(),
        success: fn(bool $result): string => $result ? 'Docker Compose is available' : 'Docker Compose is missing',
      );
    }

    if (in_array(static::REQ_AHOY, $requirements, TRUE)) {
      Task::action(
        label: 'Checking Ahoy',
        action: fn(): bool => $this->checkAhoy(),
        success: fn(bool $result): string => $result ? 'Ahoy is available' : 'Ahoy is missing',
      );
    }

    if (in_array(static::REQ_PYGMY, $requirements, TRUE)) {
      Task::action(
        label: 'Checking Pygmy',
        action: fn(): bool => $this->checkPygmy(),
        success: fn(bool $result): string => $result ? 'Pygmy is running' : 'Pygmy is not running',
      );
    }

    if (!$input->getOption(static::OPTION_NO_SUMMARY)) {
      $summary = $this->getResultsSummary();
      Tui::box($summary['content'], $summary['title']);
    }
    elseif (empty($this->missing)) {
      Tui::success('All requirements met.');
    }

    return empty($this->missing) ? Command::SUCCESS : Command::FAILURE;
  }

  /**
   * Validate and return requirements to check.
   *
   * @param array<string>|null $only
   *   Array of requirement names to check. NULL to check all.
   *
   * @return array<string>
   *   Array of validated requirement names.
   *
   * @throws \InvalidArgumentException
   *   If an unknown requirement is specified.
   */
  protected function validateRequirements(?array $only): array {
    if ($only !== NULL) {
      $unknown = array_diff($only, static::REQUIREMENTS);
      if (!empty($unknown)) {
        throw new \InvalidArgumentException(sprintf("Unknown requirements: %s.\nAvailable: %s.", implode(', ', $unknown), implode(', ', static::REQUIREMENTS)));
      }
    }

    return $only ?? static::REQUIREMENTS;
  }

  /**
   * Get present tools.
   *
   * @return array<string, string>
   *   An array of present tools with tool name as key and path as value.
   */
  public function getPresent(): array {
    return $this->present;
  }

  /**
   * Get missing tools.
   *
   * @return array<string, string>
   *   An array of missing tools with tool name as key and message as value.
   */
  public function getMissing(): array {
    return $this->missing;
  }

  /**
   * Get all check results merged.
   *
   * @return array<string, string>
   *   Combined array of present and missing tools.
   */
  public function getResults(): array {
    return array_merge($this->present, $this->missing);
  }

  /**
   * Get a formatted summary of check results.
   *
   * @return array{title: string, content: string}
   *   Array with 'title' and 'content' keys for the summary.
   */
  public function getResultsSummary(): array {
    $content = '';

    if (!empty($this->present)) {
      $content .= 'Present:' . PHP_EOL;
      foreach ($this->present as $tool => $status) {
        $content .= '  - ' . $tool . ': ' . $status . PHP_EOL;
      }
    }

    if (!empty($this->missing)) {
      if (!empty($content)) {
        $content .= PHP_EOL;
      }
      $content .= 'Missing:' . PHP_EOL;
      foreach ($this->missing as $tool => $instruction) {
        $content .= '  - ' . $tool . ': ' . $instruction . PHP_EOL;
      }
      $content .= PHP_EOL;

      return [
        'title' => 'Missing requirements',
        'content' => $content,
      ];
    }

    return [
      'title' => 'All requirements met',
      'content' => $content,
    ];
  }

  /**
   * Check if Docker is available.
   */
  protected function checkDocker(): bool {
    $result = $this->commandExists('docker');
    if ($result) {
      $this->present['Docker'] = $this->getCommandVersion('docker --version');
    }
    else {
      $this->missing['Docker'] = 'https://www.docker.com/get-started';
    }
    return $result;
  }

  /**
   * Check if Docker Compose is available.
   */
  protected function checkDockerCompose(): bool {
    $command = $this->dockerComposeVersionCommand();

    if ($command === NULL) {
      $this->missing['Docker Compose'] = 'https://docs.docker.com/compose/install/';

      return FALSE;
    }

    $this->present['Docker Compose'] = $this->getCommandVersion($command);

    return TRUE;
  }

  /**
   * Check if Ahoy is available.
   */
  protected function checkAhoy(): bool {
    $result = $this->commandExists('ahoy');
    if ($result) {
      $this->present['Ahoy'] = $this->getCommandVersion('ahoy --version');
    }
    else {
      $this->missing['Ahoy'] = 'https://github.com/ahoy-cli/ahoy';
    }
    return $result;
  }

  /**
   * Check if Pygmy is running.
   */
  protected function checkPygmy(): bool {
    if (!$this->commandExists('pygmy')) {
      $this->missing['Pygmy'] = 'Run: pygmy up';
      return FALSE;
    }

    $version = $this->getCommandVersion('pygmy version');

    $this->processRunner->run('pygmy status');
    if ($this->processRunner->getExitCode() === RunnerInterface::EXIT_SUCCESS) {
      $this->present['Pygmy'] = $version;
      return TRUE;
    }

    // Pygmy's own containers can be running while its status command is not
    // usable, so the container list is the second opinion. Commands run without
    // a shell, so the match is made here rather than piped into grep.
    if ($this->hasAmazeeioContainers()) {
      $this->present['Pygmy'] = $version;
      return TRUE;
    }

    $this->missing['Pygmy'] = 'Run: pygmy up';

    return FALSE;
  }

  /**
   * Whether any running container belongs to Pygmy.
   */
  protected function hasAmazeeioContainers(): bool {
    if (!$this->commandExists('docker')) {
      return FALSE;
    }

    $this->processRunner->run('docker', ['ps', '--format', '{{.Names}}']);

    if ($this->processRunner->getExitCode() !== RunnerInterface::EXIT_SUCCESS) {
      return FALSE;
    }

    $output = $this->processRunner->getOutput();

    return str_contains(is_string($output) ? $output : implode(PHP_EOL, $output), 'amazeeio');
  }

  /**
   * Check if a command exists.
   */
  protected function commandExists(string $command): bool {
    return $this->getExecutableFinder()->find($command) !== NULL;
  }

  /**
   * The command reporting the available Docker Compose version.
   *
   * Which form is present decides which command can report a version, so the
   * two are resolved together rather than assuming the modern subcommand.
   *
   * @return string|null
   *   The version command, or NULL when neither form is available.
   */
  protected function dockerComposeVersionCommand(): ?string {
    // Probed only when Docker is on PATH: the runner refuses to execute a
    // command it cannot resolve, and a missing tool is what this reports on.
    if ($this->commandExists('docker')) {
      $this->processRunner->run('docker compose version');

      if ($this->processRunner->getExitCode() === RunnerInterface::EXIT_SUCCESS) {
        return 'docker compose version';
      }
    }

    return $this->commandExists('docker-compose') ? 'docker-compose --version' : NULL;
  }

  /**
   * Get command version output.
   *
   * @param string $command
   *   The command to run.
   * @param int $lines
   *   Number of lines to retrieve from the output. Defaults to 1.
   */
  protected function getCommandVersion(string $command, int $lines = 1): string {
    $this->processRunner->run($command);
    $raw_output = $this->processRunner->getOutput(FALSE, $lines);
    $output = trim(is_string($raw_output) ? $raw_output : implode(PHP_EOL, $raw_output));
    return empty($output) ? 'Available' : $output;
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessRunner(): ProcessRunner {
    return $this->processRunner ?? (new ProcessRunner())->disableLog()->disableStreaming();
  }

}
