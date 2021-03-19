/**
 * @file
 * Global utilities.
 *
 */
(function($, Drupal) {

  'use strict';

  Drupal.behaviors.mytool_bootstrap_sass = {
    attach: function(context, settings) {
      // Custom code here
      // open filters
      $('#block-views-block-filtro-por-categorias-menu li div').once("mytool_bootstrap_sass").click(function (e) {
        // e.preventDefault();
        // e.stopPropagation();
        let currentSpan = $(this).find('span');
        let currentSVG = $(this).find('svg');
        currentSVG.toggleClass('active');
        $('#block-views-block-filtro-por-categorias-menu li span').not(currentSpan).removeClass('active').parent().next().slideUp();
        $('#block-views-block-filtro-por-categorias-menu li svg').not(currentSVG).removeClass('fa-caret-down active').addClass('fa-caret-right');
        $('#block-views-block-filtro-por-categorias-menu li', currentSVG).toggleClass('active');
        currentSpan.toggleClass('active').parent().next().slideToggle();
        // currentSpan.siblings().removeClass('fa-caret-right').addClass('fa-caret-down active');
      });
    }
  };

})(jQuery, Drupal);
