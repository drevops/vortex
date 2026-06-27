// @ts-check
// `@type` JSDoc annotations allow editor autocompletion and type checking
// (when paired with `@ts-check`).
// There are various equivalent ways to declare your Docusaurus config.
// @see https://docusaurus.io/docs/api/docusaurus-config

import fs from 'node:fs';

import {themes as prismThemes} from 'prism-react-renderer';

// Multi-version mode turns on automatically when a 'versioned_docs/' snapshot
// is present: 'versioned_docs/version-1.x' is v1 (the default, served at the
// bare '/docs') and the current 'content/' is v2 (served at '/docs/v2'). With
// no snapshot - local development, per-branch preview builds, and the
// 'docusaurus docs:version' run that creates the snapshot - the site builds
// 'content/' as a single unversioned set, so the config never references a
// version that does not exist yet. The publish jobs assemble the snapshot in
// CI; it is never committed to a branch.
const versioned = fs.existsSync('versioned_docs');

// The current major (the 'VORTEX_CURRENT_MAJOR' repository variable, default 1)
// drives the whole site: its docs are a snapshot under 'versioned_docs/' served
// as the default at the bare '/docs', and the live 'content/' (pulled from the
// other major's '{N}.x' branch in CI) is served at '/docs/v{other}'. Bumping
// that one variable promotes a new major - nothing else changes here.
const currentMajor = process.env.VORTEX_CURRENT_MAJOR || '1';
const otherMajor = currentMajor === '1' ? '2' : '1';
const currentDocsVersion = `${currentMajor}.x`;
const otherIsNewer = Number(otherMajor) > Number(currentMajor);

