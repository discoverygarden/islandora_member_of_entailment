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
 * Test generation and maintenance of table.
 */
class TableTest extends AbstractIslandoraKernelTestBase {

  /**
   * The database adapter manager service.
   *
   * @var \Drupal\islandora_member_of_entailment\Plugin\DatabaseAdapterManagerInterface
   */
  protected DatabaseAdapterManagerInterface $adapterManager;

  /**
   * The presently applicable adapter implementation.
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

  protected bool $saveCreatedEntity;

  /**
   * {@inheritDoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->enableModuleWithDependencies([
      'islandora_member_of_entailment',
      'path_alias',
    ]);

    $this->adapterManager = $this->container->get('plugin.manager.islandora_member_of_entailment.database_adapter');
    $this->adapter = $this->adapterManager->getDatabaseAdapterPlugin();
    $this->assertTrue($this->adapter->schema(), 'Schema installed successfully.');
    $this->installEntitySchema('path_alias');

    $this->connection = $this->container->get('database');

    $this->createEntityReferenceField('node',
      $this->contentType->id(), IslandoraUtils::MEMBER_OF_FIELD,
      "Member Of", $this->contentType->getEntityType()->getBundleOf(), cardinality: FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
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

//    var_dump($this->connection->select($this->adapter->getTableName(), 't')
//      ->fields('t')
//      ->execute()->fetchAll(\PDO::FETCH_ASSOC));
//    var_dump($this->connection->select('node__field_member_of', 't')
//      ->fields('t')
//      ->execute()->fetchAll(\PDO::FETCH_ASSOC));

    $this->assertEqualsCanonicalizing($expected, $results, $message);
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

  /**
   * Data provider to test different entity saving flows.
   */
  public function saveType() {
    return [
      'Presaved' => [TRUE],
      'Created' => [FALSE],
    ];
  }

  /**
   * Helper, setup saving.
   */
  protected function savingSetup(bool $saving) {
    $this->saveCreatedEntity = $saving;
  }

  /**
   * Test basic node creation.
   *
   * @dataProvider buildType
   */
  public function testBasicCreation(bool $regenerate, bool $saving) : void {
    $this->savingSetup($saving);
    $alpha = $this->createNode();
    $this->assertEquals($saving ? SAVED_UPDATED : SAVED_NEW, $alpha->save());
    $bravo = $this->createNode();
    $bravo->get(IslandoraUtils::MEMBER_OF_FIELD)->appendItem($alpha);
    $this->assertEquals($saving ? SAVED_UPDATED : SAVED_NEW, $bravo->save());

    if ($regenerate) {
      $this->assertTrue($this->adapter->rebuild());
    }

    $this->assertTableContents(
      [
        ['nid' => $bravo->id(), 'aid' => $alpha->id()],
      ],
      'Has the expected items.',
    );
  }

  /**
   * Test basic transitive node creation.
   *
   * @dataProvider buildType
   */
  public function testBasicTransitiveCreation(bool $regenerate, bool $saving) : void {
    $this->savingSetup($saving);
    $alpha = $this->createNode();
    $alpha->save();
    $bravo = $this->createNode();
    $bravo->get(IslandoraUtils::MEMBER_OF_FIELD)->appendItem($alpha);
    $bravo->save();
    $charlie = $this->createNode();
    $charlie->get(IslandoraUtils::MEMBER_OF_FIELD)->appendItem($bravo);
    $charlie->save();

    if ($regenerate) {
      $this->assertTrue($this->adapter->rebuild());
    }

    $this->assertTableContents(
      [
        ['nid' => $bravo->id(), 'aid' => $alpha->id()],
        ['nid' => $charlie->id(), 'aid' => $alpha->id()],
        ['nid' => $charlie->id(), 'aid' => $bravo->id()],
      ],
      'Has the expected contents.',
    );

  }

