<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Support;

/**
 * The control sequences the discovery scenarios script their answers with.
 *
 * A scenario states what it answers as the keys a person would press. Values
 * are supplied to collection now, so these survive only as the marker that an
 * entry navigates rather than types - see
 * AbstractHandlerDiscoveryTestCase::suppliedAnswers().
 */
final class Key {

  const string ENTER = "\n";

  const string DOWN = "\e[B";

  const string LEFT = "\e[D";

  const string BACKSPACE = "\177";

}
