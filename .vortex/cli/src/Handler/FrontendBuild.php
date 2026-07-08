<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Builder\FieldBuilder;
use DrevOps\Tui\Builder\PanelBuilder;
use DrevOps\Tui\Condition\Condition;
use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Utils\Env;

/**
 * Handler for the "frontend_build" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class FrontendBuild extends AbstractHandler implements FieldInterface {

  /**
   * {@inheritdoc}
   */
  public function process(Field $field, mixed $value, Context $context): void {
    if (is_bool($value)) {
      Env::writeValueDotenv('VORTEX_FRONTEND_BUILD_SKIP', $value ? '0' : '1', $context->directory . '/.env');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function field(PanelBuilder $p): FieldBuilder {
    return $p->confirm('frontend_build', 'Build front-end assets in the container?')
      ->description('Disable to build theme assets on the host or as part of deployment.')
      ->default(TRUE)
      ->when(new Condition('theme', eq: Theme::CUSTOM))
      ->weight(320);
  }

}
