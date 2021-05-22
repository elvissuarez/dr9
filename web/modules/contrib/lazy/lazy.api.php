<?php

/**
 * @file
 * Lazy-load API.
 *
 * - Services:
 *
 * $lazy = \Drupal::service('lazy');
 *
 * // Is Lazy enabled.
 * // Returns module configuration (lazy.settings) if there's any fields or
 * // text-format has lazy-load enabled. FALSE otherwise.
 * $lazy_is_enabled = $lazy->isEnabled();
 *
 *
 * // Checks whether lazy-load is disabled for the current path.
 * // Returns a boolean value.
 * $disabled_paths = '/blog/feed';
 * $lazy->isPathAllowed($disabled_paths);
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter enabled field formatters for lazy-loading.
 *
 * When there's a module offering an image-based field-formatter,
 * but Lazy-loading doesn't support yet, you can still introduce that image
 * formatter in your custom module.
 *
 * @param array $formatters
 *   Array of field formatters.
 *
 * @return array
 *   Returns an array of field formatter names.
 */
function hook_lazy_field_formatters_alter(array &$formatters) {
  $formatters[] = 'xyz_module_field_formatter';

  return $formatters;
}

/**
 * Override the default styles.
 *
 * If needed the default styles can be altered via this hook.
 *
 * @param string $css
 *   The default CSS styles.
 */
function hook_lazy_default_styles_alter(&$css) {
  $css = <<<CSS
  .js img.lazyload:not([src]) { visibility: hidden; }
  .js img.lazyloaded[data-sizes=auto] { display: block; width: 100%; }
CSS;
}

/**
 * Override the default effect styles.
 *
 * If needed the default styles can be altered via this hook.
 *
 * The "Enable default CSS effect" option must be checked in module settings.
 *
 * @param string $css
 *   The default CSS effect styles.
 */
function hook_lazy_effect_styles_alter(&$css) {
  $css = <<<CSS
  .js .lazyload,
  .js .lazyloading { opacity: 0; }
  .js .lazyloaded { opacity: 1; -webkit-transition: opacity 2000ms; transition: opacity 2000ms; }
CSS;
}

/**
 * @} End of "addtogroup hooks".
 */
