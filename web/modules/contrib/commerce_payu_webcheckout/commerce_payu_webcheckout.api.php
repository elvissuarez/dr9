<?php

/**
 * @file
 * Hooks for the commerce_payu_webcheckout module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alters discovered payuItem plugins.
 *
 * @param array $definitions
 *   Discovered definitions.
 */
function hook_payu_item_plugin_alter(array &$definitions) {
  unset($definitions['payerEmail']);
}

/**
 * @} End of "addtogroup hooks".
 */
