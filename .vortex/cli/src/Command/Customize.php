<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Command;

use DrevOps\Customizer\Config\Config;
use DrevOps\Customizer\Config\ConfigLoader;
use DrevOps\Customizer\Engine\Engine;
use DrevOps\Customizer\Engine\EngineException;
use DrevOps\Customizer\Handler\Context;
use DrevOps\Customizer\Handler\HandlerRegistry;
use DrevOps\Customizer\Resolver\InputResolver;
use DrevOps\Customizer\Schema\AgentHelp;
use DrevOps\Customizer\Schema\SchemaGenerator;
use DrevOps\Customizer\Schema\SchemaValidator;
use DrevOps\Customizer\Tui\PanelController;
use DrevOps\Customizer\Tui\PanelRenderer;
use DrevOps\Customizer\Tui\Terminal;
use DrevOps\Customizer\Tui\Theme;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Customizes the project by driving the generic customizer engine.
 *
 * The CLI stays thin: it ships the configuration (`config/vortex.yml`) and the
 * handler classes (auto-discovered by question id), then delegates collection,
 * conditionals, derivation, discovery and rendering to `drevops/customizer`.
 *
 * @package DrevOps\VortexCli\Command
 */
class Customize extends Command {

  /**
   * The namespace the engine searches for handler classes.
   */
  protected const HANDLER_NAMESPACE = 'DrevOps\\VortexCli\\Handler';

  /**
   * The prefix for per-question environment variable overrides.
   */
  protected const ENV_PREFIX = 'VORTEX_';

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this
      ->setName('customize')
      ->setDescription('Customize the project by answering questions.')
      ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Path to the configuration YAML.')
      ->addOption('prompts', 'p', InputOption::VALUE_REQUIRED, 'Answers as a JSON string or a path to a JSON file.', '')
      ->addOption('dir', 'd', InputOption::VALUE_REQUIRED, 'The project directory.', '.')
      ->addOption('update', 'u', InputOption::VALUE_NONE, 'Update an existing project (enable discovery).')
      ->addOption('schema', NULL, InputOption::VALUE_NONE, 'Print the question schema as JSON and exit.')
      ->addOption('validate', NULL, InputOption::VALUE_REQUIRED, 'Validate an answer set (JSON) against the schema and exit.', '')
      ->addOption('agent-help', NULL, InputOption::VALUE_NONE, 'Print instructions for driving the customizer non-interactively.');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $config = (new ConfigLoader())->loadFiles([$this->configPath($input)]);

    if ($input->getOption('schema')) {
      $output->writeln((string) json_encode((new SchemaGenerator($config))->generate()));

      return Command::SUCCESS;
    }

    if ($input->getOption('agent-help')) {
      $output->writeln((new AgentHelp($config, static::ENV_PREFIX))->generate());

      return Command::SUCCESS;
    }

    $validate = $this->stringOption($input, 'validate');
    if ($validate !== '') {
      return $this->validateAnswers($config, $validate, $output);
    }

    $engine = new Engine($config, new HandlerRegistry([static::HANDLER_NAMESPACE]));
    $context = new Context($this->stringOption($input, 'dir'), [], (bool) $input->getOption('update'));
    $prompts = $this->stringOption($input, 'prompts');

    if (!$input->isInteractive() || $prompts !== '') {
      $inputs = (new InputResolver(static::ENV_PREFIX))->resolve($config->fields(), $prompts, getenv());

      try {
        $engine->run($inputs, $context);
      }
      catch (EngineException $engine_exception) {
        $output->writeln('<error>' . $engine_exception->getMessage() . '</error>');

        return Command::FAILURE;
      }

      $output->writeln($engine->answers()->toJson());

      return Command::SUCCESS;
    }

    // @codeCoverageIgnoreStart
    $engine->run([], $context);
    $controller = new PanelController($config, new PanelRenderer(new Theme('green')), $engine->answers()->values, $engine->answers()->provenance);
    $answers = $controller->run(new Terminal());
    $output->writeln($answers->toJson());

    return Command::SUCCESS;
    // @codeCoverageIgnoreEnd
  }

  /**
   * Validate a JSON answer set against the schema.
   *
   * @param \DrevOps\Customizer\Config\Config $config
   *   The configuration.
   * @param string $json
   *   The answer set as JSON.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output.
   *
   * @return int
   *   The exit code.
   */
  protected function validateAnswers(Config $config, string $json, OutputInterface $output): int {
    $decoded = json_decode($json, TRUE);
    $answers = [];
    if (is_array($decoded)) {
      foreach ($decoded as $key => $value) {
        $answers[(string) $key] = $value;
      }
    }

    $errors = (new SchemaValidator($config))->validate($answers);
    foreach ($errors as $error) {
      $output->writeln('<error>' . $error . '</error>');
    }

    if ($errors === []) {
      $output->writeln('The answer set is valid.');

      return Command::SUCCESS;
    }

    return Command::FAILURE;
  }

  /**
   * Resolve the configuration path (defaults to the bundled config).
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   The input.
   *
   * @return string
   *   The configuration path.
   */
  protected function configPath(InputInterface $input): string {
    $path = $this->stringOption($input, 'config');

    return $path !== '' ? $path : __DIR__ . '/../../config/vortex.yml';
  }

  /**
   * Read a string option, defaulting to empty.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   The input.
   * @param string $name
   *   The option name.
   *
   * @return string
   *   The option value.
   */
  protected function stringOption(InputInterface $input, string $name): string {
    $value = $input->getOption($name);

    return is_string($value) ? $value : '';
  }

}
