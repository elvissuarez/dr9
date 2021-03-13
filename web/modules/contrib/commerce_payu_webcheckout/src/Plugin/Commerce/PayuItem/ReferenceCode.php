<?php

namespace Drupal\commerce_payu_webcheckout\Plugin\Commerce\PayuItem;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payu_webcheckout\Plugin\PayuItemBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Utility\Token;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Appends the order reference code.
 *
 * @PayuItem(
 *   id = "referenceCode",
 *   consumerId = "reference_sale",
 * )
 */
class ReferenceCode extends PayuItemBase implements ContainerFactoryPluginInterface {

  /**
   * The Token service.
   *
   * @var Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The current request.
   *
   * @var Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

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
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Symfony's Request Stack.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Token $token, RequestStack $request_stack) {
    $this->token = $token;
    $this->currentRequest = $request_stack->getCurrentRequest();
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
      $container->get('token'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function issueValue(PaymentInterface $payment) {
    $gateway = $payment->getPaymentGateway();
    $configuration = $gateway->getPluginConfiguration();
    $purchase_description = isset($configuration['purchase_description']) ? $configuration['purchase_description'] . '-%d' : NULL;
    $time = $this->currentRequest->server->get('REQUEST_TIME');
    return trim($this->token->replace(sprintf($purchase_description, $time)));
  }

}
