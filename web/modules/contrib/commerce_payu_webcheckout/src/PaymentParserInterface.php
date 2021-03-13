<?php

namespace Drupal\commerce_payu_webcheckout;

/**
 * Defines the base interface for a payment parser.
 *
 * A payment parser reads the request query to understand
 * whether a payment has been successful.
 */
interface PaymentParserInterface {

  /**
   * Whether the payment has been successful.
   *
   * @return bool
   *   Whether the payment was successful
   */
  public function isSuccessful();

  /**
   * The message corresponding to the gateway state.
   *
   * @return string
   *   The corresponding message.
   */
  public function getMessage();

  /**
   * The calculated state of the payment.
   *
   * @return string
   *   The payment state. Must match one of the
   *   states allowed in the payment_manual workflow.
   */
  public function getState();

  /**
   * The state as reported by the gateway.
   *
   * @return string
   *   The state as reported by the gateway.
   */
  public function getRemoteState();

  /**
   * Payment attempt ID as reported by Gateway.
   *
   * @return string
   *   The ID as reported by Gateway.
   */
  public function getRemoteId();

  /**
   * All additional items reported by the gateway.
   *
   * This method is never invoked as there is no place
   * to store such data.
   *
   * @see https://www.drupal.org/project/commerce/issues/2993870
   *
   * @return array
   *   An associative array with additional items
   *   reported by the gateway.
   */
  public function getItems();

}
