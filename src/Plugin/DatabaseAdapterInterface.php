<?php

namespace Drupal\islandora_member_of_entailment\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\node\NodeInterface;

/**
 * Database adapter plugin interface.
 */
interface DatabaseAdapterInterface extends PluginInspectionInterface {

  /**
   * Get the name of the flattened hierarchy table.
   *
   * We expect at least two columns to exist in this table:
   * - nid: Node related to ancestors.
   * - aid: Ancestor nodes.
   *
   * Ideally, both columns should be indexed to faciliate making joins across
   * the flattened relationship.
   *
   * @return string
   *   The name of the table.
   */
  public function getTableName() : string;

  /**
   * Create table(s) and indices as necessary.
   *
   * Some DB-specific functionality is somewhat less-than available via Drupal,
   * so allow for other types of things, meaning hook_schema() is not quite
   * usable.
   *
   * @see \Drupal\islandora_member_of_entailment\Plugin\DatabaseAdapterInterface::getTableName()
   */
  public function schema() : bool;

  /**
   * Drop tables and indices.
   *
   *  Some DB-specific functionality is somewhat less-than available via Drupal,
   *  so allow for other types of things, meaning hook_schema() is not quite
   *  usable.
   */
  public function uninstallSchema() : bool;

  /**
   * Rebuild table(s).
   */
  public function rebuild() : bool;

  /**
   * Track the given node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to be tracked.
   */
  public function addNode(NodeInterface $node) : bool;

  /**
   * Update tracking of the given node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to be tracked.
   */
  public function updateNode(NodeInterface $node) : bool;

  /**
   * Remove tracking of the given node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to be removed from tracking.
   */
  public function deleteNode(NodeInterface $node) : bool;

}
