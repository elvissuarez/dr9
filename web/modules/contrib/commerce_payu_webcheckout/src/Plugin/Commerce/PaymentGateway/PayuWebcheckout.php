<?php

namespace Drupal\commerce_payu_webcheckout\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_payu_webcheckout\PaymentParserInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Discovery\YamlDiscovery;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the PayU payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "payu_webcheckout",
 *   label = "payu_webcheckout",
 *   display_label = "PayU Webcheckout",
 *   modes = {
 *     "test" = @Translation("Test mode"),
 *     "live" = @Translation("Live mode"),
 *   },
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_payu_webcheckout\PluginForm\PayuWebcheckoutPaymentForm",
 *   },
 *   payment_type = "payment_manual",
 * )
 */
class PayuWebcheckout extends OffsitePaymentGatewayBase implements ContainerFactoryPluginInterface {

  const TEST_API_KEY = '4Vj8eK4rloUd272L48hsrarnUA';
  const TEST_MERCHANT_ID = '508029';
  const TEST_ACCOUNT_ID = '512321';
  const TEST_GATEWAY_URL = 'https://sandbox.gateway.payulatam.com/ppp-web-gateway/';
  const PROD_GATEWAY_URL = 'https://gateway.payulatam.com/ppp-web-gateway/';

  /**
   * Drupal module handler service.
   *
   * @var Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Drupal renderer service.
   *
   * @var Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The collection of Payu oficial Images.
   *
   * @var array
   */
  protected $images;

  /**
   * The Payu Payment parser.
   *
   * @var Drupal\commerce_payu_webcheckout\PaymentParserInterface
   */
  protected $paymentParser;

  /**
   * Constructs a new PayuWebcheckout object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_payment\PaymentTypeManager $payment_type_manager
   *   The payment type manager.
   * @param \Drupal\commerce_payment\PaymentMethodTypeManager $payment_method_type_manager
   *   The payment method type manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Drupal module handler service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Drupal renderer service.
   * @param \Drupal\commerce_payu_webcheckout\PaymentParserInterface $payment_parser
   *   PayU's payment parser.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time, ModuleHandlerInterface $module_handler, RendererInterface $renderer, PaymentParserInterface $payment_parser) {
    $this->moduleHandler = $module_handler;
    $this->renderer = $renderer;
    $this->paymentParser = $payment_parser;
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.commerce_payment_type'),
      $container->get('plugin.manager.commerce_payment_method_type'),
      $container->get('datetime.time'),
      $container->get('module_handler'),
      $container->get('renderer'),
      $container->get('payu.payment_parser')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayLabel() {
    if (!$this->configuration['image']) {
      return parent::getDisplayLabel();
    }
    $content = [
      '#theme' => 'image',
      '#uri' => $this->configuration['image'],
      '#alt' => 'PayU Webcheckout',
      '#title' => 'PayU Webcheckout',
    ];
    return $this->renderer->render($content);
  }

  /**
   * {@inheritdoc}
   */
  public function onNotify(Request $request) {
    // We catch the order.
    $hash_properties = $request->get('extra1');
    $hash_properties = unserialize($hash_properties);
    $order = $this->entityTypeManager->getStorage('commerce_order')->load($hash_properties['order_id']);

    // Make sure the order is not in the cart now.
    $order->set('cart', FALSE);
    $order->save();

    // Only move the order to the next step if payment is successful.
    if ($this->paymentParser->isSuccessful()) {
      $transition = $order->getState()->getWorkflow()->getTransition('place');
      $order->getState()->applyTransition($transition);
      $order->save();
    }

    // Create a payment regardless.
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    $payment = $payment_storage->create([
      'state' => $this->paymentParser->getState(),
      'amount' => $order->getTotalPrice(),
      'payment_gateway' => $this->entityId,
      'order_id' => $order->id(),
      'remote_id' => $this->paymentParser->getRemoteId(),
      'remote_state' => $this->paymentParser->getRemoteState(),
    ]);
    $payment->save();
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
    $modes = array_keys($this->getSupportedModes());
    return [
      'image_key' => 'no_image',
      'image' => '',
      'display_label' => $this->pluginDefinition['display_label'],
      'mode' => $modes ? reset($modes) : '',
      'purchase_description' => $this->t('Purchase from @sitename', [
        '@sitename' => '[site:name]',
      ]),
      'payment_method_types' => [],
      'payu_api_key' => self::TEST_API_KEY,
      'payu_merchant_id' => self::TEST_MERCHANT_ID,
      'payu_account_id' => self::TEST_ACCOUNT_ID,
      'payu_gateway_url' => self::TEST_GATEWAY_URL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $image_options = [];
    foreach (array_keys($this->payuImages()) as $image_key) {
      $image_options[$image_key] = ucfirst(str_replace('_', ' ', $image_key));
    }

    $form['image_key'] = [
      '#type' => 'select',
      '#title' => $this->t('Payment selection image.'),
      '#description' => $this->t('Select the image you would like to have displayed at the payment method pane. The codes you see listed are taken from the official PayU Corporative logo guidelines available <a href="@link">here</a>.', ['@link' => 'http://www.payulatam.com/logos/index.php']),
      '#options' => $image_options,
      '#default_value' => $this->configuration['image_key'],
      '#empty_option' => t('No image'),
      '#empty_value' => 'no_image',
    ];

    $form['purchase_description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Purchase description'),
      '#description' => $this->t('A brief description of the sale. You may include basic tokens in this field.'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['purchase_description'],
      '#maxlength' => 253,
    ];
    $form['payu_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Api key'),
      '#description' => $this->t('Your API Key provided by PayU.'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['payu_api_key'],
    ];
    $form['payu_merchant_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Merchant ID'),
      '#description' => $this->t('The Merchant ID provided by PayU.'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['payu_merchant_id'],
      '#maxlength' => 12,
    ];
    $form['payu_account_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Account ID'),
      '#description' => $this->t('The account ID provided by PayU.'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['payu_account_id'],
      '#maxlength' => 6,
    ];
    $form['payu_gateway_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Gateway URL'),
      '#description' => $this->t('The Gateway URL. The URL to redirect users on checkout.'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['payu_gateway_url'],
      '#attributes' => [
        'readonly' => 'readonly',
      ],
    ];
    $default_configuration = $this->defaultConfiguration();
    $default_configuration['prod_gateway_url'] = self::PROD_GATEWAY_URL;
    $form['#attached']['library'][] = 'commerce_payu_webcheckout/configuration_helper';
    $form['#attached']['drupalSettings']['commerce_payu_webcheckout']['configuration_helper']['default_settings'] = $default_configuration;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $values = $form_state->getValue($form['#parents']);
    $images = $this->payuImages();
    $this->configuration['image_key'] = $values['image_key'];
    $this->configuration['image'] = isset($images[$values['image_key']]) ? $images[$values['image_key']] : NULL;
    $this->configuration['purchase_description'] = $values['purchase_description'];
    $this->configuration['payu_api_key'] = $values['payu_api_key'];
    $this->configuration['payu_merchant_id'] = $values['payu_merchant_id'];
    $this->configuration['payu_account_id'] = $values['payu_account_id'];
    $this->configuration['payu_gateway_url'] = $values['payu_gateway_url'];
  }

}
