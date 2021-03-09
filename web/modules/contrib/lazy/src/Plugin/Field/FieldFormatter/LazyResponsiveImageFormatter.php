<?php

namespace Drupal\lazy\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\responsive_image\Plugin\Field\FieldFormatter\ResponsiveImageFormatter;

/**
 * Plugin implementation of the 'lazy_responsive_image' formatter.
 *
 * @FieldFormatter(
 *   id = "lazy_responsive_image",
 *   label = @Translation("Responsive image (Lazy-load)"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class LazyResponsiveImageFormatter extends ResponsiveImageFormatter {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = $this->t('Lazy-loading enabled');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    foreach ($elements as $delta => $element) {
      $elements[$delta]['#item_attributes']['data-lazy'] = TRUE;
    }

    return $elements;
  }

}
