<?php

namespace Drupal\commerce_payu_webcheckout;

use Drupal\commerce_price\NumberFormatter;

/**
 * Formats Currency according to Payu Rules.
 */
class PayuCurrencyFormatter implements PayuCurrencyFormatterInterface {

  /**
   * The locale to use to format currencies.
   *
   * @var string
   */
  const DEFAULT_LOCALE = 'en';

  /**
   * The currency formatter provided by commerce price.
   *
   * @var \Drupal\commerce_price\NumberFormatter
   */
  protected $numberFormatter;

  /**
   * Construct the PayuCurrencyFormatter.
   *
   * @param \Drupal\commerce_price\NumberFormatter $number_formatter
   *   The commerce price currency formatter.
   */
  public function __construct(NumberFormatter $number_formatter) {
    $this->numberFormatter = $number_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public function payuFormat($number) {
    $options = [
      'locale' => self::DEFAULT_LOCALE,
      'minimum_fraction_digits' => 2,
      'maximum_fraction_digits' => 2,
      'style' => 'decimal',
      'use_grouping' => FALSE,
    ];
    $num = $this->numberFormatter->parse($number, ['locale' => self::DEFAULT_LOCALE]);
    $num = $this->numberFormatter->format($num, $options);
    return $num;
  }

}
