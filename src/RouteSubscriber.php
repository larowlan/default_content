<?php

/**
 * @file
 * Contains \Drupal\default_content\RouteSubscriber.
 * Makes a new export route for every contentEntity
 */

namespace Drupal\default_content;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;

/**
 * Subscriber for one route to export each entity type
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach (\Drupal::entityTypeManager()->getDefinitions() as $entity_type_id => $definition) {
      if ($definition instanceOf \Drupal\Core\Entity\ContentEntityType) {
        $route = new Route($definition->getLinkTemplate('canonical') . '/export');
        $route->setDefaults([
          '_form' => '\Drupal\default_content\ExportForm',
          '_title' => 'Export'
        ]);
        $route->setRequirements([
          '_permission' => 'access site reports',
        ]);
        $collection->add('entity.'.$entity_type_id.'.export', $route);
      }
    }
  }


  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      RoutingEvents::ALTER => ['onAlterRoutes', 100]
    ];
  }

}
