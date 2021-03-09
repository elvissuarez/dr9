<?php

namespace Drupal\vbo_export\Plugin\Action;

use Drupal\Core\File\FileSystemInterface;
use Drupal\views\ViewExecutable;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Session\AccountInterface;

/**
 * Base class for export actions.
 */
abstract class VboExportBase extends ViewsBulkOperationsActionBase implements ContainerFactoryPluginInterface {

  const THEME = '';

  const EXTENSION = '';

  /**
   * The tempstore object.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $tempStore;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RendererInterface $renderer, StreamWrapperManagerInterface $streamWrapperManager, PrivateTempStoreFactory $temp_store_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->renderer = $renderer;
    $this->streamWrapperManager = $streamWrapperManager;
    $this->tempStore = $temp_store_factory->get('vbo_export_multiple');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('renderer'),
      $container->get('stream_wrapper_manager'),
      $container->get('tempstore.private')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildPreConfigurationForm(array $form, array $values, FormStateInterface $form_state) {
    $form['strip_tags'] = [
      '#title' => $this->t('Strip HTML tags'),
      '#type' => 'checkbox',
      '#default_value' => isset($values['strip_tags']) ? $values['strip_tags'] : FALSE,
    ];

    $form['field_override'] = [
      '#title' => $this->t('Override the fields configuration'),
      '#type' => 'checkbox',
      '#default_value' => isset($values['field_override']) ? $values['field_override'] : FALSE,
    ];

    if ($this->view instanceof ViewExecutable && !empty($this->view->field)) {
      $form['field_config'] = [
        '#type' => 'table',
        '#caption' => $this->t('Select the fields you want to include in the exportable. <strong>The following options only applies if the "Override the fields configuration" option is checked.</strong>'),
        '#header' => [
          $this->t('Field name'),
          $this->t('Active'),
          $this->t('Label'),
        ],
      ];

      $functional_fields = [
        'views_bulk_operations_bulk_form',
        'entity_operations',
      ];
      foreach ($this->view->field as $field_id => $field) {
        if (in_array($field_id, $functional_fields)) {
          continue;
        }
        $form['field_config'][$field_id] = [
          'name' => [
            '#markup' => $field_id,
          ],
          'active' => [
            '#type' => 'checkbox',
            '#title' => $this->t('Active'),
            '#title_display' => 'invisible',
            '#default_value' => isset($values['field_config'][$field_id]['active']) ? $values['field_config'][$field_id]['active'] : FALSE,
          ],
          'label' => [
            '#type' => 'textfield',
            '#title' => $this->t('Label'),
            '#title_display' => 'invisible',
            '#default_value' => isset($values['field_config'][$field_id]['label']) ? $values['field_config'][$field_id]['label'] : '',
          ],
        ];
      }
    }

    return $form;
  }

  /**
   * Generate output string.
   */
  protected function generateOutput() {
    $rows = [];
    for ($i = 1; $i <= $this->context['sandbox']['current_batch']; $i++) {
      $chunk = $this->tempStore->get($this->context['sandbox']['cid_prefix'] . $i);
      if ($chunk) {
        $rows = array_merge($rows, $chunk);
        $this->tempStore->delete($this->context['sandbox']['cid_prefix'] . $i);
      }
    }
    $renderable = [
      '#theme' => static::THEME,
      '#header' => $this->context['sandbox']['header'],
      '#rows' => $rows,
      '#configuration' => $this->configuration,
    ];

    return $this->renderer->render($renderable);
  }

  /**
   * Output generated string to file. Message user.
   *
   * @param string $output
   *   The string that will be saved to a file.
   */
  protected function sendToFile($output) {
    if (!empty($output)) {
      $rand = substr(hash('ripemd160', uniqid()), 0, 8);
      $filename = $this->context['view_id'] . '_' . date('Y_m_d_H_i', \Drupal::time()->getRequestTime()) . '-' . $rand . '.' . static::EXTENSION;

      $wrappers = $this->streamWrapperManager->getWrappers();
      if (isset($wrappers['private'])) {
        $wrapper = 'private';
      }
      else {
        $wrapper = 'public';
      }

      $destination = $wrapper . '://' . $filename;
      $file = file_save_data($output, $destination, FileSystemInterface::EXISTS_REPLACE);
      $file->setTemporary();
      $file->save();
      $file_url = Url::fromUri(file_create_url($file->getFileUri()));
      $link = Link::fromTextAndUrl($this->t('Click here'), $file_url);
      $this->messenger()->addStatus($this->t('Export file created, @link to download.', ['@link' => $link->toString()]));
    }
  }

