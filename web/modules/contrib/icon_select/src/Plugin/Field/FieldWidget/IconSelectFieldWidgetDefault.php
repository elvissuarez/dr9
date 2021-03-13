<?php

namespace Drupal\icon_select\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element\Checkboxes;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the icon_select_widget default input widget.
 *
 * @FieldWidget(
 *   id = "icon_select_widget_default",
 *   module = "icon_select",
 *   label = @Translation("Icon Select"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class IconSelectFieldWidgetDefault extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new IconSelectFieldWidget object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $terms = $term_storage->loadTree('icons', 0, NULL, TRUE);
    $form['#terms'] = [];

    $options = [];

    /** @var \Drupal\taxonomy\TermInterface $term */
    foreach ($terms as $term) {
      $options[$term->id()] = $term->field_symbol_id->value;
      $form['#terms'][$term->id()] = $term;
    }

    $default_value = [];
    foreach ($items as $item) {
      if (!empty($item->getValue())) {
        $default_value[$item->getValue()['target_id']] = $item->getValue()['target_id'];
      }
    }

    $element += [
      '#type' => 'checkboxes',
      '#default_value' => $default_value,
      '#options' => $options,
      '#required' => $this->fieldDefinition->isRequired(),
      '#terms' => $form['#terms'],
    ];

    $container = [
      '#type' => 'details',
      '#title' => $this->t('Choose an icon'),
      '#attributes' => ['class' => ['icon-select-wrapper']],
      'target_id' => $element,
      '#open' => FALSE,
      '#attached' => [
        'library' => ['icon_select/drupal.icon_select'],
      ],
    ];

    return $container;
  }

  /**
   * Checkbox processing.
   */
  public static function processCheckboxes(&$element, FormStateInterface $form_state, &$complete_form) {
    $element = Checkboxes::processCheckboxes($element, $form_state, $complete_form);
    if (count($element['#options']) > 0) {
      foreach ($element['#options'] as $key => $choice) {
        $icon['icon'] = [
          '#theme' => 'icon_select_svg_icon',
          '#symbol_id' => $element[$key]['#title'],
          '#attributes' => [
            'class' => [
              'icon',
              'icon--' . $element[$key]['#title'],
            ],
          ],
        ];

        // Add title.
        /** @var \Drupal\taxonomy\Entity\Term $term */
        if (!empty($element['#terms'][$key])) {
          $term = $element['#terms'][$key];
          $element[$key]['#field_prefix'] = $term->getName();
        }

        // Add markup for icon.
        $element[$key]['#field_suffix'] = $icon;

        // Unset title.
        unset($element[$key]['#title']);
      }
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $return = [];
    foreach ($values as $value) {
      if (is_array($value['target_id'])) {
        foreach ($value['target_id'] as $target_id) {
          if (!empty($target_id)) {
            $return[] = ['target_id' => $target_id];
          }
        }
      }
    }
    return $return;
  }

}
