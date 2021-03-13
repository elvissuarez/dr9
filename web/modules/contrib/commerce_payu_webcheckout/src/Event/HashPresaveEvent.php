<?php

namespace Drupal\commerce_payu_webcheckout\Event;

use Drupal\commerce_payu_webcheckout\Entity\Hash;
use Symfony\Component\EventDispatcher\Event;

/**
 * An event dispatched when hash is being saved.
 */
class HashPresaveEvent extends Event {

  const EVENT_NAME = 'payu_hash.presave';

  /**
   * The hash.
   *
   * @var \Drupal\commerce_payu_webcheckout\Entity\Hash
   */
  protected $hash;

  /**
   * Constructs a HashPresaveEvent with a Hash.
   *
   * @param \Drupal\commerce_payu_webcheckout\Entity\Hash $hash
   *   The hash.
   */
  public function __construct(Hash $hash) {
    $this->hash = $hash;
  }

  /**
   * Returns the Hash.
   *
   * @return \Drupal\commerce_payu_webcheckout\Entity\Hash
   *   The hash.
   */
  public function getHash() {
    return $this->hash;
  }

}
