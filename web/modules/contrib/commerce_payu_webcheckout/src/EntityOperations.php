<?php

namespace Drupal\commerce_payu_webcheckout;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentGatewayInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A class to act on related entity events.
 */
final class EntityOperations implements ContainerInjectionInterface {

  /**
   * The Hash entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $hashStorage;

  /**
   * Builds a new EntityOperations object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->hashStorage = $entity_manager->getStorage('payu_hash');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')
    );
  }

  /**
   * Act on a Commerce Order delete action.
   *
   * Removes all hashes associated to an order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The Order object being deleted.
   */
  public function onOrderDelete(OrderInterface $order) {
    $hashes = $this->hashStorage->loadByProperties([
      'commerce_order' => $order->id(),
    ]);
    $this->hashStorage->delete($hashes);
  }

  /**
   * Act on a Commerce Gateway delete action.
   *
   * Removes all hashes associated to a gateway.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentGatewayInterface $gateway
   *   The gateway object being deleted.
   */
  public function onGatewayDelete(PaymentGatewayInterface $gateway) {
    $hashes = $this->hashStorage->loadByProperties([
      'commerce_payment_gateway' => $gateway->id(),
    ]);
    $this->hashStorage->delete($hashes);
  }

}
