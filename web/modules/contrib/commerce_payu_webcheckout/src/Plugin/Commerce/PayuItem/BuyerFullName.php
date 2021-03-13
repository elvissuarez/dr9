<?php

namespace Drupal\commerce_payu_webcheckout\Plugin\Commerce\PayuItem;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payu_webcheckout\Plugin\PayuItemBase;

/**
 * Appends the buyerFullName.
 *
 * If you need to change how this is calculated, I suggest
 * you use the hook hook_payu_item_plugin_alter().
 *
 * @see commerce_payu_webcheckout.api.php
 *
 * @PayuItem(
 *   id = "buyerFullName"
 * )
 */
class BuyerFullName extends PayuItemBase {

  /**
   * {@inheritdoc}
   */
  public function issueValue(PaymentInterface $payment) {
    $order = $payment->getOrder();
    $billing_profile = $order->getBillingProfile();
    $address = $billing_profile->get('address')->getValue();
    $address = reset($address);
    $name = [];
    if ($address['given_name']) {
      $name[] = $address['given_name'];
    }
    if ($address['additional_name']) {
      $name[] = $address['additional_name'];
    }
    if ($address['family_name']) {
      $name[] = $address['family_name'];
    }
    return implode(' ', $name);
  }

}
