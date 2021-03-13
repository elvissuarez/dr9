<?php

namespace Drupal\commerce_payu_webcheckout_tax\Plugin\Commerce\PayuItem;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payu_webcheckout\PayuCurrencyFormatterInterface;
use Drupal\commerce_payu_webcheckout\Plugin\PayuItemBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Appends the tax base amount.
 *
 * @PayuItem(
 *   id = "taxReturnBase"
 * )
 */
class TaxReturnBase extends PayuItemBase implements ContainerFactoryPluginInterface {

  /**
   * A Payu currency formatter.
   *
   * @var Drupal\commerce_payu_webcheckout\PayuCurrencyFormatterInterface
   */
  protected $currencyFormatter;

  /**
   * Constructs a new Amount object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_payu_webcheckout\PayuCurrencyFormatterInterface $currency_formatter
   *   A Payu currency formatter.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PayuCurrencyFormatterInterface $currency_formatter) {
    $this->currencyFormatter = $currency_formatter;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('payu.currency_formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function issueValue(PaymentInterface $payment) {
    $order = $payment->getOrder();
    $total_amount = $order->getTotalPrice()->getNumber();
    $tax_amount = 0;
    foreach ($order->getItems() as $order_item) {
      foreach ($order_item->getAdjustments() as $adjustment) {
        if ($adjustment->getType() == 'tax') {
          $tax_amount += $adjustment->getAmount()->getNumber();
        }
      }
    }
    if ($tax_amount == 0) {
      return 0;
    }
    return $this->currencyFormatter->payuFormat($total_amount - $tax_amount);
  }

}
