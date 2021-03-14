<?php

namespace Drupal\upload_mass_product\Services;

use Drupal\commerce_store\Entity\Store;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_price\Price;
use Drupal\file\Entity\File;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Drupal\taxonomy\Entity\Term;

/**
 * Class UploadMassProductService.
 */
class UploadMassProductService {

  /**
   * Variable para llenar CSV.
   *
   * @var string
   * @access protected
   */
  protected $csvReport;

  /**
   * Constructor.
   */
  public function __construct() {
    $this->csvReport = '';
  }

  /**
   * Excecute Functions.
   */
  public function processFile($fid = 0) {
    if (isset($fid) && !empty($fid)) {
      $fileData = $this->getFileData($fid);

      $dataInformation = $this->getDataInformation($fileData);
      $processProducts = $this->processProducts($dataInformation);
      if ($processProducts) {
        return 'Archivo Procesado Exitosamente';
      }
      return 'Archivo Procesado con Errores';
    }
    return NULL;
  }

  /**
   * Get File Data.
   *
   * @param int $fid
   *
   * @return array $fileData
   */
  public function getFileData($fid = 0) {
    $fileData = [];
    if (!empty($fid)) {
      $file = File::load($fid);
      $full_path = $file->getFileUri();
      $file_name = basename($full_path);
      try {
        $inputFileName = \Drupal::service('file_system')->realpath('public://content/excel_files/' . $file_name);
        $spreadsheet = IOFactory::load($inputFileName);
        $sheetData = $spreadsheet->getActiveSheet();
        foreach ($sheetData->getRowIterator() as $row) {
          $cellIterator = $row->getCellIterator();
          $cellIterator->setIterateOnlyExistingCells(FALSE);
          $cells = [];
          foreach ($cellIterator as $cell) {
            $tmp_cell_val = $cell->getFormattedValue();
            if (is_object($tmp_cell_val)) {
              $tmp_cell_val = $cell->getCalculatedValue();
              if (is_object($tmp_cell_val)) {
                $tmp_cell_val = $cell->getValue();
              }
            }
            $cells[] = $tmp_cell_val;
          }
          $fileData[] = $cells;
        }
      }
      catch (Exception $e) {
        // Log the exception to watchdog.
        \Drupal::logger('type')->error($e->getMessage());
      }
    }
    return $fileData;
  }

  /**
   * Create object product with Array Information.
   *
   * @param array $fileData
   *   Array with rows from excel file.
   */
  public function getDataInformation(array $fileData) {
    $dataInformation = [];
    if (count($fileData) >= 0) {
      $header = array_shift($fileData);
      // Add header to report file.
      $this->csvReport .= implode(',', $header) . ",,RTA";
      foreach ($fileData as $row) {
        $newRow = [];
        foreach ($row as $key => $value) {
          $newRow[$this->normalizeHeader($header[$key])] = '';
          if (is_string($value) || is_numeric($value) || is_bool($value) || empty($value)) {
            $newRow[$this->normalizeHeader($header[$key])] = $value;
          }
        }
        $dataInformation[] = $newRow;
      }
    }
    return $dataInformation;
  }

