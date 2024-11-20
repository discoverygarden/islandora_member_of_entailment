<?php

namespace Drupal\Tests\islandora_member_of_entailment\Traits;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait;
use Drupal\Tests\islandora_test_support\Traits\IslandoraContentTypeTestTraits;
use Drupal\Tests\test_support\Traits\Installs\InstallsModules;
use Drupal\islandora\IslandoraUtils;
use Drupal\islandora_member_of_entailment\Plugin\DatabaseAdapterInterface;
use Drupal\islandora_member_of_entailment\Plugin\DatabaseAdapterManagerInterface;

/**
 * Module-specific test setup that might be of use for related functionality.
 */
trait SetupTrait {

  use InstallsModules;
  use EntityReferenceFieldCreationTrait;
  use IslandoraContentTypeTestTraits;

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
   * Perform module-specific setup.
   */
  protected function doIslandoraMemberOfEntailmentSetup(): void {
    assert($this instanceof KernelTestBase);

    $this->enableModuleWithDependencies([
      'path_alias',
    ]);
    $this->installEntitySchema('path_alias');

    $this->createEntityReferenceField('node', $this->contentType->id(), IslandoraUtils::MEMBER_OF_FIELD, "Member Of", $this->contentType->getEntityType()
      ->getBundleOf(), cardinality: FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    $this->enableModuleWithDependencies([
      'islandora_member_of_entailment',
    ]);
    $this->adapterManager = $this->container->get('plugin.manager.islandora_member_of_entailment.database_adapter');
    $this->adapter = $this->adapterManager->getDatabaseAdapterPlugin();
    $this->assertTrue($this->adapter->schema(), 'Schema installed successfully.');
  }

}
