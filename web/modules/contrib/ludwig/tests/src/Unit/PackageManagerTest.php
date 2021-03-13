<?php

namespace Drupal\Tests\ludwig\Unit {

  use Drupal\ludwig\PackageManager;
  use Drupal\Tests\UnitTestCase;
  use org\bovigo\vfs\vfsStream;

  /**
   * @coversDefaultClass \Drupal\ludwig\PackageManager
   * @group ludwig
   */
  class PackageManagerTest extends UnitTestCase {

    /**
     * The package manager.
     *
     * @var \Drupal\ludwig\PackageManager
     */
    protected $manager;

    /**
     * Package fixtures.
     *
     * @var array
     */
    protected $packages = [
      'extension' => [
        'lightning' => [
          'require' => [
            'symfony/css-selector' => [
              'version' => 'v3.2.8',
              'url' => 'https://github.com/symfony/css-selector/archive/v3.2.8.zip',
            ],
          ],
        ],
        'test1' => [
          'require' => [
            'symfony/intl' => [
              'version' => 'v3.2.8',
              'url' => 'https://github.com/symfony/intl/archive/v3.2.8.zip',
            ],
          ],
        ],
        'test2' => [
          'require' => [
            'symfony/config' => [
              'version' => 'v3.2.8',
              'url' => 'https://github.com/symfony/config/archive/v3.2.8.zip',
            ],
          ],
        ],
        'test3a' => [
          'require' => [
            'html2text/html2text' => [
              'version' => 'v4.3.1',
              'url' => 'https://github.com/mtibben/html2text/archive/4.3.1.zip',
            ],
          ],
        ],
        'test3b' => [
          'require' => [
            'html2text/html2text' => [
              'version' => 'v4.0.1',
              'url' => 'https://github.com/mtibben/html2text/archive/4.0.1.zip',
            ],
          ],
        ],
        'test3c' => [
          'require' => [
            'html2text/html2text' => [
              'version' => 'v4.3.1',
              'url' => 'https://github.com/mtibben/html2text/archive/4.3.1.zip',
            ],
          ],
        ],
        'entity_print' => [
          'require' => [
            'dompdf/dompdf' => [
              'version' => 'v0.8.6',
              'url' => 'https://github.com/dompdf/dompdf/releases/download/v0.8.6/dompdf_0-8-6.zip',
              'disable_warnings' => 'TRUE',
            ],
          ],
        ],
        'feeds_ex' => [
          'require' => [
            'querypath/QueryPath' => [
              'version' => 'v3.0.5',
              'url' => 'https://github.com/technosophos/querypath/archive/3.0.5.zip',
            ],
          ],
        ],
        'geoip' => [
          'require' => [
            'maxmind/web-service-common' => [
              'version' => 'v0.8.0',
              'url' => 'https://github.com/maxmind/web-service-common-php/archive/v0.8.0.zip',
            ],
          ],
        ],
      ],
      'installed' => [
        'symfony/config' => [
          'name' => 'symfony/config',
          'description' => 'Symfony Config Component',
          'homepage' => 'http://symfony.com',
          'autoload' => [
            'psr-4' => ['Symfony\\Component\\Config\\' => 'src'],
          ],
        ],
        'html2text/html2text' => [
          'name' => 'html2text/html2text',
          'description' => 'Converts HTML to formatted plain text',
          'autoload' => [
            'psr-4' => ['Html2Text\\' => ['src/', 'test/']],
          ],
        ],
        'dompdf/dompdf' => [
          'name' => 'dompdf/dompdf',
          'description' => 'DOMPDF is a CSS 2.1 compliant HTML to PDF converter',
          'homepage' => 'https://github.com/dompdf/dompdf',
          'autoload' => [
            'psr-4' => ['Dompdf\\' => 'src/'],
            'classmap' => ['lib/'],
          ],
        ],
        'querypath/QueryPath' => [
          'name' => 'querypath/QueryPath',
          'description' => 'HTML/XML querying (CSS 4 or XPath) and processing (like jQuery)',
          'homepage' => 'https://github.com/technosophos/querypath',
          'autoload' => [
            'psr-0' => ['QueryPath' => 'src/'],
            'files' => ['src/qp_functions.php'],
          ],
        ],
        'maxmind/web-service-common' => [
          'name' => 'maxmind/web-service-common',
          'description' => 'Internal MaxMind Web Service API',
          'homepage' => 'https://github.com/maxmind/web-service-common-php',
          'autoload' => [
            'psr-4' => [
              'MaxMind\\Exception\\' => 'src/Exception',
              'MaxMind\\WebService\\' => 'src/WebService',
            ],
          ],
        ],
      ],
    ];