  /**
   * Normalize some String.
   *
   * @param string $text
   *   Array with rows from excel file.
   */
  public function normalizeHeader(string $text) {
    $unwanted_array = [
      'Š' => 'S',
      'š' => 's',
      'Ž' => 'Z',
      'ž' => 'z',
      'À' => 'A',
      'Á' => 'A',
      'Â' => 'A',
      'Ã' => 'A',
      'Ä' => 'A',
      'Å' => 'A',
      'Æ' => 'A',
      'Ç' => 'C',
      'È' => 'E',
      'É' => 'E',
      'Ê' => 'E',
      'Ë' => 'E',
      'Ì' => 'I',
      'Í' => 'I',
      'Î' => 'I',
      'Ï' => 'I',
      'Ñ' => 'N',
      'Ò' => 'O',
      'Ó' => 'O',
      'Ô' => 'O',
      'Õ' => 'O',
      'Ö' => 'O',
      'Ø' => 'O',
      'Ù' => 'U',
      'Ú' => 'U',
      'Û' => 'U',
      'Ü' => 'U',
      'Ý' => 'Y',
      'Þ' => 'B',
      'ß' => 'Ss',
      'à' => 'a',
      'á' => 'a',
      'â' => 'a',
      'ã' => 'a',
      'ä' => 'a',
      'å' => 'a',
      'æ' => 'a',
      'ç' => 'c',
      'è' => 'e',
      'é' => 'e',
      'ê' => 'e',
      'ë' => 'e',
      'ì' => 'i',
      'í' => 'i',
      'î' => 'i',
      'ï' => 'i',
      'ð' => 'o',
      'ñ' => 'n',
      'ò' => 'o',
      'ó' => 'o',
      'ô' => 'o',
      'õ' => 'o',
      'ö' => 'o',
      'ø' => 'o',
      'ù' => 'u',
      'ú' => 'u',
      'û' => 'u',
      'ý' => 'y',
      'þ' => 'b',
      'ÿ' => 'y',
    ];
    $text = strtr($text, $unwanted_array);
    $text = str_replace(' ', '_', $text);
    return preg_replace('/[^A-Za-z0-9\_]/', '', mb_strtolower($text));;
  }

  /**
   * Process Array Excel Data to Update/Create Products.
   *
   * @param array $dataInformation
   *   Contains cells data.
   */
  public function processProducts(array $dataInformation) {
    $response = TRUE;
    foreach ($dataInformation as $i => $arr_product) {
      $product = '';
      $product_response = FALSE;
      $exists = $this->existsProduct($arr_product);
      if (empty($exists)) {
        $product = $this->makeProduct($arr_product);
      }
      else {
        $product = $this->updateProduct($arr_product, $exists);
      }
      $this->csvReport .= "\n";
      $this->csvReport .= $this->cleanFieldsCsv($arr_product);
      $this->csvReport .= "," . $product[0];
      \Drupal::logger('UpdateMassProduct')->notice(implode(',', $product));
      if (count($product) > 0) {
        $product_response = TRUE;
      }
      $response = $response && $product_response;
    }
    return $response;
  }

  /**
   * Check if a Product Exists yet.
   *
   * @param array $product
   *   Product Info array from excel.
   */
  public function existsProduct(array $product) {
    $product_id = 0;
    $product_sku = $this->getProductSku($product['marca'], $product['referencia_fabrica']);
    $result = $this->searchByProductSku($product_sku);
    foreach ($result as $record) {
      $product_id = $record;
    }
    return $product_id;
  }

  /**
   * Check if a Product Exists yet.
   *
   * @param array $product
   *   Product Info array from excel.
   */
  public function searchByProductSku($product_sku = '') {
    if (isset($product_sku) && !empty($product_sku)) {
      $query = \Drupal::entityQuery('commerce_product');
      $query->condition('field_product_sku', $product_sku, '=');
      $query->range(0, 1);
      $query->sort('product_id', 'asc');
      $result = $query->execute();
      return $result;
    }
    return [];
  }

