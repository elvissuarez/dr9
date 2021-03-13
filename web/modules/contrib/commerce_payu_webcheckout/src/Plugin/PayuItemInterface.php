<?php

namespace Drupal\commerce_payu_webcheckout\Plugin;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines the interface for PayuItem plugins.
 */
interface PayuItemInterface {

  /**
   * Returns the plugin Label.
   *
   * @return string
   *   The plugin Label.
   */
  public function getLabel();

  /**
   * Returns the plugin Id.
   *
   * @return string
   *   The plugin ID.
   */
  public function getId();

  /**
   * The Issuer ID.
   *
   * The ID for the value to be printed in the Payment form.
   * Should return the plugin ID if not supplied.
   *
   * @return string
   *   The issuer ID.
   */
  public function getIssuerId();

  /**
   * The Consumer ID.
   *
   * The ID for the value to be captured in the Confirmation page.
   * Should return the plugin ID if not supplied.
   *
   * @return string
   *   The consumer ID.
   */
  public function getConsumerId();

  /**
   * Retrieves the value to print in payment form.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The Payment Entity (or an entity implementing this interface).
   *
   * @return mixed
   *   The value to be printed for this item. Null if the value
   *   should be ignored
   */
  public function issueValue(PaymentInterface $payment);

  /**
   * Processes the value as retrieved in confirmation page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current Request object.
   *
   * @return mixed
   *   The processed value. Null if the value should be ignored.
   */
  public function consumeValue(Request $request);

}
