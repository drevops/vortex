<?php

declare(strict_types=1);

namespace Drupal\sw_base\Plugin\DeployStep;

use Drupal\Core\Recipe\Recipe;
use Drupal\Core\Recipe\RecipeRunner;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\deploy_steps\Attribute\DeployStep;
use Drupal\deploy_steps\DeployStepBase;
use Drupal\deploy_steps\DeployStepInterface;
use Drupal\deploy_steps\EnvironmentTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Creates the demo content model on non-production deploys.
 *
 * Applies the project 'page' recipe so the Basic page content type, its body
 * field and displays exist before the demo modules that attach behaviour to
 * that type are installed. Runs ahead of EnableDevelopmentModulesDeployStep in
 * the PRE phase (lower weight) for that reason. Idempotent - the step skips
 * once the content type exists - so it is safe on every deploy.
 *
 * @codeCoverageIgnore
 */
#[DeployStep(
  id: 'sw_base_content_model',
  label: new TranslatableMarkup('Content model setup'),
  weight: -10,
  phase: DeployStepInterface::PHASE_PRE,
)]
final class CreateContentModelDeployStep extends DeployStepBase {

  use EnvironmentTrait;

  /**
   * The Drupal application root.
   */
  protected string $appRoot;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $app_root = $container->getParameter('app.root');
    $instance->appRoot = is_string($app_root) ? $app_root : '';

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function skip(): ?string {
    if ($this->environment() === 'prod') {
      return 'production environment';
    }

    return $this->configFactory->get('node.type.page')->get('type') !== NULL ? 'content model already exists' : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function run(): void {
    // The recipe lives at the project root, but deploy steps run with the
    // Drupal root as the working directory, so it is resolved against the
    // parent of the Drupal root.
    $recipe = Recipe::createFromDirectory(dirname($this->appRoot) . '/recipes/page');
    RecipeRunner::processRecipe($recipe);
  }

}