  /**
   * Create Product based on excel file.
   *
   * @param array $product_arr
   *   Product Info array from excel.
   */
  public function makeProduct(array $product_arr) {
    $response = [];
    try {
      $status = TRUE;
      // Load Default store.
      $store = Store::load(1);
      // Create variations.
      $price = $this->getPrice(trim($product_arr['precio_venta']));
      if (empty($price)) {
        $response[] = 'PRECIO CERO';
        $status = FALSE;
      }
      $variation1 = ProductVariation::create([
        'type' => 'default',
        'sku' => $product_arr['sku'],
        'price' => new Price($price['price'], $price['currency']),
        'status' => 1,
      ]);
      $variation1->save();
      // Create product using variations previously saved.
      $product = Product::create([
        'uid' => 1,
        'type' => 'default',
        'title' => $product_arr['titulo_web'],
        'body' => $product_arr['descripcion'],
        'field_especificaciones' => $product_arr['caracteristicas'],
        'field_product_sku' => $this->getProductSku($product_arr['marca'], $product_arr['referencia_fabrica']),
        'field_tiempo_de_entrega' => $product_arr['entrega_aprox'],
        'variations' => [$variation1],
        'stores' => [$store],
      ]);
      // Set value marca @taxonomy term - reference field entity.
      $marca = $this->getBrandProduct(trim(mb_strtolower($product_arr['marca'])));
      if (isset($marca)) {
        $product->set('field_marca', $marca);
      }
      else {
        $status = FALSE;
        $response[] = 'SIN MARCA';
      }
      // Set value Categoria @taxonomy term - reference field entity.
      $r_cat = trim(mb_strtolower($product_arr['categoria']));
      $r_subcat = trim(mb_strtolower($product_arr['subcategoria']));
      $categoria = $this->getCategoryProduct($r_cat, $r_subcat);
      if (isset($categoria)) {
        $product->set('field_categoria', $categoria);
      }
      else {
        $status = FALSE;
        $response[] = 'SIN CATEGORÍA';
      }
      $product->setPublished($status);
      $product->save();
      $alias = \Drupal::service('path.alias_manager')->getAliasByPath('/product/' . $product->id());
      $response[] = "Creado";
      $response[] = "<a href='/site/es{$alias}' target='blank_'>{$product_arr['titulo_web']}</a>";
    }
    catch (Exception $e) {
      $response[] = "ERROR";
      // Log the exception to watchdog.
      \Drupal::logger('type')->error($e->getMessage());
    }
    return $response;
  }

  /**
   * Update Product based on excel file.
   *
   * @param array $product_arr
   *   Product Info array from excel.
   */
  public function updateProduct(array $product_arr, int $product_id) {
    $response = [];
    $product = Product::load($product_id);
    try {
      $status = TRUE;
      // Update Variation.
      $variations = $product->getVariationIds();
      /*Load Product Variations*/
      $price = $this->getPrice(trim($product_arr['precio_venta']));
      if (empty($price)) {
        $response[] = 'PRECIO CERO';
        $status = FALSE;
      }
      foreach ($variations as $variation) {
        $product_variation = ProductVariation::load($variation);
        $product_variation->set('sku', $product_arr['sku']);
        $product_variation->set('price', new Price($price['price'], $price['currency']));
        $product_variation->setPublished($status);
        $product_variation->save();
      }
      // Set value Title @string
      $product->setTitle($product_arr['titulo_web']);
      // Set value Descripción @string
      $product->set('body', $product_arr['descripcion']);
      // Set value Especificaciónes @string
      $product->set('field_especificaciones', $product_arr['caracteristicas']);
      // Set value Tiempo de entrega     @string
      $product->set('field_tiempo_de_entrega', $product_arr['entrega_aprox']);
      // Set value Marca @taxonomy term - reference field entity.
      $marca = $this->getBrandProduct(trim(mb_strtolower($product_arr['marca'])));
      if (isset($marca)) {
        $product->set('field_marca', $marca);
      }
      else {
        $status = FALSE;
        $response[] = 'SIN MARCA';
      }
      // Set value Categoria @taxonomy term - reference field entity.
      $r_cat = trim(mb_strtolower($product_arr['categoria']));
      $r_subcat = trim(mb_strtolower($product_arr['subcategoria']));
      $categoria = $this->getCategoryProduct($r_cat, $r_subcat);
      if (isset($categoria)) {
        $product->set('field_categoria', $categoria);
      }
      else {
        $status = FALSE;
        $response[] = 'SIN CATEGORÍA';
      }
      // Update Product.
      $product->setPublished($status);
      // Save to update Product.
      $product->save();
      $response[] = "Actualizado";
      $alias = \Drupal::service('path.alias_manager')->getAliasByPath('/product/' . $product_id);
      $response[] = "<a href='/site/es{$alias}' target='blank_'>{$product_arr['titulo_web']}</a>";
    }
    catch (Exception $e) {
      $response[] = "ERROR";
      // Log the exception to watchdog.
      \Drupal::logger('type')->error($e->getMessage());
    }
    return $response;
  }

