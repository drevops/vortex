<?php

namespace Drupal\your_site_core\Tests;

use PHPUnit\Framework\TestCase;

if (!defined('DRUPAL_ROOT')) {
  $docroot_path = sprintf('%s%s%s', getcwd(), DIRECTORY_SEPARATOR, 'docroot');
  $_SERVER['HTTP_HOST'] = getenv('LOCALDEV_URL');
  // @codingStandardsIgnoreStart
  $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
  // @codingStandardsIgnoreEnd
  define('DRUPAL_ROOT', $docroot_path);
  set_include_path($docroot_path . PATH_SEPARATOR . get_include_path());
  require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
  drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
}

/**
 * Class YourSiteCoreTestCase.
 *
 * Site core example test.
 *
 * @package Drupal\your_site_core\Tests
 */
abstract class YourSiteCoreTestCase extends TestCase {

  use YourSiteCoreTestHelperTrait;

}
