/**
 * @file
 * Helps with gateway configuration.
 */

(function ($) {

  /**
   * Attaches the configuration helper to config form.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Enhances configuration experience.
   */
  Drupal.behaviors.sortable = {
    attach: function (context, settings) {
      $('input[name="configuration[payu_webcheckout][mode]"]', context).change(function () {
        switch (this.value) {
          case 'test':
            // Set values for test configuration.
            $('input[name="configuration[payu_webcheckout][payu_api_key]"]', context).val(settings.commerce_payu_webcheckout.configuration_helper.default_settings.payu_api_key);
            $('input[name="configuration[payu_webcheckout][payu_merchant_id]"]', context).val(settings.commerce_payu_webcheckout.configuration_helper.default_settings.payu_merchant_id);
            $('input[name="configuration[payu_webcheckout][payu_account_id]"]', context).val(settings.commerce_payu_webcheckout.configuration_helper.default_settings.payu_account_id);
            // Set the value for the Gateway URL.
            $('input[name="configuration[payu_webcheckout][payu_gateway_url]"]', context).val(settings.commerce_payu_webcheckout.configuration_helper.default_settings.payu_gateway_url);
            break;

          case 'live':
            // Clean values for configuration.
            $('input[name="configuration[payu_webcheckout][payu_api_key]"]', context).val('');
            $('input[name="configuration[payu_webcheckout][payu_merchant_id]"]', context).val('');
            $('input[name="configuration[payu_webcheckout][payu_account_id]"]', context).val('');
            // Set the value for the Gateway URL.
            $('input[name="configuration[payu_webcheckout][payu_gateway_url]"]', context).val(settings.commerce_payu_webcheckout.configuration_helper.default_settings.prod_gateway_url);
            break;
        }
      });
    }
  };

})(jQuery);
