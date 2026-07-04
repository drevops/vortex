<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Customizer\Config\Field;
use DrevOps\Customizer\Handler\AbstractHandler;
use DrevOps\Customizer\Handler\Context;
use DrevOps\VortexCli\Utils\Env;
use DrevOps\VortexCli\Utils\File;

/**
 * Handler for the "hosting_project_name" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class HostingProjectName extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function validate(Field $field, mixed $value): ?string {
    return is_string($value) && Validate::isPhpPackageName($value) ? NULL : 'Please enter a valid machine name: only lowercase letters, numbers, hyphens and underscores are allowed.';
  }

  /**
   * {@inheritdoc}
   */
  public function transform(Field $field, mixed $value): mixed {
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

}
