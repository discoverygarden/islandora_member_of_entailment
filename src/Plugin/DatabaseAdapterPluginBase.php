<?php

namespace Drupal\islandora_member_of_entailment\Plugin;

use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\islandora\IslandoraUtils;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Abstract database adapter plugin base implementation.
 */
abstract class DatabaseAdapterPluginBase extends PluginBase implements DatabaseAdapterInterface, ContainerFactoryPluginInterface {

  /**
   * The database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $connection;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) : self {
    $instance = new static($configuration, $plugin_id, $plugin_definition);

    $instance->connection = $container->get('database');

    return $instance;
  }

  /**
   * Get the IDs of any parents of the given node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node of which to get the parents.
   *
   * @return int[]
   *   The parent IDs.
   */
  protected function getParents(NodeInterface $node) : array {
    if (!$node->hasField(IslandoraUtils::MEMBER_OF_FIELD)) {
      throw new \InvalidArgumentException('Cannot get parents of node without the given relationship.');
    }
    /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $list */
    $list = $node->get(IslandoraUtils::MEMBER_OF_FIELD);
    return array_map(
      function (NodeInterface $node) {
        return $node->id();
      },
      $list->referencedEntities(),
    );
  }

  /**
   * Helper; get change of relationships.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node of which to identify the relationships.
   *
   * @return array
   *   A two-tuple, containing:
   *   - an array of node IDs of parents that are no longer associated; and,
   *   - an array of node IDs of parents that are newly associated.
   */
  protected function getChangedParents(NodeInterface $node) : array {
    $original_parents = $this->getParents($node->original);
    $current_parents = $this->getParents($node);

    $new_parents = array_diff($current_parents, $original_parents);
    $deleted_parents = array_diff($original_parents, $current_parents);

    return [$deleted_parents, $new_parents];
  }

}
