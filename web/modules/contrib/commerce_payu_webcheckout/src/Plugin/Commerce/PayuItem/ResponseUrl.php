<?php

namespace Drupal\commerce_payu_webcheckout\Plugin\Commerce\PayuItem;

use Drupal\commerce_payu_webcheckout\Plugin\PayuItemBase;

/**
 * Appends the Response URL.
 *
 * The reason we do not supply this parameter is
 * because we do not want the order to take place
 * once the customer returns to our site to the
 * response page. We want the order to take place via
 * the Notify or confirmation page.
 *
 * @see https://www.drupal.org/project/commerce/issues/2934647
 *
 * @PayuItem(
 *   id = "responseUrl"
 * )
 */
class ResponseUrl extends PayuItemBase {

}
