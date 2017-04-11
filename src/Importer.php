<?php

namespace Drupal\default_content;

use Drupal\Component\Graph\Graph;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\default_content\Event\DefaultContentEvents;
use Drupal\default_content\Event\ImportEvent;
use Drupal\rest\LinkManager\LinkManagerInterface;
use Drupal\rest\Plugin\Type\ResourcePluginManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * A service for handling import of default content.
 *
 * @todo throw useful exceptions
 */
class Importer implements ImporterInterface {

  /**
   * Defines relation domain URI for entity links.
   *
   * @var string
   */
  protected $linkDomain;

  /**
   * The serializer service.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * The rest resource plugin manager.
   *
   * @var \Drupal\rest\Plugin\Type\ResourcePluginManager
   */
  protected $resourcePluginManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A list of vertex objects keyed by their link.
   *
   * @var array
   */
  protected $vertexes = [];

  /**
   * The graph entries.
   *
   * @var array
   */
  protected $graph = [];

  /**
   * The link manager service.
   *
   * @var \Drupal\rest\LinkManager\LinkManagerInterface
   */
  protected $linkManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The file system scanner.
   *
   * @var \Drupal\default_content\ScannerInterface
   */
  protected $scanner;

  /**
   * The module handler.
   * 
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs the default content manager.
   *
   * @param \Symfony\Component\Serializer\Serializer $serializer
   *   The serializer service.
   * @param \Drupal\rest\Plugin\Type\ResourcePluginManager $resource_plugin_manager
   *   The rest resource plugin manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\rest\LinkManager\LinkManagerInterface $link_manager
   *   The link manager service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\default_content\ScannerInterface $scanner
   *   The file scanner.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler.
   * @param string $link_domain
   *   Defines relation domain URI for entity links.
   */
  public function __construct(Serializer $serializer, ResourcePluginManager $resource_plugin_manager, EntityTypeManagerInterface $entity_type_manager, LinkManagerInterface $link_manager, EventDispatcherInterface $event_dispatcher, ScannerInterface $scanner, ModuleHandlerInterface $module_handler, $link_domain) {
    $this->serializer = $serializer;
    $this->resourcePluginManager = $resource_plugin_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->linkManager = $link_manager;
    $this->eventDispatcher = $event_dispatcher;
    $this->scanner = $scanner;
    $this->moduleHandler = $module_handler;
    $this->linkDomain = $link_domain;
  }

  /**
   * {@inheritdoc}
   */
  public function importContent($module) {
    $created = [];
    $folder = drupal_get_path('module', $module) . "/content";

    if (file_exists($folder)) {
      $file_map = [];
      foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
        $reflection = new \ReflectionClass($entity_type->getClass());
        // We are only interested in importing content entities.
        if ($reflection->implementsInterface(ConfigEntityInterface::class)) {
          continue;
        }
        if (!file_exists($folder . '/' . $entity_type_id)) {
          continue;
        }
        $files = $this->scanner->scan($folder . '/' . $entity_type_id);
        // Default content uses drupal.org as domain.
        // @todo Make this use a uri like default-content:.
        $this->linkManager->setLinkDomain($this->linkDomain);
        // Parse all of the files and sort them in order of dependency.
        foreach ($files as $file) {
          $contents = $this->parseFile($file);
          // Decode the file contents.
          $decoded = $this->serializer->decode($contents, 'hal_json');
          // Get the link to this entity.
          $item_uuid = $decoded['uuid'][0]['value'];

          // Throw an exception when this UUID already exists.
          if (isset($file_map[$item_uuid])) {
            $args = [
              '@uuid' => $item_uuid,
              '@first' => $file_map[$item_uuid]->uri,
              '@second' => $file->uri,
            ];
            // Reset link domain.
            $this->linkManager->setLinkDomain(FALSE);
            throw new \Exception(new FormattableMarkup('Default content with uuid @uuid exists twice: @first @second', $args));
          }

          // Store the entity type with the file.
          $file->entity_type_id = $entity_type_id;
          // Store the file in the file map.
          $file_map[$item_uuid] = $file;
          // Create a vertex for the graph.
          $vertex = $this->getVertex($item_uuid);
          $this->graph[$vertex->id]['edges'] = [];
          if (empty($decoded['_embedded'])) {
            // No dependencies to resolve.
            continue;
          }
          // Here we need to resolve our dependencies:
          foreach ($decoded['_embedded'] as $embedded) {
            foreach ($embedded as $item) {
              $uuid = $item['uuid'][0]['value'];
              $edge = $this->getVertex($uuid);
              $this->graph[$vertex->id]['edges'][$edge->id] = TRUE;
            }
          }
        }
      }

      // @todo what if no dependencies?
      $sorted = $this->sortTree($this->graph);
      foreach ($sorted as $link => $details) {
        if (!empty($file_map[$link])) {
          $file = $file_map[$link];
          $entity_type_id = $file->entity_type_id;
          $resource = $this->resourcePluginManager->createInstance('entity:' . $entity_type_id);
          $definition = $resource->getPluginDefinition();
          $contents = $this->parseFile($file);
          $class = $definition['serialization_class'];
          $entity = $this->serializer->deserialize($contents, $class, 'hal_json', ['request_method' => 'POST']);
          $entity->enforceIsNew(TRUE);
          $entity->save();
          $created[$entity->uuid()] = $entity;
        }
      }
      $this->eventDispatcher->dispatch(DefaultContentEvents::IMPORT, new ImportEvent($created, $module));
    }
    // Reset the tree.
    $this->resetTree();
    // Reset link domain.
    $this->linkManager->setLinkDomain(FALSE);
    return $created;
  }

  /**
   * {@inheritdoc}
   */
  public function importAllContent() {
    $module_names = array_keys($this->moduleHandler->getModuleList());
    return array_reduce($module_names, function (array $entities, $module_name) {
      return array_merge($entities, $this->importContent($module_name));
    }, []);
  }

  /**
   * Parses content files.
   *
   * @param object $file
   *   The scanned file.
   *
   * @return string
   *   Contents of the file.
   */
  protected function parseFile($file) {
    return file_get_contents($file->uri);
  }

  /**
   * Resets tree properties.
   */
  protected function resetTree() {
    $this->graph = [];
    $this->vertexes = [];
  }

  /**
   * Sorts dependencies tree.
   *
   * @param array $graph
   *   Array of dependencies.
   *
   * @return array
   *   Array of sorted dependencies.
   */
  protected function sortTree(array $graph) {
    $graph_object = new Graph($graph);
    $sorted = $graph_object->searchAndSort();
    uasort($sorted, 'Drupal\Component\Utility\SortArray::sortByWeightElement');
    return array_reverse($sorted);
  }

  /**
   * Returns a vertex object for a given item link.
   *
   * Ensures that the same object is returned for the same item link.
   *
   * @param string $item_link
   *   The item link as a string.
   *
   * @return object
   *   The vertex object.
   */
  protected function getVertex($item_link) {
    if (!isset($this->vertexes[$item_link])) {
      $this->vertexes[$item_link] = (object) ['id' => $item_link];
    }
    return $this->vertexes[$item_link];
  }

}
