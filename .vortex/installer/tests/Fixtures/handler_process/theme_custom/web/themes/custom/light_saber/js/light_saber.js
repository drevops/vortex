/**
 * @file Global theme behaviors.
 */

(function lightSaberBehaviors(Drupal) {
  Drupal.behaviors.lightSaber = {
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
