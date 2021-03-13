<?php

namespace Drupal\commerce_payu_webcheckout\EventSubscriber;

use Drupal\commerce_payu_webcheckout\Event\HashPresaveEvent;
use Drupal\commerce_payu_webcheckout\PayuCurrencyFormatterInterface;
use Drupal\Core\Utility\Token;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class HashPresaveEventSubscriber.
 */
class HashPresaveEventSubscriber implements EventSubscriberInterface {

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The Payu Currency formatter service.
   *
   * @var \Drupal\commerce_payu_webcheckout\PayuCurrencyFormatterInterface
   */
  protected $formatter;

  /**
   * The current request.
   *
   * @var Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * Constructs a new HashPresaveEventSubscriber object.
   *
   * @param \Drupal\Core\Utility\Token $token
   *   The Drupal Token service.
   * @param \Drupal\commerce_payu_webcheckout\PayuCurrencyFormatterInterface $formatter
   *   The Payu Currency formatter service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Symfony's Request Stack.
   */
  public function __construct(Token $token, PayuCurrencyFormatterInterface $formatter, RequestStack $request_stack) {
    $this->token = $token;
    $this->formatter = $formatter;
    $this->currentRequest = $request_stack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      HashPresaveEvent::EVENT_NAME => 'appendComponents',
    ];
    return $events;
  }

  /**
   * Adds components to the Hash.
   *
   * @param \Drupal\commerce_payu_webcheckout\HashPresaveEvent $event
   *   An instance of HashPresaveEvent.
   */
  public function appendComponents(HashPresaveEvent $event) {
    $hash = $event->getHash();

    // Load configuration data.
    $order = $hash->getOrder();

    // Retrieve the Payment Gateway configuration.
    $gateway = $hash->getPaymentGateway();
    $configuration = $gateway->getPluginConfiguration();

    // Set the API key.
    $hash->setComponent('api_key', $configuration['payu_api_key']);

    // Set the Merchant Id.
    $hash->setComponent('merchant_id', $configuration['payu_merchant_id']);

    // Set the reference Code.
    $purchase_description = isset($configuration['purchase_description']) ? $configuration['purchase_description'] . '-%d' : NULL;
    $time = $this->currentRequest->server->get('REQUEST_TIME');
    $hash->setComponent('reference_code', trim($this->token->replace(sprintf($purchase_description, $time))));

    // Get the total price.
    $total_price = $order->getTotalPrice();
    $hash->setComponent('amount', $this->formatter->payuFormat($total_price->getNumber()));

    // Set the currency.
    $hash->setComponent('currency', $total_price->getCurrencyCode());
  }

}
