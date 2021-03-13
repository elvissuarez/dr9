<?php

namespace Drupal\icon_select\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the icon_select_field text formatter.
 *
 * @FieldFormatter(
 *   id = "icon_select_formatter_default",
 *   module = "icon_select",
 *   label = @Translation("SVG Icon"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class IconSelectFormatterDefault extends FormatterBase {

  /**
   * The name of the field to which the formatter is associated.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->fieldName = $field_definition->getName();
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
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings']
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = [
      // Implement default settings.
      'apply_dimensions' => TRUE,
      'width' => 25,
      'height' => 25,
    ];
    return $settings + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['apply_dimensions'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Set image dimensions.'),
      '#default_value' => $this->getSetting('apply_dimensions'),
    ];
    $form['width'] = [
      '#type' => 'number',
      '#title' => $this->t('Image width.'),
      '#default_value' => $this->getSetting('width'),
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $this->fieldName . '][settings_edit_form][settings][apply_dimensions]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['height'] = [
      '#type' => 'number',
      '#title' => $this->t('Image height.'),
      '#default_value' => $this->getSetting('height'),
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $this->fieldName . '][settings_edit_form][settings][apply_dimensions]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    if ($this->getSetting('apply_dimensions') && $this->getSetting('width')) {
      $summary[] = $this->t('Image width:') . ' ' . $this->getSetting('width');
    }
    if ($this->getSetting('apply_dimensions') && $this->getSetting('width')) {
      $summary[] = $this->t('Image height:') . ' ' . $this->getSetting('height');
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $icons = $items->referencedEntities();

    foreach ($icons as $delta => $icon) {
      /** @var \Drupal\Core\Template\Attribute $attributes */
      $attributes = new Attribute();
      if ($this->getSetting('apply_dimensions')) {
        foreach (['width', 'height'] as $attribute_key) {
          if (!empty($attribute_value = $this->getSetting($attribute_key))) {
            $attributes[$attribute_key] = $attribute_value;
          }
        }
      }

      // Prepare classes.
      $attributes->addClass('icon', 'icon--' . $icon->field_symbol_id->value);

      if ($icon->field_svg_file->entity) {
        $elements[$delta] = [
          '#theme' => 'icon_select_svg_icon',
          '#attributes' => $attributes,
          '#symbol_id' => $icon->field_symbol_id->value,
        ];
      }
    }

    // Attach css / js library.
    if (count($elements)) {
      $elements['#attached'] = [
        'library' => ['icon_select/drupal.icon_select'],
      ];
    }

    return $elements;
  }

}
