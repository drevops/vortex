<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Unit\Answers;

use DrevOps\Tui\Answers\Answers;
use DrevOps\Tui\Answers\SummaryFormatter;
use DrevOps\Tui\Config\ConfigLoader;
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
    $config = (new ConfigLoader())->fromArray([
      'panels' => [
        ['id' => 'general', 'title' => 'General', 'fields' => [
          ['id' => 'name', 'label' => 'Name'],
          ['id' => 'machine', 'label' => 'Machine', 'derive' => ['template' => '{{name}}']],
        ]],
        ['id' => 'drupal', 'title' => 'Drupal', 'fields' => [
          ['id' => 'profile', 'label' => 'Profile'],
        ], 'panels' => [
          ['id' => 'adv', 'title' => 'Advanced', 'fields' => [['id' => 'debug', 'label' => 'Debug', 'type' => 'confirm']]],
        ]],
        ['id' => 'empty', 'title' => 'Empty', 'fields' => [['id' => 'gone', 'label' => 'Gone', 'when' => ['field' => 'name', 'eq' => 'never']]]],
      ],
    ]);
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
    $config = (new ConfigLoader())->fromArray([
      'panels' => [['id' => 'p', 'title' => 'P', 'fields' => [['id' => 'mods', 'label' => 'Mods', 'type' => 'multiselect']]]],
    ]);
    $answers = new Answers(['mods' => ['a', 'b']], ['mods' => 'edited']);

    $summary = (new SummaryFormatter())->format($config, $answers);

    $this->assertStringContainsString('Mods: a, b', $summary);
  }

}
