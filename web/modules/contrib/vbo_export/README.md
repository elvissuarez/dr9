INTRODUCTION
------------

This module provides a node bulk action to export displayed
view rows to csv or xlsx file.


INSTALLATION
------------

Install as any other module.
For Excel export use composer to get PhpSpreadsheet library:
  composer require phpoffice/phpspreadsheet ^1.6


REQUIREMENTS
------------

 * For Excel export the PhpSpreadsheet library is required
  (https://github.com/PHPOffice/PhpSpreadsheet)


USAGE
-----

The module defines two additional actions: "Generate csv from selected view results"
and "Generate xlsx from selected view results". Both can be enabled and configured
in the Views Bulk Operations field settings form on any view that has the VBO field
included.
