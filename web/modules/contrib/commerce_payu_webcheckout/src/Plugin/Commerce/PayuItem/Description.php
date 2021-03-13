<?php

namespace Drupal\commerce_payu_webcheckout\Plugin\Commerce\PayuItem;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payu_webcheckout\Plugin\PayuItemBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Utility\Token;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Appends the description.
 *
 * @PayuItem(
 *   id = "description"
 * )
 */
class Description extends PayuItemBase implements ContainerFactoryPluginInterface {

  /**
   * The Token service.
   *
   * @var Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Constructs a new Amount object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Utility\Token $token
   *   A Payu currency formatter.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Token $token) {
    $this->token = $token;
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
      $container->get('token')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function issueValue(PaymentInterface $payment) {
    $gateway = $payment->getPaymentGateway();
    $configuration = $gateway->getPluginConfiguration();
    $purchase_description = isset($configuration['purchase_description']) ? $configuration['purchase_description'] : NULL;
    return trim($this->token->replace($purchase_description));
  }

  /**
   * {@inheritdoc}
   */
  public function consumeValue(Request $request) {
    return $request->get($this->getConsumerId());
  }

}