    /**
     * {@inheritdoc}
     */
    public function setUp() {
      parent::setUp();

      $structure = [
        'profiles' => [
          'lightning' => [
            'lightning.info.yml' => 'type: profile',
            'ludwig.json' => json_encode($this->packages['extension']['lightning']),
          ],
        ],
        'modules' => [
          'test1' => [
            'ludwig.json' => json_encode($this->packages['extension']['test1']),
            'test1.info.yml' => 'type: module',
          ],
          'test3a' => [
            'ludwig.json' => json_encode($this->packages['extension']['test3a']),
            'test3a.info.yml' => 'type: module',
            'lib' => [
              'html2text-html2text' => [
                'v4.3.1' => [
                  'composer.json' => json_encode($this->packages['installed']['html2text/html2text']),
                ],
              ],
            ],
          ],
          'test3b' => [
            'ludwig.json' => json_encode($this->packages['extension']['test3b']),
            'test3b.info.yml' => 'type: module',
            'lib' => [
              'html2text-html2text' => [
                'v4.0.1' => [
                  'composer.json' => json_encode($this->packages['installed']['html2text/html2text']),
                ],
              ],
            ],
          ],
          'test3c' => [
            'ludwig.json' => json_encode($this->packages['extension']['test3c']),
            'test3c.info.yml' => 'type: module',
            'lib' => [
              'html2text-html2text' => [
                'v4.3.1' => [
                  'composer.json' => json_encode($this->packages['installed']['html2text/html2text']),
                ],
              ],
            ],
          ],
          'entity_print' => [
            'ludwig.json' => json_encode($this->packages['extension']['entity_print']),
            'entity_print.info.yml' => 'type: module',
            'lib' => [
              'dompdf-dompdf' => [
                'v0.8.6' => [
                  'composer.json' => json_encode($this->packages['installed']['dompdf/dompdf']),
                ],
              ],
            ],
          ],
          'feeds_ex' => [
            'ludwig.json' => json_encode($this->packages['extension']['feeds_ex']),
            'feeds_ex.info.yml' => 'type: module',
            'lib' => [
              'querypath-QueryPath' => [
                'v3.0.5' => [
                  'composer.json' => json_encode($this->packages['installed']['querypath/QueryPath']),
                ],
              ],
            ],
          ],
          'geoip' => [
            'ludwig.json' => json_encode($this->packages['extension']['geoip']),
            'geoip.info.yml' => 'type: module',
            'lib' => [
              'maxmind-web-service-common' => [
                'v0.8.0' => [
                  'composer.json' => json_encode($this->packages['installed']['maxmind/web-service-common']),
                ],
              ],
            ],
          ],
        ],
        'sites' => [
          'all' => [
            'modules' => [
              'test2' => [
                'ludwig.json' => json_encode($this->packages['extension']['test2']),
                'test2.info.yml' => 'type: module',
                'lib' => [
                  'symfony-config' => [
                    'v3.2.8' => [
                      'composer.json' => json_encode($this->packages['installed']['symfony/config']),
                    ],
                  ],
                ],
              ],
            ],
          ],
        ],
      ];
      vfsStream::setup('drupal', NULL, $structure);

      $this->manager = new PackageManager('vfs://drupal');

    }

