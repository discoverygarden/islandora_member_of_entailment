<?php

namespace Drupal\islandora_member_of_entailment\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\node\NodeInterface;

/**
 * Database adapter plugin interface.
 */
interface DatabaseAdapterInterface extends PluginInspectionInterface {

  /**
   * Create table(s) and indices as necessary.
   *
   * Some DB-specific functionality is somewhat less-than available via Drupal,
   * so allow for other types of things, meaning hook_schema() is not quite
   * usable.
   */
  public function schema() : void;

  /**
   * Drop tables and indices.
   *
   *  Some DB-specific functionality is somewhat less-than available via Drupal,
   *  so allow for other types of things, meaning hook_schema() is not quite
   *  usable.
   */
  public function uninstallSchema() : void;

  /**
   * Rebuild table(s).
   */
  public function rebuild() : void;

  /**
   * Track the given node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to be tracked.
   */
  public function addNode(NodeInterface $node) : void;

  /**
   * Update tracking of the given node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to be tracked.
   */
  public function updateNode(NodeInterface $node) : void;

  /**
   * Remove tracking of the given node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to be removed from tracking.
   */
  public function deleteNode(NodeInterface $node) : void;
}
