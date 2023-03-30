<?php

namespace Drevops\Installer\Tests\Functional;

/**
 * Class ExampleScriptUnitTest.
 *
 * Unit tests for script.php.
 *
 * @group scripts
 *
 * phpcs:disable Drupal.Commenting.DocComment.MissingShort
 * phpcs:disable Drupal.Commenting.FunctionComment.Missing
 */
class MainTest extends FunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->disableScriptRun();
    parent::setUp();
  }

  /**
   * {@inheritdoc}
   */
  protected $script = 'install.php';

  /**
   * @dataProvider dataProviderMain
   * @runInSeparateProcess
   */
  public function testMain($args, $expected_code, $expected_output) {
    $args = is_array($args) ? $args : [$args];
    $result = $this->runScript($args, TRUE);
    $this->assertEquals($expected_code, $result['code']);
    $this->assertStringContainsString($expected_output, $result['output']);
  }

  public static function dataProviderMain() {
    return [
      [
        '--help',
        0,
        'DrevOps Installer',
      ],
    ];
  }

}
