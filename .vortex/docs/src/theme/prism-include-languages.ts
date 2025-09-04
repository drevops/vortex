import siteConfig from '@generated/docusaurus.config';
import type * as PrismNamespace from 'prismjs';
import type { Optional } from 'utility-types';

export default function prismIncludeLanguages(
  PrismObject: typeof PrismNamespace
): void {
  const {
    themeConfig: { prism },
  } = siteConfig;
  const { additionalLanguages } = prism as { additionalLanguages: string[] };

  const PrismBefore = globalThis.Prism;
  globalThis.Prism = PrismObject;

  additionalLanguages.forEach(lang => {
    if (lang === 'php') {
      // eslint-disable-next-line global-require
      require('prismjs/components/prism-markup-templating.js');
    }
    // eslint-disable-next-line global-require, import/no-dynamic-require
    require(`prismjs/components/prism-${lang}`);
  });

  // Extend bash/shell to highlight CLI tools as functions
  if (PrismObject.languages.bash) {
    const CUSTOM_CMDS = [
      'ahoy',
      'docker',
      'git',
      'npm',
      'yarn',
      'composer',
      'drush',
    ];

    var alternation =
      '(?:' +
      CUSTOM_CMDS.map(function (s) {
        return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
      }).join('|') +
      ')';

    // Multiline + global via Prism's greedy handling; we add 'm' explicitly
    var pattern = new RegExp(
      '((?:^|[;&|]\\s*)(?:sudo\\s+)?)' + // prefix (line start/op, spaces, optional sudo)
        '\\b' +
        alternation +
        '\\b', // the command name
      'm' // IMPORTANT: multiline so ^ hits each line
    );

    PrismObject.languages.insertBefore('bash', 'function', {
      'custom-command-as-function': {
        pattern: pattern,
        lookbehind: true,
        greedy: true,
        alias: 'function',
      },
    });
  }

  // Clean up and eventually restore former globalThis.Prism object (if any)
  delete (globalThis as Optional<typeof globalThis, 'Prism'>).Prism;
  if (typeof PrismBefore !== 'undefined') {
    globalThis.Prism = PrismObject;
  }
}
