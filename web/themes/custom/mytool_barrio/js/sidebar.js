/**
 * @file
 * Provides an event handler left sidebar responsive.
 */

(function ($) {
  'use strict';

  $("document").ready(function () {
    $('.mini-submenu').on('click', function () {
      $('#left-filters').height($(window).height());
      $('#left-filters').toggle('slide');
      $('#left-filters').addClass('sidebar-filters-responsive');
    })
  });
})(jQuery);
