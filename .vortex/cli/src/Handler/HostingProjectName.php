<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Builder\FieldBuilder;
use DrevOps\Tui\Builder\PanelBuilder;
use DrevOps\Tui\Condition\Condition;
use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Derive\Derive;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Utils\Env;
use DrevOps\VortexCli\Utils\File;

/**
 * Handler for the "hosting_project_name" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class HostingProjectName extends AbstractHandler implements FieldInterface {

  /**
   * Validate the collected value.
   *
   * @param mixed $value
   *   The value.
   *
   * @return string|null
   *   An error message, or NULL when valid.
   */
  public static function validate(mixed $value): ?string {
    return is_string($value) && Validate::isPhpPackageName($value) ? NULL : 'Please enter a valid machine name: only lowercase letters, numbers, hyphens and underscores are allowed.';
  }

  /**
   * Normalize the collected value.
   *
   * @param mixed $value
   *   The value.
   *
   * @return mixed
   *   The normalized value.
   */
  public static function transform(mixed $value): mixed {
    return is_string($value) ? trim($value) : $value;
  }

  /**
   * {@inheritdoc}
   */
  public function process(Field $field, mixed $value, Context $context): void {
    $hosting = $context->answers['hosting_provider'] ?? NULL;

    if (!in_array($hosting, ['lagoon', 'acquia'], TRUE)) {
      return;
    }

    $name = is_string($value) ? $value : '';

    Env::writeValueDotenv('VORTEX_ACQUIA_APP_NAME', $name, $context->directory . '/.env');
    Env::writeValueDotenv('LAGOON_PROJECT', $name, $context->directory . '/.env');
    File::replaceContentInFile($context->directory . '/drush/sites/lagoon.site.yml', 'your_site-${env-name}', $name . '-${env-name}');
    File::replaceContentInFile($context->directory . '/drush/sites/lagoon.site.yml', '.your_site.au2.amazee.io', '.' . $name . '.au2.amazee.io');
  }

  /**
   * {@inheritdoc}
   */
  public static function field(PanelBuilder $p): FieldBuilder {
    return $p->text('hosting_project_name', 'Hosting project name')
      ->description('Name as found in the hosting configuration; usually the site machine name.')
      ->required()
      ->when(new Condition('hosting_provider', in: [HostingProvider::LAGOON, HostingProvider::ACQUIA]))
      ->derive(new Derive('{{machine_name}}'))
      ->weight(290);
  }

}
