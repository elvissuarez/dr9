<?php

/**
 * @file
 * Provides Drush commands for the icon_select module.
 */

use Drupal\icon_select\Helper\SvgSpriteGenerator;

/**
 * Implements hook_drush_command().
 */
function icon_select_drush_command() {
  $items['generate-sprites'] = [
    'callback' => 'drush_icon_select_generate_sprites',
    'description' => 'Create svg sprite map.',
    'arguments' => [],
    'options' => [],
    'aliases' => ['gens'],
  ];
  return $items;
}

/**
 * Command callback. Generate a number of elements.
 */
function drush_icon_select_generate_sprites() {
  $url = SvgSpriteGenerator::generateSprites('icons');
  if ($url) {
    drush_print('Generated sprites in ' . $url);
  }
}
