#!/usr/bin/env php
<?php

/**
 * @file
 * Badge / label capability catalog for the customizer TUI (throwaway demo).
 *
 * Prints every way a terminal can mark a value (auto / override / detected …)
 * so we can see, in a REAL terminal, which "badge" looks are actually drawable
 * and which need a special font. Nothing here touches run.php.
 *
 * Run:  php .vortex/customizer/playground/badges.php
 */

declare(strict_types=1);

// 256-colour theme approximations.
const AMBER = '179';
const BLUE = '75';
const GREEN = '108';
const GREY = '245';
const INK = '235';   // dark ink for text on a filled block

function c(string $codes, string $s): string {
  return "\033[" . $codes . 'm' . $s . "\033[0m";
}

function fg(string $n, string $s): string { return c('38;5;' . $n, $s); }

/** A filled block: coloured background, dark text, literal space padding. */
function fill(string $n, string $s): string {
  return c('48;5;' . $n . ';38;5;' . INK . ';1', ' ' . $s . ' ');
}

/** Powerline rounded caps (need a Nerd/Powerline font to render as curves). */
function pill_round(string $n, string $s): string {
  return fg($n, "\u{e0b6}") . c('48;5;' . $n . ';38;5;' . INK . ';1', $s) . fg($n, "\u{e0b4}");
}

/** Half-block soft caps (font-independent - widely supported block glyphs). */
function pill_half(string $n, string $s): string {
  return fg($n, '▐') . c('48;5;' . $n . ';38;5;' . INK . ';1', $s) . fg($n, '▌');
}

function h(string $s): void {
  echo "\n" . c('1;38;5;' . BLUE, '── ' . $s . ' ') . c('38;5;238', str_repeat('─', max(0, 60 - mb_strlen($s)))) . "\n\n";
}

function row(string $name, string $badge, string $note = ''): void {
  $line = '  ' . str_pad($name, 20) . $badge;
  if ($note !== '') {
    $line = str_pad($line, 52 + (strlen($badge) - mb_strlen(preg_replace('/\033\[[0-9;]*m/', '', $badge))));
    $line .= c('38;5;240', $note);
  }
  echo $line . "\n";
}

echo "\n" . c('1', 'Terminal badge / label catalog') . c('38;5;240', '   (run in a real terminal for colour)') . "\n";

h('Text only (SGR) - always works');
row('plain', 'auto');
row('colour', fg(AMBER, 'override'));
row('bold', c('1;38;5;' . AMBER, 'override'));
row('dim', c('2', 'auto'));
row('underline', c('4;38;5;' . BLUE, 'auto'));

h('Bracketed - pure ASCII, survives no-colour');
row('square', fg(BLUE, '[auto]'));
row('round', fg(AMBER, '(override)'));
row('angle', fg(BLUE, '<auto>'));
row('guillemet', fg(AMBER, '«override»'));

h('Filled block (reverse / background) - the real inline "badge"');
row('reverse', c('7', ' auto '), 'ESC[7m - inverts fg/bg');
row('bg blue', fill(BLUE, 'auto'), 'ESC[48;5;75m + dark ink');
row('bg amber', fill(AMBER, 'override'));
row('bg green', fill(GREEN, 'detected'));

h('"Rounded" pills - soft caps');
row('half-block', pill_half(AMBER, 'override'), 'widely supported (▐ ▌)');
row('powerline', pill_round(AMBER, 'override'), 'needs a Nerd/Powerline font');

h('Box-drawing border - works, but a multi-line region only');
echo '  square' . str_repeat(' ', 14) . fg(GREY, '┌──────┐') . "\n";
echo '  ' . str_repeat(' ', 20) . fg(GREY, '│ ') . fg(BLUE, 'auto') . fg(GREY, ' │') . "\n";
echo '  ' . str_repeat(' ', 20) . fg(GREY, '└──────┘') . "\n\n";
echo '  rounded' . str_repeat(' ', 13) . fg(GREY, '╭──────╮') . "\n";
echo '  ' . str_repeat(' ', 20) . fg(GREY, '│ ') . fg(AMBER, 'over') . fg(GREY, ' │') . "\n";
echo '  ' . str_repeat(' ', 20) . fg(GREY, '╰──────╯') . "\n";
echo '  ' . c('38;5;240', 'a per-row inline border is not possible - it would need 3 lines per row') . "\n";

h('In a panel row - recommended mix (quiet auto, loud override)');
$w = 74;
echo '  ' . str_pad('Site machine name', $w - 6) . fg(BLUE, 'auto') . "\n";
echo '      ' . c('38;5;' . GREY, 'Follows the site name') . "\n";
echo '      ' . fg(BLUE, 'widget_co') . "\n";
$label = c('1;38;5;' . BLUE, '❯ Custom theme machine name');
$badge = fill(AMBER, 'override');
$pad = $w - mb_strlen('❯ Custom theme machine name') - 8;
echo c('1;38;5;' . BLUE, '❯ Custom theme machine name') . str_repeat(' ', max(1, $pad)) . $badge . "\n";
echo '      ' . c('38;5;' . GREY, 'Machine name of your custom theme') . "\n";
echo '      ' . c('1;38;5;' . AMBER, 'blah') . '   ' . c('38;5;240', 'pinned - won\'t follow · r to reset to auto') . "\n\n";
