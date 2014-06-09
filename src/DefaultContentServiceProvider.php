<?php

/**
 * @file
 * Contains \Drupal\default_content\DefaultContentServiceProvider.
 */

namespace Drupal\default_content;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Creates a service modifier to hijack the rest typed link manager service.
 */
class DefaultContentServiceProvider implements ServiceModifierInterface, ServiceProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {}

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if ($link_manager = $container->getDefinition('rest.link_manager')) {
      $link_manager->replaceArgument(0, new Reference('default_content.link_manager.type'));
      $link_manager->replaceArgument(1, new Reference('default_content.link_manager.relation'));
      $container->setDefinition('rest.link_manager', $link_manager);
    }
  }

}
