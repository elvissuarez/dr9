<?php

namespace Drupal\vbo_export\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;

/**
 * Generates csv.
 *
 * @Action(
 *   id = "vbo_export_generate_csv_action",
 *   label = @Translation("Generate csv from selected view results"),
 *   type = "",
 *   pass_context = TRUE,
 *   pass_view = TRUE
 * )
 */
class VboExportCsv extends VboExportBase {
  const THEME = 'vbo_export_content_csv';
  const EXTENSION = 'csv';

  /**
   * {@inheritdoc}
   *
   * Add csv separator setting to preliminary config.
   */
  public function buildPreConfigurationForm(array $form, array $values, FormStateInterface $form_state) {
    $form = parent::buildPreConfigurationForm($form, $values, $form_state);
    $form['separator'] = [
      '#title' => $this->t('CSV separator'),
      '#type' => 'radios',
      '#options' => [
        ';' => $this->t('semicolon ";"'),
        ',' => $this->t('comma ","'),
        '|' => $this->t('pipe "|"'),
      ],
      '#default_value' => isset($values['separator']) ? $values['separator'] : ';',
    ];
    return $form;
  }

  /**
   * Override the generateOutput method.
   */
  protected function generateOutput() {
    $output = parent::generateOutput();

    // BOM needs to be added to UTF-8 encoded csv file
    // to make it easier to read by Excel.
    $output = chr(0xEF) . chr(0xBB) . chr(0xBF) . (string) $output;
    return $output;
  }

}
