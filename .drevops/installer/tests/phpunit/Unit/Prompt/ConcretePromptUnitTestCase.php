<?php

namespace Drevops\Installer\Tests\Unit\Prompt;


use DrevOps\Installer\Bag\Answers;
use DrevOps\Installer\Bag\Config;
use Drevops\Installer\Tests\Traits\EnvTrait;
use Drevops\Installer\Tests\Traits\FixturesTrait;
use DrevOps\Installer\Utils\Env;
use Opis\Closure\SerializableClosure;

/**
 * Base test class for all specific prompt unit tests.
 *
 * It covers generic methods.
 */
abstract class ConcretePromptUnitTestCase extends PromptUnitTestCase {

  use FixturesTrait;
  use EnvTrait;

  protected static $class;

  protected function setUp(): void {
    parent::setUp();

    static::vfsSetRoot();
    static::fixturesPrepare();
  }

  protected function tearDown(): void {
    parent::tearDown();

    static::envReset();
  }

  /**
   * @covers ::title
   * @runInSeparateProcess
   */
  public function testTitle(): void {
    $prompt = static::getInstance();
    $this->assertNotEmpty($prompt::title());
  }

  /**
   * @covers ::question
   * @runInSeparateProcess
   */
  public function testQuestion(): void {
    $prompt = static::getInstance();
    $this->assertNotEmpty($prompt::question());
  }

  /**
   * @covers ::defaultValue
   * @dataProvider dataProviderDefaultValue
   * @runInSeparateProcess
   */
  public function testDefaultValue(array $config_values, array $answers_values, mixed $expected): void {
    $prompt = static::getInstance();
    $actual = $this->callProtectedMethod($prompt, 'defaultValue', [Config::getInstance()->fromValues($config_values), Answers::getInstance()->fromValues($answers_values)]);
    $this->assertEquals($expected, $actual);
  }

  abstract public static function dataProviderDefaultValue(): array;

  /**
   * @covers ::discoveredValue
   * @dataProvider dataProviderDiscoveredValue
   * @runInSeparateProcess
   */
  public function testDiscoveredValue(null|callable $prepare_callback, array $config_values, array $answers_values, mixed $expected): void {
    // Run prepare callback before instantiating a prompt so that all required
    // environment variables could be picked up by the Config.
    $prepare_callback = static::fnu($prepare_callback);
    if (is_callable($prepare_callback)) {
      $prepare_callback($config_values, $answers_values);
    }

    $prompt = static::getInstance();
    $actual = $this->callProtectedMethod($prompt, 'discoveredValue', [Config::getInstance()->fromValues($config_values), Answers::getInstance()->fromValues($answers_values)]);
    $this->assertEquals($expected, $actual);
  }

  abstract public static function dataProviderDiscoveredValue(): array;

  /**
   * @covers ::validator
   * @dataProvider dataProviderValidator
   * @runInSeparateProcess
   */
  public function testValidator(mixed $value, array $config_values, array $answers_values, mixed $expected_exception): void {
    if ($expected_exception) {
      $this->expectException(\Exception::class);
      $this->expectExceptionMessage($expected_exception);
    }

    $prompt = static::getInstance();
    $this->callProtectedMethod($prompt, 'validator', [$value, Config::getInstance()->fromValues($config_values), Answers::getInstance()->fromValues($answers_values)]);

    if (!$expected_exception) {
      $this->assertTrue(TRUE);
    }
  }

  abstract public static function dataProviderValidator(): array;

  /**
   * @covers ::valueNormalizer
   * @dataProvider dataProviderValueNormalizer
   * @runInSeparateProcess
   */
  public function testValueNormalizer(mixed $value, array $config_values, array $answers_values, mixed $expected): void {
    $prompt = static::getInstance();
    $actual = $this->callProtectedMethod($prompt, 'valueNormalizer', [$value, Config::getInstance()->fromValues($config_values), Answers::getInstance()->fromValues($answers_values)]);
    $this->assertEquals($expected, $actual);
  }

  abstract public static function dataProviderValueNormalizer(): array;

  protected function getInstance() {
    return new static::$class($this->io());
  }

}
