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
   * @return int|null
   *   The immediate ancestor ID or NULL.
   */
  public function getImmediateAncestor(int $nid): ?int {
    return $this->getAncestor($nid, FALSE);
  }

  /**
   * Get the first ancestor.
   *
   * @param int $nid
   *   The node ID.
   *
   * @return int|null
   *   The first ancestor ID or NULL.
   *
   * @throws \Exception
   */
  public function getFirstAncestor(int $nid): ?int {
    return $this->getAncestor($nid, TRUE);
  }

  /**
   * Get the ancestor.
   *
   * @param int $nid
   *   The node ID.
   * @param bool $top
   *   Whether to get the top ancestor.
   *
   * @return int|null
   *   The ancestor ID or NULL.
   */
  private function getAncestor(int $nid, $top = FALSE): ?int {
    // Query to fetch the paths for the given nid.
    $query = \Drupal::database()->select('islandora_member_of_entailment', 'i')
      ->fields('i', ['path'])
      ->condition('nid', $nid);

    // Execute the query and fetch the results.
    $result = $query->execute()->fetchCol();

    // Initialize variables to track the longest path.
    $ancestorPath = [];

    // Iterate through the fetched paths.
    foreach ($result as $path) {
      // Clean up the path format to make it valid JSON.
      // Remove curly braces.
      $path = trim($path, '{}');
      // Split by commas.
      $path_values = explode(',', $path);

      // Check if this path is longer than the currently tracked longest path.
      if (count($path_values) > count($ancestorPath)) {
        $ancestorPath = $path_values;
      }
    }

    if (!empty($ancestorPath)) {
      if ($top) {
        return end($ancestorPath);
      }
      else {
        return reset($ancestorPath);
      }
    }

    return NULL;
  }

}
