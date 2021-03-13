<?php

namespace Drupal\commerce_payu_webcheckout\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines annotation for PayuItem.
 *
 * @see \Drupal\commerce_payu_webcheckout\Plugin\PayuItemManager
 * @see plugin_api
 *
 * @Annotation
 */
class PayuItem extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The Consumer ID.
   *
   * The ID for the value to be captured in the Confirmation page.
   *
   * @var string
   */
  public $consumerId;

  /**
   * The Issuer ID.
   *
   * The ID for the value to be printed in the Payment form.
   *
   * @var string
   */
  public $issuerId;

  /**
   * The administrative label of the item.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
