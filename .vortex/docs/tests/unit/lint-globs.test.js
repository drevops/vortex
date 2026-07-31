import fs from 'fs';
import path from 'path';

// Yarn runs package scripts through 'sh', which has no 'globstar': an unquoted
// '**' collapses to a single '*', so the linters receive a shell-expanded list
// that stops one directory below 'content/'. Both tools then exit 0 and report
// a plausible file count, leaving deeper pages unchecked with no signal.
const DOCS_ROOT = path.resolve(__dirname, '../..');
const CONTENT_DIR = 'content';
const SCRIPTS = ['spellcheck', 'lint-docs', 'lint-docs-fix'];

const packageJson = JSON.parse(
  fs.readFileSync(path.join(DOCS_ROOT, 'package.json'), 'utf8')
);

const tokenize = script => (script || '').match(/"[^"]*"|\S+/g) || [];

const isQuoted = token => token.startsWith('"') && token.endsWith('"');

const isGlob = token => /[*?]/.test(token);

// '**/' spans any number of directories, '*' stops at the separator.
const globToRegExp = glob => {
  const source = glob.replace(/\*\*\/|\*|[.+^${}()|[\]\\?]/g, match => {
    if (match === '**/') {
      return '(?:[^/]+/)*';
    }

    if (match === '*') {
      return '[^/]*';
    }

    return `\\${match}`;
  });

  return new RegExp(`^${source}$`);
};

const collectPages = (dir, pages = []) => {
  const entries = fs.readdirSync(path.join(DOCS_ROOT, dir), {
    withFileTypes: true,
  });

  for (const entry of entries) {
    const relative = `${dir}/${entry.name}`;

    if (entry.isDirectory()) {
      collectPages(relative, pages);
      continue;
    }

    if (entry.name.endsWith('.mdx')) {
      pages.push(relative);
    }
  }

  return pages;
};

describe('Documentation lint globs', () => {
  const pages = collectPages(CONTENT_DIR);

  test('documentation pages are discoverable', () => {
    expect(pages.length).toBeGreaterThan(0);
  });

  describe.each(SCRIPTS)('%s', name => {
    const args = tokenize(packageJson.scripts[name]);

    test('passes globs quoted so the tool expands them, not the shell', () => {
      expect(args.filter(arg => !isQuoted(arg) && isGlob(arg))).toEqual([]);
    });

    test('covers every documentation page', () => {
      const globs = args
        .filter(isQuoted)
        .map(arg => globToRegExp(arg.slice(1, -1)));

      expect(
        pages.filter(page => !globs.some(glob => glob.test(page)))
      ).toEqual([]);
    });
  });
});
