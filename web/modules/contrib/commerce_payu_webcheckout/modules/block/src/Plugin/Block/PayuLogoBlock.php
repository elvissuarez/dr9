<?php

namespace Drupal\commerce_payu_webcheckout_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Discovery\YamlDiscovery;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a generic Menu block.
 *
 * @Block(
 *   id = "payu_logo_block",
 *   admin_label = @Translation("PayU Logo"),
 *   category = @Translation("Commerce Payu Webcheckout"),
 * )
 */
class PayuLogoBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The collection of Payu oficial Images.
   *
   * @var array
   */
  protected $images;

  /**
   * Drupal module handler service.
   *
   * @var Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new SystemMenuBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler')
    );
  }

  /**
   * Obtains Images set in yaml.
   *
   * @return array
   *   An array with images found in Yaml files
   *   whose keys are the image key and whose
   *   value, is the actual image path to use.
   */
  protected function payuImages() {
    if ($this->images) {
      return $this->images;
    }
    $discovery = new YamlDiscovery('payu_images', $this->moduleHandler->getModuleDirectories());
    $definitions = $discovery->findAll();
    $this->images = reset($definitions);
    return $this->payuImages();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'image_key' => 'no_image',
      'image' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $image_options = [];
    foreach (array_keys($this->payuImages()) as $image_key) {
      $image_options[$image_key] = ucfirst(str_replace('_', ' ', $image_key));
    }

    $form['image_key'] = [
      '#type' => 'select',
      '#title' => $this->t('Prefered image.'),
      '#description' => $this->t('Select the image you would like to have displayed in this block. The codes you see listed are taken from the official PayU Corporative logo guidelines available <a href="@link">here</a>.', ['@link' => 'http://www.payulatam.com/logos/index.php']),
      '#options' => $image_options,
      '#default_value' => $this->configuration['image_key'],
      '#empty_option' => $this->t('No image'),
      '#empty_value' => 'no_image',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $images = $this->payuImages();
    $image_key = $form_state->getValue('image_key');
    $this->configuration['image_key'] = $image_key;
    $this->configuration['image'] = isset($images[$image_key]) ? $images[$image_key] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if (!$this->configuration['image']) {
      return [
        '#markup' => $this->t('Purchases powered by PayU.'),
      ];
    }
    return [
      '#theme' => 'image',
      '#uri' => $this->configuration['image'],
      '#alt' => 'PayU',
      '#title' => 'PayU',
    ];
  }

}
