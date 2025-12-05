<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Command;

use DrevOps\VortexInstaller\Runner\ExecutableFinderAwareInterface;
use DrevOps\VortexInstaller\Runner\ExecutableFinderAwareTrait;
use DrevOps\VortexInstaller\Runner\ProcessRunner;
use DrevOps\VortexInstaller\Runner\ProcessRunnerAwareInterface;
use DrevOps\VortexInstaller\Runner\ProcessRunnerAwareTrait;
use DrevOps\VortexInstaller\Runner\RunnerInterface;
use DrevOps\VortexInstaller\Task\Task;
use DrevOps\VortexInstaller\Utils\Tui;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Check requirements command.
 */
class CheckRequirementsCommand extends Command implements ProcessRunnerAwareInterface, ExecutableFinderAwareInterface {

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
  public static $defaultName = 'check-requirements';

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
    $this->setName('check-requirements');
    $this->setDescription('Check if required tools are installed and running.');
    $this->setHelp('Checks for Docker, Docker Compose, Ahoy, and Pygmy.');
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

    $this->processRunner ??= $this->getProcessRunner()->setCwd($this->cwd);
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
    $result = $this->dockerComposeExists();
    if ($result) {
      $this->present['Docker Compose'] = $this->getCommandVersion('docker compose version');
    }
    else {
      $this->missing['Docker Compose'] = 'https://docs.docker.com/compose/install/';
    }
    return $result;
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

    $this->processRunner->run('docker ps --format "{{.Names}}" | grep -q amazeeio');
    // @phpstan-ignore-next-line notIdentical.alwaysFalse
    if ($this->processRunner->getExitCode() === RunnerInterface::EXIT_SUCCESS) {
      $this->present['Pygmy'] = $version;
      return TRUE;
    }

    $this->missing['Pygmy'] = 'Run: pygmy up';

    return FALSE;
  }

  /**
   * Check if a command exists.
   */
  protected function commandExists(string $command): bool {
    return $this->getExecutableFinder()->find($command) !== NULL;
  }

  /**
   * Check if Docker Compose exists.
   */
  protected function dockerComposeExists(): bool {
    $this->processRunner->run('docker compose version');
    if ($this->processRunner->getExitCode() === RunnerInterface::EXIT_SUCCESS) {
      return TRUE;
    }

    return $this->commandExists('docker-compose');
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
