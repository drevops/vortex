<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Unit\Answers;

use DrevOps\Tui\Answers\Answers;
use DrevOps\Tui\Answers\SummaryFormatter;
use DrevOps\Tui\Builder\Form;
use DrevOps\Tui\Builder\PanelBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the answers summary formatter.
 */
#[CoversClass(SummaryFormatter::class)]
#[Group('answers')]
final class SummaryFormatterTest extends TestCase {

  public function testFormatsGroupedByPanel(): void {
    $config = Form::create('T')
      ->panel('general', 'General', function (PanelBuilder $p): void {
        $p->text('name', 'Name');
        $p->text('machine', 'Machine')->derive(['template' => '{{name}}']);
      })
      ->panel('drupal', 'Drupal', function (PanelBuilder $p): void {
        $p->text('profile', 'Profile');
        $p->panel('adv', 'Advanced', function (PanelBuilder $sp): void {
          $sp->confirm('debug', 'Debug');
        });
      })
      ->panel('empty', 'Empty', function (PanelBuilder $p): void {
        $p->text('gone', 'Gone')->when(['field' => 'name', 'eq' => 'never']);
      })
      ->build();
    $answers = new Answers(
      ['name' => 'Acme', 'machine' => 'acme', 'profile' => 'standard', 'debug' => TRUE],
      ['name' => 'edited', 'machine' => 'derived', 'profile' => 'default', 'debug' => 'edited'],
    );

    $summary = (new SummaryFormatter())->format($config, $answers);

    $this->assertStringContainsString('General', $summary);
    $this->assertStringContainsString('Name: Acme (edited)', $summary);
    $this->assertStringContainsString('Machine: acme (derived)', $summary);
    $this->assertStringContainsString('Drupal', $summary);
    $this->assertStringContainsString('Profile: standard', $summary);
    $this->assertStringContainsString('Advanced', $summary);
    $this->assertStringContainsString('Debug: yes (edited)', $summary);
    // Defaults carry no badge.
    $this->assertStringNotContainsString('(default)', $summary);
    // A panel with no active answers is omitted.
    $this->assertStringNotContainsString('Empty', $summary);
    $this->assertStringNotContainsString('Gone', $summary);
  }

  public function testFormatsListValues(): void {
    $config = Form::create('T')
      ->panel('p', 'P', function (PanelBuilder $p): void {
        $p->multiselect('mods', 'Mods');
      })
      ->build();
    $answers = new Answers(['mods' => ['a', 'b']], ['mods' => 'edited']);

    $summary = (new SummaryFormatter())->format($config, $answers);

    $this->assertStringContainsString('Mods: a, b', $summary);
  }

}
