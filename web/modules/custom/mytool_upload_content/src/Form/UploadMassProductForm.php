<?php

namespace Drupal\upload_mass_product\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\Environment;

/**
 * Implements an example form.
 */
class UploadMassProductForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'upload_mass_product_form';

  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [
      '#attributes' => ['enctype' => 'multipart/form-data'],
    ];

    $validators = [
      'file_validate_extensions' => ['csv xls xlsx'],
      'file_validate_size' => [Environment::getUploadMaxSize()],
    ];

    $form['link'] = [
      '#type' => 'link',
      '#title' => "Descargar Plantilla Excel",
      '#url' => Url::fromUri(file_create_url('public://plantillas/plantillaproductos-gs.xlsx')),
      '#attributes' => [
        ' class' => ['btn', 'btn-primary', 'active', 'pull-right', 'col-md-4'],
        ' role' => "button",
        ' style' => ['margin: 5px'],
      ],
    ];

    $form['fieldset'] = [
      '#type' => 'fieldset',
      '#title' => 'Creación/Actualización Productos My Tool',
    ];

    $form['fieldset']['email'] = [
      '#type' => 'email',
      '#description' => 'Predeterminado: info@mytool.com.co',
      '#title' => $this->t('Correo Confirmación'),
      '#placeholder' => $this->t('Predeterminado: info@mytool.com.co'),
      // '#pattern' => '*@mytool.com',
    ];

    $form['fieldset']['excel_file'] = [
      '#type' => 'managed_file',
      '#name' => 'excel_file',
      '#title' => t('Seleccionar Archivo *'),
      '#size' => 20,
      '#description' => t('Formato Excel unicamente soportado'),
      '#upload_validators' => $validators,
      '#upload_location' => 'public://content/excel_files/',
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Ejecutar Actualización'),
      '#button_type' => 'success',
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('excel_file') == NULL) {
      $form_state->setErrorByName('excel_file', $this->t('Debe Seleccionar un Archivo.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $process = \Drupal::service('upload_mass_product.upload_mass_product_services')->processFile($form_state->getValue('excel_file')[0]);
    $messenger = \Drupal::messenger();
    if ($process) {
      $messenger->addMessage($process, $messenger::TYPE_STATUS);
    }
    else {
      $messenger->addMessage('Error Procesando el Archivo', $messenger::TYPE_ERROR);
    }
    $to = $form_state->getValue('email');
    if (!isset($to)||empty($to)) {
      $to = \Drupal::config('system.site')->get('mail');
    }
    \Drupal::service('upload_mass_product.upload_mass_product_services')->sendNotificationMailUmp($to);
  }

}
