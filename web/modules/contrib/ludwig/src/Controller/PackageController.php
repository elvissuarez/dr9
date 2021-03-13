<?php

namespace Drupal\ludwig\Controller;

use Drupal\Core\Link;
use Drupal\ludwig\PackageManagerInterface;
use Drupal\ludwig\PackageDownloaderInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Url;
use Drupal\Core\FileTransfer\FileTransferException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Displays the Packages report.
 */
class PackageController implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The package manager.
   *
   * @var \Drupal\ludwig\PackageManagerInterface
   */
  protected $packageManager;

  /**
   * The package downloader.
   *
   * @var \Drupal\ludwig\PackageDownloaderInterface
   */
  protected $packageDownloader;

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * The RequestStack object.
   *
   * @var Symfony\Component\HttpFoundation\RequestStack
   */
  private $requestStack;

  /**
   * Constructs a new PackageController object.
   *
   * @param \Drupal\ludwig\PackageManagerInterface $package_manager
   *   The package manager.
   * @param \Drupal\ludwig\PackageDownloaderInterface $package_downloader
   *   The package downloader.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_extension_list
   *   The module extension list.
   * @param Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The RequestStack object.
   */
  public function __construct(PackageManagerInterface $package_manager, PackageDownloaderInterface $package_downloader, TranslationInterface $string_translation, ModuleExtensionList $module_extension_list, RequestStack $request_stack) {
    $this->packageManager = $package_manager;
    $this->packageDownloader = $package_downloader;
    $this->setStringTranslation($string_translation);
    $this->moduleExtensionList = $module_extension_list;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ludwig.package_manager'),
      $container->get('ludwig.package_downloader'),
      $container->get('string_translation'),
      $container->get('extension.list.module'),
      $container->get('request_stack')
    );
  }

  /**
   * Shows the status of all required packages.
   *
   * @return array
   *   Returns a render array as expected by drupal_render().
   */
  public function page() {
    // If requested, download the missing packages first.
    if ($this->requestStack->getCurrentRequest()->query->get('missing') == 'download') {
      $this->download();
      return new RedirectResponse(Url::fromRoute('ludwig.packages')->toString());
    }
    $info = $this->moduleExtensionList->getAllInstalledInfo();
    $build = [];
    $build['packages'] = [
      '#theme' => 'table',
      '#header' => [
        'package' => $this->t('Package'),
        'paths' => $this->t('Paths'),
        'resource' => $this->t('Resource'),
        'version' => $this->t('Version'),
        'required_by' => $this->t('Required by'),
        'status' => $this->t('Status'),
      ],
      '#attributes' => [
        'class' => ['system-status-report'],
      ],
    ];
    $missing = 0;
    foreach ($this->packageManager->getPackages() as $package_name => $package) {
      if ($package['installed'] === FALSE) {
        $missing++;
      }

      $guide_link = 'https://www.drupal.org/docs/contributed-modules/ludwig/ludwig-errors-warnings-and-notices';

      if (($package['resource'] == 'classmap' || $package['resource'] == 'files') && empty($package['disable_warnings'])) {
        $package['description'] .= $this->t('<br><strong>Warning! The @resource type libraries are not supported by Ludwig automatically. @read_more.</strong>', [
          '@resource' => strtoupper($package['resource']),
          '@read_more' => Link::fromTextAndUrl($this->t('Read more'), Url::fromUri($guide_link))->toString(),
        ]);
      }
      elseif ($package['resource'] == 'exclude-from-classmap' && empty($package['disable_warnings'])) {
        $package['description'] .= $this->t('<br><strong>Notice! The @resource property is not supported by Ludwig.</strong> Despite this notice, the library is loaded properly and the module should work nicely. @read_more.', [
          '@resource' => strtoupper($package['resource']),
          '@read_more' => Link::fromTextAndUrl($this->t('Read more'), Url::fromUri($guide_link))->toString(),
        ]);
      }
      elseif ($package['resource'] == 'target-dir' && empty($package['disable_warnings'])) {
        $package['description'] .= $this->t('<br><strong>Warning! The @resource property is not supported by Ludwig.</strong> This module may lack some functionality. @read_more.', [
          '@resource' => strtoupper($package['resource']),
          '@read_more' => Link::fromTextAndUrl($this->t('Read more'), Url::fromUri($guide_link))->toString(),
        ]);
      }
      elseif (($package['resource'] == 'legacy' || $package['resource'] == 'unknown') && empty($package['disable_warnings'])) {
        $package['description'] .= $this->t('<br><strong>Warning! The @resource library type. Not supported by Ludwig. @read_more.</strong>', [
          '@resource' => strtoupper($package['resource']),
          '@read_more' => Link::fromTextAndUrl($this->t('Read more'), Url::fromUri($guide_link))->toString(),
        ]);
      }
      elseif (!$package['installed']) {
        $package['description'] = $this->t('@download the library and place it in @path', [
          '@download' => Link::fromTextAndUrl($this->t('Download'), Url::fromUri($package['download_url']))->toString(),
          '@path' => $package['path'],
        ]);
      }

      $package_column = [];
      if (!empty($package['homepage'])) {
        $package_column[] = [
          '#type' => 'link',
          '#title' => $package['name'],
          '#url' => Url::fromUri($package['homepage']),
          '#options' => [
            'attributes' => ['target' => '_blank'],
          ],
        ];
      }
      else {
        $package_column[] = [
          '#plain_text' => $package['name'],
        ];
      }
      if (!empty($package['description'])) {
        $package_column[] = [
          '#prefix' => '<div class="description">',
          '#markup' => $package['description'],
          '#suffix' => '</div>',
        ];
      }
      $required_by = $package['provider'];
      if (isset($info[$package['provider']])) {
        $required_by = $info[$package['provider']]['name'];
      }

      $build['packages']['#rows'][$package_name] = [
        'class' => $package['installed'] ? [] : ['error'],
        'data' => [
          'package' => [
            'data' => $package_column,
          ],
          'paths' => implode(', ', $package['paths']),
          'resource' => $package['resource'],
          'version' => $package['version'],
          'required_by' => $required_by,
          'status' => $package['installed'] ? $this->t('Installed') : $this->t('Missing'),
        ],
      ];
    }

    if (!empty($missing)) {
      // There are some missing packages, so render the
      // "Download all missing packages" clickable button.
      $build['#markup'] = $this->t('<div class="button"><a href="@packages-url">Download and unpack all missing packages (@missing)</a></div><div>&nbsp;</div>', [
        '@packages-url' => Url::fromRoute('ludwig.packages')->toString() . '?missing=download',
        '@missing' => $missing,
      ]);
    }
    else {
      // There are no missing packages. For the UX consistency
      // purpose render the button again, but as disabled one.
      $build['#markup'] = $this->t('<div class="button is-disabled">Download and unpack missing packages (0)</div><div>&nbsp;</div>');
    }

    return $build;
  }

  /**
   * Downloads missing packages.
   */
  public function download() {
    $packages = array_filter($this->packageManager->getPackages(), function ($package) {
      return empty($package['installed']);
    });
    if (!empty($packages)) {
      $logger = \Drupal::logger('ludwig');
      $messenger = \Drupal::messenger();
      foreach ($packages as $name => $package) {
        try {
          $this->packageDownloader->download($package);
          $logger->info($this->t('The @name package has been downloaded and unpacked successfully.', [
            '@name' => $name,
          ]));
          $messenger->addMessage(t('The @name package has been downloaded and unpacked successfully.', [
            '@name' => $name,
          ]));
        }
        catch (FileTransferException $e) {
          $logger->error($e->getMessage());
          $messenger->addMessage($e->getMessage(), 'error');
          continue;
        }
        catch (\Exception $e) {
          $logger->error($e->getMessage());
          $messenger->addMessage($e->getMessage(), 'error');
          continue;
        }
      }
    }
  }

}
