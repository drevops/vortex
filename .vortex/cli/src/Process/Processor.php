<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Process;

use DrevOps\PhpTui\Answers\Answers;
use DrevOps\PhpTui\Handler\HandlerRegistry;
use DrevOps\VortexCli\Prompts\Handlers\CiProvider;
use DrevOps\VortexCli\Prompts\Handlers\HandlerInterface;
use DrevOps\VortexCli\Prompts\Handlers\HostingProvider;
use DrevOps\VortexCli\Prompts\Handlers\Internal;
use DrevOps\VortexCli\Prompts\Handlers\Starter;
use DrevOps\VortexCli\Utils\Config;

/**
 * Applies collected answers by running handlers in a fixed order.
 *
 * Every handler runs, not only the ones whose question was shown: a question
 * hidden by a condition still leaves its handler to act on the answer set as a
 * whole. Order is the CLI's concern rather than the form's - specific string
 * replacements must run before the generic ones they overlap with - so it comes
 * from the weight map and not from the order questions appear in.
 *
 * @package DrevOps\VortexCli\Process
 */
class Processor {

  /**
   * The handlers contributing guidance once the files are in place.
   */
  protected const array POST_INSTALL = [Starter::class, HostingProvider::class, CiProvider::class, Internal::class];

  /**
   * The handlers contributing guidance once a build has been attempted.
   */
  protected const array POST_BUILD = [Starter::class, HostingProvider::class, CiProvider::class];

  /**
   * Apply the collected answers to the project directory.
   *
   * @param \DrevOps\PhpTui\Answers\Answers $answers
   *   The self-describing answer set.
   * @param \DrevOps\PhpTui\Handler\HandlerRegistry $handlers
   *   The handler registry resolving a question id to its handler class.
   * @param \DrevOps\VortexCli\Utils\Config $config
   *   The CLI configuration the handlers operate on.
   * @param array<int,array{id:string,weight:int}> $processors
   *   The question-less processors that always run, each an id and a weight.
   * @param array<string,int> $weights
   *   The processing weight of each question id; lower processes earlier.
   *
   * @throws \RuntimeException
   *   When an id has no handler behind it.
   */
  public function apply(Answers $answers, HandlerRegistry $handlers, Config $config, array $processors, array $weights): void {
    $items = $weights;

    foreach ($processors as $processor) {
      $items[$processor['id']] = $processor['weight'];
    }

    asort($items);

    $responses = $this->responses($answers, $handlers, $config, $weights);

    foreach (array_keys($items) as $id) {
      $handler = $this->handler($handlers, $config, (string) $id)->setResponses($responses);

      // A question the form never asked has no answer to act on, so its
      // handler sits out - the questions it depends on already removed
      // whatever it would have removed.
      if (array_key_exists($id, $weights) && !$handler->shouldRun($responses)) {
        continue;
      }

      $handler->process();
    }
  }

  /**
   * The answer set every handler sees, including the questions never asked.
   *
   * A question hidden by a condition is not collected, but a handler still
   * reads it - and reads it expecting a value, not a missing key. Every
   * question is present, with the ones never asked holding NULL.
   *
   * @param \DrevOps\PhpTui\Answers\Answers $answers
   *   The collected answers.
   * @param \DrevOps\PhpTui\Handler\HandlerRegistry $handlers
   *   The handler registry resolving a question id to its handler class.
   * @param \DrevOps\VortexCli\Utils\Config $config
   *   The CLI configuration the handlers operate on.
   * @param array<string,int> $weights
   *   The processing weight of each question id; its keys are every question.
   *
   * @return array<string,mixed>
   *   The answers, keyed by every question id.
   */
  public function responses(Answers $answers, HandlerRegistry $handlers, Config $config, array $weights): array {
    return array_replace(array_fill_keys(array_keys($weights), NULL), $answers->values);
  }

  /**
   * Collect the guidance handlers offer once the files are in place.
   *
   * @param \DrevOps\PhpTui\Answers\Answers $answers
   *   The self-describing answer set.
   * @param \DrevOps\VortexCli\Utils\Config $config
   *   The CLI configuration the handlers operate on.
   *
   * @return string
   *   The concatenated messages; empty when no handler has anything to say.
   */
  public function postInstall(Answers $answers, Config $config): string {
    $messages = '';

    foreach (static::POST_INSTALL as $class) {
      $handler = new $class($config);
      $handler->setResponses($answers->values);
      $messages .= (string) $handler->postInstall();
    }

    return $messages;
  }

  /**
   * Collect the guidance handlers offer once a build has been attempted.
   *
   * @param \DrevOps\PhpTui\Answers\Answers $answers
   *   The self-describing answer set.
   * @param \DrevOps\VortexCli\Utils\Config $config
   *   The CLI configuration the handlers operate on.
   * @param string $result
   *   How the build ended.
   *
   * @return string
   *   The concatenated messages; empty when no handler has anything to say.
   */
  public function postBuild(Answers $answers, Config $config, string $result): string {
    $messages = '';

    foreach (static::POST_BUILD as $class) {
      $handler = new $class($config);
      $handler->setResponses($answers->values);
      $messages .= (string) $handler->postBuild($result);
    }

    return $messages;
  }

  /**
   * Resolve an id to its handler.
   *
   * @param \DrevOps\PhpTui\Handler\HandlerRegistry $handlers
   *   The handler registry.
   * @param \DrevOps\VortexCli\Utils\Config $config
   *   The CLI configuration the handler operates on.
   * @param string $id
   *   The question id.
   *
   * @return \DrevOps\VortexCli\Prompts\Handlers\HandlerInterface
   *   The handler.
   *
   * @throws \RuntimeException
   *   When the id has no handler behind it.
   */
  protected function handler(HandlerRegistry $handlers, Config $config, string $id): HandlerInterface {
    $class = $handlers->resolve($id);

    if ($class === NULL || !is_a($class, HandlerInterface::class, TRUE)) {
      throw new \RuntimeException(sprintf('Handler for "%s" not found.', $id));
    }

    return new $class($config);
  }

}
