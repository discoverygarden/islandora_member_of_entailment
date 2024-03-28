<?php

namespace Drupal\Tests\islandora_member_of_entailment\Kernel;

use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\islandora\IslandoraUtils;
use Drupal\islandora_member_of_entailment\Plugin\DatabaseAdapterInterface;
use Drupal\islandora_member_of_entailment\Plugin\DatabaseAdapterManagerInterface;
use Drupal\Tests\islandora_test_support\Kernel\AbstractIslandoraKernelTestBase;

/**
 * Test generation and maintenance of table.
 */
class TableTest extends AbstractIslandoraKernelTestBase {

  use DependencySerializationTrait;

  /**
   * The database adapter manager service.
   *
   * @var \Drupal\islandora_member_of_entailment\Plugin\DatabaseAdapterManagerInterface
   */
  protected DatabaseAdapterManagerInterface $adapterManager;

  /**
   * The presently appliable adapter implementation.
   *
   * @var \Drupal\islandora_member_of_entailment\Plugin\DatabaseAdapterInterface
   */
  protected DatabaseAdapterInterface $adapter;

  /**
   * The database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $connection;

  /**
   * {@inheritDoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->enableModuleWithDependencies(['islandora_member_of_entailment']);

    $this->adapterManager = $this->container->get('plugin.manager.islandora_member_of_entailment.database_adapter');
    $this->adapter = $this->adapterManager->getDatabaseAdapterPlugin();
    $this->adapter->schema();

    $this->connection = $this->container->get('database');
  }

  /**
   * Test basic node creation.
   */
  public function testBasicCreation() {
    $alpha = $this->createNode();
    $alpha->save();

    $this->assertCount(
      1,
      $this->connection->select($this->adapter->getTableName(), 't')
        ->fields('t', [])
        ->execute(),
      'Has the single item.',
    );

    $bravo = $this->createNode();
    $bravo->get(IslandoraUtils::MEMBER_OF_FIELD)->appendItem($alpha);
    $bravo->save();

    $query = $this->connection->select($this->adapter->getTableName(), 't')
      ->fields('t');
    $this->assertCount(
      2,
      $query->execute(),
      'Has only two items.'
    );

    $query = $this->connection->select($this->adapter->getTableName(), 't')
      ->fields('t');
    $query->condition(
      $query->orConditionGroup()
        ->condition(
          $query->andConditionGroup()
            ->condition('nid', $alpha->id())
            ->isNull('aid')
        )
        ->condition(
          $query->andConditionGroup()
            ->condition('nid', $bravo->id())
            ->condition('aid', $alpha->id())
        )
    );

    $this->assertCount(
      2,
      $query->execute(),
      'Has the two specific items.',
    );
  }

  public function testTransitiveCreation() {
    $alpha = $this->createNode();
    $alpha->save();
    $bravo = $this->createNode();
    $bravo->get(IslandoraUtils::MEMBER_OF_FIELD)->appendItem($alpha);
    $bravo->save();
    $charlie = $this->createNode();
    $charlie->get(IslandoraUtils::MEMBER_OF_FIELD)->appendItem($bravo);
    $charlie->save();

    $query = $this->connection->select($this->adapter->getTableName(), 't')
      ->fields('t');
    $this->assertCount(
      4,
      $query->execute(),
      'Has only four items.'
    );

    $query = $this->connection->select($this->adapter->getTableName(), 't')
      ->fields('t');
    $query->condition(
      $query->orConditionGroup()
        ->condition(
          $query->andConditionGroup()
            ->condition('nid', $alpha->id())
            ->isNull('aid')
        )
        ->condition(
          $query->andConditionGroup()
            ->condition('nid', $bravo->id())
            ->condition('aid', $alpha->id())
        )
        ->condition(
          $query->andConditionGroup()
            ->condition('nid', $charlie->id())
            ->condition('aid', $alpha->id())
        )
        ->condition(
          $query->andConditionGroup()
            ->condition('nid', $charlie->id())
            ->condition('aid', $bravo->id())
        )
    );

    $this->assertCount(
      4,
      $query->execute(),
      'Has the two specific items.',
    );
  }

}
