<?php

namespace Drupal\lazy;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\PathMatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class Lazy.
 */
class Lazy implements LazyInterface {

  /**
   * A config object for the module configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Represents the current path for the current request.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $pathCurrent;

  /**
   * The path alias manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $pathAliasManager;

  /**
   * The path matcher service.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Lazy constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path stack.
   * @param \Drupal\path_alias\AliasManagerInterface $alias_manager
   *   The path alias manager.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(ConfigFactoryInterface $config_factory, CurrentPathStack $current_path, AliasManagerInterface $alias_manager, PathMatcherInterface $path_matcher, RequestStack $request_stack) {
    $this->config = $config_factory->get('lazy.settings')->get();
    $this->pathCurrent = $current_path;
    $this->pathAliasManager = $alias_manager;
    $this->pathMatcher = $path_matcher;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugins(): array {
    return [
      'artdirect' => 'artdirect/ls.artdirect',
      'aspectratio' => 'aspectratio/ls.aspectratio',
      'attrchange' => 'attrchange/ls.attrchange',
      'bgset' => 'bgset/ls.bgset',
      'blur-up' => 'blur-up/ls.blur-up',
      'custommedia' => 'custommedia/ls.custommedia',
      'fix-edge-h-descriptor' => 'fix-edge-h-descriptor/ls.fix-edge-h-descriptor',
      'fix-ios-sizes' => 'fix-ios-sizes/fix-ios-sizes',
      'include' => 'include/ls.include',
      'native-loading' => 'native-loading/ls.native-loading',
      'noscript' => 'noscript/ls.noscript',
      'object-fit' => 'object-fit/ls.object-fit',
      'optimumx' => 'optimumx/ls.optimumx',
      'parent-fit' => 'parent-fit/ls.parent-fit',
      'print' => 'print/ls.print',
      'progressive' => 'progressive/ls.progressive',
      'respimg' => 'respimg/ls.respimg',
      'rias' => 'rias/ls.rias',
      'static-gecko-picture' => 'static-gecko-picture/ls.static-gecko-picture',
      'twitter' => 'twitter/ls.twitter',
      'unload' => 'unload/ls.unload',
      'unveilhooks' => 'unveilhooks/ls.unveilhooks',
      'video-embed' => 'video-embed/ls.video-embed',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    $status = [];

    if ($filters = $this->isFiltersEnabled()) {
      $status = array_merge($status, $filters);
    }

    if ($fields = $this->isFieldsEnabled($this->config['image_fields'])) {
      $status = array_merge($status, $fields);
    }

    $this->config['status'] = $status;

    return count($status) ? $this->config : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isFieldsEnabled(array $image_fields) {
    $status = [];

    $image_fields = is_array($image_fields) ? $image_fields : [];

    foreach ($image_fields as $tag => $option) {
      if ($image_fields[$tag]) {
        $status[$tag] = (bool) $option;
      }
    }

    return count($status) ? $status : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isFiltersEnabled() {
    $status = [];

    foreach (filter_formats() as $filter) {
      if (
        $filter->status()
        && isset($filter->getDependencies()['module'])
        && in_array('lazy', $filter->getDependencies()['module'], TRUE)
      ) {
        $status[$filter->id()] = TRUE;
      }
    }

    return count($status) ? $status : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isPathAllowed($disabled_paths): bool {
    $request = $this->requestStack->getCurrentRequest();

    // Disable lazy-loading for all AMP pages.
    if ($request->query->has('amp')) {
      return FALSE;
    }

    $current_path = $request->getPathInfo();

    $current_path_matcher = $this->pathMatcher->matchPath($current_path, $disabled_paths);
    $path_alias = $this->pathAliasManager->getAliasByPath($current_path);
    $path_alias_matcher = $this->pathMatcher->matchPath($path_alias, $disabled_paths);

    return !($current_path_matcher || $path_alias_matcher);
  }

}
