<?php

use Drupal\user\Entity\User;
/**
 * @file
 * Drush integration for the default_content module.
 */

/**
 * Implements hook_drush_command().
 */
function default_content_drush_command() {
  $items['default-content-export'] = [
    'description' => dt('Exports a single entity'),
    'arguments' => [
      'entity_type' => dt('The entity type to export.'),
      'entity_id' => dt('The ID of the entity to export.'),
    ],
    'options' => [
      'file' => dt('Write out the exported content to a file instead of stdout'),
      'run-as' => dt('Run as a particular user (uid)'),
    ],
    'aliases' => ['dce'],
    'required-arguments' => 2,
  ];
  $items['default-content-export-references'] = [
    'description' => dt('Exports an entity and all its referenced entities.'),
    'arguments' => [
      'entity_type' => dt('The entity type to export.'),
      'entity_id' => dt('The ID of the entity to export.'),
    ],
    'options' => [
      'folder' => dt('Folder to export to, entities are grouped by entity type into directories.'),
      'run-as' => dt('Run as a particular user (uid)'),
    ],
    'aliases' => ['dcer'],
    'required-arguments' => 1,
  ];
  $items['default-content-export-module'] = [
    'description' => dt('Exports all the content defined in a module info file.'),
    'arguments' => [
      'module' => dt('The name of the module.'),
    ],
    'options' => [
      'run-as' => dt('Run as a particular user (uid)'),
    ],
    'aliases' => ['dcem'],
    'required-arguments' => 1,
  ];

  return $items;
}

/**
 * Exports a piece of content into the stdout or into a file.
 *
 * @param string $entity_type_id
 *   The entity type ID.
 * @param mixed $entity_id
 *   The entity ID to export.
 */
function drush_default_content_export($entity_type_id, $entity_id) {
  _drush_default_content_setup();
  /** @var \Drupal\default_content\DefaultContentManagerInterface $manager */
  $manager = \Drupal::service('default_content.manager');
  $export = $manager->exportContent($entity_type_id, $entity_id);

  if ($file = drush_get_option('file')) {
    file_put_contents($file, $export);
  }
  else {
    drush_print($export);
  }
  _drush_default_content_teardown();
}

/**
 * Exports a piece of content and all its referenced entities.
 *
 * @param string $entity_type_id
 *   The entity type ID.
 * @param mixed $entity_id
 *   (Optional) The entity ID to export or all entities will be exported.
 */
function drush_default_content_export_references($entity_type_id, $entity_id = NULL) {
  _drush_default_content_setup();
  /** @var \Drupal\default_content\DefaultContentManagerInterface $manager */
  $manager = \Drupal::service('default_content.manager');

  $folder = drush_get_option('folder', '.');
  if (is_null($entity_id) && ($entities = \Drupal::entityQuery($entity_type_id)->execute())) {
    // @todo Add paging.
    foreach ($entities as $entity_id) {
      $serialized_by_type = $manager->exportContentWithReferences($entity_type_id, $entity_id);
      $manager->writeDefaultContent($serialized_by_type, $folder);
    }
  }
  else {
    $serialized_by_type = $manager->exportContentWithReferences($entity_type_id, $entity_id);
    $manager->writeDefaultContent($serialized_by_type, $folder);
  }
  _drush_default_content_teardown();
}

/**
 * Exports all of the content for a given module.
 *
 * @param string $module_name
 *   The module name to export.
 */
function drush_default_content_export_module($module_name) {
  _drush_default_content_setup();
  /** @var \Drupal\default_content\DefaultContentManagerInterface $manager */
  $manager = \Drupal::service('default_content.manager');
  $serialized_by_type = $manager->exportModuleContent($module_name);
  $module_folder = \Drupal::moduleHandler()->getModule($module_name)->getPath() . '/content';
  $manager->writeDefaultContent($serialized_by_type, $module_folder);
  _drush_default_content_teardown();
}

/**
 * Check "run-as" option and switch user if given.
 *
 * @return void
 */
function _drush_default_content_setup() {
  if ($uid = drush_get_option('run-as')) {
    $accountSwitcher = \Drupal::service('account_switcher');
    $account = User::load($uid);
    $accountSwitcher->switchTo($account);
  }
}

/**
 * Check "run-as" option and switch user back if given.
 *
 * @return void
 */
function _drush_default_content_teardown() {
  if ($uid = drush_get_option('run-as')) {
    $accountSwitcher = \Drupal::service('account_switcher');
    $accountSwitcher->switchBack();
  }
}
