<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Unit\Schema;

use DrevOps\Tui\Builder\Form;
use DrevOps\Tui\Builder\PanelBuilder;
use DrevOps\Tui\Condition\Condition;
use DrevOps\Tui\Config\Config;
use DrevOps\Tui\Schema\SchemaValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the schema validator.
 */
#[CoversClass(SchemaValidator::class)]
#[Group('schema')]
final class SchemaValidatorTest extends TestCase {

  public function testValidPasses(): void {
    $errors = (new SchemaValidator($this->config()))->validate([
      'name' => 'Acme',
      'profile' => 'standard',
      'agree' => TRUE,
      'mods' => ['a', 'b'],
    ]);

    $this->assertSame([], $errors);
  }

  public function testMissingRequired(): void {
    $errors = (new SchemaValidator($this->config()))->validate(['profile' => 'standard']);

    $this->assertContains('Missing required question "name".', $errors);
  }

  public function testRequiredEmptyString(): void {
    $errors = (new SchemaValidator($this->config()))->validate(['name' => '']);

    $this->assertContains('Question "name" is required.', $errors);
  }

  public function testWrongType(): void {
    $errors = (new SchemaValidator($this->config()))->validate(['name' => 'Acme', 'agree' => 'yes']);

    $this->assertContains('Question "agree" must be a boolean.', $errors);
  }

  public function testInvalidSelectOption(): void {
    $errors = (new SchemaValidator($this->config()))->validate(['name' => 'Acme', 'profile' => 'bogus']);

    $this->assertContains('Question "profile" must be one of: standard, minimal.', $errors);
  }

  public function testInvalidMultiselectOption(): void {
    $errors = (new SchemaValidator($this->config()))->validate(['name' => 'Acme', 'mods' => ['a', 'z']]);

    $this->assertContains('Question "mods" contains an invalid option "z".', $errors);
  }

  public function testMultiselectWrongType(): void {
    $errors = (new SchemaValidator($this->config()))->validate(['name' => 'Acme', 'mods' => 'notalist']);

    $this->assertContains('Question "mods" must be a list.', $errors);
  }

  public function testUnknownQuestion(): void {
    $errors = (new SchemaValidator($this->config()))->validate(['name' => 'Acme', 'bogus' => 'x']);

    $this->assertContains('Unknown question "bogus".', $errors);
  }

  public function testInactiveRequiredFieldSkipped(): void {
    // 'custom' is required but only appears when profile == custom.
    $errors = (new SchemaValidator($this->config()))->validate(['name' => 'Acme', 'profile' => 'standard']);

    $this->assertSame([], $errors);
  }

  public function testNumberAcceptsIntRejectsString(): void {
    $validator = new SchemaValidator($this->config());

    $this->assertSame([], $validator->validate(['name' => 'Acme', 'port' => 8080]));
    $this->assertContains('Question "port" must be a number.', $validator->validate(['name' => 'Acme', 'port' => '8080']));
  }

  public function testPauseAcceptsBoolRejectsString(): void {
    $validator = new SchemaValidator($this->config());

    $this->assertSame([], $validator->validate(['name' => 'Acme', 'ack' => TRUE]));
    $this->assertContains('Question "ack" must be a boolean.', $validator->validate(['name' => 'Acme', 'ack' => 'yes']));
  }

  public function testSearchOptionMembership(): void {
    $validator = new SchemaValidator($this->config());

    $this->assertSame([], $validator->validate(['name' => 'Acme', 'engine' => 'solr']));
    $this->assertContains('Question "engine" must be one of: solr, none.', $validator->validate(['name' => 'Acme', 'engine' => 'bogus']));
  }

  public function testMultisearchOptionMembership(): void {
    $validator = new SchemaValidator($this->config());

    $this->assertSame([], $validator->validate(['name' => 'Acme', 'tags' => ['a']]));
    $this->assertContains('Question "tags" contains an invalid option "z".', $validator->validate(['name' => 'Acme', 'tags' => ['z']]));
  }

  /**
   * Build a config exercising every validation branch.
   */
  protected function config(): Config {
    return Form::create('T')
      ->panel('p', 'p', function (PanelBuilder $p): void {
        $p->text('name')->required();
        $p->select('profile')->option('standard')->option('minimal');
        $p->confirm('agree');
        $p->multiselect('mods')->option('a')->option('b');
        $p->text('custom')->required()->when(new Condition('profile', eq: 'custom'));
        $p->number('port');
        $p->pause('ack');
        $p->search('engine')->option('solr')->option('none');
        $p->multisearch('tags')->option('a')->option('b');
      })
      ->build();
  }

}
