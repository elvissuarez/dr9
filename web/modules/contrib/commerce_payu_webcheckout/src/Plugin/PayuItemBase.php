<?php

namespace Drupal\commerce_payu_webcheckout\Plugin;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines the base class for all PayuItem plugins.
 */
abstract class PayuItemBase extends PluginBase implements PayuItemInterface {

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->pluginDefinition['id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return (!empty($this->pluginDefinition['label'])) ? $this->pluginDefinition['label'] : $this->pluginDefinition['id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getIssuerId() {
    return (!empty($this->pluginDefinition['issuerId'])) ? $this->pluginDefinition['issuerId'] : $this->pluginDefinition['id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getConsumerId() {
    return (!empty($this->pluginDefinition['consumerId'])) ? $this->pluginDefinition['consumerId'] : $this->pluginDefinition['id'];
  }

  /**
   * {@inheritdoc}
   */
  public function issueValue(PaymentInterface $payment) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function consumeValue(Request $request) {
    return NULL;
  }

}
