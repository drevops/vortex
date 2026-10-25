/**
 * @jest-environment node
 */

import { spawnSync } from 'child_process';
import path from 'path';

const DOCS_ROOT = path.resolve(__dirname, '../..');
const VALE = path.join(DOCS_ROOT, 'node_modules/.bin/vale');
const FIXTURES = 'tests/fixtures/vale';

const lint = fixture => {
  const file = path.join(FIXTURES, fixture);
  const args = ['--output=JSON', '--no-exit', '--minAlertLevel=warning', file];
  const result = spawnSync(VALE, args, { cwd: DOCS_ROOT, encoding: 'utf8' });

  if (result.status !== 0) {
    throw new Error(`Vale failed: ${result.stderr || result.stdout}`);
  }

  const alerts = Object.values(JSON.parse(result.stdout)).flat();

  return alerts.map(alert => `${alert.Line}:${alert.Check}:${alert.Match}`);
};

describe('Vale prose rules', () => {
  test('a page that follows every rule reports no alerts', () => {
    expect(lint('valid.mdx')).toEqual([]);
  });

  describe('a page that breaks each rule', () => {
    let alerts;

    beforeAll(() => {
      alerts = lint('invalid.mdx');
    });

    test.each(dataProviderAlerts())('reports %s', alert => {
      expect(alerts).toContain(alert);
    });

    test('reports nothing else', () => {
      const expected = dataProviderAlerts().map(([alert]) => alert);

      expect([...alerts].sort()).toEqual([...expected].sort());
    });
  });
});

function dataProviderAlerts() {
  return [
    ['7:Vortex.BoldName:Vortex'],
    ['9:Vortex.CodeFormatting:VORTEX_DEBUG'],
    ['11:Vale.Terms:composer'],
    ['11:Vortex.CodeFormatting:composer.json'],
    ['13:Vortex.Condescending:easy'],
    ['15:Vortex.Contractions:does not'],
    ['17:Google.EmDash: — '],
    ['17:Vortex.Dashes:—'],
    ['19:Vortex.HedgeStacks:may potentially'],
    ['21:Vortex.LinkText:click here'],
    ['23:Vortex.Marketing:powerful'],
    ['25:Vortex.Numbers:two'],
    ['27:Vortex.OxfordComma:and'],
    ['29:Vortex.SentenceLength:This'],
    ['31:Vortex.ThroatClearing:Please note'],
    ['33:Vortex.Weasel:very'],
    ['35:Vortex.Wordy:prior to'],
    ['37:Vortex.WordListCase:above'],
    ['39:Vale.Terms:github'],
    ['41:write-good.ThereIs:There are'],
    ['43:Vortex.Latin:e.g.'],
    ['45:Google.Will:will'],
    ['47:Vortex.Headings:Deployment Notifications'],
    ['51:Vortex.Contractions:does\nnot'],
    ['55:Vortex.OxfordComma:or'],
    ['58:Vortex.Contractions:should not'],
    ['60:Vortex.OxfordComma:and'],
    ['63:Vortex.OxfordComma:and'],
    ['65:Vortex.OxfordComma:and'],
    ['67:Vortex.Latin:e.g.'],
  ];
}
