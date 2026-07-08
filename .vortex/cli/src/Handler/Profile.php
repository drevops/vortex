<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Builder\FieldBuilder;
use DrevOps\Tui\Builder\PanelBuilder;
use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Utils\Env;
use DrevOps\VortexCli\Utils\File;

/**
 * Handler for the "profile" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class Profile extends AbstractHandler implements OptionsInterface, FieldInterface {

  const STANDARD = 'standard';

  const MINIMAL = 'minimal';

  const DEMO_UMAMI = 'demo_umami';

  const CUSTOM = 'custom';

  /**
   * {@inheritdoc}
   */
  public function process(Field $field, mixed $value, Context $context): void {
    $v = is_string($value) ? $value : '';

    // If user selected 'custom', use the ProfileCustom response instead.
    $profile_custom = $context->answers['profile_custom'] ?? NULL;
    if ($v === 'custom' && is_string($profile_custom)) {
      $v = $profile_custom;
    }

    $t = $context->directory;
    $webroot = is_string($context->answers['webroot'] ?? NULL) ? $context->answers['webroot'] : 'web';

    Env::writeValueDotenv('DRUPAL_PROFILE', $v, $t . '/.env');

    // Assume that profiles provided as a path are contrib profiles.
    $is_contrib_profile = str_contains($v, DIRECTORY_SEPARATOR);

    if (in_array($v, ['standard', 'minimal', 'demo_umami'], TRUE) || $is_contrib_profile) {
      File::remove(sprintf('%s/%s/profiles/your_site_profile', $t, $webroot));
      File::remove(sprintf('%s/%s/profiles/custom/your_site_profile', $t, $webroot));

      File::replaceContentAsync([
        '/profiles/your_site_profile,' => '',
        '/profiles/custom/your_site_profile,' => '',
      ]);
    }
    else {
      File::replaceContentAsync('your_site_profile', $v);
      File::renameInDir($t, 'your_site_profile', $v);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function options(): array {
    return [
      self::STANDARD => 'Standard',
      self::MINIMAL => 'Minimal',
      self::DEMO_UMAMI => 'Demo Umami',
      self::CUSTOM => 'Custom (next prompt)',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function field(PanelBuilder $p): FieldBuilder {
    return $p->select('profile', 'Profile')
      ->description('The Drupal installation profile the site is built on.')
      ->default(fn (Context $c): string => ($c->answers['starter'] ?? '') === Starter::INSTALL_PROFILE_DRUPALCMS ? Starter::INSTALL_PROFILE_DRUPALCMS_PATH : self::STANDARD)->required()
      ->options(self::options())
      ->weight(270);
  }

}
