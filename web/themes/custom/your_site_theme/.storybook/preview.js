/**
 * @file
 * Storybook preview configuration.
 *
 * Drupal renders every story, so the preview needs the address of the render
 * endpoint.
 */

// The static build is served from the site's own origin, so the endpoint is
// resolved in the browser instead of being compiled into the stories. The
// development server runs on its own origin and reads STORYBOOK_SERVER_URL.
// Storybook inlines 'process.env' at build time, so no 'process' guard is
// needed in the browser.
const drupalUrl = process.env.STORYBOOK_SERVER_URL || window.location.origin;

export default {
  parameters: {
    server: {
      url: `${drupalUrl}/storybook/stories/render`,
    },
  },
};
