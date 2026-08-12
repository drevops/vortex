<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Command;

use DrevOps\VortexCli\Form\VortexForm;
use DrevOps\VortexCli\Schema\AgentHelp;
use DrevOps\VortexCli\Schema\SchemaGenerator;
use DrevOps\VortexCli\Schema\SchemaValidator;
use DrevOps\VortexCli\Utils\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Lets a command describe and validate its questions without running them.
 *
 * The questions are declared by the build rather than by any one verb, so this
 * surface answers identically wherever it is mounted. That matters because a
 * bare invocation routes to a different verb depending on the target directory,
 * and an agent must be able to describe the questions either way.
 */
trait AgentSurfaceTrait {

  const OPTION_PROMPTS = 'prompts';

  const OPTION_SCHEMA = 'schema';

  const OPTION_VALIDATE = 'validate';

  const OPTION_AGENT_HELP = 'agent-help';

  /**
   * Add the options that make up the agent surface.
   */
  protected function addAgentSurfaceOptions(): void {
    $this->addOption(static::OPTION_PROMPTS, 'p', InputOption::VALUE_REQUIRED, 'A JSON string with prompt answers or a path to a JSON file. Keys are prompt IDs from --schema.');
    $this->addOption(static::OPTION_SCHEMA, NULL, InputOption::VALUE_NONE, 'Output prompt schema as JSON.');
    $this->addOption(static::OPTION_VALIDATE, NULL, InputOption::VALUE_NONE, 'Validate answers without making any changes.');
    $this->addOption(static::OPTION_AGENT_HELP, NULL, InputOption::VALUE_NONE, 'Output instructions for AI agents on how to use the CLI.');
  }

  /**
   * Answer an agent surface option, if one was requested.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   The input.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output.
   *
   * @return int|null
   *   The exit code when the surface answered, or NULL to carry on.
   */
  protected function handleAgentSurface(InputInterface $input, OutputInterface $output): ?int {
    if ($input->getOption(static::OPTION_AGENT_HELP)) {
      return $this->handleAgentHelp($output);
    }

    if ($input->getOption(static::OPTION_SCHEMA)) {
      return $this->handleSchema($output);
    }

    if ($input->getOption(static::OPTION_VALIDATE)) {
      return $this->handleValidate($input, $output);
    }

    return NULL;
  }

  /**
   * Handle --schema option.
   */
  protected function handleSchema(OutputInterface $output): int {
    $config = Config::fromString('{}');

    $generator = new SchemaGenerator(VortexForm::handlers($config));
    $schema = $generator->generate();

    $output->write((string) json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    return Command::SUCCESS;
  }

  /**
   * Handle --validate option.
   */
  protected function handleValidate(InputInterface $input, OutputInterface $output): int {
    $prompts_option = $input->getOption(static::OPTION_PROMPTS);

    if (empty($prompts_option) || !is_string($prompts_option)) {
      $output->writeln('The --validate option requires --prompts.');

      return Command::FAILURE;
    }

    if (is_file($prompts_option) && !is_readable($prompts_option)) {
      $output->writeln(sprintf('Cannot read --prompts file: %s.', $prompts_option));

      return Command::FAILURE;
    }

    $prompts_json = is_file($prompts_option) ? (string) file_get_contents($prompts_option) : $prompts_option;

    // Decoded twice on purpose: an associative decode renders both '{}' and
    // '[]' as an empty array, so the object shape can only be established from
    // the untyped decode.
    $decoded = json_decode($prompts_json);

    if (!$decoded instanceof \stdClass) {
      $output->writeln('Invalid JSON in --prompts. Expected a JSON object.');

      return Command::FAILURE;
    }

    $user_config = json_decode($prompts_json, TRUE);

    $config = Config::fromString('{}');

    $validator = new SchemaValidator(VortexForm::handlers($config));
    $result = $validator->validate($user_config);

    $output->write((string) json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    return $result['valid'] ? Command::SUCCESS : Command::FAILURE;
  }

  /**
   * Handle --agent-help option.
   */
  protected function handleAgentHelp(OutputInterface $output): int {
    $output->write(AgentHelp::render());

    return Command::SUCCESS;
  }

}