  /**
   * Execute multiple handler.
   *
   * Execute action on multiple entities to generate csv output
   * and display a download link.
   */
  public function executeMultiple(array $entities) {
    // Free up some memory.
    unset($entities);

    if (empty($this->getHeader()) || empty($this->view->result)) {
      return;
    }

    $rows = $this->getRows();
    $processed = $this->context['sandbox']['processed'] + count($rows);
    $this->saveRows($rows);

    // Generate the output file if the last row has been processed.
    if (!isset($this->context['sandbox']['total']) || $processed >= $this->context['sandbox']['total']) {
      $output = $this->generateOutput();
      $this->sendToFile($output);
    }
  }

  /**
   * Get rows from views results.
   *
   * @return array
   *   An array of rows in a single batch prepared for theming.
   */
  protected function getRows() {
    // Render rows.
    $this->view->render($this->view->current_display);
    $index = $this->context['sandbox']['processed'];
    $rows = [];
    foreach (array_keys($this->view->result) as $num) {
      foreach (array_keys($this->getHeader()) as $field_id) {
        $rows[$index][$field_id] = (string) $this->view->style_plugin->getField($num, $field_id);
      }
      $index++;
    }
    return $rows;
  }

  /**
   * Prepares sandbox data (header and cache ID).
   *
   * @return array
   *   Table header.
   */
  protected function getHeader() {
    // Build output header array.
    $header = &$this->context['sandbox']['header'];
    if (!empty($header)) {
      return $header;
    }

    return $this->setHeader();
  }

  /**
   * Sets table header from view header.
   *
   * @return array
   *   Table header.
   */
  protected function setHeader() {
    $this->context['sandbox']['header'] = [];
    $header = &$this->context['sandbox']['header'];
    $functional_fields = [
      'views_bulk_operations_bulk_form',
      'entity_operations',
    ];

    foreach ($this->view->field as $id => $field) {
      if ($this->configuration['field_override']) {
        foreach ($this->configuration['field_config'] as $id => $field_settings) {
          if ($field_settings['active']) {
            if (!empty($field_settings['label'])) {
              $header[$id] = $field_settings['label'];
            }
            elseif (isset($this->view->field[$id])) {
              $header[$id] = $this->view->field[$id]->options['label'];
            }
          }
        }
      }
      else {
        $is_excluded = in_array($field->options['plugin_id'], $functional_fields) || $field->options['exclude'];
        // Skip Views Bulk Operations field and excluded fields.
        if ($is_excluded) {
          continue;
        }
        $header[$id] = $field->options['label'];
      }
    }
    return $header;
  }

  /**
   * Gets Cache ID for current batch.
   *
   * @return string
   *   Cache unique ID for Temporary storage.
   */
  protected function getCid() {
    if (!isset($this->context['sandbox']['cid_prefix'])) {
      $this->context['sandbox']['cid_prefix'] = $this->context['view_id'] . ':'
        . $this->context['display_id'] . ':' . $this->context['action_id'] . ':'
        . md5(serialize(array_keys($this->context['list']))) . ':';
    }

    return $this->context['sandbox']['cid_prefix'] . $this->context['sandbox']['current_batch'];
  }

  /**
   * Saves batch data into Private storage.
   *
   * @param array $rows
   *   Rows from batch.
   */
  protected function saveRows(array &$rows) {
    $this->tempStore->set($this->getCid(), $rows);
    unset($rows);
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $this->executeMultiple([$entity]);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $access = $object->access('view', $account, TRUE);
    return $access->isAllowed();
  }

}
