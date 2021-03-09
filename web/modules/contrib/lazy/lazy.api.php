<?php

/**
 * @file
 * Lazy-load API.
 *
 * - How to add other image-based field formatters?
 * - Services.
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
 * Available service calls.
 */
$lazy = \Drupal::service('lazy');

// Is Lazy enabled.
// Returns module configuration (lazy.settings) if there's any fields or
// text-format has lazy-load enabled. FALSE otherwise.
$lazy_is_enabled = $lazy->isEnabled();

// Checks whether lazy-load is disabled for the current path.
// Returns a boolean value.
$disabled_paths = '/blog/feed';
$lazy_disabled_paths = $lazy->isPathAllowed($disabled_paths);
