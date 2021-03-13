<?php

namespace Drupal\commerce_payu_webcheckout\Plugin\Commerce\PayuItem;

use Drupal\commerce_payu_webcheckout\Plugin\PayuItemBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\commerce_payment\Entity\PaymentInterface;

/**
 * Consumes the Test parameter.
 *
 * @PayuItem(
 *   id = "test"
 * )
 */
class Test extends PayuItemBase {

  /**
   * {@inheritdoc}
   */
  public function consumeValue(Request $request) {
    return $request->get($this->getConsumerId());
  }

  /**
   * {@inheritdoc}
   */
  public function issueValue(PaymentInterface $payment) {
    $gateway = $payment->getPaymentGateway();
    $configuration = $gateway->getPluginConfiguration();
    return (isset($configuration['mode']) && $configuration['mode'] == 'test') ? 1 : 0;
  }

}
