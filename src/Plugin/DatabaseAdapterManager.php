<?php

namespace Drupal\islandora_member_of_entailment\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\islandora_member_of_entailment\Annotation\DatabaseAdapter;

/**
 * Database adapter manager service implementation.
 */
class DatabaseAdapterManager extends DefaultPluginManager {

  /**
   * Constructor.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cacheBackend,
    ModuleHandlerInterface $module_handler,
    protected Connection $connection,
  ) {
    parent::__construct(
      'Plugin/islandora_member_of_entailment/database_adapter',
      $namespaces,
      $module_handler,
      DatabaseAdapterInterface::class,
      DatabaseAdapter::class,
    );

    $this->alterInfo('islandora_member_of_entailment_database_adapter_plugins');
    $this->setCacheBackend($cacheBackend, 'islandora_member_of_entailment_database_adapter_plugins');
  }

  /**
   * {@inheritDoc}
   */
  public function getDatabaseAdapterPlugin() : DatabaseAdapterInterface {
    return $this->createInstance($this->connection->driver());
  }

}
