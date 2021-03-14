<?php

namespace Drupal\synchronize_file_product\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for this site.
 */
class SynchronizeFileProductForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'synchronize_file_product.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'synchronize_file_product_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);
    $form['description'] = [
      '#markup' => "<h3>La configuración creada en este formulario se usará para la ejecucion de la tarea recurrente.</h3><p>Por favor confirme los datos antes de realizar cualquier modificación</p>",
    ];
    $form['product_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Ubicacion del Directorio'),
      '#default_value' => $config->get('product_path'),
    ];
    $form['notifications'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email Envío Reporte'),
      '#default_value' => $config->get('notifications'),
    ];
    $form['executeUpdate'] = [
      '#type' => 'submit',
      '#value' => t('Ejecutar Actualizacion de Imagenes'),
      '#submit' => ['::newSubmissionHandlerUpdate'],
      '#attributes' => [
        ' class' => ['btn', 'btn-success', 'pull-right'],
      ],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $this->configFactory->getEditable(static::SETTINGS)
      // Set the submitted configuration setting.
      ->set('product_path', $form_state->getValue('product_path'))
      ->set('notifications', $form_state->getValue('notifications'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Custom submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function newSubmissionHandlerUpdate(array &$form, FormStateInterface $form_state) {
    $service = \Drupal::service('synchronize_file_product.synchronize_file_product_services');
    $service->processEntireDir();
    $service->sendNotificationMailSfp();
    $this->messenger()->addWarning('Sin cambios en los campos configurados.');
    $this->messenger()->addStatus('Actualizacion de Imagenes Ejecutada. Revise su Email');
  }

}
