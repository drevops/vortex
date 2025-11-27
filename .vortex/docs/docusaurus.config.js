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
  url: 'https://www.vortextemplate.com/',
  // Set the /<baseUrl>/ pathname under which your site is served
  // For GitHub pages deployment, it is often '/<projectName>/'
  baseUrl: '/',

  // GitHub pages deployment config.
  organizationName: 'DrevOps',
  projectName: 'Vortex',

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
          routeBasePath: '/docs',
          sidebarPath: './sidebars.js',
          path: 'content',
          // Please change this to your repo.
          // Remove this to remove the "edit this page" links.
          editUrl: 'https://github.com/drevops/vortex/tree/develop/.vortex/docs/',
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
            label: 'Get started',
            href: '/docs/getting-started',
          },
          {
            label: 'Features',
            href: '/docs/features',
          },
          {
            label: 'Documentation',
            href: '/docs',
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
                label: 'Get started',
                href: '/docs/getting-started',
              },
              {
                label: 'Features',
                href: '/docs/features',
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
          {
            from: ['/quickstart'],
            to: '/docs/getting-started',
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
            to: '/docs/workflows',
          },
          {
            from: ['/getting-started'],
            to: '/docs/getting-started',
          },
          {
            from: ['/contributing'],
            to: '/docs/contributing',
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
        ],
      },
    ],
  ],
};

export default config;
