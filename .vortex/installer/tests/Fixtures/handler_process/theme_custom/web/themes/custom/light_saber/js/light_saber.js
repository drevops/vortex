/**
 * @file Global theme behaviors.
 */

((Drupal) => {
  Drupal.behaviors.lightSaber = {
    attach(context) {
      const body = context.querySelector
        ? context.querySelector('body')
        : document.body;

      if (!body || body.classList.contains('star-wars-theme-processed')) {
        return;
      }

      body.classList.add('star-wars-theme-processed');
    },
  };
})(Drupal);
