<?php

namespace Drupal\vbo_export\Plugin\Action;

/**
 * Generates csv.
 *
 * @Action(
 *   id = "vbo_export_generate_xlsx_action",
 *   label = @Translation("Generate xlsx from selected view results"),
 *   type = "",
 *   pass_context = TRUE,
 *   pass_view = TRUE
 * )
 */
class VboExportXlsx extends VboExportBase {
  const THEME = 'vbo_export_content_xlsx';
  const EXTENSION = 'xlsx';

}
