<?php

namespace DrevOps\Installer\Utils\Tests;

use DrevOps\Installer\Utils\Validator;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \DrevOps\Installer\Utils\Validator
 */
class ValidatorTest extends TestCase {

  /**
   * @dataProvider dataProviderNotEmpty
   * @covers ::notEmpty
   */
  public function testNotEmpty(string|int|array|null $value, bool $expected): void {
    if ($expected) {
      $this->expectException(\Exception::class);
      $this->expectExceptionMessage('The value cannot be empty.');
    }

    Validator::notEmpty($value);

    if (!$expected) {
      $this->assertTrue(TRUE);
    }
  }

  public static function dataProviderNotEmpty(): array {
    return [
      [NULL, TRUE],
      ['', TRUE],
      [[], TRUE],

      ['a', FALSE],
      [1, FALSE],
      [['a'], FALSE],
    ];
  }

  /**
   * @dataProvider dataProviderHumanName
   * @covers ::humanName
   */
  public function testHumanName(?string $value, bool $expected): void {
    if ($expected) {
      $this->expectException(\Exception::class);
      $this->expectExceptionMessage('The name must contain only letters, numbers, and dashes.');
    }

    Validator::humanName($value);

    if (!$expected) {
      $this->assertTrue(TRUE);
    }
  }

  public static function dataProviderHumanName(): array {
    return [
      ['John-Doe', FALSE],
      ['JohnDoe123', FALSE],
      [NULL, TRUE],
      ['', TRUE],
      ['John#Doe', TRUE],
      ['@JohnDoe', TRUE],
    ];
  }

  /**
   * @dataProvider dataProviderMachineName
   * @covers ::machineName
   */
  public function testMachineName(?string $value, bool $expected): void {
    if ($expected) {
      $this->expectException(\Exception::class);
      $this->expectExceptionMessage('The name must contain only lowercase letters, numbers, and underscores.');
    }

    Validator::machineName($value);

    if (!$expected) {
      $this->assertTrue(TRUE);
    }
  }

  public static function dataProviderMachineName(): array {
    return [
      ['machine_name', FALSE],
      [NULL, TRUE],
      ['', TRUE],
      ['machine-name', TRUE],
      ['MACHINE_NAME', TRUE],
      ['machineName123', TRUE],
    ];
  }

  /**
   * @dataProvider dataProviderInList
   * @covers ::inList
   */
  public function testInList(array $items, string|array $value, bool $is_multiple, bool|array $expected): void {
    if ($expected) {
      $this->expectException(\Exception::class);
      $this->expectExceptionMessage('The following values are not valid: ' . implode(', ', $expected));
    }

    Validator::inList($items, $value, $is_multiple);

    if (!$expected) {
      $this->assertTrue(TRUE);
    }
  }

  public static function dataProviderInList(): array {
    return [
      [[], 'apple', FALSE, ['apple']],
      [['apple', 'orange'], 'apple', FALSE, FALSE],
      [['apple', 'orange'], 'banana', FALSE, ['banana']],
      [['apple', 'orange'], ['banana', 'apple'], FALSE, ['banana']],

      [['apple', 'orange'], 'apple', TRUE, FALSE],
      [['apple', 'orange'], ['apple', 'orange'], TRUE, FALSE],
      [['apple', 'orange'], 'banana', TRUE, ['banana']],
      [['apple', 'orange'], ['banana', 'apple'], TRUE, ['banana']],
    ];
  }

  /**
   * @dataProvider dataProviderDockerImageName
   * @covers ::dockerImageName
   */
  public function testDockerImageName(?string $value, bool $expected): void {
    if ($expected) {
      $this->expectException(\Exception::class);
      $this->expectExceptionMessage('The name must contain only lowercase letters, numbers, dashes, and underscores.');
    }

    Validator::dockerImageName($value);

    if (!$expected) {
      $this->assertTrue(TRUE);
    }
  }

