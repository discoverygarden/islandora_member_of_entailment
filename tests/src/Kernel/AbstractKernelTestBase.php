<?php

namespace Drupal\Tests\islandora_member_of_entailment\Kernel;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\islandora\IslandoraUtils;
use Drupal\islandora_member_of_entailment\Plugin\DatabaseAdapterInterface;
use Drupal\islandora_member_of_entailment\Plugin\DatabaseAdapterManagerInterface;
use Drupal\Tests\islandora_test_support\Kernel\AbstractIslandoraKernelTestBase;

/**
 * Common testing basis.
 */
abstract class AbstractKernelTestBase extends AbstractIslandoraKernelTestBase {

  use SetupTrait;

  /**
   * The database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $connection;

  /**
   * Flag, control whether ::createEntity() presaves the created entity.
   *
   * @var bool
   */
  protected bool $saveCreatedEntity;

  /**
   * {@inheritDoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->doIslandoraMemberOfEntailmentSetup();

    $this->connection = $this->container->get('database');
  }

  /**
   * {@inheritDoc}
   */
  public function tearDown(): void {
    $this->adapterManager = $this->container->get('plugin.manager.islandora_member_of_entailment.database_adapter');
    $this->adapter = $this->adapterManager->getDatabaseAdapterPlugin();
    $this->adapter->uninstallSchema();

    parent::tearDown();
  }

  /**
   * Helper; test contents of node LUT table.
   *
   * @param array $expected
   *   The expected contents.
   * @param string $message
   *   Associated assertion message.
   */
  protected function assertTableContents(array $expected, string $message = '') : void {
    $query = $this->connection->select($this->adapter->getTableName(), 't')
      ->fields('t', ['nid', 'aid'])
      ->execute();
    $results = $query->fetchAll(\PDO::FETCH_ASSOC);

    $this->assertEqualsCanonicalizing($expected, $results, $message);
  }

  /**
   * Helper, setup saving.
   */
  protected function savingSetup(bool $saving) {
    $this->saveCreatedEntity = $saving;
  }

  /**
   * {@inheritDoc}
   */
  protected function createEntity(string $entityTypeId, array $values = []): EntityInterface {
    // XXX: Essentially copypasta from
    // Drupal\Tests\test_support\Traits\Support\InteractsWithEntities::createEntity();
    // however, avoids performing the ::save() of the entity, to allow it to
    // happen in the test.
    $entity = $this->storage($entityTypeId)->create($values);

    if ($this->saveCreatedEntity) {
      $entity->save();
    }

    return $entity;
  }

  /**
   * Data provider to test with full regeneration instead of LUT maintenance.
   */
  public function buildType() {
    return [
      'Maintaining table, Presaved' => [FALSE, TRUE],
      'Regenerating table, Presaved' => [TRUE, TRUE],
      'Maintaining table, Created' => [FALSE, FALSE],
      'Regenerating table, Created' => [TRUE, FALSE],
    ];
  }

}
