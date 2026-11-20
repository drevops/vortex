<?php

declare(strict_types=1);

namespace Drupal\light_saber\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Site\Settings;

/**
 * Hook implementations for light_saber.
 */
class LightSaberHooks {

  /**
   * Implements hook_preprocess_HOOK() for html.
   */
  #[Hook('preprocess_html')]
  public function preprocessHtml(array &$variables): void {
    $this->preprocessNavigation($variables);
  }

  /**
   * Preprocess administration navigation.
   */
  protected function preprocessNavigation(array &$variables): void {
    // The 'hide_admin_navigation' setting removes the administration navigation
    // so pages can be themed while logged in as an administrator.
    if (Settings::get('hide_admin_navigation')) {
      unset($variables['page_top']['navigation']);
      unset($variables['page_top']['top_bar']);
    }
  }

}
