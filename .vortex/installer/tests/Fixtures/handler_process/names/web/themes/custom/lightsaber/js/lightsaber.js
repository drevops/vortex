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
(function LightsaberBehaviors($, Drupal) {
  Drupal.behaviors.lightsaber = {
    attach(context) {
      // give me example code here that would be using context with body
      $(context)
        .find('body')
        .once('the-new-hope-theme')
        .each(function iterateBody() {
          // Example: Add a class to the body element.
          $(this).addClass('the-new-hope-theme-processed');
        });
    },
  };
})(jQuery, Drupal);
