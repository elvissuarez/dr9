<?php

namespace Drupal\commerce_payu_webcheckout\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Alters notify route access requirement.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('commerce_payment.notify')) {
      $route->setRequirement('_check_payu_signature', 'TRUE');
    }
    if ($route = $collection->get('commerce_payment.checkout.return')) {
      $route->setRequirement('_disable_return_for_payu', 'TRUE');
    }
  }

}
