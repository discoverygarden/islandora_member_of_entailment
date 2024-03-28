<?php

namespace Drupal\islandora_member_of_entailment\Plugin;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Database adapter plugin manager interface.
 */
interface DatabaseAdapterManagerInterface extends PluginManagerInterface {

  /**
   * Get the adapter applicable for the current database.
   *
   * @return \Drupal\islandora_member_of_entailment\Plugin\DatabaseAdapterInterface
   *   The applicable plugin.
   */
  public function getDatabaseAdapterPlugin() : DatabaseAdapterInterface;

}
