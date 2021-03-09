<?php

/**
 * @file
 * Theme implementation for csv output.
 */

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

/**
 * Csv content builder function.
 */
function theme_vbo_export_content_csv($variables) {
  $config = $variables['configuration'];
  // Sanitize data.
  foreach ($variables['header'] as $key => $item) {
    $variables['header'][$key] = strtr($item, [$config['separator'] => ' ']);
  }

  $content_replacements = [
    "\r\n" => ' ',
    "\n\r" => ' ',
    "\r" => ' ',
    "\n" => ' ',
    "\t" => ' ',
    $config['separator'] => ' ',
  ];

  // Generate output.
  $csv_rows = [];
  $csv_rows[] = implode($config['separator'], $variables['header']);
  foreach ($variables['rows'] as $row_index => $row) {
    foreach ($row as $cell_key => $cell) {
      $row[$cell_key] = strtr($cell, $content_replacements);
    }
    $csv_rows[] = implode($config['separator'], $row);
    unset($variables['rows'][$row_index]);
  }

  $csv_string = implode(PHP_EOL, $csv_rows);
  if (!empty($config['strip_tags'])) {
    $csv_string = strip_tags($csv_string);
  }
  return $csv_string;
}

/**
 * Xlsx content builder function.
 */
function theme_vbo_export_content_xlsx($variables) {
  $config = $variables['configuration'];
  $current_user = \Drupal::currentUser();

  // Load PhpSpreadsheet library.
  if (!_vbo_export_library_exists(Spreadsheet::class)) {
    \Drupal::logger('vbo_export')->error('PhpSpreadsheet library not installed.');
    return '';
  }

  // Create PHPExcel spreadsheet and add rows to it.
  $spreadsheet = new Spreadsheet();
  $spreadsheet->removeSheetByIndex(0);
  $spreadsheet->getProperties()
    ->setCreated(\Drupal::time()->getRequestTime())
    ->setCreator($current_user->getDisplayName())
    ->setTitle('VBO Export - ' . date('d-m-Y H:i', \Drupal::time()->getRequestTime()))
    ->setLastModifiedBy($current_user->getDisplayName());
  $worksheet = $spreadsheet->createSheet();
  $worksheet->setTitle((string) t('Export'));

  // Set header.
  $col_index = 1;
  foreach ($variables['header'] as $label) {
    // Sanitize data.
    if ($config['strip_tags']) {
      $label = strip_tags($label);
    }
    $worksheet->setCellValueExplicitByColumnAndRow($col_index++, 1, trim($label), DataType::TYPE_STRING);
  }

  // Set rows.
  foreach ($variables['rows'] as $row_index => $row) {
    $col_index = 1;
    foreach ($row as $cell) {
      // Sanitize data.
      if ($config['strip_tags']) {
        $cell = strip_tags($cell);
      }
      // Rows start from 1 and we need to account for header.
      $worksheet->setCellValueExplicitByColumnAndRow($col_index++, $row_index + 2, trim($cell), DataType::TYPE_STRING);
    }
    unset($variables['rows'][$row_index]);
  }

  // Add some additional styling to the worksheet.
  $spreadsheet->getDefaultStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
  $last_column = $worksheet->getHighestColumn();
  $last_column_index = Coordinate::columnIndexFromString($last_column);

  // Define the range of the first row.
  $first_row_range = 'A1:' . $last_column . '1';

  // Set first row in bold.
  $worksheet->getStyle($first_row_range)->getFont()->setBold(TRUE);

  // Activate an autofilter on the first row.
  $worksheet->setAutoFilter($first_row_range);

  // Set wrap text and top vertical alignment for the entire worksheet.
  $full_range = 'A1:' . $last_column . $worksheet->getHighestRow();
  $worksheet->getStyle($full_range)->getAlignment()
    ->setWrapText(TRUE)
    ->setVertical(Alignment::VERTICAL_TOP);

  for ($column = 0; $column <= $last_column_index; $column++) {
    $worksheet->getColumnDimensionByColumn($column)->setAutoSize(TRUE);
  }

  // Set a minimum and maximum width for columns.
  // TODO: move this to module settings.
  $min_column_width = 15;
  $max_column_width = 85;

  // Added a try-catch block
  // due to https://github.com/PHPOffice/PHPExcel/issues/556.
  try {
    $worksheet->calculateColumnWidths();
  }
  catch (Exception $e) {

  }

  for ($column = 0; $column <= $last_column_index; $column++) {
    $width = $worksheet->getColumnDimensionByColumn($column)->getWidth();
    if ($width < $min_column_width) {
      $worksheet->getColumnDimensionByColumn($column)->setAutoSize(FALSE);
      $worksheet->getColumnDimensionByColumn($column)->setWidth($min_column_width);
    }
    elseif ($width > $max_column_width) {
      $worksheet->getColumnDimensionByColumn($column)->setAutoSize(FALSE);
      $worksheet->getColumnDimensionByColumn($column)->setWidth($max_column_width);
    }
  }

  $objWriter = new Xlsx($spreadsheet);
  // Catch the output of the spreadsheet.
  ob_start();
  $objWriter->save('php://output');
  $excelOutput = ob_get_clean();
  return $excelOutput;
}
