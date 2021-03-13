/**
 * @file
 * Icon select javascript behaviours
 */

(function ($, Drupal, window, document) {
  'use strict';

  Drupal.behaviors.icon_select_backend = {
    attach: function (context, settings) {

      settings.icon_select = settings.icon_select || drupalSettings.icon_select;

      var checkboxes = $('.icon-select-wrapper input[type="checkbox"]', context).once('iconSelectCheckboxes');
      var checkbox_wrapper = $('.icon-select-wrapper .form-type-checkbox', context).once('iconSelectCheckboxWrapper');

      // Allowing using contextual links in such wrappers that are <a> tags.
      checkboxes.bind('click', function (e) {
        $(this).closest('.form-wrapper').find('input[type="checkbox"]').not(this).prop('checked', false);
        e.stopPropagation();
      });

      checkboxes.change(function () {
        $(this).closest('.icon-select-wrapper').find('.form-type-checkbox').removeClass('selected');
        $(this).closest('.form-item').toggleClass('selected', this.checked);
      });

      checkboxes.each(function () {
        if (this.checked) {
          $(this).closest('.form-item').toggleClass('selected', this.checked);
        }
      });

      // Activate checkbox on click of the parent item as well.
      checkbox_wrapper.on('click', function (e) {
        var checkbox = $(this).find('input');
        checkbox.trigger('click');
      });
    }
  };

})(jQuery, Drupal, this, this.document);
