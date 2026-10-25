/**
 * @file
 * Storybook configuration.
 *
 * Stories are authored in Twig as "*.stories.twig" next to the component they
 * document and compiled into "*.stories.json" by the Storybook Drupal module.
 */

export default {
  stories: ['../components/**/*.stories.json'],
  framework: {
    name: '@storybook/server-webpack5',
    options: {},
  },
  core: {
    // A non-empty list turns host validation on, so the development server
    // answers only requests addressed to localhost or an IP address.
    allowedHosts: ['localhost'],
  },
  features: {
    // Change detection maps edited files to stories through the webpack
    // module graph, which Drupal-rendered components are not part of.
    changeDetection: false,
  },
};
