<?php

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
    ],
    'aliases' => ['dcer'],
    'required-arguments' => 2,
  ];

  $items['default-content-bulk-export'] = [
    'description' => dt('Bulk export for the whole content type entities.'),
    'aliases' => ['dcebe'],
    'arguments' => [
      'entity_type' => dt('The entity type to export.'),
    ],
    'options' => [
      'file' => dt('Write out the exported content to a file instead of stdout'),
    ],
  ];

  $items['default-content-import'] = [
    'description' => dt('Import content.'),
    'aliases' => ['dcei']
  ];

  return $items;
}

function drush_default_content_import() {
  \Drupal::service('default_content.manager')->importContent(_drush_default_content_get_content_folder());
}

/**
 * Exports a piece of content into the stdout or into a file.
 *
 * @param string $entity_type_id
 *   The entity type ID.
 * @param $entity_id
 *   The entity ID to export.
 */
function drush_default_content_export($entity_type_id, $entity_id) {
  $export = _export_entity($entity_type_id, $entity_id);
  if ($file = drush_get_option('file')) {
    file_put_contents($file, $export);
  }
  else {
    drush_print($export);
  }
}

function _export_entity($entity_type_id, $entity_id) {
  /** @var \Drupal\default_content\DefaultContentManagerInterface $manager */
  $manager = \Drupal::service('default_content.manager');
  return $manager->exportContent($entity_type_id, $entity_id);
}

function drush_default_content_bulk_export($content_type) {

  $storage = \Drupal::entityManager()->getStorage($content_type);
  $entities = $storage->loadMultiple();

  $content_folder = _drush_default_content_get_content_folder();
  file_prepare_directory($content_folder, FILE_CREATE_DIRECTORY);
  $entity_type_folder = $content_folder . '/' . $content_type;
  file_prepare_directory($entity_type_folder, FILE_CREATE_DIRECTORY);

  foreach($entities as $entity) {
    $export = _export_entity($content_type, $entity->id());
    file_put_contents($entity_type_folder. '/' . $entity->uuid() . '.json', $export);
  }

}

function _drush_default_content_get_content_folder() {
  return realpath(config_get_config_directory(CONFIG_STAGING_DIRECTORY)) . '/content';
}

/**
 * Exports a piece of content and all its referenced entities.
 *
 * @param string $entity_type_id
 *   The entity type ID.
 * @param mixed $entity_id
 *   The entity ID to export.
 */
function drush_default_content_export_references($entity_type_id, $entity_id) {
  /** @var \Drupal\default_content\DefaultContentManagerInterface $manager */
  $manager = \Drupal::service('default_content.manager');

  $folder = _drush_default_content_get_content_folder();

  $serialized_by_type = $manager->exportContentWithReferences($entity_type_id, $entity_id);
  foreach ($serialized_by_type as $entity_type => $serialized_entities) {
    // Ensure that the folder per entity type exists.
    $entity_type_folder = "$folder/$entity_type";
    file_prepare_directory($entity_type_folder, FILE_CREATE_DIRECTORY);
    foreach ($serialized_entities as $entity_id => $serialized_entity) {
      file_put_contents($entity_type_folder . '/' . $entity_id . '.json', $serialized_entity);
    }
  }
}
