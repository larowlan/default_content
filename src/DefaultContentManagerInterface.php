<?php

/**
 * @file
 * Contains \Drupal\default_content\DefaultContentManager.
 */

namespace Drupal\default_content;


/**
 * An interface defining a default content importer.
 */
interface DefaultContentManagerInterface {
  /**
   * Set the scanner.
   *
   * @param \Drupal\default_content\DefaultContentScanner $scanner
   *   The system scanner.
   */
  public function setScanner(DefaultContentScanner $scanner);

  /**
   * Imports default content for a given module.
   *
   * @param string $module
   *   The module to create the default content for.
   *
   * @return array[\Drupal\Core\Entity\EntityInterface]
   *   The created entities.
   */
  public function importContent($module);

}