  public static function dataProviderDockerImageName(): array {
    return [
      ['alpine', FALSE],
      ['alpine:latest', FALSE],
      ['localhost/latest', FALSE],
      ['library/alpine', FALSE],
      ['localhost:1234/test', FALSE],
      ['test:1234/blaboon', FALSE],
      ['alpine:3.7', FALSE],
      ['docker.example.edu/gmr/alpine:3.7', FALSE],
      ['docker.example.com:5000/gmr/alpine@sha256:5a156ff125e5a12ac7ff43ee5120fa249cf62248337b6d04abc574c8', FALSE],
      ['docker.example.co.uk/gmr/alpine/test2:latest', FALSE],
      ['registry.dobby.org/dobby/dobby-servers/arthound:2019-08-08', FALSE],
      ['owasp/zap:3.8.0', FALSE],
      ['registry.dobby.co/dobby/dobby-servers/github-run:2021-10-04', FALSE],
      ['docker.elastic.co/kibana/kibana:7.6.2', FALSE],
      ['registry.dobby.org/dobby/dobby-servers/lerphound:latest', FALSE],
      ['registry.dobby.org/dobby/dobby-servers/marbletown-poc:2021-03-29', FALSE],
      ['marbles/marbles:v0.38.1', FALSE],
      ['registry.dobby.org/dobby/dobby-servers/loophole@sha256:5a156ff125e5a12ac7ff43ee5120fa249cf62248337b6d04abc574c8', FALSE],
      ['sonatype/nexon:3.30.0', FALSE],
      ['prom/node-exporter:v1.1.1', FALSE],
      ['sosedoff/pgweb@sha256:5a156ff125e5a12ac7ff43ee5120fa249cf62248337b6d04abc574c8', FALSE],
      ['sosedoff/pgweb:latest', FALSE],
      ['registry.dobby.org/dobby/dobby-servers/arpeggio:2021-06-01', FALSE],
      ['registry.dobby.org:5000/dobby/antique-penguin:release-production', FALSE],
      ['dalprodictus/halcon:6.7.5', FALSE],
      ['antigua/antigua:v31', FALSE],
      ['weblate/weblate:4.7.2-1', FALSE],
      ['redis:4.0.01-alpine', FALSE],
      ['registry.dobby.com/dobby/dobby-servers/github-run:latest', FALSE],
      [NULL, TRUE],
      ['alp+ine', TRUE],
      ['registry.dobby.com/dobby/dobby-servers/github-run::latest', TRUE],
      ['registry.dobby.com/dobby/dobby-servers/github-run:+:latest', TRUE],
    ];
  }

  /**
   * @dataProvider dataProviderUrl
   * @covers ::url
   */
  public function testUrl(?string $value, bool $require_protocol, bool $expected): void {
    if ($expected) {
      $this->expectException(\Exception::class);
      $this->expectExceptionMessage('The URL is not valid.');
    }

    Validator::url($value, $require_protocol);

    if (!$expected) {
      $this->assertTrue(TRUE);
    }
  }

