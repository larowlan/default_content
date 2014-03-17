<?php

/**
 * @file
 * Contains \Drupal\default_content\TargetIdResolver.
 */

namespace Drupal\default_content;

use Drupal\serialization\EntityResolver\EntityResolverInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Resolves entities from data that contains an entity UUID.
 */
class TargetIdResolver implements EntityResolverInterface {

  /**
   * {@inheritdoc}
   */
  public function resolve(NormalizerInterface $normalizer, $data, $entity_type) {
    if (isset($data['target_id'])) {
      return $data['target_id'];
    }
    return NULL;
  }

}
