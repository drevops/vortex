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
(function YourSiteThemeBehaviors($, Drupal) {
  Drupal.behaviors.your_site_theme = {
    attach(context) {
      // give me example code here that would be using context with body
      $(context)
        .find('body')
        .once('your-site-theme')
        .each(function iterateBody() {
          // Example: Add a class to the body element.
          $(this).addClass('your-site-theme-processed');
        });
    },
  };
})(jQuery, Drupal);
