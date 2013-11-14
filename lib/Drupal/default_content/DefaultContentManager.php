<?php

/**
 * @file
 * Contains \Drupal\default_content\DefaultContentManager.
 */

namespace Drupal\default_content;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Session\AccountInterface;
use Drupal\rest\Plugin\Type\ResourcePluginManager;
use Gliph\Graph\DirectedAdjacencyList;
use Gliph\Traversal\DepthFirst;
use Symfony\Component\Serializer\Serializer;

/**
 * A service for handling import of default content.
 * @todo throw useful exceptions
 */
class DefaultContentManager implements DefaultContentManagerInterface {

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
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * The file system scanner.
   *
   * @var \Drupal\default_content\DefaultContentScanner
   */
  protected $scanner;

  /**
   * The tree resolver.
   *
   * @var \Gliph\Graph\DirectedAdjacencyList
   */
  protected $tree = FALSE;

  /**
   * Constructs the default content manager.
   *
   * @param \Symfony\Component\Serializer\Serializer $serializer
   *   The serializer service.
   * @param \Drupal\rest\Plugin\Type\ResourcePluginManager $resource_plugin_manager
   *   The rest resource plugin manager.
   * @param \Drupal\Core\Session|AccountInterface $current_user .
   *   The current user.
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The entity manager service.
   */
  public function __construct(Serializer $serializer, ResourcePluginManager $resource_plugin_manager, AccountInterface $current_user, EntityManager $entity_manager) {
    $this->serializer = $serializer;
    $this->resourcePluginManager = $resource_plugin_manager;
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function importContent($module) {
    $created = array();
    $folder = drupal_get_path('module', $module) . "/content";

    if (file_exists($folder)) {
      foreach ($this->entityManager->getDefinitions() as $entity_type => $entity_type_info) {
        $reflection = new \ReflectionClass($entity_type_info['class']);
        // We are only interested in importing content entities.
        if ($reflection->implementsInterface('\Drupal\Core\Config\Entity\ConfigEntityInterface')) {
          continue;
        }
        $files = $this->scanner()->scan('/^(.*)\.json/', $folder . '/' . $entity_type);
        foreach ($files as $file) {
          $resource = $this->resourcePluginManager->getInstance(array('id' => 'entity:' . $entity_type));
          $definition = $resource->getPluginDefinition();
          $contents = $this->parseFile($file);
          $class = $definition['serialization_class'];
          $unserialized = $this->serializer->deserialize($contents, $class, 'hal_json', array('request_method' => 'POST'));
          $unserialized->enforceIsNew(TRUE);
          $resource->post(NULL, $unserialized);
          // Here we need to resolve our dependencies;
          //foreach ($unserialized->embedded as $embedded) {
          //  $this->tree()->addDirectedEdge($unserialized, $embedded);
          //}
        }
      }

      //foreach($this->sortTree() as $unserialized) {
        //$resource = $this->resourcePluginManager->getInstance(array('id' => 'entity:' . $unserialized->entityType()));
        //$resource->post(NULL, $unserialized);
      //}
    }
    // Reset the tree.
    $this->resetTree();
    return $created;
  }

  /**
   * Utility to get a default content scanner
   *
   * @return \Drupal\default_content\DefaultContentScanner
   *   A system listing implementation.
   */
  protected function scanner() {
    if ($this->scanner) {
      return $this->scanner;
    }
    return new DefaultContentScanner();
  }

  /**
   * {@inheritdoc}
   */
  public function setScanner(DefaultContentScanner $scanner) {
    $this->scanner = $scanner;
  }

  /**
   * Parses content files
   */
  protected function parseFile($file) {
    return file_get_contents($file->uri);
  }

  protected function tree() {
    if ($this->tree) {
      return $this->tree;
    }
    return new DirectedAdjacencyList();
  }

  protected function resetTree() {
    $this->tree = FALSE;
  }

  protected function sortTree() {
    return DepthFirst::toposort($this->tree());
  }

}

/**
<?php

use \Gliph\Graph\DirectedAdjacencyList;
use \Gliph\Traversal\DepthFirst;

$graph = new DirectedAdjacencyList();

foreach ($entity_list as $entity) {
if ($entity->has('embedded')) {
foreach ($entity->get('embedded') as $embedded) {
$graph->addDirectedEdge($entity, $embedded);
}
}
}

$tsl = DepthFirst::toposort($graph);

foreach($tsl as $entity) {
// do your import thang
}
 */
