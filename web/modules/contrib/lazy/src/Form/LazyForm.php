<?php

namespace Drupal\lazy\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\lazy\Lazy;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleHandler;

/**
 * Configure Lazy settings for this site.
 */
class LazyForm extends ConfigFormBase {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Lazy-load service.
   *
   * @var \Drupal\lazy\Lazy
   */
  protected $lazyLoad;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * Constructs a LazyForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\lazy\Lazy $lazy_load
   *   The Lazy-load service.
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   The module handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Lazy $lazy_load, ModuleHandler $module_handler) {
    parent::__construct($config_factory);
    $this->lazyLoad = $lazy_load;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Instantiates a new instance of this class.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container this instance should use.
   *
   * @return \Drupal\Core\Form\ConfigFormBase|\Drupal\Core\Form\FormBase|static
   *   A static class.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('lazy'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'lazy_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['lazy.settings', 'image.settings'];
  }

  /**
   * Builds the status report for all enabled filter formats & field formatters.
   *
   * @param array $fields
   *   List of all enabled filter formats, and field formatters.
   */
  protected function getEnabledFiltersAndFields(array $fields) {
    $links = [];
    $anchor_links = [
      'filter' => [],
      'field' => [],
    ];

    foreach (filter_formats() as $key => $filter) {
      if (
        $filter->status()
        && isset($filter->getDependencies()['module'])
        && in_array('lazy', $filter->getDependencies()['module'], TRUE)
      ) {
        $links['filter'][$filter->id()] = Link::createFromRoute(
          $filter->label(), 'entity.filter_format.edit_form', [
            'filter_format' => $filter->id(),
          ], [
            'query' => [
              'destination' => Url::fromRoute('lazy.config_form')->toString(),
            ],
          ]);
      }
    }

    $image_fields = is_array($fields) ? $fields : [];
    $entity_type = $entity_bundle = $field_name = $view_mode = '';
    foreach ($image_fields as $tag => $option) {
      if ($image_fields[$tag]) {
        list($entity_type, $entity_bundle, $field_name, $view_mode) = explode('--', $tag);
        if (($entity_type !== NULL) && ($entity_bundle !== NULL)) {
          if ($entity_type === 'paragraph') {
            $key = 'paragraphs_type';
          }
          elseif ($entity_type === 'taxonomy_term') {
            $key = 'taxonomy_vocabulary';
          }
          elseif ($entity_type === 'contact_message') {
            $key = 'contact_form';
          }
          else {
            $key = "${entity_type}_type";
          }
          $links['field'][$tag] = Link::createFromRoute(
            "${entity_bundle}.${field_name} (${view_mode})",
            "entity.entity_view_display.$entity_type.view_mode", [
              $key => $entity_bundle,
              'view_mode_name' => $view_mode,
            ]);
        }
      }
    }

    foreach ($links as $key => $link_group) {
      foreach ($link_group as $link) {
        $anchor_links[$key][] = $link->toString();
      }
    }

    $filter_links_result = (count($anchor_links['filter'])) ? implode(', ', $anchor_links['filter']) : 'none';
    $filter_message_type = ($filter_links_result === 'none')
      ? MessengerInterface::TYPE_WARNING
      : MessengerInterface::TYPE_STATUS;
    $this->messenger()->addMessage(
      $this->t("The <strong>text-formats</strong> have lazy-loading enabled: ${filter_links_result}"),
      $filter_message_type
    );

    $field_links_result = (count($anchor_links['field'])) ? implode(', ', $anchor_links['field']) : 'none';
    $field_message_type = ($field_links_result === 'none')
      ? MessengerInterface::TYPE_WARNING
      : MessengerInterface::TYPE_STATUS;
    $this->messenger()->addMessage(
      $this->t("The <strong>fields</strong> have lazy-loading enabled: ${field_links_result}"),
      $field_message_type
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('lazy.settings');

    $image_fields = is_array($config->get('image_fields')) ? $config->get('image_fields') : [];
    $this->getEnabledFiltersAndFields($image_fields);

    $form['preview'] = [
      '#type' => 'details',
      '#title' => $this->t('Preview'),
      '#open' => FALSE,
    ];
    $form['preview']['spacer'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $this->t('Following empty space is intentional. Scroll down for an image preview.'),
      '#attributes' => [
        'style' => 'height: 3000px;',
      ],
    ];
    $form['preview']['image'] = [
      '#theme' => 'image',
      '#uri' => $this->config('image.settings')->get('preview_image'),
      '#alt' => $this->t('Preview image'),
      '#title' => $this->t('Preview image'),
      '#attributes' => [
        'width' => 480,
        'height' => 360,
        'style' => 'width: 480px; height: 360px;',
        'data-lazy' => TRUE,
      ],
    ];

    $form['settings'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Settings'),
      '#parents' => ['lazy_tabs'],
    ];

    $form['text_format'] = [
      '#type' => 'details',
      '#title' => $this->t('Text-formats only'),
      '#group' => 'lazy_tabs',
    ];
    $form['text_format']['alter_tag'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select the inline elements to be lazy-loaded via filter.'),
      '#description' => $this->t('Only selected tags will be lazy-loaded in activated text-formats.'),
      '#options' => [
        'img' => $this->t('Enable for images (%img tags)', ['%img' => '<img>']),
        'iframe' => $this->t('Enable for iframes (%iframe tags)', ['%iframe' => '<iframe>']),
      ],
      '#default_value' => $config->get('alter_tag'),
    ];

    $form['field'] = [
      '#type' => 'details',
      '#title' => $this->t('Fields only'),
      '#group' => 'lazy_tabs',
    ];
    $form['field']['formatters'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Additional image formatters'),
      '#description' => $this->t('Lazy-load, by default extends existing compatible image formatters. Additionally these dedicated image formatters can be enabled too.'),
      '#options' => [
        'lazy_image' => $this->t('Image (Lazy-load)'),
        'lazy_responsive_image' => $this->t('Responsive image (Lazy-load)'),
      ],
      '#default_value' => $config->get('formatters') ?: [],
    ];

    $form['shared'] = [
      '#type' => 'details',
      '#title' => $this->t('Shared settings'),
      '#group' => 'lazy_tabs',
    ];
    $form['shared']['preferNative'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Prefer native lazy-loading'),
      '#description' => $this->t('If checked and the browser supports, native lazy-loading will be used, otherwise <em>lazysizes</em> library will be used for all browsers.'),
      '#default_value' => $config->get('preferNative'),
      '#required' => FALSE,
    ];
    $form['shared']['skipClass'] = [
      '#type' => 'textfield',
      '#title' => $this->t('skipClass'),
      '#description' => $this->t('Elements having this class name will be ignored.'),
      '#default_value' => $config->get('skipClass'),
      '#size' => 20,
      '#required' => TRUE,
    ];
    $form['shared']['placeholderSrc'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder image URL'),
      '#description' => $this->t('Suggestion: 1x1 pixels transparent GIF: <code>@code</code>', [
        '@code' => 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==',
      ]),
      '#default_value' => $config->get('placeholderSrc'),
      '#size' => 100,
      '#maxlength' => 255,
      '#required' => FALSE,
    ];
    $form['shared']['cssEffect'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable default CSS effect'),
      '#description' => $this->t('If checked the default CSS transition effect is applied with matching class names.'),
      '#default_value' => $config->get('cssEffect'),
      '#required' => FALSE,
    ];

    $form['request_path'] = [
      '#type' => 'details',
      '#title' => $this->t('Visibility'),
      '#description' => $this->t('This configuration applies to both <em>image fields</em> and <em>inline images/iframes</em> on following pages.'),
      '#group' => 'lazy_tabs',
    ];
    $form['request_path']['disabled_paths'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Disabled paths'),
      '#default_value' => $config->get('disabled_paths'),
      '#description' => $this->t("Specify on what pages should lazy-loading disabled. Enter one path per line. The '*' character is a wildcard. An example path is %user-wildcard for every user page. %front is the front page.", [
        '%user-wildcard' => '/user/*',
        '%front' => '<front>',
      ]),
    ];
    $form['request_path']['amp'] = [
      '#markup' => $this->t('Note: Lazy-load is automatically disabled for <a href=":url" title="Accelerated Mobile Pages">AMP</a> (pages with <code>?amp</code> in the query-strings).', [
        ':url' => 'https://www.drupal.org/project/amp',
      ]),
      '#prefix' => '<div class="description">',
      '#suffix' => '</div>',
      '#access' => $this->moduleHandler->moduleExists('amp'),
    ];

    $form['lazysizes'] = [
      '#type' => 'details',
      '#title' => $this->t('Lazysizes configuration'),
      '#description' => $this->t('<div class="messages"><strong>lazysizes</strong> is a fast (jank-free), SEO-friendly and self-initializing lazyloader for images (including responsive images <code>picture</code>/<code>srcset</code>), iframes, scripts/widgets and much more. It also prioritizes resources by differentiating between crucial in view and near view elements to make perceived performance even faster.</div><p>The plugin can be configured with the following options. Check out the <a href=":repo">official repository</a> for <a href=":doc">documentation</a> and <a href=":examples">examples</a>.</p>',
        [
          ':repo' => 'https://github.com/aFarkas/lazysizes',
          ':doc' => 'https://github.com/aFarkas/lazysizes/blob/gh-pages/README.md',
          ':examples' => 'http://afarkas.github.io/lazysizes/#examples',
        ]
      ),
      '#group' => 'lazy_tabs',
    ];
    $form['lazysizes']['lazysizes_lazyClass'] = [
      '#type' => 'textfield',
      '#title' => $this->t('lazyClass'),
      '#description' => $this->t('Marker class for all elements which should be lazy loaded.'),
      '#default_value' => $config->get('lazysizes.lazyClass'),
      '#required' => TRUE,
    ];
    $form['lazysizes']['lazysizes_loadedClass'] = [
      '#type' => 'textfield',
      '#title' => $this->t('loadedClass'),
      '#description' => $this->t('This class will be added to any element as soon as the image is loaded or the image comes into view. Can be used to add unveil effects or to apply styles.'),
      '#default_value' => $config->get('lazysizes.loadedClass'),
      '#required' => TRUE,
    ];
    $form['lazysizes']['lazysizes_loadingClass'] = [
      '#type' => 'textfield',
      '#title' => $this->t('loadingClass'),
      '#description' => $this->t('This class will be added to img element as soon as image loading starts. Can be used to add unveil effects.'),
      '#default_value' => $config->get('lazysizes.loadingClass'),
      '#required' => TRUE,
    ];
    $form['lazysizes']['lazysizes_preloadClass'] = [
      '#type' => 'textfield',
      '#title' => $this->t('preloadClass'),
      '#description' => $this->t('Marker class for elements which should be lazy pre-loaded after onload. Those elements will be even preloaded, if the <code>preloadAfterLoad</code> option is set to <code>false</code>.'),
      '#default_value' => $config->get('lazysizes.preloadClass'),
      '#required' => TRUE,
    ];
    $form['lazysizes']['lazysizes_errorClass'] = [
      '#type' => 'textfield',
      '#title' => $this->t('errorClass'),
      '#description' => $this->t('The error class if image fails to load'),
      '#default_value' => $config->get('lazysizes.errorClass'),
      '#required' => TRUE,
    ];
    $form['lazysizes']['lazysizes_autosizesClass'] = [
      '#type' => 'textfield',
      '#title' => $this->t('autosizesClass'),
      '#description' => '',
      '#default_value' => $config->get('lazysizes.autosizesClass'),
      '#required' => TRUE,
    ];
    $form['lazysizes']['lazysizes_srcAttr'] = [
      '#type' => 'textfield',
      '#title' => $this->t('srcAttr'),
      '#description' => $this->t('The attribute, which should be transformed to <code>src</code>.'),
      '#default_value' => $config->get('lazysizes.srcAttr'),
      '#required' => TRUE,
    ];
    $form['lazysizes']['lazysizes_srcsetAttr'] = [
      '#type' => 'textfield',
      '#title' => $this->t('srcsetAttr'),
      '#description' => $this->t('The attribute, which should be transformed to <code>srcset</code>.'),
      '#default_value' => $config->get('lazysizes.srcsetAttr'),
      '#required' => TRUE,
    ];
    $form['lazysizes']['lazysizes_sizesAttr'] = [
      '#type' => 'textfield',
      '#title' => $this->t('sizesAttr'),
      '#description' => $this->t('The attribute, which should be transformed to <code>sizes</code>. Makes almost only makes sense with the value <code>"auto"</code>. Otherwise, the <code>sizes</code> attribute should be used directly.'),
      '#default_value' => $config->get('lazysizes.sizesAttr'),
      '#required' => TRUE,
    ];
    $form['lazysizes']['lazysizes_minSize'] = [
      '#type' => 'number',
      '#title' => $this->t('minSize'),
      '#description' => $this->t('For <code>data-sizes="auto"</code> feature. The minimum size of an image that is used to calculate the <code>sizes</code> attribute. In case it is under <code>minSize</code> the script traverses up the DOM tree until it finds a parent that is over <code>minSize</code>.'),
      '#default_value' => $config->get('lazysizes.minSize'),
      '#required' => TRUE,
      '#attributes' => [
        'min' => 0,
      ],
    ];
    $form['lazysizes']['lazysizes_customMedia'] = [
      '#type' => 'textarea',
      '#title' => $this->t('customMedia'),
      '#description' => $this->t('The <code>customMedia</code> option object is an alias map for different media queries. It can be used to separate/centralize your multiple specific media queries implementation (layout) from the <code>source[media]</code> attribute (content/structure) by creating labeled media queries.'),
      '#default_value' => json_encode($config->get('lazysizes.customMedia'), JSON_FORCE_OBJECT),
      '#required' => TRUE,
      '#rows' => 1,
    ];
    $form['lazysizes']['lazysizes_init'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('init'),
      '#description' => $this->t('By default lazysizes initializes itself, to load in view assets as soon as possible. In the unlikely case you need to setup/configure something with a later script you can set this option to <code>false</code> and call <code>lazySizes.init();</code> later explicitly.'),
      '#default_value' => $config->get('lazysizes.init'),
      '#required' => FALSE,
    ];
    $form['lazysizes']['lazysizes_expFactor'] = [
      '#type' => 'number',
      '#title' => $this->t('expFactor'),
      '#description' => $this->t('The <code>expFactor</code> is used to calculate the "preload expand", by multiplying the normal <code>expand</code> with the <code>expFactor</code> which is used to preload assets while the browser is idling (no important network traffic and no scrolling). (Reasonable values are between <code>1.5</code> and <code>4</code> depending on the <code>expand</code> option).'),
      '#default_value' => $config->get('lazysizes.expFactor'),
      '#required' => TRUE,
      '#min' => 0,
      '#step' => 0.1,
    ];
    $form['lazysizes']['lazysizes_hFac'] = [
      '#type' => 'number',
      '#title' => $this->t('hFac'),
      '#description' => $this->t('The <code>hFac</code> (horizontal factor) modifies the horizontal expand by multiplying the <code>expand</code> value with the <code>hFac</code> value. Use case: In case of carousels there is often the wish to make the horizontal expand narrower than the normal vertical expand option. Reasonable values are between 0.4 - 1. In the unlikely case of a horizontal scrolling website also 1 - 1.5.'),
      '#default_value' => $config->get('lazysizes.hFac'),
      '#required' => TRUE,
      '#min' => 0,
      '#step' => 0.1,
    ];
    $form['lazysizes']['lazysizes_loadMode'] = [
      '#type' => 'number',
      '#title' => $this->t('loadMode'),
      '#description' => $this->t("The <code>loadMode</code> can be used to constrain the allowed loading mode. Possible values are 0 = don't load anything, 1 = only load visible elements, 2 = load also very near view elements (<code>expand</code> option) and 3 = load also not so near view elements (<code>expand</code> * <code>expFactor</code> option). This value is automatically set to <code>3</code> after onload. Change this value to <code>1</code> if you (also) optimize for the onload event or change it to <code>3</code> if your onload event is already heavily delayed."),
      '#default_value' => $config->get('lazysizes.loadMode'),
      '#required' => TRUE,
      '#min' => 0,
    ];
    $form['lazysizes']['lazysizes_loadHidden'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('loadHidden'),
      '#description' => $this->t('Whether to load <code>visibility: hidden</code> elements. Important: lazySizes will load hidden images always delayed. If you want them to be loaded as fast as possible you can use <code>opacity: 0.001</code> but never <code>visibility: hidden</code> or <code>opacity: 0</code>.'),
      '#default_value' => $config->get('lazysizes.loadHidden'),
      '#required' => FALSE,
    ];
    $form['lazysizes']['lazysizes_ricTimeout'] = [
      '#type' => 'number',
      '#title' => $this->t('ricTimeout'),
      '#description' => $this->t('The timeout option used for the <code>requestIdleCallback</code>. Reasonable values between: 0, 100 - 1000. (Values below 50 disable the <code>requestIdleCallback</code> feature.)'),
      '#default_value' => $config->get('lazysizes.ricTimeout'),
      '#required' => TRUE,
      '#min' => 0,
    ];
    $form['lazysizes']['lazysizes_throttleDelay'] = [
      '#type' => 'number',
      '#title' => $this->t('throttleDelay'),
      '#description' => $this->t('The timeout option used to throttle all listeners. Reasonable values between: 66 - 200.'),
      '#default_value' => $config->get('lazysizes.throttleDelay'),
      '#required' => TRUE,
      '#min' => 0,
    ];

    $plugins = array_keys($this->lazyLoad->getPlugins());
    $form['lazysizes']['lazysizes_plugins'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Plugins'),
      '#description' => $this->t('Review the <a href=":doc">documentation</a> before enabling any of the plugins. Enabled plugins may offer additional configuration which you can override via <code>:code</code>', [
        ':doc' => 'https://github.com/aFarkas/lazysizes#plugins',
        ':code' => 'window.lazySizesConfig',
      ]),
      '#options' => array_combine($plugins, $plugins),
      '#default_value' => $config->get('lazysizes.plugins'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Check if the provided string is a valid JSON object.
   *
   * @param string $string
   *   JSON data as string.
   *
   * @return bool
   *   Returns true if string is a valid JSON, false otherwise.
   */
  private function isJson($string): bool {
    json_decode($string, FALSE);
    return (json_last_error() == JSON_ERROR_NONE);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if (($custom_media = $form_state->getValue('lazysizes_customMedia')) && !$this->isJson($custom_media)) {
      $form_state->setErrorByName('lazysizes_customMedia', $this->t('Not a valid JavaScript object.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $alter_tag = $form_state->getValue('alter_tag');
    foreach ($alter_tag as $key => $value) {
      $alter_tag[$key] = (string) $value;
    }

    $formatters = $form_state->getValue('formatters');
    foreach ($formatters as $key => $value) {
      $formatters[$key] = (string) $value;
    }

    $this->config('lazy.settings')
      ->set('alter_tag', $alter_tag)
      ->set('formatters', $formatters)
      ->set('skipClass', $form_state->getValue('skipClass'))
      ->set('disabled_paths', $form_state->getValue('disabled_paths'))
      ->set('placeholderSrc', $form_state->getValue('placeholderSrc'))
      ->set('preferNative', (bool) $form_state->getValue('preferNative'))
      ->set('cssEffect', (bool) $form_state->getValue('cssEffect'))

      ->set('lazysizes.lazyClass', $form_state->getValue('lazysizes_lazyClass'))
      ->set('lazysizes.loadedClass', $form_state->getValue('lazysizes_loadedClass'))
      ->set('lazysizes.loadingClass', $form_state->getValue('lazysizes_loadingClass'))
      ->set('lazysizes.preloadClass', $form_state->getValue('lazysizes_preloadClass'))
      ->set('lazysizes.errorClass', $form_state->getValue('lazysizes_errorClass'))
      ->set('lazysizes.autosizesClass', $form_state->getValue('lazysizes_autosizesClass'))
      ->set('lazysizes.srcAttr', $form_state->getValue('lazysizes_srcAttr'))
      ->set('lazysizes.srcsetAttr', $form_state->getValue('lazysizes_srcsetAttr'))
      ->set('lazysizes.sizesAttr', $form_state->getValue('lazysizes_sizesAttr'))
      ->set('lazysizes.minSize', (int) $form_state->getValue('lazysizes_minSize'))
      ->set('lazysizes.customMedia', json_decode($form_state->getValue('lazysizes_customMedia'), TRUE))
      ->set('lazysizes.init', (bool) $form_state->getValue('lazysizes_init'))
      ->set('lazysizes.expFactor', (float) $form_state->getValue('lazysizes_expFactor'))
      ->set('lazysizes.hFac', (float) $form_state->getValue('lazysizes_hFac'))
      ->set('lazysizes.loadMode', (int) $form_state->getValue('lazysizes_loadMode'))
      ->set('lazysizes.loadHidden', (bool) $form_state->getValue('lazysizes_loadHidden'))
      ->set('lazysizes.ricTimeout', (int) $form_state->getValue('lazysizes_ricTimeout'))
      ->set('lazysizes.throttleDelay', (int) $form_state->getValue('lazysizes_throttleDelay'))
      ->set('lazysizes.plugins', array_filter($form_state->getValue('lazysizes_plugins')))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
