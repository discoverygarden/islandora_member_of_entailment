<?php

namespace Drupal\islandora_member_of_entailment\Traits;

/**
 * Trait to get first ancestor and immediate ancestor.
 */
trait IslandoraAncestorTrait {

  /**
   * Get the immediate ancestor.
   *
   * @param int $nid
   *   The node ID.
   *
   * @return mixed
   *   The immediate ancestor ID or NULL.
   *
   * @throws \Exception
   */
  public function getImmediateAncestor(int $nid): mixed {
    $query = \Drupal::database()->select('islandora_member_of_entailment', 'i')
      ->fields('i', ['aid'])
      ->condition('nid', $nid)
      ->execute()
      ->fetchField();
    return $query ?: NULL;
  }

  /**
   * Get the first ancestor.
   *
   * @param int $nid
   *   The node ID.
   *
   * @return mixed
   *   The first ancestor ID or NULL.
   *
   * @throws \Exception
   */
  public function getFirstAncestor($nid): mixed {
    $query = \Drupal::database()->select('islandora_member_of_entailment', 'i')
      ->fields('i', ['path'])
      ->condition('nid', $nid)
      ->execute()
      ->fetchField();

    if ($query) {
      $path = trim($query, '{}');
      $path_values = explode(',', $path);
      return end($path_values);
    }
    return NULL;
  }

}
