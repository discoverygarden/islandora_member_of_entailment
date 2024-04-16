<?php

namespace Drupal\islandora_member_of_entailment\Drush\Commands;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\islandora_member_of_entailment\Plugin\DatabaseAdapterManagerInterface;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Rebuild drush command implementation.
 */
class RebuildCommand extends DrushCommands implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Constructor.
   */
  public function __construct(
    protected DatabaseAdapterManagerInterface $databaseAdapterManager,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.islandora_member_of_entailment.database_adapter'),
    );
  }

  /**
   * Rebuild the LUT.
   */
  #[CLI\Command(name: 'islandora-member-of-entailment:rebuild')]
  public function rebuild() {
    $this->io()->info($this->t('Rebuilding...'));
    if ($this->databaseAdapterManager->getDatabaseAdapterPlugin()->rebuild()) {
      $this->io()->info($this->t('Done!'));
    }
    else {
      $this->io->error($this->t('Error?'));
    }
  }

}
