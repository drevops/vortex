/**
 * @file
 * Storybook preview configuration.
 *
 * Drupal renders every story, so the preview needs the address of the render
 * endpoint.
 */

// The static build is served from the site's own origin, so the endpoint is
// resolved in the browser instead of being compiled into the stories. The
// development server runs on its own origin and reads STORYBOOK_DRUPAL_URL.
const drupalUrl = (typeof process !== 'undefined' && process.env && process.env.STORYBOOK_DRUPAL_URL) || window.location.origin;

export default {
  parameters: {
    server: {
      url: `${drupalUrl}/storybook/stories/render`,
    },
  },
};
