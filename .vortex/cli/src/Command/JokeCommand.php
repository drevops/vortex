<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Joke command.
 *
 * Allows to get a random joke.
 *
 * @package DrevOps\VortexCli\Command
 */
class JokeCommand extends Command {

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this
      ->setName('joke')
      ->addOption(
        name: 'topic',
        mode: InputOption::VALUE_OPTIONAL,
        default: 'general'
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $topic = $input->getOption('topic');
    $topic = is_string($topic) ? $topic : 'general';

    try {
      $joke = $this->getJoke($topic);
    }
    catch (\Exception) {
      $output->writeln('<error>Unable to retrieve a joke.</error>');

      return Command::FAILURE;
    }

    if (!isset($joke->setup) || !isset($joke->punchline)) {
      $output->writeln('<error>Unable to retrieve a joke.</error>');

      return Command::FAILURE;
    }

    $output->writeln($joke->setup);
    $output->writeln("<info>{$joke->punchline}</info>\n");

    return Command::SUCCESS;
  }

  /**
   * Get a joke from the API.
   *
   * @param string $topic
   *   Topic of the joke.
   */
  protected function getJoke(string $topic): object {
    $url = sprintf('https://official-joke-api.appspot.com/jokes/%s/random', $topic);

    $response = $this->getContent($url);

    $json = json_decode($response);

    if (empty($json)) {
      throw new \Exception(sprintf('Unable to decode the response from %s.', $url));
    }

    /** @var \stdClass $joke */
    [$joke] = (array) $json;

    return $joke;
  }

  /**
   * Get the content from the API.
   *
   * @param string $url
   *   URL to get the content from.
   */
  protected function getContent(string $url): string {
    // @codeCoverageIgnoreStart
    $response = file_get_contents($url);

    if (empty($response)) {
      throw new \Exception(sprintf('Unable to retrieve a joke from %s.', $url));
    }

    return $response;
    // @codeCoverageIgnoreEnd
  }

}
