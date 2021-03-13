<?php

namespace Drupal\commerce_payu_webcheckout\Plugin\Commerce\PayuItem;

/**
 * Appends the payerEmail.
 *
 * Email address for the account acquiring the product/service.
 *
 * If you need to change how this is calculated, I suggest
 * you use the hook hook_payu_item_plugin_alter().
 *
 * @see commerce_payu_webcheckout.api.php
 *
 * @PayuItem(
 *   id = "payerEmail"
 * )
 */
class PayerEmail extends BuyerEmail {

}