    /**
     * @covers ::getPackages
     */
    public function testGetPackages() {
      $expected_packages = [
        'symfony/css-selector' => [
          'name' => 'symfony/css-selector',
          'version' => 'v3.2.8',
          'description' => '',
          'homepage' => '',
          'provider' => 'lightning',
          'provider_path' => 'profiles/lightning',
          'download_url' => 'https://github.com/symfony/css-selector/archive/v3.2.8.zip',
          'disable_warnings' => FALSE,
          'path' => 'profiles/lightning/lib/symfony-css-selector/v3.2.8',
          'namespace' => '',
          'paths' => [],
          'installed' => FALSE,
          'resource' => '',
        ],
        'symfony/intl' => [
          'name' => 'symfony/intl',
          'version' => 'v3.2.8',
          'description' => '',
          'homepage' => '',
          'provider' => 'test1',
          'provider_path' => 'modules/test1',
          'download_url' => 'https://github.com/symfony/intl/archive/v3.2.8.zip',
          'disable_warnings' => FALSE,
          'path' => 'modules/test1/lib/symfony-intl/v3.2.8',
          'namespace' => '',
          'paths' => [],
          'installed' => FALSE,
          'resource' => '',
        ],
        'symfony/config_src' => [
          'name' => 'symfony/config',
          'version' => 'v3.2.8',
          'description' => 'Symfony Config Component',
          'homepage' => 'http://symfony.com',
          'provider' => 'test2',
          'provider_path' => 'sites/all/modules/test2',
          'download_url' => 'https://github.com/symfony/config/archive/v3.2.8.zip',
          'disable_warnings' => FALSE,
          'path' => 'sites/all/modules/test2/lib/symfony-config/v3.2.8',
          'namespace' => 'Symfony\\Component\\Config',
          'paths' => ['src'],
          'installed' => TRUE,
          'resource' => 'psr-4',
        ],
        'html2text/html2text_src-test' => [
          'name' => 'html2text/html2text',
          'version' => 'v4.3.1',
          'description' => 'Converts HTML to formatted plain text',
          'homepage' => '',
          'provider' => 'test3a',
          'provider_path' => 'modules/test3a',
          'download_url' => 'https://github.com/mtibben/html2text/archive/4.3.1.zip',
          'disable_warnings' => FALSE,
          'path' => 'modules/test3a/lib/html2text-html2text/v4.3.1',
          'namespace' => 'Html2Text',
          'paths' => ['src', 'test'],
          'installed' => TRUE,
          'resource' => 'psr-4',
        ],
        'dompdf/dompdf_src' => [
          'name' => 'dompdf/dompdf',
          'version' => 'v0.8.6',
          'description' => 'DOMPDF is a CSS 2.1 compliant HTML to PDF converter',
          'homepage' => 'https://github.com/dompdf/dompdf',
          'provider' => 'entity_print',
          'provider_path' => 'modules/entity_print',
          'download_url' => 'https://github.com/dompdf/dompdf/releases/download/v0.8.6/dompdf_0-8-6.zip',
          'disable_warnings' => 'TRUE',
          'path' => 'modules/entity_print/lib/dompdf-dompdf/v0.8.6',
          'namespace' => 'Dompdf',
          'paths' => ['src'],
          'installed' => TRUE,
          'resource' => 'psr-4',
        ],
        'dompdf/dompdf_lib' => [
          'name' => 'dompdf/dompdf',
          'version' => 'v0.8.6',
          'description' => 'DOMPDF is a CSS 2.1 compliant HTML to PDF converter',
          'homepage' => 'https://github.com/dompdf/dompdf',
          'provider' => 'entity_print',
          'provider_path' => 'modules/entity_print',
          'download_url' => 'https://github.com/dompdf/dompdf/releases/download/v0.8.6/dompdf_0-8-6.zip',
          'disable_warnings' => 'TRUE',
          'path' => 'modules/entity_print/lib/dompdf-dompdf/v0.8.6',
          'namespace' => 'classmap',
          'paths' => ['lib'],
          'installed' => TRUE,
          'resource' => 'classmap',
        ],
        'querypath/QueryPath_src/QueryPath' => [
          'name' => 'querypath/QueryPath',
          'version' => 'v3.0.5',
          'description' => 'HTML/XML querying (CSS 4 or XPath) and processing (like jQuery)',
          'homepage' => 'https://github.com/technosophos/querypath',
          'provider' => 'feeds_ex',
          'provider_path' => 'modules/feeds_ex',
          'download_url' => 'https://github.com/technosophos/querypath/archive/3.0.5.zip',
          'disable_warnings' => FALSE,
          'path' => 'modules/feeds_ex/lib/querypath-QueryPath/v3.0.5',
          'namespace' => 'QueryPath',
          'paths' => ['src/QueryPath'],
          'installed' => TRUE,
          'resource' => 'psr-0',
        ],
        'querypath/QueryPath_src/qp_functions.php' => [
          'name' => 'querypath/QueryPath',
          'version' => 'v3.0.5',
          'description' => 'HTML/XML querying (CSS 4 or XPath) and processing (like jQuery)',
          'homepage' => 'https://github.com/technosophos/querypath',
          'provider' => 'feeds_ex',
          'provider_path' => 'modules/feeds_ex',
          'download_url' => 'https://github.com/technosophos/querypath/archive/3.0.5.zip',
          'disable_warnings' => FALSE,
          'path' => 'modules/feeds_ex/lib/querypath-QueryPath/v3.0.5',
          'namespace' => 'files',
          'paths' => ['src/qp_functions.php'],
          'installed' => TRUE,
          'resource' => 'files',
        ],
        'maxmind/web-service-common_src/Exception' => [
          'name' => 'maxmind/web-service-common',
          'version' => 'v0.8.0',
          'description' => 'Internal MaxMind Web Service API',
          'homepage' => 'https://github.com/maxmind/web-service-common-php',
          'provider' => 'geoip',
          'provider_path' => 'modules/geoip',
          'download_url' => 'https://github.com/maxmind/web-service-common-php/archive/v0.8.0.zip',
          'disable_warnings' => FALSE,
          'path' => 'modules/geoip/lib/maxmind-web-service-common/v0.8.0',
          'namespace' => 'MaxMind\\Exception',
          'paths' => ['src/Exception'],
          'installed' => TRUE,
          'resource' => 'psr-4',
        ],
        'maxmind/web-service-common_src/WebService' => [
          'name' => 'maxmind/web-service-common',
          'version' => 'v0.8.0',
          'description' => 'Internal MaxMind Web Service API',
          'homepage' => 'https://github.com/maxmind/web-service-common-php',
          'provider' => 'geoip',
          'provider_path' => 'modules/geoip',
          'download_url' => 'https://github.com/maxmind/web-service-common-php/archive/v0.8.0.zip',
          'disable_warnings' => FALSE,
          'path' => 'modules/geoip/lib/maxmind-web-service-common/v0.8.0',
          'namespace' => 'MaxMind\\WebService',
          'paths' => ['src/WebService'],
          'installed' => TRUE,
          'resource' => 'psr-4',
        ],
      ];

      $required_packages = $this->manager->getPackages();
      $this->assertEquals($expected_packages, $required_packages);
    }

  }
}

namespace {

  if (!function_exists('drupal_valid_test_ua')) {

    /**
     * {@inheritdoc}
     */
    function drupal_valid_test_ua($new_prefix = NULL) {
      return FALSE;
    }

  }
}
