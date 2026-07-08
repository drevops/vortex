<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Unit\Answers;

use DrevOps\Tui\Answers\Answers;
use DrevOps\Tui\Answers\SummaryFormatter;
use DrevOps\Tui\Builder\Form;
use DrevOps\Tui\Builder\PanelBuilder;
use DrevOps\Tui\Condition\Condition;
use DrevOps\Tui\Derive\Derive;
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
        $p->text('machine', 'Machine')->derive(new Derive('{{name}}'));
      })
      ->panel('drupal', 'Drupal', function (PanelBuilder $p): void {
        $p->text('profile', 'Profile');
        $p->panel('adv', 'Advanced', function (PanelBuilder $sp): void {
          $sp->confirm('debug', 'Debug');
        });
      })
      ->panel('empty', 'Empty', function (PanelBuilder $p): void {
        $p->text('gone', 'Gone')->when(new Condition('name', eq: 'never'));
      })
      ->build();
    $answers = Answers::forConfig(
      $config,
      ['name' => 'Acme', 'machine' => 'acme', 'profile' => 'standard', 'debug' => TRUE],
      ['name' => 'edited', 'machine' => 'derived', 'profile' => 'default', 'debug' => 'edited'],
    );

    $summary = (new SummaryFormatter())->format($answers);

    $this->assertStringContainsString('General', $summary);
    $this->assertStringContainsString('Name: Acme (edited)', $summary);
    $this->assertStringContainsString('Machine: acme (derived)', $summary);
    $this->assertStringContainsString('Drupal', $summary);
    $this->assertStringContainsString('Profile: standard', $summary);
    $this->assertStringContainsString('Advanced', $summary);
    $this->assertStringContainsString('Debug: yes (edited)', $summary);
    // Sub-panel content indents below its parent.
    $this->assertStringContainsString("Drupal\n  Profile: standard\n  Advanced\n    Debug: yes (edited)", $summary);
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
    $answers = Answers::forConfig($config, ['mods' => ['a', 'b']], ['mods' => 'edited']);

    $summary = (new SummaryFormatter())->format($answers);

    $this->assertStringContainsString('Mods: a, b', $summary);
  }

  public function testMasksPasswordValues(): void {
    $config = Form::create('T')
      ->panel('p', 'P', function (PanelBuilder $p): void {
        $p->password('token', 'Token');
        $p->password('unset', 'Unset');
      })
      ->build();
    $answers = Answers::forConfig($config, ['token' => 's3cret-long', 'unset' => ''], ['token' => 'edited', 'unset' => 'default']);

    $summary = (new SummaryFormatter())->format($answers);

    $this->assertStringNotContainsString('s3cret-long', $summary);
    // The mask has a fixed length so it does not leak the value's length.
    $this->assertStringContainsString('Token: ********', $summary);
    $this->assertStringContainsString('Unset: ', $summary);
  }

  public function testBareAnswersFormatEmpty(): void {
    // An answer set assembled without a configuration carries no snapshots.
    $this->assertSame('', (new SummaryFormatter())->format(new Answers(['name' => 'Acme'], ['name' => 'edited'])));
  }

}
