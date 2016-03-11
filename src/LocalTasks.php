<?php

/**
 * @file
 * Contains \Drupal\default_content\Plugin\Derivative\LocalTasks.
 */

namespace Drupal\default_content;

use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides local task definitions one to export each entity type
 */
class LocalTasks extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  var $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct($entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $definition) {
      if ($definition instanceOf \Drupal\Core\Entity\ContentEntityType) {
        $this->derivatives["default_content.export.".$entity_type_id] = [
          'route_name' => "entity.$entity_type_id.export",
          'weight' => 5,
          'title' => $this->t('Export'),
          'base_route' => "entity.$entity_type_id.canonical",
        ];
      }
    }
    return $this->derivatives;
  }

}
