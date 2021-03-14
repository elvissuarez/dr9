/**
 * @file
 * Provides an event handler for hidden elements in dropdown menus.
 */

(function ($, Drupal, Bootstrap) {
  'use strict';

  $('.flexslider').flexslider({
    animation: "slide",
    animationLoop: false,
    itemWidth: 210,
    itemMargin: 5,
    minItems: 3,
    maxItems: 5
  });

})(jQuery, Drupal, Drupal.bootstrap);
