<?php

namespace Drupal\commerce_payu_webcheckout\Plugin\Commerce\PayuItem;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payu_webcheckout\Plugin\PayuItemBase;

/**
 * Appends the Confirmation URL.
 *
 * @PayuItem(
 *   id = "confirmationUrl"
 * )
 */
class ConfirmationUrl extends PayuItemBase {

  /**
   * {@inheritdoc}
   */
  public function issueValue(PaymentInterface $payment) {
    $gateway = $payment->getPaymentGateway();
    return $gateway->getPlugin()->getNotifyUrl()->toString();
  }

}
