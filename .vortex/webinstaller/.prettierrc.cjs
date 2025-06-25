module.exports = {
  semi: true,
  trailingComma: 'es5',
  singleQuote: true,
  printWidth: 80,
  tabWidth: 2,
  useTabs: false,
  bracketSpacing: true,
  bracketSameLine: false,
  arrowParens: 'avoid',
  endOfLine: 'lf',
  insertFinalNewline: true,
  overrides: [
    {
      files: '*.html',
      options: {
        printWidth: 999,
        tabWidth: 2,
        useTabs: false,
        bracketSameLine: true,
        htmlWhitespaceSensitivity: 'ignore',
        singleAttributePerLine: false,
      },
    },
  ],
};
