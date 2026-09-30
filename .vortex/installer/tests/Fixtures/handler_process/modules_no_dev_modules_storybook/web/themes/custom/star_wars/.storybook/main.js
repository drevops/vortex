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
};