  /**
   * Get All bundle fields from default product.
   */
  public function bundleFieldsProduct() {
    $bundle_fields = \Drupal::entityManager()->getFieldDefinitions('commerce_product', 'default');
    $fields = [];
    foreach ($bundle_fields as $field) {
      $fields[$field->getName()] = $field->getType();
    }
    return $fields;
  }

  /**
   * Get Arr Price from string cell.
   */
  public function getPrice($price_str) {
    $currency = [
      "$" => 'COP',
      "US$" => 'USD',
    ];
    $price = [
      "price" => 0,
      "currency" => 'COP',
    ];
    if (!empty($price_str)) {
      try {
        list($c, $blank, $p) = explode(" ", $price_str);
        $p = (int) str_replace(',', '', $p);
        $price = [
          "price" => $p,
          "currency" => $currency[$c],
        ];
      }
      catch (Exception $e) {
        // Log the exception to watchdog.
        \Drupal::logger('type')->error($e->getMessage());
      }
    }
    return $price;
  }

  /**
   * Generate SKU based on excel info.
   */
  public function getProductSku($brand, $factory_reference) {
    if (!empty($brand)&&!empty($factory_reference)) {
      $b = substr(mb_strtoupper($brand), 0, 3);
      return $b . '-' . $factory_reference;
    }
    return '000-000-000';
  }

  /**
   * Get Taxonomy Term.
   *
   * @param string $brand
   *   String with brand name   *.
   *
   * @return Entity
   *   Taxonomy Term Entity for brand or NULL.
   */
  public function getBrandProduct($brand) {
    $properties = ['vid' => 'marcas', 'langcode' => 'es'];
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties($properties);
    $response = NULL;
    foreach ($terms as $term) {
      if (strcasecmp($term->name->value, $brand) == 0) {
        $response = $term;
        break;
      }
    }
    return $response;
  }

  /**
   * Get Taxonomy Term.
   *
   * @param string $cat
   *   String with category name.
   * @param string $subcat
   *   String with subcategory name.
   *
   * @return Entity
   *   Taxonomy Term Entity for brand or NULL.
   */
  public function getCategoryProduct($cat, $subcat) {
    $properties = ['vid' => 'categorias', 'langcode' => 'es', 'name' => $subcat];
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties($properties);
    $response = NULL;
    foreach ($terms as $term) {
      if (strcasecmp(mb_strtolower($term->name->value), $subcat) == 0) {
        $pid = $term->parent->target_id;
        $parent = Term::load($pid);
        if (strcasecmp(mb_strtolower($parent->getName()), $cat) == 0) {
          $response = $term;
          break;
        }
      }
    }
    return $response;
  }

  /**
   * Send Notification Mail.
   *
   * @param string $to
   *   Email address to.
   */
  public function sendNotificationMailUmp($to) {
    $module = 'upload_mass_product';
    $key = 'upload_mass_product_mail';
    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $params['subject'] = 'Actualización Productos My Tool';
    $params['message'] = 'Reporte de Actualización';
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

  /**
   * Clear line Breaks over CSV rows.
   */
  public function cleanFieldsCsv($product_arr) {
    $response = '';
    foreach ($product_arr as $row) {
      $tmp = preg_replace('/,/', '.', $row);
      $response .= trim(preg_replace('/\s+/', ' ', $tmp));
      $response .= ',';
    }
    return $response;
  }

}
