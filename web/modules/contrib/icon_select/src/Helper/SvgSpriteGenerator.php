<?php

namespace Drupal\icon_select\Helper;

use Drupal\Core\File\FileSystemInterface;
use enshrined\svgSanitize\Sanitizer;

/**
 * Helper class to generate SVG image maps.
 */
class SvgSpriteGenerator {

  /**
   * Generate an svg sprite-sheet from a vocabulary.
   *
   * @param string $vocabulary_id
   *   Vocabulary ID.
   *
   * @return string
   *   URI to the sprite-sheet.
   */
  public static function generateSprites($vocabulary_id) {

    /** @var \Drupal\taxonomy\TermStorageInterface $term_storage */
    $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $terms = $term_storage->loadTree($vocabulary_id, 0, NULL, TRUE);

    $sprite = '<svg xmlns="http://www.w3.org/2000/svg" '
      . 'xmlns:xlink="http://www.w3.org/1999/xlink" '
      . 'style="width:0; height:0; visibility:hidden; position:absolute;">';

    // Check for unique class name.
    foreach ($terms as $term) {
      if ($term->field_symbol_id->value) {

        $symbol_content = '';
        $symbol_viewbox = '0 0 88 88';

        $symbol_id = $term->field_symbol_id->value;
        $svg_file_content = NULL;
        try {
          $uri = $term->field_svg_file->entity->getFileUri();
          $svg_file_content = file_exists($uri) ? file_get_contents($uri) : NULL;
        }
        catch (\Exception $e) {
          \Drupal::logger('icon_select')->error($e->getMessage());
        }

        $symbol_xml = simplexml_load_string($svg_file_content);

        if ($symbol_xml) {
          $symbol_xml->registerXPathNamespace('svg', 'http://www.w3.org/2000/svg');
          $symbol_nodes = $symbol_xml->xpath('/svg:svg/svg:*');
          foreach ($symbol_nodes as $node) {
            $symbol_content .= $node->asXML();
          }
          if (isset($symbol_xml->attributes()->viewBox)) {
            $symbol_viewbox = $symbol_xml->attributes()->viewBox;
          }
          else {
            if (isset($symbol_xml->attributes()->width) && isset($symbol_xml->attributes()->height)) {
              $width = $symbol_xml->attributes()->width;
              $height = $symbol_xml->attributes()->height;
              $symbol_viewbox = '0 0 ' . $width . ' ' . $height;
            }
            else {
              $symbol_content = '<text font-size="16" y="0">Missing</text>';
              $symbol_content .= '<text font-size="16" y="20">viewBox</text>';
              $symbol_content .= '<text font-size="16" y="40">in icon</text>';
              $symbol_content .= '<text fill="red" font-size="16" y="60">' . $symbol_id . '</text>';
            }
          }
          $sprite .= '<symbol id="' . $symbol_id . '" viewBox="' . $symbol_viewbox . '">';
          $sprite .= $symbol_content;
          $sprite .= '</symbol>';
        }
        else {
          $message = t('Symbol with ID @symbol_id and Term ID @term_id could not be added to the SVG Sprite.',
            ['@symbol_id' => $symbol_id, '@term_id' => $term->id()]);
          \Drupal::logger('icon_select')->error($message);
        }
      }
    }

    $sprite .= '</svg>';

    // Create a new sanitizer instance.
    $sanitizer = new Sanitizer();
    // Pass the svg to the sanitizer and get it back clean.
    $sprite = $sanitizer->sanitize($sprite);

    // Save MD5 Hash.
    $hash = md5($sprite);
    \Drupal::state()->set('icon_select_hash', $hash);

    $destination = SvgSpriteGenerator::getSpriteDestinationPath();
    $dirname = \Drupal::service('file_system')->dirname($destination);
    \Drupal::service('file_system')->prepareDirectory($dirname, FileSystemInterface::CREATE_DIRECTORY);
    $file = file_save_data($sprite, $destination, FileSystemInterface::EXISTS_REPLACE);

    if ($file) {
      return $file->getFileUri();
    }
    else {
      \Drupal::logger('icon_select')->error(t('SVG sprite file could not be saved.'));
    }
  }

  /**
   * Gets the path where the sprite-sheet should be stored at.
   *
   * @return string
   *   Relative path to the sprite-sheet.
   */
  public static function getSpriteDestinationPath() {
    $config = \Drupal::service('config.factory')->getEditable('icon_select.settings');
    $path = !empty($config->get('path')) ? $config->get('path') : 'icons/icon_select_map.svg';
    $destination = "public://" . $path;
    return $destination;
  }

}
