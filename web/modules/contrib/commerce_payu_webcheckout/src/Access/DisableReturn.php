<?php

namespace Drupal\commerce_payu_webcheckout\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Disables the OnReturn callback when using PayU.
 *
 * The reason we are disabling the On Return Commerce
 * callback is that we don't want the order to be placed
 * in that callback. For that, we are using the On Notify
 * callback following PayU's recommendation.
 *
 * @see https://www.drupal.org/project/commerce/issues/2934647
 */
class DisableReturn implements AccessInterface {

  /**
   * Denies access when gateway is PayU.
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
    $commerce_order = $request->get('commerce_order');
    if ($commerce_order) {
      $payment_gateway = $commerce_order->get('payment_gateway')->entity;
      $plugin = $payment_gateway->getPlugin();
      if ($plugin->getPluginId() == 'payu_webcheckout') {
        return AccessResult::forbidden('Return page for Payu has been disabled.')->setCacheMaxAge(0);
      }
    }
    return AccessResult::allowed()->setCacheMaxAge(0);
  }

}
