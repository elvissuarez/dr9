<?php

namespace Drupal\icon_select\Commands;

use Drupal\icon_select\Helper\SvgSpriteGenerator;
use Drush\Commands\DrushCommands;

/**
 * Drush 9 commands.
 */
class IconSelectCommands extends DrushCommands {

  /**
   * Create svg sprite map.
   *
   * @command generate-sprites
   * @aliases gens
   */
  public function sprites() {
    $url = SvgSpriteGenerator::generateSprites('icons');
    if ($url) {
      $this->output()->writeln('Generated sprites in ' . $url);
    }
  }

}
