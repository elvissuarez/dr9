<?php

namespace Drupal\yoast_seo\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\yoast_seo\EntityAnalyser;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for yoast_seo_preview form handlers.
 */
class AnalysisFormHandler implements EntityHandlerInterface {

  use DependencySerializationTrait;

  /**
   * The entity analyser.
   *
   * @var \Drupal\yoast_seo\EntityAnalyser
   */
  protected $entityAnalyser;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * SeoPreviewFormHandler constructor.
   *
   * @param \Drupal\yoast_seo\EntityAnalyser $entity_analyser
   *   The entity analyser.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(EntityAnalyser $entity_analyser, MessengerInterface $messenger) {
    $this->entityAnalyser = $entity_analyser;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('yoast_seo.entity_analyser'),
      $container->get('messenger')
    );
  }

  /**
   * Ajax Callback for returning entity preview to seo library.
   *
   * @param array $form
   *   The complete form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response.
   */
  public function analysisSubmitAjax(array &$form, FormStateInterface $form_state) {
    // Prevent firing accidental submissions from entity builder callbacks.
    $form_state->setTemporaryValue('entity_validated', FALSE);

    $preview_entity = $form_state->getFormObject()->buildEntity($form, $form_state);
    $preview_entity->in_preview = TRUE;

    $entity_data = $this->entityAnalyser->createEntityPreview($preview_entity);

    // The current value of the alias field, if any,
    // takes precedence over the entity url.
    $user_input = $form_state->getUserInput();
    if (!empty($user_input['path'][0]['alias'])) {
      $entity_data['url'] = $user_input['path'][0]['alias'];
    }

    // Any form errors were displayed when our form with the analysis was
    // rendered. Any new messages are from form validation. We don't want to
    // leak those to the user because they'll get them during normal submission
    // so we clear them here.
    $this->messenger->deleteAll();

    $response = new AjaxResponse();
    $response->addCommand(new InvokeCommand(
      'body',
      'trigger',
      ['updateSeoData', $entity_data])
    );
    return $response;
  }

  /**
   * Adds yoast_seo_preview submit.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function addAnalysisSubmit(array &$element, FormStateInterface $form_state) {
    // Tell the form API not to change the build id as this causes issues with
    // other modules like paragraphs due to the form cache. See
    // https://www.drupal.org/project/yoast_seo/issues/2992284#comment-13134728
    // This must be called here because in `analysisSubmitAjax` it would be too
    // late.
    $triggeringElement = $form_state->getTriggeringElement();
    if ($triggeringElement !== NULL && end($triggeringElement['#parents']) === 'yoast_seo_preview_button') {
      $form_state->addRebuildInfo(
        'copy',
        [
          '#build_id' => TRUE,
          '#action' => TRUE,
        ]
      );
    }

    $element['yoast_seo_preview_button'] = [
      '#type' => 'button',
      '#value' => t('Seo preview'),
      '#attributes' => [
        'class' => ['yoast-seo-preview-submit-button'],
        // Inline styles are bad but we can't reliably use class order here.
        'style' => 'display: none',
      ],
      '#ajax' => [
        'callback' => [$this, 'analysisSubmitAjax'],
      ],
    ];
  }

}