  /**
   * Test basic multiple node creation.
   *
   * @dataProvider buildType
   */
  public function testBasicMultipleCreation(bool $regenerate, bool $saving) : void {
    $this->savingSetup($saving);
    $alpha = $this->createNode();
    $alpha->save();
    $bravo = $this->createNode();
    $bravo->get(IslandoraUtils::MEMBER_OF_FIELD)->appendItem($alpha);
    $bravo->save();
    $charlie = $this->createNode();
    $charlie->get(IslandoraUtils::MEMBER_OF_FIELD)->appendItem($alpha);
    $charlie->save();

    if ($regenerate) {
      $this->assertTrue($this->adapter->rebuild());
    }

    $this->assertTableContents(
      [
        ['nid' => $bravo->id(), 'aid' => $alpha->id()],
        ['nid' => $charlie->id(), 'aid' => $alpha->id()],
      ],
      'Has the expected contents.',
    );

  }

  /**
   * Test basic multiple node creation.
   *
   * @dataProvider buildType
   */
  public function testDiamondCreation(bool $regenerate, bool $saving) : void {
    $this->savingSetup($saving);
    $alpha = $this->createNode();
    $alpha->save();
    $bravo = $this->createNode();
    $bravo->get(IslandoraUtils::MEMBER_OF_FIELD)->appendItem($alpha);
    $bravo->save();
    $charlie = $this->createNode();
    $charlie->get(IslandoraUtils::MEMBER_OF_FIELD)->appendItem($alpha);
    $charlie->save();
    $delta = $this->createNode();
    $delta_members = $delta->get(IslandoraUtils::MEMBER_OF_FIELD);
    $delta_members->appendItem($bravo);
    $delta_members->appendItem($charlie);
    $delta->save();

    if ($regenerate) {
      $this->assertTrue($this->adapter->rebuild());
    }

    $this->assertTableContents(
      [
        ['nid' => $bravo->id(), 'aid' => $alpha->id()],
        ['nid' => $charlie->id(), 'aid' => $alpha->id()],
        ['nid' => $delta->id(), 'aid' => $alpha->id()],
        // XXX: Yes, expecting multiple of these, with multiple routes back.
        ['nid' => $delta->id(), 'aid' => $alpha->id()],
        ['nid' => $delta->id(), 'aid' => $bravo->id()],
        ['nid' => $delta->id(), 'aid' => $charlie->id()],
      ],
      'Has the expected contents.',
    );

  }

  /**
   * Test basic multiple node creation.
   *
   * @dataProvider buildType
   */
  public function testTransitionViaDiamondCreation(bool $regenerate, bool $saving) : void {
    $this->savingSetup($saving);
    $alpha = $this->createNode();
    $alpha->save();
    $bravo = $this->createNode();
    $bravo->get(IslandoraUtils::MEMBER_OF_FIELD)->appendItem($alpha);
    $bravo->save();
    $charlie = $this->createNode();
    $charlie->get(IslandoraUtils::MEMBER_OF_FIELD)->appendItem($alpha);
    $charlie->save();
    $delta = $this->createNode();
    $delta_members = $delta->get(IslandoraUtils::MEMBER_OF_FIELD);
    $delta_members->appendItem($bravo);
    $delta_members->appendItem($charlie);
    $delta->save();
    $echo = $this->createNode();
    $echo->get(IslandoraUtils::MEMBER_OF_FIELD)->appendItem($delta);
    $echo->save();

    if ($regenerate) {
      $this->assertTrue($this->adapter->rebuild());
    }

    $this->assertTableContents(
      [
        ['nid' => $bravo->id(), 'aid' => $alpha->id()],
        ['nid' => $charlie->id(), 'aid' => $alpha->id()],
        ['nid' => $delta->id(), 'aid' => $alpha->id()],
        // XXX: Yes, expecting multiple of these, with multiple routes back.
        ['nid' => $delta->id(), 'aid' => $alpha->id()],
        ['nid' => $delta->id(), 'aid' => $bravo->id()],
        ['nid' => $delta->id(), 'aid' => $charlie->id()],
        ['nid' => $echo->id(), 'aid' => $alpha->id()],
        // XXX: Yes, expecting multiple of these, with multiple routes back.
        ['nid' => $echo->id(), 'aid' => $alpha->id()],
        ['nid' => $echo->id(), 'aid' => $bravo->id()],
        ['nid' => $echo->id(), 'aid' => $charlie->id()],
        ['nid' => $echo->id(), 'aid' => $delta->id()],
      ],
      'Has the expected contents.',
    );

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

}
