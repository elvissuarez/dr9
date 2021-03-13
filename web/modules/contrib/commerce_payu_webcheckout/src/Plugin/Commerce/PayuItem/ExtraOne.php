<?php

namespace Drupal\commerce_payu_webcheckout\Plugin\Commerce\PayuItem;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payu_webcheckout\Plugin\PayuItemBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Populates the extra1 field.
 *
 * This module uses the extra1 field to send a
 * serialization of order ID and payment gateway.
 *
 * We do this because we want the order to be processed
 * on the onNotify Callback instead of the onReturn callback and
 * currently, Commerce does not pass such callback the
 * Commerce order.
 *
 * @see https://www.drupal.org/project/commerce/issues/2934647
 *
 * @PayuItem(
 *   id = "extra1"
 * )
 */
class ExtraOne extends PayuItemBase {

  /**
   * {@inheritdoc}
   */
  public function issueValue(PaymentInterface $payment) {
    $value = [
      'order_id' => $payment->getOrderId(),
      'gateway_id' => $payment->getPaymentGatewayId(),
    ];
    return serialize($value);
  }

  /**
   * {@inheritdoc}
   */
  public function consumeValue(Request $request) {
    return $request->get($this->getConsumerId());
  }

}
