import fs from 'fs';
import path from 'path';

// 'docusaurus start' and 'docusaurus build' generate into the directory named
// by 'DOCUSAURUS_GENERATED_FILES_DIR_NAME'. Sharing that directory lets a build
// add its analytics client module to a running dev server, whose page never
// defines 'window.gtag'.
const DOCS_ROOT = path.resolve(__dirname, '../..');
const GENERATED_DIR_VAR = 'DOCUSAURUS_GENERATED_FILES_DIR_NAME';
const DEV_GENERATED_DIR = '.docusaurus-dev';

const packageJson = JSON.parse(
  fs.readFileSync(path.join(DOCS_ROOT, 'package.json'), 'utf8')
);

const gitignore = fs.readFileSync(path.join(DOCS_ROOT, '.gitignore'), 'utf8');

const tokenize = script => (script || '').match(/"[^"]*"|\S+/g) || [];

const generatedDirs = script =>
  tokenize(script)
    .filter(token => token.startsWith(`${GENERATED_DIR_VAR}=`))
    .map(token => token.slice(GENERATED_DIR_VAR.length + 1));

const countCommands = (script, command) =>
  tokenize(script).filter(token => token === command).length;

describe('Docusaurus generated files directory', () => {
  test('the dev server generates outside the build directory', () => {
    expect(generatedDirs(packageJson.scripts.start)).toEqual([
      DEV_GENERATED_DIR,
    ]);
    expect(generatedDirs(packageJson.scripts.build)).toEqual([]);
  });

  test('clear removes both generated directories', () => {
    expect(generatedDirs(packageJson.scripts.clear)).toEqual([
      DEV_GENERATED_DIR,
    ]);
    expect(countCommands(packageJson.scripts.clear, 'clear')).toBe(2);
  });

  test('the dev server directory is ignored by git', () => {
    expect(gitignore.split('\n')).toContain(DEV_GENERATED_DIR);
  });
});
