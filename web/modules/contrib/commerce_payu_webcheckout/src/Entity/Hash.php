<?php

namespace Drupal\commerce_payu_webcheckout\Entity;

use Drupal\commerce_payu_webcheckout\Event\HashPresaveEvent;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the PayuHash entity.
 *
 * @ContentEntityType(
 *   id = "payu_hash",
 *   label = @Translation("PayU Hash"),
 *   base_table = "payu_hash",
 *   entity_keys = {
 *     "id" = "id",
 *     "created" = "created",
 *   },
 *   fieldable = FALSE,
 * )
 */
class Hash extends ContentEntityBase implements ContentEntityInterface {

  /**
   * The generated hash.
   *
   * @var string
   *   The hash.
   */
  protected $hash;

  /**
   * Calculates hash from components.
   */
  protected function generateHash() {
    $components = $this->getComponents();
    $values = [];
    foreach ($components as $component) {
      $values = array_merge($values, array_values($component));
    }
    $hash = md5(implode('~', array_values($values)));
    $this->hash = $hash;
  }

  /**
   * Id accessor.
   *
   * @return int
   *   The Id.
   */
  public function getId() {
    return $this->get('id')->value;
  }

  /**
   * Hash accessor.
   *
   * @return string
   *   The hash.
   */
  protected function getHash() {
    $this->generateHash();
    return $this->hash;
  }

  /**
   * Order accessor.
   *
   * @return Drupal\commerce_order\Entity\Order
   *   The Order.
   */
  public function getOrder() {
    return $this->get('commerce_order')->entity;
  }

  /**
   * Payment gateway accessor.
   *
   * @return Drupal\commerce_payment\Entity\PaymentGateway
   *   The chosen Payment gateway.
   */
  public function getPaymentGateway() {
    return $this->get('commerce_payment_gateway')->entity;
  }

  /**
   * UID accessor.
   *
   * @return int
   *   The order owner.
   */
  public function getUser() {
    return $this->get('uid')->entity;
  }

  /**
   * Created accessor.
   *
   * @return int
   *   The created timestamp.
   */
  public function created() {
    return $this->get('created')->value;
  }

  /**
   * Components accessor.
   *
   * @return array
   *   An associative array of components whose keys
   *   are component machine names (for reference) and whose
   *   values are component corresponding values.
   */
  public function getComponents() {
    return $this->get('components')->getValue();
  }

  /**
   * Retrieves a component.
   *
   * @return string|Null
   *   The component value if found, Null otherwise.
   */
  public function getComponent($component_name) {
    $components = $this->get('components')->getValue();
    foreach ($components as $component) {
      if (isset($component[$component_name])) {
        return $component[$component_name];
      }
    }
    return NULL;
  }

  /**
   * Adds a new component.
   *
   * @param string $component_name
   *   The component name.
   * @param string $component_value
   *   The component value.
   */
  public function setComponent($component_name, $component_value) {
    $components = $this->get('components')->getValue();
    if (!$components) {
      $components[] = [];
    }
    $components[0][$component_name] = $component_value;
    $this->set('components', $components);
  }

  /**
   * Components mutator.
   *
   * @param array $components
   *   The new components.
   */
  public function setComponents(array $components) {
    $this->set('components', $components);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    $event = new HashPresaveEvent($this);
    \Drupal::service('event_dispatcher')->dispatch(HashPresaveEvent::EVENT_NAME, $event);
    parent::preSave($storage);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['commerce_order'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Order'))
      ->setDescription(t('The order attached to this hash.'))
      ->setSetting('target_type', 'commerce_order')
      ->setReadOnly(TRUE);

    $fields['commerce_payment_gateway'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Payment gateway'))
      ->setDescription(t('The payment gateway attached to this hash.'))
      ->setSetting('target_type', 'commerce_payment_gateway')
      ->setReadOnly(TRUE);

    $fields['components'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Components.'))
      ->setRequired(TRUE)
      ->setDefaultValue([])
      ->setDescription(t('The components that make up the hash.'))
      ->setReadOnly(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time when the hash was created.'));

    return $fields;
  }

  /**
   * Implementation of magic method.
   *
   * @return string
   *   The hash that represents this entity.
   */
  public function __toString() {
    return $this->getHash();
  }

}
