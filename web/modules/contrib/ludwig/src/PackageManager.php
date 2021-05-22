<?php

namespace Drupal\ludwig;

use Drupal\Core\Extension\ExtensionDiscovery;

/**
 * Provides information about ludwig-managed packages.
 *
 * Extensions (modules, profiles) can define a ludwig.json which is
 * discovered by this class. This discovery works even without a
 * Drupal installation, and covers non-installed extensions.
 */
class PackageManager implements PackageManagerInterface {

  /**
   * The app root.
   *
   * @var string
   */
  protected $root;

  /**
   * Constructs a new PackageManager object.
   *
   * @param string $root
   *   The app root.
   */
  public function __construct($root) {
    $this->root = $root;
  }

  /**
   * {@inheritdoc}
   */
  public function getPackages() {
    $listing = new ExtensionDiscovery($this->root);
    // Get all profiles, and modules belonging to those profiles.
    $profiles = $listing->scan('profile');
    $profile_directories = array_map(function ($profile) {
      return $profile->getPath();
    }, $profiles);
    $listing->setProfileDirectories($profile_directories);
    $modules = $listing->scan('module');
    /** @var \Drupal\Core\Extension\Extension[] $extensions */
    $extensions = $profiles + $modules;

    $packages = [];
    foreach ($extensions as $extension_name => $extension) {
      $extension_path = $extension->getPath();
      // Let's check if this module has ludwig.json file and
      // proceed only if it does.
      if (is_file($this->root . '/' . $extension_path . '/ludwig.json')) {
        $config = $this->jsonRead($this->root . '/' . $extension_path . '/ludwig.json');
        $config += [
          'require' => [],
        ];
        // Let's check if this module has .module file and
        // load its content if it has. We will need it later.
        if (is_file($this->root . '/' . $extension_path . '/' . $extension_name . '.module')) {
          $module_file = file_get_contents($this->root . '/' . $extension_path . '/' . $extension_name . '.module');
          $has_ludwig_service = strpos($module_file, 'ludwig.require_once') !== FALSE;
        }
        foreach ($config['require'] as $package_name => $package_data) {
          $package_path = $extension_path . '/lib/' . str_replace('/', '-', $package_name) . '/' . $package_data['version'];
          $disable_warnings = isset($package_data['disable_warnings']) ? $package_data['disable_warnings'] : FALSE;
          // Disable the 'classmap' or 'files' warning if this
          // module's .module file is calling ludwig.require_once
          // service with this package name as an argument.
          if (!empty($has_ludwig_service) && !empty($module_file) && ((strpos($module_file, "requireOnce('".$package_name) !== FALSE) || (strpos($module_file, "requireOnce( '".$package_name) !== FALSE) || (strpos($module_file, 'requireOnce("'.$package_name) !== FALSE) || (strpos($module_file, 'requireOnce( "'.$package_name) !== FALSE))) {
             $disable_warnings = TRUE;
          }
          $package = $this->jsonRead($this->root . '/' . $package_path . '/composer.json');
          $description = !empty($package['description']) ? $package['description'] : '';
          $homepage = !empty($package['homepage']) ? $package['homepage'] : '';
          // Create the base package data array.
          $package_base = [
            'name' => $package_name,
            'version' => $package_data['version'],
            'description' => $description,
            'homepage' => $homepage,
            'provider' => $extension_name,
            'provider_path' => $extension_path,
            'download_url' => $package_data['url'],
            'disable_warnings' => $disable_warnings,
            'path' => $package_path,
          ];
          if (empty($package)) {
            // Add new package. This one needs a download.
            $package_append = [
              'namespace' => '',
              'paths' => [],
              'installed' => FALSE,
              'resource' => '',
            ];
            $packages[$package_name] = array_merge($package_base, $package_append);
            continue;
          }
          if (!empty($package['autoload'])) {
            $resources = array_keys($package['autoload']);
            // Iterate through all autoload types.
            foreach ($resources as $resource) {
              if (!empty($package['autoload'][$resource])) {
                if ($resource == 'files' || $resource == 'classmap' || $resource == 'exclude-from-classmap' || $resource == 'target-dir') {
                  $autoload = $package['autoload'];
                  $package_namespaces = [$resource];
                }
                elseif ($resource == 'psr-4' || $resource == 'psr-0') {
                  $autoload = $package['autoload'][$resource];
                  $package_namespaces = array_keys($autoload);
                }
                else {
                  // The unknown library type.
                  $package_append = [
                    'namespace' => '',
                    'paths' => [],
                    'installed' => TRUE,
                    'resource' => 'unknown',
                  ];
                  $packages[$package_name] = array_merge($package_base, $package_append);
                  continue;
                }
                // Iterate through all the resources inside single autoload type.
                foreach ($package_namespaces as $namespace) {
                  $paths_raw = $autoload[$namespace];
                  // Support for both single path (string) and multiple
                  // paths (array) inside one resource.
                  $paths = [];
                  if (is_array($paths_raw)) {
                    $paths = $paths_raw;
                  }
                  elseif (is_string($paths_raw)) {
                    $paths[] = $paths_raw;
                  }
                  // Autoloading fails if the namespace ends with a backslash.
                  $namespace = trim($namespace, '\\');
                  // Iterate through all the paths inside this resource.
                  foreach ($paths as $key => $value) {
                    $paths[$key] = rtrim($paths[$key], './');
                    // Core only assumes that LudwigServiceProvider is adding
                    // PSR-4 paths, each PSR-0 path needs to be converted
                    // in order to work.
                    if ($resource == 'psr-0' && !empty($namespace)) {
                      if (!empty($paths[$key])) {
                        $paths[$key] .= '/';
                      }
                      $paths[$key] .= str_replace('\\', '/', $namespace);
                    }
                  }
                  // Combine $package_name an $paths into uniuqe $name_path value.
                  $name_path = $package_name . '_' . implode('-', $paths);
                  // Two versions of the same package are not possible.
                  // If multiple providers require the same package
                  // we keep the highest required version only, since it has
                  // the best probability to work for all providers, and
                  // it is the most secure.
                  if (!isset($packages[$name_path]) || $packages[$name_path]['version'] < $package_data['version']) {
                    // If the current item is going to be replaced with the new
                    // one, unset the current item first to keep all packages
                    // nicely sorted by provider name inside the 'Packages' table.
                    if (isset($packages[$name_path])) {
                      unset($packages[$name_path]);
                    }
                    // Add new package.
                    $package_append = [
                      'namespace' => $namespace,
                      'paths' => $paths,
                      'installed' => TRUE,
                      'resource' => $resource,
                    ];
                    $packages[$name_path] = array_merge($package_base, $package_append);
                  }
                }
              }
            }
          }
          elseif (!empty($package['include-path'])) {
            // This is the Legacy library (depricated type).
            // They do not have autoload composer.json section
            // but "include-path" section instead.
            $package_append = [
              'namespace' => '',
              'paths' => [],
              'installed' => TRUE,
              'resource' => 'legacy',
            ];
            $packages[$package_name] = array_merge($package_base, $package_append);
          }
          else {
            // The library without the autoload section.
            $package_append = [
              'namespace' => '',
              'paths' => [],
              'installed' => TRUE,
              'resource' => 'inactive',
            ];
            $packages[$package_name] = array_merge($package_base, $package_append);
          }
        }
      }
    }

    return $packages;
  }

  /**
   * Reads and decodes a json file into an array.
   *
   * @param string $filename
   *   Name of the file to read.
   *
   * @return array
   *   The decoded json data.
   */
  protected function jsonRead($filename) {
    $data = [];
    if (file_exists($filename)) {
      $data = file_get_contents($filename);
      $data = json_decode($data, TRUE);
      if (!$data) {
        $data = [];
      }
    }

    return $data;
  }

}
