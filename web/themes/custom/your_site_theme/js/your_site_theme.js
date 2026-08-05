/**
 * @file Global theme behaviors.
 */

(function yourSiteThemeBehaviors(Drupal) {
  Drupal.behaviors.yourSiteTheme = {
    attach(context) {
      const body = context.querySelector
        ? context.querySelector('body')
        : document.body;

      if (!body || body.classList.contains('your-site-theme-processed')) {
        return;
      }

      body.classList.add('your-site-theme-processed');
    },
  };
})(Drupal);
