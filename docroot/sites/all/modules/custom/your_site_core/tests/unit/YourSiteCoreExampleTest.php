<?php

namespace Drupal\your_site_core\Tests;

/**
 * Class YourSiteCoreExampleTest.
 */
class YourSiteCoreExampleTest extends YourSiteCoreTestCase {

  /**
   * @dataProvider providerModuleListExample
   */
  public function testModuleListExample($value, $expected) {
    $list = module_list();
    $this->assertEquals($expected, in_array($value, $list));
  }

  public function providerModuleListExample() {
    return [
      ['system', TRUE],
      ['non_existing_module', FALSE],
    ];
  }

}
