<?php

namespace Drupal\icon_select\Twig\Extension;

use Drupal\Core\Template\Attribute;

/**
 * Twig extension for icon rendering.
 */
class IconSelectExtension extends \Twig_Extension {

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      new \Twig_SimpleFunction('svg_icon', [$this, 'iconSelectRender']),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'icon_select';
  }

  /**
   * Returns an icon for a symbol id.
   *
   * @return array
   *   A render array of an icon.
   */
  public function iconSelectRender($symbol_id, $classes = []) {
    /** @var \Drupal\Core\Template\Attribute $attributes */
    $attributes = new Attribute();

    // Prepare classes.
    $attributes->addClass('icon', 'icon--' . $symbol_id);
    $attributes->addClass($classes);

    $build = [
      '#theme' => 'icon_select_svg_icon',
      '#attributes' => $attributes,
      '#symbol_id' => $symbol_id,
    ];

    return $build;
  }

}
