<?php

declare(strict_types=1);

namespace Drupal\ys_deploy\Plugin\DeployStep;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ys_deploy\Attribute\DeployStep;
use Drupal\ys_deploy\DeployStepBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Records the environment the most recent deploy ran against.
 *
 * A minimal, safe example deploy step that demonstrates the pattern: dependency
 * injection via create(), the inherited environment() helper, and an idempotent
 * run(). Remove it on a real project, and add your own steps as DeployStep
 * plugins in any enabled module's Plugin/DeployStep/ namespace.
 */
#[DeployStep(
  id: 'record_environment',
  label: new TranslatableMarkup('Record deployment environment'),
  weight: 0,
)]
final class RecordEnvironment extends DeployStepBase implements ContainerFactoryPluginInterface {

  /**
   * The state key the deployed environment is recorded under.
   */
  public const STATE_KEY = 'ys_deploy.deployed_environment';

  /**
   * Constructs a RecordEnvironment object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
    protected readonly StateInterface $state,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self($configuration, $plugin_id, $plugin_definition, $container->get('state'));
  }

  /**
   * {@inheritdoc}
   */
  public function run(): void {
    $this->state->set(self::STATE_KEY, $this->environment());
  }

}
