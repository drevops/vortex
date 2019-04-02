<?php

/**
 * Class YourSiteDrupalExampleTest.
 */
class YourSiteDrupalExampleTest extends YourSiteDrupalTestCase {

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
