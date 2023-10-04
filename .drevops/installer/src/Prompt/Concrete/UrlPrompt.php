<?php

namespace DrevOps\Installer\Prompt\Concrete;


use DrevOps\Installer\Bag\Answers;
use DrevOps\Installer\Bag\Config;
use DrevOps\Installer\Prompt\AbstractPrompt;
use DrevOps\Installer\Utils\Strings;
use DrevOps\Installer\Utils\Validator;

class UrlPrompt extends AbstractPrompt {

  const ID = 'url';

  /**
   * {@inheritdoc}
   */
  public static function title() {
    return 'URL';
  }

  public static function question() {
    return 'What is your site public URL?';
  }

  /**
   * {@inheritdoc}
   */
  protected function defaultValue(Config $config, Answers $answers): mixed {
    $value = $answers->get('machine_name', '');

    if ($value) {
      $value = str_replace('_', '-', $value);
      $value .= '.com';
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  protected function discoveredValue(Config $config, Answers $answers): mixed {
    $origin = NULL;

    $webroot = $config->getWebroot();
    $path = $config->getDstDir() . "/$webroot/sites/default/settings.php";

    if (!is_readable($path)) {
      return NULL;
    }

    $contents = file_get_contents($path);

    // Drupal 8+.
    if (preg_match('/\$config\s*\[\'stage_file_proxy.settings\'\]\s*\[\'origin\'\]\s*=\s*[\'"]([^\'"]+)[\'"];/', $contents, $matches)) {
      if (!empty($matches[1])) {
        $origin = $matches[1];
      }
    }
    // Drupal 7.
    elseif (preg_match('/\$conf\s*\[\'stage_file_proxy_origin\'\]\s*=\s*[\'"]([^\'"]+)[\'"];/', $contents, $matches)) {
      if (!empty($matches[1])) {
        $origin = $matches[1];
      }
    }

    if ($origin) {
      $origin = parse_url($origin, PHP_URL_HOST);
    }

    return !empty($origin) ? $origin : NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function validator(mixed $value, Config $config, Answers $answers): void {
    Validator::Url($value);
  }

  /**
   * {@inheritdoc}
   */
  protected function valueNormalizer($value, Config $config, Answers $answers): mixed {
    return Strings::toUrl($value);
  }

}
