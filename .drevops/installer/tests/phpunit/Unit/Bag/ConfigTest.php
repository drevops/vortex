<?php

namespace Drevops\Installer\Tests\Unit\Bag;

use DrevOps\Installer\Bag\Config;
use Drevops\Installer\Tests\Traits\EnvTrait;
use Drevops\Installer\Tests\Unit\UnitTestBase;
use DrevOps\Installer\Utils\Env;

/**
 * @coversDefaultClass \Drevops\Installer\Bag\Config
 * @runTestsInSeparateProcesses
 */
class ConfigTest extends UnitTestBase {

  use EnvTrait;

  /**
   * @covers ::__construct
   */
  public function testConstructor(): void {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Cannot instantiate Singleton class directly. Use ::getInstance() instead.');
    (new Config());
  }

  /**
   * @covers ::getInstance
   */
  public function ConfigInstance(): void {
    $first = Config::getInstance();
    $second = Config::getInstance();

    $this->assertSame($first, $second, 'Both instances should be the same');
  }

  /**
   * @covers ::__clone
   */
  public function testCloneIsDisabled(): void {
    Config::getInstance();

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Cloning of Singleton is disallowed.');
  }

  /**
   * @covers ::__wakeup
   */
  public function testUnserializeIsDisabled(): void {
    Config::getInstance();

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Unserializing instances of Singleton classes is disallowed.');
  }

  /**
   * @covers ::get
   * @covers ::set
   */
  public function testSetAndGet(): void {
    $config = Config::getInstance();

    $config->set('testKey', 'testValue');
    $this->assertEquals('testValue', $config->get('testKey'));
    $this->assertEquals('default', $config->get('nonExistentKey', 'default'));
  }

  /**
   * @covers ::set
   */
  public function testSetAndGetReadonly(): void {
    $config = Config::getInstance();

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage("Cannot modify a read-only config.");

    $config->setReadOnly();
    $config->set('testKey', 'testValue');
  }

  /**
   * @covers ::getAll
   * @covers ::__construct
   */
  public function testGetAll(): void {
    $config = Config::getInstance();

    $internal_keys = self::getAllConfigKeys();
    $this->assertEquals($internal_keys, array_keys($config->getAll()));

    $config->set('key1', 'value1');
    $config->set('key2', 'value2');

    $actual = $config->getAll();
    $this->assertEquals(array_merge($internal_keys, ['key1', 'key2']), array_keys($actual));
    $this->assertEquals('value1', $actual['key1']);
    $this->assertEquals('value2', $actual['key2']);
  }

  /**
   * @covers ::getAll
   * @covers ::__construct
   * @covers ::collectFromEnv
   */
  public function testGetAllEnv(): void {
    // Override internal env var.
    static::envSet(Env::INSTALLER_LOCAL_REPO, 'some/path');
    // Override non-internal env var.
    static::envSet(Env::WEBROOT, 'rootdoc');

    $config = Config::getInstance();

    $internal_keys = self::getAllConfigKeys();
    $this->assertEquals($internal_keys, array_keys($config->getAll()));

    $config->set('key1', 'value1');
    $config->set('key2', 'value2');

    $actual = $config->getAll();
    $this->assertEquals(array_merge($internal_keys, ['key1', 'key2']), array_keys($actual));
    $this->assertEquals('value1', $actual['key1']);
    $this->assertEquals('value2', $actual['key2']);
    $this->assertEquals('some/path', $actual[Env::INSTALLER_LOCAL_REPO]);
    $this->assertEquals('rootdoc', $actual[Env::WEBROOT]);
  }

  /**
   * @covers ::fromValues
   */
  public function testFromValues(): void {
    $config = Config::getInstance();
    $config->fromValues([
      'keyA' => 'valueA',
      'keyB' => 'valueB',
    ]);

    $this->assertEquals('valueA', $config->get('keyA'));
    $this->assertEquals('valueB', $config->get('keyB'));
    $this->assertEquals(getcwd(), $config->get(Env::INSTALLER_DST_DIR));

    $config = Config::getInstance();
    $config->fromValues([
      'path' => 'dst',
    ]);

    $this->assertEquals('valueA', $config->get('keyA'));
    $this->assertEquals('valueB', $config->get('keyB'));
    $this->assertEquals('dst', $config->get(Env::INSTALLER_DST_DIR));
  }

  /**
   * @covers ::clear
   */
  public function testClear(): void {
    $config = Config::getInstance();

    $config->set('someKey', 'someValue');
    $this->assertEquals('someValue', $config->get('someKey'));

    $config->clear();
    $this->assertEquals([], $config->getAll());
  }

  /**
   * @covers ::isQuiet
   */
  public function testIsQuiet(): void {
    $config = Config::getInstance();
    $this->assertEquals(FALSE, $config->isQuiet());

    $config->set('quiet', TRUE);
    $this->assertEquals(TRUE, $config->isQuiet());
  }

  /**
   * @covers ::getDstDir
   */
  public function testGetDstDirDefault(): void {
    $config = Config::getInstance();
    $this->assertEquals(getcwd(), $config->getDstDir());
  }

  /**
   * @covers ::getDstDir
   */
  public function testGetDstDirEnv(): void {
    static::envSet(Env::INSTALLER_DST_DIR, 'dst');
    $config = Config::getInstance();
    $this->assertEquals('dst', $config->getDstDir());
  }

  /**
   * @covers ::getWebroot
   */
  public function testGetWebrootDefault(): void {
    $config = Config::getInstance();
    $this->assertEquals('web', $config->getWebroot());
  }

  /**
   * @covers ::getWebroot
   */
  public function testGetWebrootEnv(): void {
    static::envSet(Env::WEBROOT, 'rootdoc');
    $config = Config::getInstance();
    $this->assertEquals('rootdoc', $config->getWebroot());
  }

  protected static function getAllConfigKeys(): array {
    return [
      Env::DB_DIR,
      Env::DB_DOCKER_IMAGE,
      Env::DB_DOWNLOAD_CURL_URL,
      Env::DB_DOWNLOAD_SOURCE,
      Env::DB_DOWNLOAD_SOURCE_DOCKER_REGISTRY,
      Env::DB_FILE,
      Env::DEPLOY_TYPES,
      Env::DRUPAL_THEME,
      Env::DRUPAL_VERSION,
      Env::INSTALLER_COMMIT,
      Env::INSTALLER_DEBUG,
      Env::INSTALLER_DEMO_MODE,
      Env::INSTALLER_DEMO_MODE_SKIP,
      Env::INSTALLER_DST_DIR,
      Env::INSTALLER_INSTALL_PROCEED,
      Env::INSTALLER_LOCAL_REPO,
      Env::INSTALLER_TMP_DIR,
      Env::PROJECT,
      Env::PROVISION_OVERRIDE_DB,
      Env::PROVISION_USE_PROFILE,
      Env::DREVOPS_VERSION,
      Env::DREVOPS_VERSION_URLENCODED,
      Env::WEBROOT,
    ];
  }

}
