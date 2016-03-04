<?php

/**
 * @file
 * Contains \Drupal\default_content\DefaultContentServiceProvider.
 */

namespace Drupal\default_content;

class DefaultContentServiceProvider extends \Drupal\Core\DependencyInjection\ServiceProviderBase {

  public function alter(\Drupal\Core\DependencyInjection\ContainerBuilder $container) {
    $container
      ->getDefinition('rest.link_manager.type')
      ->setClass('Drupal\default_content\LinkManager\TypeLinkManager');

    $container
      ->getDefinition('rest.link_manager.relation')
      ->setClass('Drupal\default_content\LinkManager\RelationLinkManager');
  }

}
