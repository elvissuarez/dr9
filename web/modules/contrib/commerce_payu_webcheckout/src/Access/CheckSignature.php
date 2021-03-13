<?php

namespace Drupal\commerce_payu_webcheckout\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Determines whether user has access to edit a views base entity.
 */
class CheckSignature implements AccessInterface {

  /**
   * An instance of the entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Builds a new CheckSignature object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * Checks whether signature is correct.
   *
   * AccessResult::neutral() is not used because it is currently the
   * same as setting it to forbidden.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   *
   * @see https://www.drupal.org/project/drupal/issues/2861074
   */
  public function access(Request $request, AccountInterface $account) {
    $hash_properties = $request->get('extra1');
    if (!$hash_properties) {
      // Do not act if payload in extra 1 is not set.
      return AccessResult::allowed()->setCacheMaxAge(0);
    }

    $hash_properties = unserialize($hash_properties);
    if (!is_array($hash_properties) || !isset($hash_properties['gateway_id'])) {
      // Do not act for gateways other than payu.
      return AccessResult::allowed()->setCacheMaxAge(0);
    }

    // Retrieve the stored hash.
    $hash = $this->entityManager->getStorage('payu_hash')->loadByProperties([
      'commerce_order' => $hash_properties['order_id'],
      'commerce_payment_gateway' => $hash_properties['gateway_id'],
    ]);
    if (!$hash) {
      // Deny access if hash doesn't exist.
      return AccessResult::forbidden('No PayU Hash found related to this transaction.')->setCacheMaxAge(0);
    }
    $hash = reset($hash);

    // Obtain both the state and signature from request.
    $state = $request->get('state_pol');
    $signature = $request->get('sign');

    // Calculate a new hash and compare it.
    $hash->setComponent('state', $state);
    if ((string) $hash != $signature) {
      return AccessResult::forbidden('The provided signature is not valid.')->setCacheMaxAge(0);
    }

    return AccessResult::allowed()->setCacheMaxAge(0);
  }

}