/** @type {import('@docusaurus/types').Config} */
const config = {
  title: 'Vortex - Drupal project template',
  tagline: 'Vortex documentation',
  favicon: 'img/favicon.ico',

  stylesheets: [
    {
      href: 'https://fonts.googleapis.com',
      rel: 'preconnect',
    },
    {
      href: 'https://fonts.gstatic.com',
      rel: 'preconnect',
      crossorigin: 'anonymous',
    },
    {
      href: 'https://fonts.googleapis.com/css2?family=Sora:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap',
      rel: 'stylesheet',
    },
  ],

  // Set the production url of your site here
  url: 'https://www.vortextemplate.com/',
  // Set the /<baseUrl>/ pathname under which your site is served
  // For GitHub pages deployment, it is often '/<projectName>/'
  baseUrl: '/',

  // GitHub pages deployment config.
  organizationName: 'DrevOps',
  projectName: 'Vortex',

  onBrokenLinks: 'throw',
  onBrokenMarkdownLinks: 'throw',
  onBrokenAnchors: 'warn',

  // Even if you don't use internationalization, you can use this field to set
  // useful metadata like html lang. For example, if your site is Chinese, you
  // may want to replace "en" with "zh-Hans".
  i18n: {
    defaultLocale: 'en',
    locales: ['en'],
  },

  presets: [
    [
      'classic',
      /** @type {import('@docusaurus/preset-classic').Options} */
      ({
        docs: {
          routeBasePath: '/docs',
          sidebarPath: './sidebars.js',
          path: 'content',
          // Please change this to your repo.
          // Remove this to remove the "edit this page" links.
          editUrl: 'https://github.com/drevops/vortex/tree/main/.vortex/docs/',
          // In versioned (aggregate) builds the current major is the snapshot
          // in 'versioned_docs/' served at the bare '/docs' (the default), and
          // the live 'content/' is the other major at '/docs/v{other}'. Both
          // are derived from 'VORTEX_CURRENT_MAJOR' - no manual edits to flip.
          ...(versioned ? {
            lastVersion: currentDocsVersion,
            versions: {
              [currentDocsVersion]: {
                label: `v${currentMajor}`,
              },
              current: {
                label: `v${otherMajor}`,
                path: `v${otherMajor}`,
                banner: otherIsNewer ? 'unreleased' : 'unmaintained',
              },
            },
          } : {}),
        },
        blog: false,
        theme: {
          customCss: './src/css/custom.css',
        },
        gtag: {
          trackingID: 'G-9T1JR2V5RL',
          anonymizeIP: true,
        },
      }),
    ],
  ],

  markdown: {
    mermaid: true,
  },

  themes: [
    [
      '@easyops-cn/docusaurus-search-local',
      /** @type {import("@easyops-cn/docusaurus-search-local").PluginOptions} */
      ({
        // @see https://github.com/easyops-cn/docusaurus-search-local#theme-options
        searchBarPosition: 'left',
        docsDir: 'content',
        docsRouteBasePath: '/docs',
        indexBlog: false,
        hashed: true,
        highlightSearchTermsOnTargetPage: true,
        explicitSearchResultPath: true,
      }),
    ],
    '@docusaurus/theme-mermaid',
  ],

  themeConfig:
  /** @type {import('@docusaurus/preset-classic').ThemeConfig} */
    ({
      image: 'img/diagram-dark.png',
      navbar: {
        title: 'Vortex',
        logo: {
          alt: 'Vortex Logo',
          src: 'img/logo-vortex-dark.svg',
          srcDark: 'img/logo-vortex-light.svg',
        },
        items: [
          {
            label: 'Introduction',
            href: '/docs',
          },
          {
            label: 'Features',
            href: '/docs/features',
          },
          {
            label: 'Installation',
            href: '/docs/installation',
          },
          {
            label: 'Docs',
            href: '/docs/development',
          },
          {
            label: 'Support',
            href: '/docs/support',
          },
          {
            href: 'https://github.com/drevops/vortex',
            label: 'GitHub',
            position: 'right',
            title: 'View source on GitHub',
          },
          {
            href: 'https://drupal.slack.com/archives/CRE86HQTW',
            label: 'Slack',
            position: 'right',
            title: 'Join us on Slack',
          },
          ...(versioned ? [{
            type: 'docsVersionDropdown',
            position: 'right',
          }] : []),
          {
            type: 'search',
            position: 'right',
          },
        ],
      },
      footer: {
        style: 'dark',
        links: [
          {
            title: 'Product',
            items: [
              {
                label: 'Features',
                href: '/docs/features',
              },
              {
                label: 'FAQs',
                href: '/docs/faqs',
              },
            ],
          },
          {
            title: 'Resources',
            items: [
              {
                label: 'Documentation',
                href: '/docs',
              },
              {
                label: 'Support',
                href: '/docs/support',
              },
            ],
          },
          {
            title: 'Community',
            items: [
              {
                label: 'GitHub',
                href: 'https://github.com/drevops/vortex',
              },
              {
                label: 'Slack',
                href: 'https://drupal.slack.com/archives/CRE86HQTW',
              },
            ],
          },
        ],
        copyright: `Vortex version: ${process.env.RELEASE_VERSION || 'development'} <br/>Drupal is a <a class="copyright_link" href="https://www.drupal.org/about/trademark">registered trademark</a> of <a class="copyright_link" href="https://dri.es/">Dries Buytaert</a>.<br/>Copyright ©${new Date().getFullYear()} <a class="copyright_link" href="https://www.drevops.com/">DrevOps&reg;</a>. Built with <a class="copyright_link" href="https://docusaurus.io/">Docusaurus</a>.`,
      },
      prism: {
        theme: prismThemes.github,
        darkTheme: prismThemes.dracula,
        additionalLanguages: ['bash', 'json', 'php'],
      },
      colorMode: {
        defaultMode: 'light',
        disableSwitch: false,
        respectPrefersColorScheme: true,
      },
      docs: {
        sidebar: {
          autoCollapseCategories: true,
        },
      },
    }),

  plugins: [
    [
      '@docusaurus/plugin-client-redirects',
      {
        redirects: [
          // The current major is the default at the bare '/docs', so its
          // explicit '/docs/v{current}' path redirects there.
          ...(versioned ? [{
            from: `/docs/v${currentMajor}`,
            to: '/docs',
          }] : []),
          {
            from: ['/quickstart'],
            to: '/docs',
          },
          {
            from: ['/ci'],
            to: '/docs/continuous-integration',
          },
          {
            from: ['/drupal'],
            to: '/docs/drupal',
          },
          {
            from: ['/hosting'],
            to: '/docs/hosting',
          },
          {
            from: ['/tools'],
            to: '/docs/tools',
          },
          {
            from: ['/workflows'],
            to: '/docs/development',
          },
          {
            from: ['/getting-started'],
            to: '/docs',
          },
          {
            from: ['/contributing'],
            to: '/docs/contributing',
          },
          {
            from: '/docs/contributing/maintenance/scripts',
            to: '/docs/contributing/maintenance/template',
          },
          {
            from: '/docs/contributing/maintenance/tests',
            to: '/docs/contributing/maintenance/template',
          },
          {
            from: ['/docs/getting-started'],
            to: '/docs',
          },
          {
            from: ['/docs/getting-started/architecture'],
            to: '/docs/architecture',
          },
          {
            from: ['/docs/getting-started/features'],
            to: '/docs/features',
          },
          {
            from: ['/docs/getting-started/installation'],
            to: '/docs/installation',
          },
          {
            from: ['/docs/getting-started/faqs'],
            to: '/docs/faqs',
          },
          {
            from: '/support',
            to: '/docs/support',
          },
          {
            from: '/features',
            to: '/docs/features',
          },
          {
            from: '/docs/workflows/testing',
            to: '/docs/development',
          },
          {
            from: '/docs/workflows/testing/phpunit',
            to: '/docs/development/phpunit',
          },
          {
            from: '/docs/workflows/testing/behat',
            to: '/docs/development/behat',
          },
          {
            from: '/docs/workflows/development',
            to: '/docs/development',
          },
          {
            from: '/docs/workflows/development/phpunit',
            to: '/docs/development/phpunit',
          },
          {
            from: '/docs/workflows/development/behat',
            to: '/docs/development/behat',
          },
          {
            from: '/docs/workflows/development/database',
            to: '/docs/development/database',
          },
          {
            from: '/docs/workflows/development/debugging',
            to: '/docs/development/debugging',
          },
          {
            from: '/docs/workflows/development/composer',
            to: '/docs/development/composer',
          },
          {
            from: '/docs/workflows/development/faqs',
            to: '/docs/development/faqs',
          },
          {
            from: '/docs/workflows/deployment',
            to: '/docs/deployment',
          },
          {
            from: '/docs/workflows/notifications',
            to: '/docs/deployment/notifications',
          },
          {
            from: '/docs/workflows/notifications/email',
            to: '/docs/deployment/notifications',
          },
          {
            from: '/docs/workflows/notifications/github',
            to: '/docs/deployment/notifications',
          },
          {
            from: '/docs/workflows/notifications/jira',
            to: '/docs/deployment/notifications',
          },
          {
            from: '/docs/workflows/notifications/newrelic',
            to: '/docs/deployment/notifications',
          },
          {
            from: '/docs/workflows/notifications/slack',
            to: '/docs/deployment/notifications',
          },
          {
            from: '/docs/workflows/notifications/webhook',
            to: '/docs/deployment/notifications',
          },
          {
            from: '/docs/workflows/releasing',
            to: '/docs/releasing',
          },
          {
            from: '/docs/workflows/releasing/gitflow',
            to: '/docs/releasing/gitflow',
          },
          {
            from: '/docs/workflows/releasing/versioning',
            to: '/docs/releasing/versioning',
          },
          {
            from: '/docs/workflows',
            to: '/docs/architecture',
          },
          {
            from: '/docs/workflows/variables',
            to: '/docs/development/variables',
          },
          {
            from: '/docs/variables',
            to: '/docs/development/variables',
          },
          {
            from: '/docs/workflows/updating-vortex',
            to: '/docs/updating-vortex',
          },
          {
            from: '/docs/drupal/composer',
            to: '/docs/drupal/composer-json',
          },
        ],
      },
    ],
  ],
};

export default config;
