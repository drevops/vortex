/**
 * @file Global theme behaviors.
 */

((Drupal) => {
  Drupal.behaviors.lightsaber = {
    attach(context) {
      const body = context.querySelector
        ? context.querySelector('body')
        : document.body;

      if (!body || body.classList.contains('the-new-hope-theme-processed')) {
        return;
      }

      body.classList.add('the-new-hope-theme-processed');
    },
  };
})(Drupal);
