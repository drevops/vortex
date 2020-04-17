<?php

namespace Drevops\Tests;

/**
 * Class InstallerDotEnvTest.
 *
 * phpcs:disable Drupal.Commenting.FunctionComment.Missing
 * phpcs:disable Drupal.Commenting.DocComment.MissingShort
 */
class InstallerDotEnvTest extends DrevopsTestCase {

  /**
   * Backup value of the $GLOBALS['_SERVER'] variable.
   *
   * @var array
   */
  protected $backupServer;

  /**
   * Backup value of the $GLOBALS['_ENV'] variable.
   *
   * @var array
   */
  protected $backupEnv;

  public function setUp(): void {
    $this->backupEnv = $GLOBALS['_ENV'];
    $this->backupServer = $GLOBALS['_SERVER'];

    parent::setUp();
  }

  public function tearDown(): void {
    $GLOBALS['_ENV'] = $this->backupEnv;
    $GLOBALS['_SERVER'] = $this->backupServer;
  }

  public function testGetEnv() {
    $content = 'var1=val1';
    $filename = $this->createFixtureEnvFile($content);

    $this->assertEmpty(getenv('var1'));
    load_dotenv($filename);
    $this->assertEquals('val1', getenv('var1'));

    // Try overloading with the same value - should not allow.
    $content = 'var1=val11';
    $filename = $this->createFixtureEnvFile($content);
    load_dotenv($filename);
    $this->assertEquals('val1', getenv('var1'));

    // Force overriding of existing variables.
    $content = 'var1=val11';
    $filename = $this->createFixtureEnvFile($content);
    load_dotenv($filename, TRUE);
    $this->assertEquals('val11', getenv('var1'));
  }

  /**
   * @dataProvider providerGlobals
   */
  public function testGlobals($content, $env_before, $server_before, $env_after, $server_after, $allow_override) {
    $filename = $this->createFixtureEnvFile($content);

    $GLOBALS['_ENV'] = $env_before;
    $GLOBALS['_SERVER'] = $server_before;

    load_dotenv($filename, $allow_override);

    $this->assertEquals($GLOBALS['_ENV'], $env_after);
    $this->assertEquals($GLOBALS['_SERVER'], $server_after);

    $this->assertTrue(TRUE);
  }

  public function providerGlobals() {
    return [
      [
        '', [], [], [], [], FALSE,
      ],
      [
        '', ['var1' => 'val1'], ['var2' => 'val2'], ['var1' => 'val1'], ['var2' => 'val2'], FALSE,
      ],
      // Simple value.
      [
        'var3=val3',
        ['var1' => 'val1'],
        ['var2' => 'val2'],
        ['var1' => 'val1', 'var3' => 'val3'],
        ['var2' => 'val2', 'var3' => 'val3'],
        FALSE,
      ],
      // Multiple values.
      [
        '
        var3=val3
        var4=val4
        ',
        ['var1' => 'val1'],
        ['var2' => 'val2'],
        ['var1' => 'val1', 'var3' => 'val3', 'var4' => 'val4'],
        ['var2' => 'val2', 'var3' => 'val3', 'var4' => 'val4'],
        FALSE,
      ],
      // Empty value.
      [
        'var3=',
        ['var1' => 'val1'],
        ['var2' => 'val2'],
        ['var1' => 'val1', 'var3' => ''],
        ['var2' => 'val2', 'var3' => ''],
        FALSE,
      ],
      [
        'var3=""',
        ['var1' => 'val1'],
        ['var2' => 'val2'],
        ['var1' => 'val1', 'var3' => ''],
        ['var2' => 'val2', 'var3' => ''],
        FALSE,
      ],
      // Preserve existing values.
      [
        '
        var1=val11
        var4=val4
        ',
        ['var1' => 'val1'],
        ['var2' => 'val2'],
        ['var1' => 'val1', 'var4' => 'val4'],
        ['var2' => 'val2', 'var1' => 'val11', 'var4' => 'val4'],
        FALSE,
      ],
      // Override existing values.
      [
        '
        var1=val11
        var4=val4
        ',
        ['var1' => 'val1'],
        ['var2' => 'val2'],
        ['var1' => 'val11', 'var4' => 'val4'],
        ['var2' => 'val2', 'var1' => 'val11', 'var4' => 'val4'],
        TRUE,
      ],
      // Comments.
      [
        '
        var3=val3
        # var4=val4
        ',
        ['var1' => 'val1'],
        ['var2' => 'val2'],
        ['var1' => 'val1', 'var3' => 'val3'],
        ['var2' => 'val2', 'var3' => 'val3'],
        FALSE,
      ],
      [
        '
        var3=val3
        var4=val4 # inline comment
        ',
        ['var1' => 'val1'],
        ['var2' => 'val2'],
        ['var1' => 'val1', 'var3' => 'val3', 'var4' => 'val4'],
        ['var2' => 'val2', 'var3' => 'val3', 'var4' => 'val4'],
        FALSE,
      ],
      [
        '
        var3=val3
        var4="val4 # inside"
        ',
        ['var1' => 'val1'],
        ['var2' => 'val2'],
        ['var1' => 'val1', 'var3' => 'val3', 'var4' => 'val4 # inside'],
        ['var2' => 'val2', 'var3' => 'val3', 'var4' => 'val4 # inside'],
        FALSE,
      ],
      [
        '
        var3=val3
        #var4="val4 # inside"
        ',
        ['var1' => 'val1'],
        ['var2' => 'val2'],
        ['var1' => 'val1', 'var3' => 'val3'],
        ['var2' => 'val2', 'var3' => 'val3'],
        FALSE,
      ],
      [
        '
        var3=val3
        var4="val4 # inside" # inline comment after code
        ',
        ['var1' => 'val1'],
        ['var2' => 'val2'],
        ['var1' => 'val1', 'var3' => 'val3', 'var4' => 'val4 # inside'],
        ['var2' => 'val2', 'var3' => 'val3', 'var4' => 'val4 # inside'],
        FALSE,
      ],
    ];
  }

  protected function createFixtureEnvFile($content) {
    $filename = tempnam(sys_get_temp_dir(), '.env');
    file_put_contents($filename, $content);

    return $filename;
  }

}
