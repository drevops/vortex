<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Drush;

use Consolidation\Config\Config;
use Consolidation\SiteAlias\SiteAlias;
use Consolidation\SiteAlias\SiteAliasManager;
use DrevOps\VortexTooling\Drush\ProductionGuardListener;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\NullOutput;

/**
 * Tests for the Drush production guard listener.
 */
#[CoversClass(ProductionGuardListener::class)]
#[Group('drush')]
class ProductionGuardListenerTest extends TestCase {

  /**
   * The skip variable value before the test, or FALSE if it was unset.
   *
   * @var string|false
   */
  protected $skipVariableOriginal;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // An inherited value would bypass the guard in every case.
    $this->skipVariableOriginal = getenv(ProductionGuardListener::SKIP_VARIABLE);
    putenv(ProductionGuardListener::SKIP_VARIABLE);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    putenv($this->skipVariableOriginal === FALSE ? ProductionGuardListener::SKIP_VARIABLE : ProductionGuardListener::SKIP_VARIABLE . '=' . $this->skipVariableOriginal);

    parent::tearDown();
  }

  /**
   * Tests the guard against command targets.
   *
   * @param string $command
   *   The command name.
   * @param array<string, string> $arguments
   *   The command arguments.
   * @param string $self
   *   The name of the '@self' alias.
   * @param mixed $production_aliases
   *   The 'vortex.production_aliases' configuration value, or NULL to leave
   *   it unset.
   * @param string|null $skip
   *   The value of the skip variable, or NULL to leave it unset.
   * @param bool $expected_run
   *   Whether the command is expected to run.
   * @param string[] $expected_messages
   *   The expected log messages.
   * @param array<string, string> $resolved
   *   Alias records the alias manager returns, keyed by the requested name.
   *   Any other name besides '@self' has no record.
   */
  #[DataProvider('dataProviderInvoke')]
  public function testInvoke(string $command, array $arguments, string $self, mixed $production_aliases, ?string $skip, bool $expected_run, array $expected_messages = [], array $resolved = []): void {
    if ($skip !== NULL) {
      putenv(ProductionGuardListener::SKIP_VARIABLE . '=' . $skip);
    }

    $config = $production_aliases === NULL ? [] : ['vortex' => ['production_aliases' => $production_aliases]];
    $messages = [];
    $logger = static::createLogger($messages);

    $self_alias = new SiteAlias([], $self);
    $alias_manager = $this->createStub(SiteAliasManager::class);
    $alias_manager->method('getSelf')->willReturn($self_alias);
    $records = ['@self' => $self_alias] + array_map(fn(string $resolved_name): SiteAlias => new SiteAlias([], $resolved_name), $resolved);
    $alias_manager->method('get')->willReturnCallback(fn(string $name): SiteAlias|false => $records[$name] ?? FALSE);

    $listener = new ProductionGuardListener();
    $listener->setConfig(new Config($config));
    $listener->setLogger($logger);
    $listener->setSiteAliasManager($alias_manager);

    $command_object = new Command($command);
    $command_object->addArgument('source', InputArgument::OPTIONAL);
    $command_object->addArgument('target', InputArgument::OPTIONAL);

    $event = new ConsoleCommandEvent($command_object, new ArrayInput($arguments, $command_object->getDefinition()), new NullOutput());

    $listener($event);

    $this->assertSame($expected_run, $event->commandShouldRun());
    $this->assertSame(!$expected_run, $event->isPropagationStopped());
    $this->assertSame($expected_messages, $messages);
  }

  /**
   * Data provider for testInvoke().
   */
  public static function dataProviderInvoke(): \Iterator {
    $prod = ['prod'];

    yield 'sql:sync, production target' => ['sql:sync', ['source' => '@self', 'target' => '@prod'], '@self', $prod, NULL, FALSE, [static::blocked('sql:sync', '@prod')]];
    yield 'sql:sync, production target with a site' => ['sql:sync', ['source' => '@self', 'target' => '@your_site.prod'], '@self', $prod, NULL, FALSE, [static::blocked('sql:sync', '@your_site.prod')]];
    yield 'sql:sync, self target resolved to production' => ['sql:sync', ['source' => '@stage', 'target' => '@self'], '@prod', $prod, NULL, FALSE, [static::blocked('sql:sync', '@prod')]];
    yield 'sql:sync, production source' => ['sql:sync', ['source' => '@prod', 'target' => '@self'], '@self', $prod, NULL, TRUE];
    yield 'sql:sync, non-production target' => ['sql:sync', ['source' => '@prod', 'target' => '@stage'], '@self', $prod, NULL, TRUE];
    yield 'sql:sync, environment ending with the production name' => ['sql:sync', ['source' => '@self', 'target' => '@your_site.preprod'], '@self', $prod, NULL, TRUE];
    yield 'sql:sync, environment starting with the production name' => ['sql:sync', ['source' => '@self', 'target' => '@production'], '@self', $prod, NULL, TRUE];
    yield 'sql:sync, site named as the production environment' => ['sql:sync', ['source' => '@self', 'target' => '@prod.stage'], '@self', $prod, NULL, TRUE];
    yield 'sql:sync, production target resolved to a default environment' => ['sql:sync', ['source' => '@self', 'target' => '@prod'], '@self', $prod, NULL, FALSE, [static::blocked('sql:sync', '@prod')], ['@prod' => '@prod.live']];
    yield 'sql:sync, target resolved to a production environment' => ['sql:sync', ['source' => '@self', 'target' => '@live'], '@self', $prod, NULL, FALSE, [static::blocked('sql:sync', '@your_site.prod')], ['@live' => '@your_site.prod']];
    yield 'sql:sync, non-production target resolved to a default environment' => ['sql:sync', ['source' => '@self', 'target' => '@stage'], '@self', $prod, NULL, TRUE, [], ['@stage' => '@stage.live']];

    yield 'core:rsync, production target' => ['core:rsync', ['source' => '@self:%files', 'target' => '@prod:%files'], '@self', $prod, NULL, FALSE, [static::blocked('core:rsync', '@prod')]];
    yield 'core:rsync, production target without a path' => ['core:rsync', ['source' => '@self', 'target' => '@prod'], '@self', $prod, NULL, FALSE, [static::blocked('core:rsync', '@prod')]];
    yield 'core:rsync, production target resolved to a default environment' => ['core:rsync', ['source' => '@self:%files', 'target' => '@prod:%files'], '@self', $prod, NULL, FALSE, [static::blocked('core:rsync', '@prod')], ['@prod' => '@prod.live']];
    yield 'core:rsync, production source' => ['core:rsync', ['source' => '@prod:%files', 'target' => '@self:%files'], '@self', $prod, NULL, TRUE];
    yield 'core:rsync, path target' => ['core:rsync', ['source' => '@self:%files', 'target' => '/var/backups/files'], '@prod', $prod, NULL, TRUE];
    yield 'core:rsync, remote path target' => ['core:rsync', ['source' => '@self:%files', 'target' => 'user@prod:/var/www'], '@self', $prod, NULL, TRUE];

    yield 'sql:drop, production self' => ['sql:drop', [], '@prod', $prod, NULL, FALSE, [static::blocked('sql:drop', '@prod')]];
    yield 'sql:drop, non-production self' => ['sql:drop', [], '@self', $prod, NULL, TRUE];
    yield 'site:install, production self with a site' => ['site:install', [], '@lagoon.prod', $prod, NULL, FALSE, [static::blocked('site:install', '@lagoon.prod')]];
    yield 'site:install, non-production self' => ['site:install', [], '@lagoon.develop', $prod, NULL, TRUE];

    yield 'unguarded command, production self' => ['cache:rebuild', [], '@prod', $prod, NULL, TRUE];
    yield 'unguarded command, production target' => ['sql:query', ['source' => '@self', 'target' => '@prod'], '@prod', $prod, NULL, TRUE];

    yield 'skip variable set to 1' => ['sql:drop', [], '@prod', $prod, '1', TRUE, [static::bypassed('sql:drop', '@prod')]];
    yield 'skip variable set to 0' => ['sql:drop', [], '@prod', $prod, '0', FALSE, [static::blocked('sql:drop', '@prod')]];
    yield 'skip variable empty' => ['sql:drop', [], '@prod', $prod, '', FALSE, [static::blocked('sql:drop', '@prod')]];
    yield 'skip variable on a non-production target' => ['sql:drop', [], '@stage', $prod, '1', TRUE];

    yield 'config, single string' => ['sql:drop', [], '@prod', 'prod', NULL, FALSE, [static::blocked('sql:drop', '@prod')]];
    yield 'config, entry with a leading @' => ['sql:drop', [], '@prod', ['@prod'], NULL, FALSE, [static::blocked('sql:drop', '@prod')]];
    yield 'config, entry with a site' => ['sql:drop', [], '@lagoon.main', ['lagoon.main'], NULL, FALSE, [static::blocked('sql:drop', '@lagoon.main')]];
    yield 'config, entry with a site, other site' => ['sql:drop', [], '@other.main', ['lagoon.main'], NULL, TRUE];
    yield 'config, multiple entries' => ['sql:drop', [], '@lagoon.main', ['prod', 'main'], NULL, FALSE, [static::blocked('sql:drop', '@lagoon.main')]];
    yield 'config, invalid entries' => ['sql:drop', [], '@prod', [' ', '@', 123, NULL], NULL, TRUE];
    yield 'config, empty' => ['sql:drop', [], '@prod', [], NULL, TRUE];
    yield 'config, unset' => ['sql:drop', [], '@prod', NULL, NULL, TRUE];
  }

  /**
   * Tests that an event without a command is ignored.
   */
  public function testInvokeWithoutCommand(): void {
    $messages = [];
    $logger = static::createLogger($messages);

    $listener = new ProductionGuardListener();
    $listener->setConfig(new Config(['vortex' => ['production_aliases' => ['prod']]]));
    $listener->setLogger($logger);
    $listener->setSiteAliasManager(new SiteAliasManager());

    $event = new ConsoleCommandEvent(NULL, new ArrayInput([]), new NullOutput());

    $listener($event);

    $this->assertTrue($event->commandShouldRun());
    $this->assertSame([], $messages);
  }

  /**
   * Builds the message logged for a blocked command.
   */
  protected static function blocked(string $command, string $alias): string {
    return sprintf('error: Refusing to run %s against the production site alias %s. Set VORTEX_DRUSH_PRODUCTION_GUARD_SKIP=1 to bypass this guard.', $command, $alias);
  }

  /**
   * Builds the message logged for a bypassed command.
   */
  protected static function bypassed(string $command, string $alias): string {
    return sprintf('warning: Running %s against the production site alias %s because VORTEX_DRUSH_PRODUCTION_GUARD_SKIP is set to 1.', $command, $alias);
  }

  /**
   * Creates a logger that records interpolated messages.
   *
   * @param string[] $messages
   *   The array that receives the messages, prefixed with their level.
   */
  protected static function createLogger(array &$messages): LoggerInterface {
    return new class($messages) extends AbstractLogger {

      /**
       * Recorded messages, prefixed with their level.
       *
       * @var string[]
       */
      protected array $messages;

      /**
       * Constructs the logger.
       *
       * @param string[] $messages
       *   The array that receives the messages.
       */
      public function __construct(array &$messages) {
        $this->messages = &$messages;
      }

      /**
       * {@inheritdoc}
       */
      public function log($level, string|\Stringable $message, array $context = []): void {
        $replacements = [];

        foreach ($context as $key => $value) {
          $replacements['{' . $key . '}'] = is_scalar($value) ? (string) $value : '';
        }

        $this->messages[] = (is_string($level) ? $level : '') . ': ' . strtr((string) $message, $replacements);
      }

    };
  }

}
