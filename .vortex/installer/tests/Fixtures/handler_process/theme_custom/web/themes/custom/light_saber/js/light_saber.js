/**
 * @file Global theme behaviors.
 * @param $
 * @param Drupal
 * @global Drupal, jQuery
 */

/**
 * Global theme behaviors.
 *
 * @param {jQuery} $ The jQuery object.
 * @param {Drupal} Drupal The Drupal object.
 */
(function LightSaberBehaviors($, Drupal) {
  Drupal.behaviors.light_saber = {
    attach(context) {
      // give me example code here that would be using context with body
      $(context)
        .find('body')
        .once('star-wars-theme')
        .each(function iterateBody() {
          // Example: Add a class to the body element.
          $(this).addClass('star-wars-theme-processed');
        });
    },
  };
})(jQuery, Drupal);
