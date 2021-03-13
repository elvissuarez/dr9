<?php

namespace Drupal\commerce_payu_webcheckout\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm;
use Drupal\commerce_payu_webcheckout\Plugin\PayuItemInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The form generating POST data to the PayU gateway.
 */
class PayuWebcheckoutPaymentForm extends PaymentOffsiteForm implements ContainerInjectionInterface {

  /**
   * The Plugin manager for PayuItem plugins.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $payuItemManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The Hash entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $hashStorage;

  /**
   * The Commerce Payment entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $paymentStorage;

  /**
   * Builds a new PayuWebcheckoutPaymentForm object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $payu_item_manager
   *   The Plugin manager for PayuItem plugins.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher service.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   */
  public function __construct(PluginManagerInterface $payu_item_manager, EventDispatcherInterface $event_dispatcher, EntityManagerInterface $entity_manager) {
    $this->payuItemManager = $payu_item_manager;
    $this->eventDispatcher = $event_dispatcher;
    $this->hashStorage = $entity_manager->getStorage('payu_hash');
    $this->paymentStorage = $entity_manager->getStorage('commerce_payment');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.payu_item'),
      $container->get('event_dispatcher'),
      $container->get('entity.manager')
    );
  }

  /**
   * Retrieves all form data to be sent over to PayU.
   *
   * @return array
   *   Array with all retrieved data using the PayuItem manager
   *   whose keys are parameter machine names and whose values
   *   are the corresponding parameter values.
   */
  protected function retrieveFormData() {
    $data = [];
    $definitions = $this->payuItemManager->getDefinitions();
    foreach ($definitions as $definition) {
      $plugin = $this->payuItemManager->createInstance($definition['id']);
      if ($plugin instanceof PayuItemInterface) {
        $issued_data = $plugin->issueValue($this->entity);
        if ($issued_data) {
          $data[$plugin->getIssuerId()] = $issued_data;
        }
      }
    }
    return $data;
  }

  /**
   * Retrieves a Hash for the current order.
   *
   * If the hash is not found, a new one will be created.
   *
   * @return Drupal\commerce_payu_webcheckout\Entity\Hash
   *   The found hash (or a newly created one).
   */
  protected function retrieveHashForCurrentOrder() {
    $hashes = $this->hashStorage->loadByProperties([
      'commerce_order' => $this->entity->getOrder()->id(),
      'commerce_payment_gateway' => $this->getEntity()->getPaymentGatewayId(),
    ]);
    if ($hashes) {
      $hash = reset($hashes);
      // We reset the components array.
      $hash->setComponents([]);
      return $hash;
    }
    return $this->hashStorage->create([
      'commerce_order' => $this->entity->getOrder()->id(),
      'commerce_payment_gateway' => $this->getEntity()->getPaymentGatewayId(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Retrieve the redirect URL.
    $gateway = $this->getEntity()->getPaymentGateway();
    $configuration = $gateway->getPluginConfiguration();
    $redirect_url = $configuration['payu_gateway_url'];

    // Retrieve all data we want to send to PayU.
    $data = $this->retrieveFormData();

    // Retrieve a hash for this order.
    $hash = $this->retrieveHashForCurrentOrder();
    $hash->save();

    // Add the hash to the data array.
    $data['signature'] = (string) $hash;

    // Build the redirection form.
    $form = $this->buildRedirectForm($form, $form_state, $redirect_url, $data, self::REDIRECT_POST);

    return $form;
  }

}
