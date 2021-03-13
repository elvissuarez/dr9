<?php

namespace Drupal\commerce_payu_webcheckout\Plugin\Commerce\PayuItem;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payu_webcheckout\Plugin\PayuItemBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Appends the billingAddress.
 *
 * If you need to change how this is calculated, I suggest
 * you use the hook hook_payu_item_plugin_alter().
 *
 * @see commerce_payu_webcheckout.api.php
 *
 * @PayuItem(
 *   id = "billingAddress",
 *   consumerId = "billing_address",
 * )
 */
class BillingAddress extends PayuItemBase {

  /**
   * {@inheritdoc}
   */
  public function issueValue(PaymentInterface $payment) {
    $order = $payment->getOrder();
    $billing_profile = $order->getBillingProfile();
    $address = $billing_profile->get('address')->getValue();
    $address = reset($address);
    $address_line = [];
    if ($address['address_line1']) {
      $address_line[] = $address['address_line1'];
    }
    if ($address['address_line2']) {
      $address_line[] = $address['address_line2'];
    }
    return implode(' ', $address_line);
  }

  /**
   * {@inheritdoc}
   */
  public function consumeValue(Request $request) {
    return $request->get($this->getConsumerId());
  }

}
