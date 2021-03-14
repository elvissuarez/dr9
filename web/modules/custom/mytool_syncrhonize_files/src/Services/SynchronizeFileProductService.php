<?php

namespace Drupal\synchronize_file_product\Services;

use Drupal\Core\File\Exception\FileException;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\file\Entity\File;

/**
 * Class SynchronizeFileProductService.
 */
class SynchronizeFileProductService {

  /**
   * Variable para llenar CSV.
   *
   * @var string
   * @access protected
   */
  protected $csvReport;

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'synchronize_file_product.settings';

  /**
   * Name Field Product Path.
   *
   * @var string
   */
  const FIELD_PATH = 'product_path';

  /**
   * Constructor.
   */
  public function __construct() {
    $csvHeader = ['MARCA', 'REFERENCIA', 'ID UNICO', 'ENCONTRADO', 'IMAGEN', 'URL', 'RESULTADO'];
    $this->csvReport = implode(',', $csvHeader) . "\n";
  }

  /**
   * Get files under directory.
   */
  public function getFiles() {
    $options['key'] = "filename";
    $files = [];
    try {
      $directory = $this->productsDirectory();
      if (is_dir($directory)) {
        // In future use
        // $files = \Drupal::service('file_system')->scanDirectory($directory);
        $files = file_scan_directory($directory, '/.*\.jpg$/', $options);
        ksort($files);
      }
      else {
        \Drupal::logger('FilesProduct')->error('Directorio no existe');
      }
    }
    catch (FileException $e) {
      \Drupal::logger('FilesProduct')->error($e->getMessage());
    }
    return $files;
  }

  /**
   * Process Files Array.
   */
  public function processEntireDir() {
    $files = $this->getFiles();
    foreach ($files as $imageFileInfo) {
      // get_object_vars($file) = uri, filename, name.
      $result = $this->processSingleFile($imageFileInfo);
      $this->csvReport .= $imageFileInfo->filename . ',';
      $this->csvReport .= $imageFileInfo->uri . ',';
      $this->csvReport .= $result . "\n";
    }
  }

  /**
   * Process Single File.
   */
  public function processSingleFile($imageFileInfo = NULL) {
    $processedFile = 'ERROR';
    $brand = $this->getFileBrand($imageFileInfo->uri, $imageFileInfo->filename);
    $reference = $this->getFileReference($imageFileInfo->uri, $imageFileInfo->filename);
    $serviceProcessProduct = \Drupal::service('upload_mass_product.upload_mass_product_services');
    $product_sku = $serviceProcessProduct->getProductSku($brand, $reference);
    $searchProduct = $serviceProcessProduct->searchByProductSku($product_sku);
    $this->csvReport .= $brand . ',';
    $this->csvReport .= $reference . ',';
    $this->csvReport .= $product_sku . ',';
    if (count($searchProduct) > 0) {
      $values = array_values($searchProduct);
      $productId = array_shift($values);
      $processedFile = $this->updateImagesProduct($productId, $imageFileInfo);
      $this->csvReport .= 'SI,';
    }
    else {
      $this->csvReport .= 'NO,';
    }
    return $processedFile;
  }

  /**
   * Upload Images for Product Node.
   */
  public function updateImagesProduct($productId, $imageFileInfo) {
    $response = '';
    try {
      $product = Product::load($productId);
      $variations = $product->getVariationIds();
      $values = array_values($variations);
      $variation = array_shift($values);
      $product_variation = ProductVariation::load($variation);
      $productVariationImages = $product_variation->get('field_imagen_producto')->getValue();
      // update all images ever
      $imageExists = FALSE;
      foreach ($productVariationImages as $image) {
        if (strcasecmp($image['title'], $imageFileInfo->name) == 0) {
          $imageExists = TRUE;
          break 1;
        }
      }
      // Check first if the file exists for the uri.
      $files = \Drupal::entityTypeManager()
        ->getStorage('file')
        ->loadByProperties(['uri' => $imageFileInfo->uri]);
      $imageFile = reset($files);
      if (!$imageExists) {
        // If not create a file.
        if (!$imageFile) {
          $imageFile = File::create([
            'uri' => $imageFileInfo->uri,
          ]);
          $imageFile->save();
        }
        // Attach Image file.
        $product_variation->field_imagen_producto[] = [
          'target_id' => $imageFile->id(),
          'alt' => $imageFileInfo->name,
          'title' => $imageFileInfo->name,
        ];
        $product_variation->save();
        $response = 'Nueva Imagen Asignada';
      }
      else {
        $response = 'Imagen Sin Cambio';
      }
      /** sort images based on name **/
      $productVariationImages = $product_variation->get('field_imagen_producto')->getValue();
      if(isset($productVariationImages) && count($productVariationImages) > 0){
        foreach ($productVariationImages as $key => $value) {
          $order[$key] = $value['title'];
        }
        array_multisort($order, SORT_ASC, $productVariationImages);
        $product_variation->set('field_imagen_producto', $productVariationImages);
        $product_variation->save();
      }
    }
    catch (FileException $e) {
      $response = 'ERROR';
      // Log the exception to watchdog.
      \Drupal::logger('type')->error($e->getMessage());
    }
    return $response;
  }

  /**
   * Image brand referenced.
   */
  public function getFileBrand($uri = '', $filename = '') {
    if (isset($uri) && !empty($uri)) {
      $remove = $this->productsDirectory() . '/';
      $step1 = str_replace($remove, '', $uri);
      $step2 = str_replace($filename, '', $step1);
      return mb_strtoupper(preg_replace('/\/.*/', '', trim($step2)));
    }
    return '';
  }

  /**
   * Image Reference name.
   */
  public function getFileReference($uri = '', $filename = '') {
    if (isset($uri) && !empty($uri)) {
      $remove = $this->productsDirectory() . '/';
      $step1 = str_replace($remove, '', $uri);
      $step2 = str_replace('/' . $filename, '', $step1);
      return mb_strtoupper(preg_replace('/.*\//', '', trim($step2)));
    }
    return '';
  }

  /**
   * Get Configuration settings.
   */
  public function confSettings() {
    return \Drupal::config(static::SETTINGS);
  }

  /**
   * Get Directory settings.
   */
  public function productsDirectory() {
    return $this->confSettings()->get(static::FIELD_PATH);
  }

  /**
   * Send Notification Mail.
   *
   * @param string $to
   *   Email address to.
   */
  public function sendNotificationMailSfp() {
    $to = $this->confSettings()->get('notifications');
    $module = 'upload_mass_product';
    $key = 'upload_mass_product_mail';
    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $params['subject'] = 'Imagenes Productos My Tool';
    $params['message'] = 'Reporte de Actualización de Imagenes de Productos';
    $attachments = [
      'filecontent' => $this->csvReport,
      'filename' => date('YmdHis') . '.csv',
      'filemime' => 'text/csv',
      'encoding' => 'utf8',
    ];
    $params['attachments'][] = $attachments;
    $send = TRUE;

    $result = \Drupal::service('plugin.manager.mail')->mail($module, $key, $to, $langcode, $params, NULL, $send);
    if ($result['result'] !== TRUE) {
      \Drupal::logger('mail')->error(print_r($result));
    }
    else {
      \Drupal::logger('mail')->notice('Notificación Entregada');
    }
  }

}
