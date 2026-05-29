#!/usr/bin/env node
/**
 * Custom svg-term renderer with configurable lineHeight.
 *
 * This script uses svg-term as a library to have full control over the theme,
 * specifically setting lineHeight to fix box-drawing character rendering.
 *
 * Usage:
 *   node svg-term-render.js <input.json> <output.svg> [options]
 *
 * Options:
 *   --at <ms>          Timestamp of frame to render
 *   --line-height <n>  Line height multiplier (default: 1.0)
 *   --font-family <s>  Font family (default: Consolas, monospace)
 */

const fs = require('fs');
const {render} = require('svg-term');
// svg-term's background rects are drawn without a theme prop, so they fall
// back to this shared module-level object. Mutating it keeps bg colours in
// sync with fg colours.
const svgTermDefaultTheme = require('svg-term/lib/default-theme');

// Parse command line arguments.
const args = process.argv.slice(2);

if (args.length < 2 || args.includes('--help')) {
  console.log('Usage: node svg-term-render.js <input.json> <output.svg> [options]');
  console.log('');
  console.log('Options:');
  console.log('  --at <ms>          Timestamp of frame to render');
  console.log('  --line-height <n>  Line height multiplier (default: 1.0)');
  console.log('  --font-family <s>  Font family (default: Consolas, monospace)');
  process.exit(args.includes('--help') ? 0 : 1);
}

const inputFile = args[0];
const outputFile = args[1];

// Parse options.
let at = null;
let lineHeight = 1.0;
let fontFamily = 'Consolas, "Courier New", Courier, "Liberation Mono", monospace';

for (let i = 2; i < args.length; i++) {
  if (args[i] === '--at' && i + 1 < args.length) {
    at = parseInt(args[i + 1], 10);
    i++;
  } else if (args[i] === '--line-height' && i + 1 < args.length) {
    lineHeight = parseFloat(args[i + 1]);
    i++;
  } else if (args[i] === '--font-family' && i + 1 < args.length) {
    fontFamily = args[i + 1];
    i++;
  }
}

// Read input cast file and convert v3 to v2 if needed.
// svg-term only supports asciicast v1 and v2 formats, but asciinema 3.x
// produces v3 format with two breaking differences:
//   1. Header uses {term: {cols, rows, type}} instead of {width, height}
//   2. Timestamps are relative (delta from previous event) not absolute
// Additionally, v3 introduces event type "x" (exit) which v2 doesn't have.
let input = fs.readFileSync(inputFile, 'utf8');
const lines = input.split('\n');
if (lines.length > 0) {
  try {
    const header = JSON.parse(lines[0]);
    if (header.version === 3) {
      // Convert header.
      header.version = 2;
      if (header.term) {
        header.width = header.term.cols;
        header.height = header.term.rows;
        if (!header.env) {
          header.env = {};
        }
        if (header.term.type) {
          header.env.TERM = header.term.type;
        }
        delete header.term;
      }
      // Convert event lines: relative timestamps to absolute, drop non-"o" events.
      const convertedLines = [JSON.stringify(header)];
      let absoluteTime = 0;
      for (let i = 1; i < lines.length; i++) {
        const line = lines[i].trim();
        if (!line) {
          continue;
        }
        try {
          const event = JSON.parse(line);
          absoluteTime += event[0];
          if (event[1] === 'o') {
            convertedLines.push(JSON.stringify([parseFloat(absoluteTime.toFixed(6)), 'o', event[2]]));
          }
        } catch (_) {
          // Skip malformed lines.
        }
      }
      input = convertedLines.join('\n') + '\n';
    }
  } catch (_) {
    // Not valid JSON header - let svg-term handle the error.
  }
}

// Atom One Dark palette. svg-term indexes ANSI colours by numeric attribute
// 0-15 - named keys like 'blue' are silently ignored and fall through to
// ansi-to-rgb's dark VGA defaults.
const theme = {
  0: [40, 44, 52],         // black         #282c34
  1: [224, 108, 117],      // red           #e06c75
  2: [152, 195, 121],      // green         #98c379
  3: [209, 154, 102],      // yellow        #d19a66
  4: [97, 175, 239],       // blue          #61afef
  5: [198, 120, 221],      // magenta       #c678dd
  6: [86, 182, 194],       // cyan          #56b6c2
  7: [171, 178, 191],      // white         #abb2bf
  8: [92, 99, 112],        // brightBlack   #5c6370
  9: [224, 108, 117],      // brightRed     #e06c75
  10: [152, 195, 121],     // brightGreen   #98c379
  11: [209, 154, 102],     // brightYellow  #d19a66
  12: [97, 175, 239],      // brightBlue    #61afef
  13: [198, 120, 221],     // brightMagenta #c678dd
  14: [86, 182, 194],      // brightCyan    #56b6c2
  15: [255, 255, 255],     // brightWhite   #ffffff
  background: [40, 44, 52],       // #282c34
  text: [171, 178, 191],          // #abb2bf
  cursor: [82, 139, 255],         // #528bff
  bold: [171, 178, 191],          // #abb2bf
  fontSize: 1.67,
  lineHeight: lineHeight,
  fontFamily: fontFamily,
};

Object.assign(svgTermDefaultTheme, theme);

const options = {
  theme: theme,
};

if (at !== null) {
  options.at = at;
}

// Render SVG.
try {
  const svg = render(input, options);
  fs.writeFileSync(outputFile, svg, 'utf8');
  console.log(`SVG rendered successfully: ${outputFile}`);
  console.log(`  lineHeight: ${lineHeight}`);
  console.log(`  fontFamily: ${fontFamily}`);
  if (at !== null) {
    console.log(`  at: ${at}ms`);
  }
} catch (error) {
  console.error('Error rendering SVG:', error.message);
  process.exit(1);
}
