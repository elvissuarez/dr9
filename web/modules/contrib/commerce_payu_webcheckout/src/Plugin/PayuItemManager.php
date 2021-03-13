<?php

namespace Drupal\commerce_payu_webcheckout\Plugin;

use Drupal\commerce_payu_webcheckout\Annotation\PayuItem;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Plugin manager for plugins of type PayuItem.
 */
class PayuItemManager extends DefaultPluginManager {

  /**
   * Constructs a new PayuItemManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Commerce/PayuItem', $namespaces, $module_handler, PayuItemInterface::class, PayuItem::class);
    $this->setCacheBackend($cache_backend, 'commerce_payu_webcheckout_item_plugins');
    $this->alterInfo('payu_item_plugin');
  }

}
