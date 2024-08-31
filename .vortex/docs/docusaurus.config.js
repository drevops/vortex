// @ts-check
// `@type` JSDoc annotations allow editor autocompletion and type checking
// (when paired with `@ts-check`).
// There are various equivalent ways to declare your Docusaurus config.
// @see https://docusaurus.io/docs/api/docusaurus-config

import {themes as prismThemes} from 'prism-react-renderer';

/** @type {import('@docusaurus/types').Config} */
const config = {
  title: 'Vortex - Drupal project template',
  tagline: 'Vortex documentation',
  favicon: 'img/favicon.ico',

  // Set the production url of your site here
  url: 'https://docs.drevops.com/',
  // Set the /<baseUrl>/ pathname under which your site is served
  // For GitHub pages deployment, it is often '/<projectName>/'
  baseUrl: '/',

  // GitHub pages deployment config.
  organizationName: 'DrevOps',
  projectName: 'scaffold',

  onBrokenLinks: 'throw',
  onBrokenMarkdownLinks: 'throw',
  onBrokenAnchors: 'throw',

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
          routeBasePath: '/',
          sidebarPath: './sidebars.js',
          path: 'content',
          // Please change this to your repo.
          // Remove this to remove the "edit this page" links.
          editUrl: 'https://github.com/drevops/scaffold/tree/develop/.vortex/docs/',
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
        docsRouteBasePath: '/',
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
      image: 'img/drevops_services_dark.png',
      navbar: {
        title: 'Vortex',
        logo: {
          alt: 'Vortex Logo',
          src: 'img/logo-vortex-dark.svg',
          srcDark: 'img/logo-vortex-light.svg',
        },
        items: [
          {
            label: 'Drupal',
            href: '/drupal',
          },
          {
            label: 'Tools',
            href: '/tools',
          },
          {
            label: 'CI',
            href: '/ci',
          },
          {
            label: 'Hosting',
            href: '/hosting',
          },
          {
            label: 'Workflows',
            href: '/workflows',
          },
          {
            href: 'https://github.com/drevops/scaffold',
            label: 'GitHub',
            position: 'right',
            title: 'View source on GitHub',
          },
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
            label: 'GitHub',
            href: 'https://github.com/drevops/scaffold',
          },
        ],
        copyright: `Copyright ©${new Date().getFullYear()} DrevOps&reg;. Built with Docusaurus.`,
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
};

export default config;