  public static function dataProviderUrl(): array {
    return [
      ['example.com', FALSE, FALSE],
      ['example.com/', FALSE, FALSE],

      ['a.example.com', FALSE, FALSE],
      ['a.example.com/', FALSE, FALSE],
      ['a.b.c.example.com', FALSE, FALSE],
      ['a.b.c.example.com/', FALSE, FALSE],
      ['user:pass@example.com', FALSE, FALSE],
      ['user:pass@example.com/', FALSE, FALSE],
      ['user:pass@a.example.com', FALSE, FALSE],
      ['user:pass@a.example.com/', FALSE, FALSE],
      ['user:pass@a.b.c.example.com', FALSE, FALSE],
      ['user:pass@a.b.c.example.com/', FALSE, FALSE],
      ['user:pass@www.example.com', FALSE, FALSE],
      ['user:pass@www.example.com/', FALSE, FALSE],

      ['//example.com', FALSE, FALSE],
      ['//example.com/', FALSE, FALSE],
      ['//a.example.com', FALSE, FALSE],
      ['//a.example.com/', FALSE, FALSE],
      ['//a.b.c.example.com', FALSE, FALSE],
      ['//a.b.c.example.com/', FALSE, FALSE],
      ['//user:pass@example.com', FALSE, FALSE],
      ['//user:pass@example.com/', FALSE, FALSE],
      ['//user:pass@a.example.com', FALSE, FALSE],
      ['//user:pass@a.example.com/', FALSE, FALSE],
      ['//user:pass@a.b.c.example.com', FALSE, FALSE],
      ['//user:pass@a.b.c.example.com/', FALSE, FALSE],
      ['//user:pass@www.example.com', FALSE, FALSE],
      ['//user:pass@www.example.com/', FALSE, FALSE],

      ['http://example.com', FALSE, FALSE],
      ['http://example.com/', FALSE, FALSE],
      ['http://a.example.com', FALSE, FALSE],
      ['http://a.example.com/', FALSE, FALSE],
      ['http://a.b.c.example.com', FALSE, FALSE],
      ['http://a.b.c.example.com/', FALSE, FALSE],
      ['http://user:pass@example.com', FALSE, FALSE],
      ['http://user:pass@example.com/', FALSE, FALSE],
      ['http://user:pass@a.example.com', FALSE, FALSE],
      ['http://user:pass@a.example.com/', FALSE, FALSE],
      ['http://user:pass@a.b.c.example.com', FALSE, FALSE],
      ['http://user:pass@a.b.c.example.com/', FALSE, FALSE],
      ['http://user:pass@www.example.com', FALSE, FALSE],
      ['http://user:pass@www.example.com/', FALSE, FALSE],

      ['https://example.com', FALSE, FALSE],
      ['https://example.com/', FALSE, FALSE],
      ['https://a.example.com', FALSE, FALSE],
      ['https://a.example.com/', FALSE, FALSE],
      ['https://a.b.c.example.com', FALSE, FALSE],
      ['https://a.b.c.example.com/', FALSE, FALSE],
      ['https://user:pass@example.com', FALSE, FALSE],
      ['https://user:pass@example.com/', FALSE, FALSE],
      ['https://user:pass@a.example.com', FALSE, FALSE],
      ['https://user:pass@a.example.com/', FALSE, FALSE],
      ['https://user:pass@a.b.c.example.com', FALSE, FALSE],
      ['https://user:pass@a.b.c.example.com/', FALSE, FALSE],
      ['https://user:pass@www.example.com', FALSE, FALSE],
      ['https://user:pass@www.example.com/', FALSE, FALSE],

      // Require protocol.
      ['example.com', TRUE, TRUE],
      ['example.com/', TRUE, TRUE],

      ['a.example.com', TRUE, TRUE],
      ['a.example.com/', TRUE, TRUE],
      ['a.b.c.example.com', TRUE, TRUE],
      ['a.b.c.example.com/', TRUE, TRUE],
      ['user:pass@example.com', TRUE, TRUE],
      ['user:pass@example.com/', TRUE, TRUE],
      ['user:pass@a.example.com', TRUE, TRUE],
      ['user:pass@a.example.com/', TRUE, TRUE],
      ['user:pass@a.b.c.example.com', TRUE, TRUE],
      ['user:pass@a.b.c.example.com/', TRUE, TRUE],
      ['user:pass@www.example.com', TRUE, TRUE],
      ['user:pass@www.example.com/', TRUE, TRUE],

      ['//example.com', TRUE, TRUE],
      ['//example.com/', TRUE, TRUE],
      ['//a.example.com', TRUE, TRUE],
      ['//a.example.com/', TRUE, TRUE],
      ['//a.b.c.example.com', TRUE, TRUE],
      ['//a.b.c.example.com/', TRUE, TRUE],
      ['//user:pass@example.com', TRUE, TRUE],
      ['//user:pass@example.com/', TRUE, TRUE],
      ['//user:pass@a.example.com', TRUE, TRUE],
      ['//user:pass@a.example.com/', TRUE, TRUE],
      ['//user:pass@a.b.c.example.com', TRUE, TRUE],
      ['//user:pass@a.b.c.example.com/', TRUE, TRUE],
      ['//user:pass@www.example.com', TRUE, TRUE],
      ['//user:pass@www.example.com/', TRUE, TRUE],

      ['http://example.com', TRUE, FALSE],
      ['http://example.com/', TRUE, FALSE],
      ['http://a.example.com', TRUE, FALSE],
      ['http://a.example.com/', TRUE, FALSE],
      ['http://a.b.c.example.com', TRUE, FALSE],
      ['http://a.b.c.example.com/', TRUE, FALSE],
      ['http://user:pass@example.com', TRUE, FALSE],
      ['http://user:pass@example.com/', TRUE, FALSE],
      ['http://user:pass@a.example.com', TRUE, FALSE],
      ['http://user:pass@a.example.com/', TRUE, FALSE],
      ['http://user:pass@a.b.c.example.com', TRUE, FALSE],
      ['http://user:pass@a.b.c.example.com/', TRUE, FALSE],
      ['http://user:pass@www.example.com', TRUE, FALSE],
      ['http://user:pass@www.example.com/', TRUE, FALSE],

      ['https://example.com', TRUE, FALSE],
      ['https://example.com/', TRUE, FALSE],
      ['https://a.example.com', TRUE, FALSE],
      ['https://a.example.com/', TRUE, FALSE],
      ['https://a.b.c.example.com', TRUE, FALSE],
      ['https://a.b.c.example.com/', TRUE, FALSE],
      ['https://user:pass@example.com', TRUE, FALSE],
      ['https://user:pass@example.com/', TRUE, FALSE],
      ['https://user:pass@a.example.com', TRUE, FALSE],
      ['https://user:pass@a.example.com/', TRUE, FALSE],
      ['https://user:pass@a.b.c.example.com', TRUE, FALSE],
      ['https://user:pass@a.b.c.example.com/', TRUE, FALSE],
      ['https://user:pass@www.example.com', TRUE, FALSE],
      ['https://user:pass@www.example.com/', TRUE, FALSE],

      [NULL, FALSE, TRUE],
      ['', FALSE, TRUE],
      ['a_b.c', FALSE, TRUE],

      ['ex_ample.com', FALSE, TRUE],
      ['_example.com', FALSE, TRUE],
      ['a._example.com', FALSE, TRUE],
      ['_a.example.com', FALSE, TRUE],
      ['_a._b.example.com', FALSE, FALSE],
      ['a..example.com', FALSE, TRUE],
      ['example', FALSE, TRUE],
      ['example.c', FALSE, TRUE],
      ['.example.com', FALSE, TRUE],
      ['example-.com' , FALSE, TRUE],
      ['example.com-', FALSE, TRUE],

    ];
  }

}
