<?php

namespace Drupal\islandora_member_of_entailment\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\ArgumentPluginBase;

/**
 * Argument to limit to descendants of an entity.
 *
 * @ViewsArgument("islandora_member_of_entailment_argument_is_descendant_of_entity")
 */
class IsDescendantOfEntity extends ArgumentPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE) {
    $this->ensureMyTable();

    // Get the current node ID from the argument.
    $nid = $this->argument;

    // Make sure content is filtered on ancestor ID.
    $this->query->addWhere('AND', "{$this->tableAlias}.aid", $nid);
  }

  /**
   * {@inheritdoc}
   */
  public function title() {
    return $this->t('Descendants of @nid', ['@nid' => $this->argument]);
  }

}
