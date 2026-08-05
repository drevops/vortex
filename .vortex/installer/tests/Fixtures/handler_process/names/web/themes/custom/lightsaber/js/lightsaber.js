/**
 * @file Global theme behaviors.
 */

(function lightsaberBehaviors(Drupal) {
  Drupal.behaviors.lightsaber = {
    attach(context) {
      // The context is the document on load and an element on AJAX, and that
      // element may be the body itself, so resolve through the owning document.
      const body = context.ownerDocument
        ? context.ownerDocument.body
        : document.body;

      if (body.classList.contains('the-new-hope-theme-processed')) {
        return;
      }

      body.classList.add('the-new-hope-theme-processed');
    },
  };
})(Drupal);
