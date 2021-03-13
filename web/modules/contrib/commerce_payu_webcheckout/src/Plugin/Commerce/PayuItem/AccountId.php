<?php

namespace Drupal\commerce_payu_webcheckout\Plugin\Commerce\PayuItem;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payu_webcheckout\Plugin\PayuItemBase;

/**
 * Appends the Account ID.
 *
 * @PayuItem(
 *   id = "accountId"
 * )
 */
class AccountId extends PayuItemBase {

  /**
   * {@inheritdoc}
   */
  public function issueValue(PaymentInterface $payment) {
    $gateway = $payment->getPaymentGateway();
    $configuration = $gateway->getPluginConfiguration();
    return isset($configuration['payu_account_id']) ? $configuration['payu_account_id'] : NULL;
  }

}
