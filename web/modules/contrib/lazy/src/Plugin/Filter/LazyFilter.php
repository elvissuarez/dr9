<?php

namespace Drupal\lazy\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\lazy\Lazy;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a filter to lazy-load images.
 *
 * @Filter(
 *   id = "lazy_filter",
 *   title = @Translation("Lazy-load images and iframes"),
 *   description = @Translation("<a href=':url'>Configure options</a>", arguments = {":url" = "/admin/config/content/lazy"}),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
 *   weight = 20
 * )
 */
class LazyFilter extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Lazy-load service.
   *
   * @var \Drupal\lazy\Lazy
   */
  protected $lazyLoad;

  /**
   * Constructs a LazyFilter object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\lazy\Lazy $lazy_load
   *   Lazy-load service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, Lazy $lazy_load) {
    $this->configFactory = $config_factory;
    $this->lazyLoad = $lazy_load;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   A new container instance to replace the current.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('lazy')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode): FilterProcessResult {
    $config = $this->configFactory->get('lazy.settings')->get();
    $opt_skipClass = $config['skipClass'];
    $opt_selector = $config['lazysizes']['lazyClass'];
    $opt_tags = $config['alter_tag'];
    $opt_src = ($config['lazysizes']['srcAttr'] !== 'src') ? $config['lazysizes']['srcAttr'] : 'data-filterlazy-src';

    $result = new FilterProcessResult($text);
    $html_dom = Html::load($text);

    $path_allowed = $this->lazyLoad->isPathAllowed($config['disabled_paths']);

    if ($path_allowed) {
      foreach ($opt_tags as $tag => $status) {
        $matches = $html_dom->getElementsByTagName($tag);
        foreach ($matches as $element) {
          $classes = $element->getAttribute('class');
          $classes = ($classes !== '') ? explode(' ', $classes) : [];
          $parent_classes = $element->parentNode->getAttribute('class');
          $parent_classes = ($parent_classes !== '') ? explode(' ', $parent_classes) : [];
          if (empty($opt_tags[$tag])) {
            // If the `tag` is not enabled remove the selector class
            // used by JavaScript library (lazysizes).
            if (($key = array_search($opt_selector, $classes, FALSE)) !== FALSE) {
              unset($classes[$key]);
              $element->setAttribute('class', implode(' ', $classes));
              if (empty($classes)) {
                $element->removeAttribute('class');
              }
            }
          }
          else {
            // `tag` is enabled. Make sure skipClass is not set before
            // proceeding.
            if (!in_array($opt_skipClass, $classes, FALSE) && !in_array($opt_skipClass, $parent_classes, FALSE)) {
              $classes[] = $opt_selector;
              $classes = array_unique($classes);
              $element->setAttribute('class', implode(' ', $classes));

              $element->setAttribute('loading', 'lazy');

              $src = $element->getAttribute('src');
              // If defined use placeholder image in `src` attribute.
              // Remove otherwise.
              if ($config['placeholderSrc']) {
                $element->setAttribute('src', $config['placeholderSrc']);
              }
              else {
                $element->removeAttribute('src');
              }

              $element->setAttribute($opt_src, $src);
            }
          }
        }
      }
    }
    $result->setProcessedText(Html::serialize($html_dom));

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    $settings = $this->configFactory->get('lazy.settings');
    $tags = $settings->get('alter_tag');
    $skip = $settings->get('skipClass');
    $options = ['%img' => '<img>', '%iframe' => '<iframe>'];

    $skip_help = $this->t('If you want certain elements skip lazy-loading, add <code>%class</code> class name.', ['%class' => $skip]);

    if (!empty($tags)) {
      if ($tags['img'] && $tags['iframe']) {
        return $this->t('Lazy-loading is enabled for both %img and %iframe tags.', $options) . ' ' . $skip_help;
      }

      if ($tags['img']) {
        return $this->t('Lazy-loading is enabled for %img tags.', $options) . ' ' . $skip_help;
      }

      if ($tags['iframe']) {
        return $this->t('Lazy-loading is enabled for %iframe tags.', $options) . ' ' . $skip_help;
      }
    }

    return $this->t('Lazy-loading is not enabled.');
  }

}
