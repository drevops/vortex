/**
 * @file Global theme behaviors.
 */

/**
 * Wraps the behaviors so that anything declared here stays out of the global
 * scope.
 *
 * @param {object} Drupal  The Drupal object.
 */
(function yourSiteThemeBehaviors(Drupal) {
  Drupal.behaviors.yourSiteTheme = {
    attach(context) {
      // The context is the document on load and an element on AJAX, and that
      // element may be the body itself, so resolve through the owning document.
      const body = context.ownerDocument ? context.ownerDocument.body : document.body;

      if (body.classList.contains('star-wars-theme-processed')) {
        return;
      }

      body.classList.add('star-wars-theme-processed');
    },
  };
})(Drupal);
