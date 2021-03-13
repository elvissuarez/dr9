<?php

namespace Drupal\commerce_payu_webcheckout;

/**
 * Defines the base interface for Payu Currency decorator.
 */
interface PayuCurrencyFormatterInterface {

  /**
   * Formats a number/currency according to PayU specs.
   *
   * 1. If the second decimal of the $number parameter is zero,
   * The returning value should only have one decimal.
   * 2. If the second decimal of the $number parameter is non zero,
   * The returning value should have two decimals.
   *
   * @param string $number
   *   The number to decorate in string format.
   *
   * @return string
   *   The formatted number as a string.
   */
  public function payuFormat($number);

}
