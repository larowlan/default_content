<?php

/**
 * @file
 * Contains \Drupal\default_content\DefaultContentServiceProvider.
 */

namespace Drupal\default_content;

use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\DependencyInjection\ContainerBuilder;

/**
 * A default content service provider.
 */
class DefaultContentServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $container
      ->getDefinition('rest.link_manager.type')
      ->setClass('Drupal\default_content\LinkManager\TypeLinkManager');

    $container
      ->getDefinition('rest.link_manager.relation')
      ->setClass('Drupal\default_content\LinkManager\RelationLinkManager');
  }

}
