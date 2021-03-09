<?php

namespace Drupal\lazy;

/**
 * Interface LazyInterface.
 */
interface LazyInterface {

  /**
   * List of available Lazysizes plugins.
   *
   * @return array
   *   Returns an array of all available lazysizes plugins.
   */
  public function getPlugins(): array;

  /**
   * Is Lazy-load enabled?
   *
   * @return \Drupal\Core\Config\ConfigFactoryInterface|false
   *   Returns module configuration (lazy.settings) if there is any field or
   *   text-format has lazy-load enabled. FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Is Lazy-load enabled for any image-field?
   *
   * @param array $image_fields
   *   A simple dataset for all lazy-load enabled image fields in following
   *   format: {media-type}--{bundle}--{field_name}--{view-mode}.
   *
   * @return array|false
   *   Return an array of field names that enabled Lazy-load, FALSE otherwise.
   */
  public function isFieldsEnabled(array $image_fields);

  /**
   * Is Lazy-load enabled for any text-format filters?
   *
   * @return array|false
   *   Returns an array of filters that has enabled Lazy-load, FALSE otherwise.
   */
  public function isFiltersEnabled();

  /**
   * Is lazy-loading allowed for current path?
   *
   * @param string $disabled_paths
   *   List of paths Lazy should be disabled.
   *
   * @return bool
   *   Returns TRUE if lazy-loading is allowed for current path.
   */
  public function isPathAllowed($disabled_paths): bool;

}
