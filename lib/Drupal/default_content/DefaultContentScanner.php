<?php

/**
 * @file
 * Contains \Drupal\default_content\DefaultContentScanner.
 */

namespace Drupal\default_content;

use \Drupal\Core\SystemListing;

/**
 * A scanner to find YAML files in a given folder.
 */
class DefaultContentScanner extends SystemListing {

  /**
   * {@inheritdoc}
   */
  public function scan($mask, $directory, $key = 'name') {
    if (!in_array($key, array('uri', 'filename', 'name'))) {
      $key = 'uri';
    }
    $directories = array($directory);
    $nomask = '/^(CVS|lib|templates|css|js)$/';
    $files = array();
    // Get current list of items.
    foreach ($directories as $dir) {
      $files = array_merge($files, $this->process($files, $this->scanDirectory($dir, $key, $mask, $nomask)));
    }
    return $files;
  }

}
