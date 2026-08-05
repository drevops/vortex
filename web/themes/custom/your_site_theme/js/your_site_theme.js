/**
 * @file Global theme behaviors.
 */

(function yourSiteThemeBehaviors(Drupal) {
  Drupal.behaviors.yourSiteTheme = {
    attach(context) {
      // The context is the document on load and an element on AJAX, and that
      // element may be the body itself, so resolve through the owning document.
      const body = context.ownerDocument
        ? context.ownerDocument.body
        : document.body;

      if (body.classList.contains('your-site-theme-processed')) {
        return;
      }

      body.classList.add('your-site-theme-processed');
    },
  };
})(Drupal);
